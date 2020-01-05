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
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

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
		try {
			if(date('i') == 0 && date('s') < 10){
				sleep(10);
			}
			$plugin = plugin::byId(__CLASS__);
			$plugin->deamon_start(true);
		} catch (\Exception $e) {
			
		}
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
		$lights = $hue->getLights();
		foreach ($lights as $id => $light) {
			$modelId = $light->getModelId();
			log::add('philipsHue', 'debug', 'Found light model : '.$modelId);
			if (count(self::devicesParameters($light->getModelId())) == 0) {
				$modelId = 'default_nocolor';
				log::add('philipsHue', 'debug', 'No configuration found for light : ' . $light->getModelId() . ' => ' . json_encode(utils::o2a($light)));
				if(!in_array($light->getColorMode(),array('hs','ct','xy'))){
					$modelId = 'default_color';
				}
				log::add('philipsHue', 'debug', 'Use generic configuration : '.$modelId);
			}
			$eqLogic = self::byLogicalId('light' . $id, 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId('light' . $id);
				$eqLogic->setName($light->getName());
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setConfiguration('device', $modelId);
			}
			$eqLogic->setConfiguration('category', 'light');
			$eqLogic->setConfiguration('id', $id);
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
		$sensors = self::sanitizeSensors($hue->getSensors());
		foreach ($sensors as $id => $sensor) {
			$sensor = array_values($sensor)[0];
			log::add('philipsHue', 'debug', 'Found sensor model : '.$sensor->getModelId());
			if (count(self::devicesParameters($sensor->getModelId())) == 0) {
				log::add('philipsHue', 'debug', 'No configuration found for sensor : ' . $sensor->getModelId() . ' => ' . json_encode(utils::o2a($sensor)));
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
				$eqLogic->setConfiguration('device', $sensor->getModelId());
			}
			$eqLogic->setConfiguration('category', 'sensor');
			$eqLogic->setConfiguration('id', $id);
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
		try {
			$groups = $hue->getgroups();
			$lights = $hue->getLights();
		} catch (Exception $e) {
			sleep(5);
			$groups = $hue->getgroups();
			$lights = $hue->getLights();
		}
		if (self::$_eqLogics == null) {
			self::$_eqLogics = self::byType('philipsHue');
		}
		$sensors = self::sanitizeSensors($hue->getSensors());
		$timezone = config::byKey('timezone', 'core', 'Europe/Brussels');
		foreach (self::$_eqLogics as &$eqLogic) {
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
						if ($obj == null || !is_object($obj)) {
							continue;
						}
						$lastupdate = 0;
						$datetime = new \DateTime();
						if (isset($obj->getState()->lastupdated) && $obj->getState()->lastupdated !== "none") {
							$datetime = new \DateTime($obj->getState()->lastupdated, new \DateTimeZone("UTC"));
							$datetime->setTimezone(new \DateTimezone($timezone));
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
							}else if ($key == 'buttonevent') {
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
							$eqLogic->checkAndUpdateCmd($key, $value, $datetime->format('Y-m-d H:i:s'));
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
							$eqLogic->checkAndUpdateCmd($cmd, $value,false);
						}
					}
				} else {
					switch ($eqLogic->getConfiguration('category')) {
						case 'light':
						$obj = $lights[$eqLogic->getConfiguration('id')];
						if ($obj == null || !is_object($obj)) {
							break;
						}
						$isReachable = ($eqLogic->getConfiguration('alwaysOn', 0) == 0) ? $obj->isReachable() : true;
						$eqLogic->checkAndUpdateCmd('isReachable', $obj->isReachable(),false);
						break;
						case 'group':
						$obj = $groups[$eqLogic->getConfiguration('id')];
						break;
					}
					if ($obj === null || !is_object($obj)) {
						continue;
					}
					if (!$isReachable || !$obj->isOn()) {
						$luminosity = 0;
						$color = '#000000';
					} else {
						$luminosity = $obj->getBrightness();
						if (is_object($eqLogic->getCmd('info', 'color_state'))) {
							$rgb = $obj->getRGB();
							$color = '#' . sprintf('%02x', $rgb['red']) . sprintf('%02x', $rgb['green']) . sprintf('%02x', $rgb['blue']);
							if (!is_nan($rgb['red']) && !is_nan($rgb['green']) && !is_nan($rgb['blue']) && $color == '#000000') {
								$luminosity = 0;
							}
						}
					}
					$eqLogic->checkAndUpdateCmd('luminosity_state',$luminosity,false);
					$eqLogic->checkAndUpdateCmd('state', $obj->isOn(),false);
					$eqLogic->checkAndUpdateCmd('color_state', $color,false);
					$cmd = $eqLogic->getCmd('info', 'alert_state');
					if (is_object($cmd)) {
						$value = (!$isReachable || $obj->getAlert() == "none") ? 0 : 1;
						$eqLogic->checkAndUpdateCmd($cmd, $value,false);
					}
					if($eqLogic->getConfiguration('category') != 'group'){
						$cmd = $eqLogic->getCmd('info', 'rainbow_state');
						if (is_object($cmd)) {
							$value = (!$isReachable || $obj->getEffect() == "none") ? 0 : 1;
							$eqLogic->checkAndUpdateCmd($cmd, $value,false);
						}
					}
					$cmd = $eqLogic->getCmd('info', 'color_temp_state');
					if (is_object($cmd)) {
						$eqLogic->checkAndUpdateCmd($cmd, $obj->getColorTemp(),false);
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
				$return += is_json($content, array());
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
		if ($this->getConfiguration('category') == 'light') {
			$cmd = $this->getCmd('info', 'isReachable');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setName(__('Joignable', __FILE__));
				$cmd->setType('info');
				$cmd->setSubtype('binary');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setIsVisible(0);
				$cmd->setLogicalId('isReachable');
				$cmd->save();
			}
		}
		$scene_cmd = $this->getCmd('action', 'scene');
		if ($this->getConfiguration('category') == 'group') {
			$groups = self::getPhilipsHue()->getgroups();
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
					$scene_cmd->setName(__('Scene', __FILE__));
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
		
		$animation = $this->getCmd('action', 'animation');
		if($this->getConfiguration('animation',1) == 0){
			if (is_object($animation)) {
				$animation->remove();
			}
		}else{
			if (!is_object($animation)) {
				$animation = new philipsHueCmd();
				$animation->setName(__('Animation', __FILE__));
				$animation->setType('action');
				$animation->setSubtype('message');
				$animation->setEqLogic_id($this->getId());
				$animation->setIsVisible(0);
				$animation->setLogicalId('animation');
			}
			$animation->setDisplay('title_possibility_list', json_encode(array('sunset', 'sunrise')));
			$animation->setDisplay('message_placeholder', __('Options', __FILE__));
			$animation->setDisplay('title_placeholder', __('Nom de l\'animation', __FILE__));
			$animation->save();
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
	
	public function getImage() {
		$imgpath = $this->getImgFilePath();
		if ($imgpath === false) {
			return 'plugins/philipsHue/plugin_info/philipsHue_icon.png';
		}
		return 'plugins/philipsHue/core/config/devices/' . $imgpath;
	}
	
	public function animation($_animation, $_options) {
		if (count(system::ps('core/php/jeeHueAnimation.php id=' . $this->getId())) > 0) {
			return true;
		}
		$cmd = 'php ' . dirname(__FILE__) . '/../../core/php/jeeHueAnimation.php id=' . $this->getId();
		$cmd .= ' animation=' . $_animation;
		$cmd .= ' ' . $_options;
		$cmd .= ' >> ' . log::getPathToLog('philipsHue_animation') . ' 2>&1 &';
		shell_exec($cmd);
		$this->setCache('current_animate', 1);
	}
	
	public function stopAnimation() {
		if (count(system::ps('core/php/jeeHueAnimation.php id=' . $this->getId())) > 0) {
			system::kill('core/php/jeeHueAnimation.php id=' . $this->getId(), false);
		}
		$this->setCache('current_animate', 0);
		return true;
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
		if ($this->getLogicalId() != 'off'){
			$command->transitionTime($transistion_time);
		}
		$command->on(true);
		if ($this->getLogicalId() != 'animation' && $eqLogic->getCache('current_animate', 0) == 1) {
			$eqLogic->stopAnimation();
		}
		switch ($this->getLogicalId()) {
			case 'on':
			//$command->brightness(255);
			//$command->rgb(255, 255, 255);
			break;
			case 'off':
			//if ($eqLogic->getConfiguration('model') != "LWB004") {
			//$command->effect('none');
			//}
			//$command->alert('none');
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
			case 'animation':
			$eqLogic->animation($_options['title'], $_options['message']);
			return;
		}
		$hue->sendCommand($command);
	}
	
	/*     * **********************Getteur Setteur*************************** */
}
?>
