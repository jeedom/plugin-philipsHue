<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class philipsHue extends eqLogic {
	/*     * *************************Attributs****************************** */

	private static $_hue = null;
	private static $_eqLogics = null;

	/*     * ***********************Methode static*************************** */

	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('philipsHue', 'pull');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('philipsHue', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		$cron = cron::byClassAndFunction('philipsHue', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->halt();
	}

	public static function deamon_changeAutoMode($_mode) {
		$cron = cron::byClassAndFunction('philipsHue', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->setEnable($_mode);
		$cron->save();
	}

	public static function cronDaily() {
		self::deamon_start();
	}

	public static function findBridgeIp() {
		$response = @file_get_contents('http://www.meethue.com/api/nupnp');
		if ($response === false) {
			return false;
		}
		$bridges = json_decode($response);
		if (isset($bridges[0])) {
			return $bridges[0]->internalipaddress;
		}
		return false;
	}

	public static function getPhilipsHue() {
		if (!self::$_hue !== null) {
			if (config::byKey('bridge_ip', 'philipsHue') == '' || config::byKey('bridge_ip', 'philipsHue') == '-') {
				$ip = self::findBridgeIp();
				if ($ip !== false) {
					config::save('bridge_ip', $ip, 'philipsHue');
				}
				if (config::byKey('bridge_ip', 'philipsHue') == '' || config::byKey('bridge_ip', 'philipsHue') == '-') {
					throw new Exception(__('L\'adresse du bridge ne peut etre vide', __FILE__));
				}
			}
			self::$_hue = new \Phue\Client(config::byKey('bridge_ip', 'philipsHue'), config::byKey('bridge_username', 'philipsHue', 'newdeveloper'));
		}
		return self::$_hue;
	}

	public function createUser() {
		if (config::byKey('bridge_ip', 'philipsHue') == '') {
			throw new Exception(__('L\'adresse du bridge ne peut etre vide', __FILE__));
		}
		$hue = new \Phue\Client(config::byKey('bridge_ip', 'philipsHue'));
		try {
			$hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
			throw new Exception(__('Impossible de joindre le bridge', __FILE__));
		}
		if (class_exists('event')) {
			event::add('jeedom::alert', array(
				'level' => 'warning',
				'message' => __('Veuillez appuyer sur le bouton du bridge', __FILE__),
			));
		} else {
			nodejs::pushUpdate('jeedom::alert', array(
				'level' => 'warning',
				'message' => __('Veuillez appuyer sur le bouton du bridge', __FILE__),
			));
		}
		for ($i = 1; $i <= 30; ++$i) {
			try {
				$response = $hue->sendCommand(
					new \Phue\Command\CreateUser
				);
				config::save('bridge_username', $response->username, 'philipsHue');
				break;
			} catch (\Phue\Transport\Exception\LinkButtonException $e) {

			} catch (Exception $e) {
				throw new Exception(__('Impossible de creer l\'utilisateur. Veuillez bien presser le bouton du bridge puis réessayer : ', __FILE__) . $e->getMessage());
			}
			sleep(1);
		}
		if (!$hue->sendCommand(new \Phue\Command\IsAuthorized)) {
			throw new Exception(__('Impossible de creer l\'utilisateur. Veuillez bien presser le bouton du bridge puis réessayer : ', __FILE__) . $e->getMessage());
		}
	}

	public static function syncBridge() {
		try {
			$hue = self::getPhilipsHue();
		} catch (Exception $e) {
			self::createUser();
		}
		try {
			$hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
			throw new Exception(__('Impossible de joindre le bridge', __FILE__));
		}
		if (!$hue->sendCommand(new \Phue\Command\IsAuthorized)) {
			self::createUser();
		}
		self::$_hue = null;
		$hue = self::getPhilipsHue();
		if (!$hue->sendCommand(new \Phue\Command\IsAuthorized)) {
			throw new Exception(__('Impossible de creer l\'utilisateur. Veuillez bien presser le bouton du bridge puis réessayer : ', __FILE__) . $e->getMessage());
		}
		$lights_exist = array();
		$groups_exist = array(0 => 0);
		$sensors_exist = array();
		foreach ($hue->getLights() as $id => $light) {
			if (count(self::devicesParameters($light->getModelId())) == 0) {
				log::add('philipsHue', 'debug', 'No configuration found for light : ' . print_r($light, true));
				continue;
			}
			$eqLogic = self::byLogicalId('light' . $id, 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId('light' . $id);
				$eqLogic->setName($light->getName());
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('category', 'light');
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->setConfiguration('device', $light->getModelId());
			$eqLogic->setConfiguration('modelName', $light->getModel()->getName());
			$eqLogic->setConfiguration('type', $light->getType());
			$eqLogic->setConfiguration('softwareVersion', $light->getSoftwareVersion());
			$eqLogic->save();
			$lights_exist[$id] = $id;
		}
		$eqLogic = self::byLogicalId('group0', 'philipsHue');
		if (!is_object($eqLogic)) {
			$eqLogic = new self();
			$eqLogic->setLogicalId('group0');
			$eqLogic->setName(__('Toute les lampes', __FILE__));
			$eqLogic->setEqType_name('philipsHue');
			$eqLogic->setConfiguration('device', 'GROUP0');
			$eqLogic->setIsVisible(1);
			$eqLogic->setIsEnable(1);
		}
		$eqLogic->setConfiguration('category', 'group');
		$eqLogic->setConfiguration('id', 0);
		$eqLogic->save();
		foreach ($hue->getgroups() as $id => $group) {
			$eqLogic = self::byLogicalId('group' . $id, 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId('group' . $id);
				$eqLogic->setName($group->getName());
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('device', 'GROUP');
			$eqLogic->setConfiguration('category', 'group');
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->save();
			$groups_exist[$id] = $id;
		}
		foreach (self::sanitizeSensors($hue->getSensors()) as $id => $sensor) {
			$sensor = array_values($sensor)[0];
			if (count(self::devicesParameters($sensor->getModelId())) == 0) {
				log::add('philipsHue', 'debug', 'No configuration found for sensor : ' . print_r($sensor, true));
				continue;
			}
			$eqLogic = self::byLogicalId('sensor' . $id, 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId('sensor' . $id);
				$eqLogic->setName($sensor->getName());
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('category', 'sensor');
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->setConfiguration('device', $sensor->getModelId());
			$eqLogic->setConfiguration('modelName', $sensor->getModel()->getName());
			$eqLogic->setConfiguration('softwareVersion', $sensor->getSoftwareVersion());
			$eqLogic->save();
			$sensors_exist[$id] = $id;
		}

		foreach (self::byType('philipsHue') as $eqLogic) {
			if ($eqLogic->getConfiguration('category') == 'light') {
				if (!isset($lights_exist[$eqLogic->getConfiguration('id')])) {
					$eqLogic->remove();
				}
			} else if ($eqLogic->getConfiguration('category') == 'group') {
				if (!isset($groups_exist[$eqLogic->getConfiguration('id')])) {
					$eqLogic->remove();
				}
			} else if ($eqLogic->getConfiguration('category') == 'sensor') {
				if (!isset($sensors_exist[$eqLogic->getConfiguration('id')])) {
					$eqLogic->remove();
				}
			}
		}
		self::deamon_start();
	}

	public static function sanitizeSensors($_sensors) {
		$return = array();
		foreach ($_sensors as $id => $sensor) {
			$unique_id = explode('-', $sensor->getUniqueId())[0];
			if ($unique_id == '') {
				$unique_id = $id;
			}
			if (!isset($return[$unique_id])) {
				$return[$unique_id] = array();
			}
			$return[$unique_id][$id] = $sensor;
		}
		return $return;
	}

	public static function pull($_eqLogic_id = null) {
		try {
			$hue = philipsHue::getPhilipsHue();
		} catch (Exception $e) {
			return;
		}
		if (self::$_eqLogics == null) {
			self::$_eqLogics = self::byType('philipsHue');
		}
		$groups = $hue->getgroups();
		$lights = $hue->getLights();
		$sensors = self::sanitizeSensors($hue->getSensors());

		foreach (self::$_eqLogics as $eqLogic) {
			if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
				continue;
			}
			if ($eqLogic->getIsEnable() == 0 || $eqLogic->getLogicalId() == 'group0') {
				continue;
			}
			$isReachable = true;
			try {
				if ($eqLogic->getConfiguration('category') == 'sensor') {
					$sensor = $sensors[$eqLogic->getConfiguration('id')];
					foreach ($sensor as $id => $obj) {
						$lastupdate = 0;
						if (isset($obj->getState()->lastupdated)) {
							$lastupdate = strtotime($obj->getState()->lastupdated) + 3600;
						}
						foreach ($obj->getState() as $key => $value) {
							if ($key == 'lastupdated') {
								continue;
							}
							$cmd = $eqLogic->getCmd('info', $key);
							if (!is_object($cmd)) {
								continue;
							}
							if ($cmd->getConfiguration('onType') != '' && $cmd->getConfiguration('onType') != $obj->getType()) {
								continue;
							}
							if ($key == 'temperature') {
								$value = $value / 100;
							}
							if ($key == 'buttonevent') {
								switch ($value) {
									case 34:
										$value = 1;
										break;
									case 16:
										$value = 2;
										break;
									case 17:
										$value = 3;
										break;
									case 18:
										$value = 4;
										break;
								}
							}
							$eqLogic->checkAndUpdateCmd($key, $value, date('Y-m-d H:i:s', $lastupdate));
						}
						foreach ($obj->getConfig() as $key => $value) {
							if ($key == 'battery') {
								$eqLogic->batteryStatus($value);
								continue;
							}
							$cmd = $eqLogic->getCmd('info', $key);
							if (!is_object($cmd)) {
								continue;
							}
							if ($cmd->getConfiguration('onType') != '' && $cmd->getConfiguration('onType') != $obj->getType()) {
								continue;
							}
							$eqLogic->checkAndUpdateCmd($cmd, $value);
						}
					}
				} else {
					switch ($eqLogic->getConfiguration('category')) {
						case 'light':
							$obj = $lights[$eqLogic->getConfiguration('id')];
							$isReachable = ($eqLogic->getConfiguration('alwaysOn', 0) == 0) ? $obj->isReachable() : true;
							break;
						case 'group':
							$obj = $groups[$eqLogic->getConfiguration('id')];
							break;
					}
					if (!$isReachable || !$obj->isOn()) {
						echo "je passe";
						$luminosity = 0;
						$color = '#000000';
					} else {
						$rgb = $obj->getRGB();
						$color = '#' . sprintf('%02x', $rgb['red']) . sprintf('%02x', $rgb['green']) . sprintf('%02x', $rgb['blue']);
						$luminosity = $obj->getBrightness();
						if ($color == '#000000' && is_object($eqLogic->getCmd('info', 'color_state'))) {
							$luminosity = 0;
						}
					}
					$cmd = $eqLogic->getCmd('info', 'luminosity_state');
					if (is_object($cmd)) {
						$eqLogic->checkAndUpdateCmd($cmd, $luminosity);
					}
					$cmd = $eqLogic->getCmd('info', 'color_state');
					if (is_object($cmd)) {
						$eqLogic->checkAndUpdateCmd($cmd, $color);
					}
					$cmd = $eqLogic->getCmd('info', 'alert_state');
					if (is_object($cmd)) {
						$value = (!$isReachable || $obj->getAlert() == "none") ? 0 : 1;
						$eqLogic->checkAndUpdateCmd($cmd, $value);
					}
					$cmd = $eqLogic->getCmd('info', 'rainbow_state');
					if (is_object($cmd)) {
						$value = (!$isReachable || $obj->getEffect() == "none") ? 0 : 1;
						$eqLogic->checkAndUpdateCmd($cmd, $value);
					}
					$cmd = $eqLogic->getCmd('info', 'color_temp_state');
					if (is_object($cmd)) {
						$eqLogic->checkAndUpdateCmd($cmd, $obj->getColorTemp());
					}
				}
			} catch (Exception $e) {
				if ($_eqLogic_id != null) {
					log::add('philipsHue', 'error', $e->getMessage());
				}
			}
		}
	}

	public static function devicesParameters($_device = '') {
		$return = array();
		foreach (ls(dirname(__FILE__) . '/../config/devices/', '*.json') as $file) {
			try {
				$content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $file);
				if (is_json($content)) {
					$return += json_decode($content, true);
				}
			} catch (Exception $e) {

			}
		}
		if (isset($_device) && $_device != '') {
			if (isset($return[$_device])) {
				return $return[$_device];
			}
			return array();
		}
		return $return;
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {
		if ($this->getConfiguration('category') != 'sensor') {
			$this->setCategory('light', 1);
		}
	}

	public function postSave() {
		if ($this->getConfiguration('applyDevice') != $this->getConfiguration('device')) {
			$this->applyModuleConfiguration();
		}
		$groups = self::getPhilipsHue()->getgroups();
		$lights = self::getPhilipsHue()->getlights();
		$scene_cmd = $this->getCmd('action', 'scene');
		if ($this->getConfiguration('category') == 'group') {
			$scene_str = '';
			foreach (self::getPhilipsHue()->getScenes() as $scene) {
				$name = $scene->getName();
				if ($name == '') {
					continue;
				}
				if (!isset($groups[$this->getConfiguration('id')])) {
					continue;
				}
				$find = false;
				$lights_ids = $groups[$this->getConfiguration('id')]->getLightIds();
				foreach ($scene->getLightIds() as $value) {
					if (in_array($value, $lights_ids)) {
						$find = true;
						break;
					}
				}
				if (!$find) {
					continue;
				}
				$scene_str .= $scene->getId() . '|' . $name . ';';
			}
			if ($scene_str != '') {
				$scene_cmd = $this->getCmd('action', 'scene');
				if (!is_object($scene_cmd)) {
					$scene_cmd = new philipsHueCmd();
					$scene_cmd->setName('Scene');
					$scene_cmd->setType('action');
					$scene_cmd->setSubtype('select');
					$scene_cmd->setEqLogic_id($this->getId());
					$scene_cmd->setIsVisible(1);
					$scene_cmd->setLogicalId('scene');
				}
				$scene_cmd->setConfiguration('listValue', trim($scene_str, ';'));
				$scene_cmd->save();
			} else {
				$scene_cmd = $this->getCmd('action', 'scene');
				if (is_object($scene_cmd)) {
					$scene_cmd->remove();
				}
			}
		} else {
			$scene_cmd = $this->getCmd('action', 'scene');
			if (is_object($scene_cmd)) {
				$scene_cmd->remove();
			}
		}
	}

	public function applyModuleConfiguration() {
		$this->setConfiguration('applyDevice', $this->getConfiguration('device'));
		$this->save();
		if ($this->getConfiguration('device') == '') {
			return true;
		}
		$device = self::devicesParameters($this->getConfiguration('device'));
		if (!is_array($device)) {
			return true;
		}
		$this->import($device);
	}

	public function getImgFilePath() {
		if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $this->getConfiguration('device') . '.png')) {
			return $this->getConfiguration('device') . '.png';
		}
		return false;
	}

}

class philipsHueCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = null) {
		if ($this->getType() != 'action') {
			return;
		}
		$eqLogic = $this->getEqLogic();
		$hue = philipsHue::getPhilipsHue();
		if ($eqLogic->getConfiguration('category') == 'sensor') {
			$sensors = philipsHue::sanitizeSensors($hue->getSensors());
			if (!isset($sensors[$eqLogic->getConfiguration('id')])) {
				return;
			}
			$sensor = $sensors[$eqLogic->getConfiguration('id')];
			foreach ($sensor as $mine) {
				foreach ($this->getConfiguration('toUpdate') as $value) {
					if (isset($value['onType']) && $value['onType'] != $mine->getType()) {
						continue;
					}
					$toSet = $value['value'];
					if (isset($value['valueType'])) {
						switch ($value['valueType']) {
							case 'boolean':
								$toSet = (boolean) $value['value'];
								break;
							case 'int':
								$toSet = (int) $value['value'];
								break;
						}
					}
					if ($value['type'] == 'config') {
						$command = new \Phue\Command\UpdateSensorConfig($mine);
						$command = $command->configAttribute($value['key'], $toSet);
						$hue->sendCommand($command);
					}
				}
			}
			return;
		}
		$transition = $eqLogic->getCmd(null, 'transition_state');
		$transistion_time = 0;
		if (is_object($transition)) {
			$transistion_time = $transition->execCmd(null, 2);
			if ($transistion_time !== 0) {
				$transition->event(0);
			}
		}
		$transistion_time = ($transistion_time == 0) ? 1 : $transistion_time;

		switch ($eqLogic->getConfiguration('category')) {
			case 'light':
				$command = new \Phue\Command\SetLightState($eqLogic->getConfiguration('id'));
				break;
			case 'group':
				$command = new \Phue\Command\SetGroupState($eqLogic->getConfiguration('id', 0));
				break;
			default:
				return;
		}
		$command->transitionTime($transistion_time);
		$command->on(true);
		switch ($this->getLogicalId()) {
			case 'on':

				break;
			case 'off':
				if ($eqLogic->getConfiguration('model') != "LWB004") {
					$command->effect('none');
				}
				$command->alert('none');
				$command->on(false);
				break;
			case 'luminosity':
				if ($_options['slider'] == 0) {
					if ($eqLogic->getConfiguration('model') != "LWB004") {
						$command->effect('none');
					}
					$command->alert('none');
					$command->on(false);
				} else {
					$command->brightness($_options['slider']);
				}
				break;
			case 'color_temp':
				$command->colorTemp((int) $_options['slider']);
				break;
			case 'color':
				if ($_options['color'] == '#000000') {
					if ($eqLogic->getConfiguration('model') != "LWB004") {
						$command->effect('none');
					}
					$command->alert('none');
					$command->on(false);
				} else {
					list($r, $g, $b) = str_split(str_replace('#', '', $_options['color']), 2);
					$command->rgb(hexdec($r), hexdec($g), hexdec($b));
				}
				break;
			case 'alert_on':
				$command->alert('lselect');
				break;
			case 'alert_off':
				$command->alert('none');
				break;
			case 'rainbow_on':
				$command->effect('colorloop');
				break;
			case 'rainbow_off':
				$command->effect('none');
				break;
			case 'transition':
				if (is_object($transition)) {
					$transition->event($_options['slider']);
				}
				return;
			case 'scene':
				$command->scene($_options['select']);
				break;
		}
		$hue->sendCommand($command);
	}

	/*     * **********************Getteur Setteur*************************** */
}
?>
