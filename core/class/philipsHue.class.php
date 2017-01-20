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
		if (self::$_hue !== null) {
			return self::$_hue;
		}
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
		return self::$_hue;
	}

	public static function syncGroup() {
		$hue = self::getPhilipsHue();
		for ($i = 1; $i < 17; $i++) {
			$group_config = config::byKey('group' . $i, 'philipsHue');
			$group_id = '';
			foreach ($hue->getGroups() as $groupId => $group) {
				if ($group->getName() == 'Jeedom ' . $i) {
					$group_id = $groupId;
				}
			}
			if (trim($group_config) == '' || trim($group_config) == '-') {
				if ($group_id != '') {
					$hue->getGroups()[$group_id]->delete();
				}
				continue;
			}
			$lights = explode(',', $group_config);
			foreach ($lights as $light) {
				if (!is_numeric($light)) {
					throw new Exception(__('Tous les ids de lampe pour les groupes doivent être des chiffres : ', __FILE__) . $group_config);

				}
			}
			if ($group_id == '') {
				$hue->sendCommand(
					new \Phue\Command\CreateGroup('Jeedom ' . $i, $lights)
				);
				continue;
			}
			$group = $hue->getGroups()[$group_id];
			$group->setLights($lights);
		}
	}

	public static function deleteGroup() {
		$hue = self::getPhilipsHue();
		foreach ($hue->getGroups() as $group) {
			$group->delete();
		}
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
		self::syncGroup();
		$lights_exist = array();
		$groups_exist = array(0 => 0);
		foreach ($hue->getLights() as $id => $light) {
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
			$eqLogic->setConfiguration('model', $light->getModelId());
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
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('category', 'group');
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->save();
			$groups_exist[$id] = $id;
		}
		foreach (self::byType('philipsHue') as $eqLogic) {
			if ($eqLogic->getConfiguration('category') == 'light') {
				if (!isset($lights_exist[$eqLogic->getConfiguration('id')])) {
					$eqLogic->remove();
				}
			} else {
				if (!isset($groups_exist[$eqLogic->getConfiguration('id')])) {
					$eqLogic->remove();
				}
			}
		}
	}

	public static function pull($_eqLogic_id = null) {
		try {
			$hue = philipsHue::getPhilipsHue();
		} catch (Exception $e) {
			return;
		}
		$groups = $hue->getgroups();
		$lights = $hue->getLights();
		foreach (eqLogic::byType('philipsHue') as $eqLogic) {
			if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
				continue;
			}
			if ($eqLogic->getIsEnable() == 0 || $eqLogic->getLogicalId() == 'group0') {
				continue;
			}
			try {
				$isReachable = true;
				switch ($eqLogic->getConfiguration('category')) {
					case 'light':
						$obj = $lights[$eqLogic->getConfiguration('id')];
						if ($eqLogic->getConfiguration('alwaysOn', 0) == 0) {
							$isReachable = $obj->isReachable();
						}
						break;
					case 'group':
						$obj = $groups[$eqLogic->getConfiguration('id')];
						break;
					default:
						continue;
				}
				if (!$isReachable || !$obj->isOn()) {
					$luminosity = 0;
					$color = '#000000';
				} else {
					$rgb = $obj->getRGB();
					$color = '#' . sprintf('%02x', $rgb['red']) . sprintf('%02x', $rgb['green']) . sprintf('%02x', $rgb['blue']);
					$luminosity = $obj->getBrightness();
					if ($color == '#000000') {
						$luminosity = 0;
					}
				}
				$eqLogic->checkAndUpdateCmd('luminosity_state', $luminosity);
				$eqLogic->checkAndUpdateCmd('color_state', $color);
				$value = (!$isReachable || $obj->getAlert() == "none") ? 0 : 1;
				$eqLogic->checkAndUpdateCmd('alert_state', $value);
				$value = (!$isReachable || $obj->getEffect() == "none") ? 0 : 1;
				$eqLogic->checkAndUpdateCmd('rainbow_state', $value);
			} catch (Exception $e) {
				if ($_eqLogic_id != null) {
					log::add('philipsHue', 'error', $e->getMessage());
				}
			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {
		$this->setCategory('light', 1);
	}

	/*     * *********************Methode d'instance************************* */

	public function postSave() {
		$luminosity_id = null;
		if ($this->getLogicalId() != 'group0') {
			$cmd = $this->getCmd(null, 'luminosity_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('luminosity_state');
				$cmd->setName(__('Etat Luminosité', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('generic_type', 'LIGHT_STATE');
			$cmd->save();
			$luminosity_id = $cmd->getId();
		}

		$cmd = $this->getCmd(null, 'on');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('on');
			$cmd->setName(__('On', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setDisplay('generic_type', 'LIGHT_ON');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'off');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('off');
			$cmd->setName(__('Off', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setDisplay('generic_type', 'LIGHT_OFF');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'luminosity');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('luminosity');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Luminosité', __FILE__));
			$cmd->setTemplate('dashboard', 'light');
			$cmd->setTemplate('mobile', 'light');
			$cmd->setOrder(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('slider');
		$cmd->setConfiguration('minValue', '0');
		$cmd->setConfiguration('maxValue', '255');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setDisplay('generic_type', 'LIGHT_SLIDER');
		$cmd->setValue($luminosity_id);
		$cmd->save();

		$color_id = null;
		if ($this->getLogicalId() != 'group0') {
			$cmd = $this->getCmd(null, 'color_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('color_state');
				$cmd->setName(__('Etat Couleur', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setDisplay('generic_type', 'LIGHT_COLOR');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
			$color_id = $cmd->getId();
		}

		$cmd = $this->getCmd(null, 'color');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('color');
			$cmd->setName(__('Couleur', __FILE__));
			$cmd->setOrder(1);
		}
		$cmd->setType('action');
		$cmd->setSubType('color');
		$cmd->setDisplay('generic_type', 'LIGHT_SET_COLOR');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setValue($color_id);
		$cmd->save();

		$alert_id = null;
		if ($this->getConfiguration('category') != 'group') {
			$cmd = $this->getCmd(null, 'alert_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('alert_state');
				$cmd->setName(__('Etat Alerte', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('generic_type', 'DONT');
			$cmd->save();
			$alert_id = $cmd->getId();
		} else {
			$cmd = $this->getCmd(null, 'alert_state');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}

		$cmd = $this->getCmd(null, 'alert_on');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('alert_on');
			$cmd->setName(__('Alerte On', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setDisplay('generic_type', 'LIGHT_MODE');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setValue($alert_id);
		$cmd->save();

		$cmd = $this->getCmd(null, 'alert_off');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('alert_off');
			$cmd->setName(__('Alerte Off', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setDisplay('generic_type', 'LIGHT_MODE');
		$cmd->setValue($alert_id);
		$cmd->save();

		if ($this->getConfiguration('model') != "LWB004") {
			$rainbow_id = null;
			if ($this->getConfiguration('category') != 'group') {
				$cmd = $this->getCmd(null, 'rainbow_state');
				if (!is_object($cmd)) {
					$cmd = new philipsHueCmd();
					$cmd->setLogicalId('rainbow_state');
					$cmd->setIsVisible(1);
					$cmd->setName(__('Etat Arc en ciel', __FILE__));
					$cmd->setIsVisible(0);
				}
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setDisplay('generic_type', 'DONT');
				$cmd->setEqLogic_id($this->getId());
				$cmd->save();
				$rainbow_id = $this->getId();
			}

			$cmd = $this->getCmd(null, 'rainbow_on');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('rainbow_on');
				$cmd->setName(__('Arc en ciel On', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('generic_type', 'LIGHT_MODE');
			$cmd->setValue($rainbow_id);
			$cmd->save();

			$cmd = $this->getCmd(null, 'rainbow_off');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('rainbow_off');
				$cmd->setName(__('Arc en ciel Off', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('generic_type', 'LIGHT_MODE');
			$cmd->setValue($rainbow_id);
			$cmd->save();

		} else {
			$cmd = $this->getCmd(null, 'rainbow_on');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'rainbow_off');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'rainbow_state');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}

		$cmd = $this->getCmd(null, 'transition');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('transition');
			$cmd->setName(__('Transition', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('action');
		$cmd->setSubType('slider');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setConfiguration('minValue', '0');
		$cmd->setConfiguration('maxValue', '1800');
		$cmd->setDisplay('generic_type', 'DONT');
		$cmd->save();

		$cmd = $this->getCmd(null, 'transition_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('transition_state');
			$cmd->setName(__('Transition status', __FILE__));
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setIsVisible(0);
		$cmd->setEqLogic_id($this->getId());
		$cmd->setDisplay('generic_type', 'DONT');
		$cmd->save();

		if ($this->getLogicalId() == 'group0') {
			$scenes_id = array();
			foreach (self::getPhilipsHue()->getScenes() as $scene) {
				$scenes_id[$scene->getId()] = $scene->getId();
				$name = $scene->getName();
				if ($name == '') {
					continue;
				}
				$cmd = $this->getCmd(null, 'set_scene_' . $scene->getId());
				if (!is_object($cmd)) {
					$cmd = new philipsHueCmd();
					$cmd->setLogicalId('set_scene_' . $scene->getId());
					$cmd->setName(__('Scène ' . $name, __FILE__));
					$cmd->setIsVisible(0);
				}
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setConfiguration('id', $scene->getId());
				$cmd->setEqLogic_id($this->getId());
				try {
					$cmd->save();
				} catch (Exception $e) {

				}
			}
			foreach ($this->getCmd('action') as $cmd) {
				if (strpos($cmd->getLogicalId(), 'set_scene_') === false) {
					continue;
				}
				if (!isset($scenes_id[$cmd->getConfiguration('id')])) {
					$cmd->remove();
				}
			}
		}

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

		$transition = $eqLogic->getCmd(null, 'transition_state');
		$transistion_time = 0;
		if (is_object($transition)) {
			$transistion_time = $transition->execCmd(null, 2);
			if ($transistion_time !== 0) {
				$transition->event(0);
			}
		}
		if ($transistion_time == 0) {
			$transistion_time = 1;
		}
		$hue = philipsHue::getPhilipsHue();
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
					$luminosity_state = $eqLogic->getCmd(null, 'luminosity_state');
					$command->brightness($_options['slider']);
				}
				break;
			case 'color':
				if ($_options['color'] == '#000000') {
					if ($eqLogic->getConfiguration('model') != "LWB004") {
						$command->effect('none');
					}
					$command->alert('none');
					$command->on(false);
				} else {
					list($r, $g, $b) = str_split($_options['color'], 2);
					$command->setRGB(hexdec($r) / 255, hexdec($g) / 255, hexdec($b) / 255);
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
			default:
				if (strpos($this->getLogicalId(), 'set_scene_') !== false) {
					$command = new \Phue\Command\SetGroupState(0);
					$command->scene($this->getConfiguration('id'));
					$hue->sendCommand($command);
				}
				return;
		}
		$hue->sendCommand($command);
	}

	/*     * **********************Getteur Setteur*************************** */
}
?>
