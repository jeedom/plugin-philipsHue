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
		self::$_hue = new \Phue\Client(config::byKey('bridge_ip', 'philipsHue'), 'newdeveloper');
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
				$hue->sendCommand(
					new \Phue\Command\CreateUser('newdeveloper')
				);
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
			if ($eqLogic->getIsEnable() == 0) {
				continue;
			}
			if ($eqLogic->getLogicalId() == 'group0') {
				continue;
			}
			try {
				$isReachable = true;
				switch ($eqLogic->getConfiguration('category')) {
					case 'light':
						$obj = $lights[$eqLogic->getConfiguration('id')];
						$isReachable = $obj->isReachable();
						break;
					case 'group':
						$obj = $groups[$eqLogic->getConfiguration('id')];
						break;
					default:
						return;
				}
				if (!$isReachable || !$obj->isOn()) {
					$luminosity = 0;
					$color = '#000000';
				} else {
					$luminosity = $obj->getBrightness();
					$color = array(
						'hue' => $obj->getHue(),
						'sat' => $obj->getSaturation(),
						'bri' => $obj->getBrightness(),
					);
					$color = '#' . philipsHue::rgb2hex(philipsHue::hsb2rgb($color));
				}

				$cmd = $eqLogic->getCmd(null, 'luminosity_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($luminosity);
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'color_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($color);
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'alert_state');
				if (is_object($cmd)) {
					$value = (!$isReachable || $obj->getAlert() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'rainbow_state');
				if (is_object($cmd)) {
					$value = (!$isReachable || $obj->getEffect() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}
			} catch (Exception $e) {
				if ($_eqLogic_id != null) {
					log::add('philipsHue', 'error', $e->getMessage());
				}
			}
		}
	}

	/*****************************COLOR CONVERTION**************************************/
	public function hex2rgb($sHex) {
		$sHex = trim($sHex, '#');
		list($mRed, $mGreen, $mBlue) = str_split($sHex, 2);
		$hRgb = [
			'red' => hexdec($mRed),
			'green' => hexdec($mGreen),
			'blue' => hexdec($mBlue),
		];
		return $hRgb;
	}

	public function rgb2hex($hRgb) {
		$sResult = "";
		foreach ([
			'red',
			'green',
			'blue',
		] as $sKey) {
			$sResult .= str_pad(dechex($hRgb[$sKey]), 2, "0", STR_PAD_LEFT);
		}
		return $sResult;
	}

	public function hsb2rgb($hHsb) {
		$fHue = ($hHsb['hue'] / 65535) * 360;
		$fSat = $hHsb['sat'] / 255;
		$fBri = $hHsb['bri'] / 255;
		$fC = (1.0 - abs(2 * $fBri - 1.0)) * $fSat;
		$fX = $fC * (1.0 - abs(fmod(($fHue / 60.0), 2) - 1.0));
		$fM = $fBri - ($fC / 2.0);
		if ($fHue < 60) {
			$fRed = $fC;
			$fGreen = $fX;
			$fBlue = 0;
		} else if ($fHue < 120) {
			$fRed = $fX;
			$fGreen = $fC;
			$fBlue = 0;
		} else if ($fHue < 180) {
			$fRed = 0;
			$fGreen = $fC;
			$fBlue = $fX;
		} else if ($fHue < 240) {
			$fRed = 0;
			$fGreen = $fX;
			$fBlue = $fC;
		} else if ($fHue < 300) {
			$fRed = $fX;
			$fGreen = 0;
			$fBlue = $fC;
		} else {
			$fRed = $fC;
			$fGreen = 0;
			$fBlue = $fX;
		}
		$fRed = ($fRed + $fM) * 255;
		$fGreen = ($fGreen + $fM) * 255;
		$fBlue = ($fBlue + $fM) * 255;
		return [
			'red' => floor($fRed),
			'green' => floor($fGreen),
			'blue' => floor($fBlue),
		];
	}

	public function rgb2hsb($hRgb) {
		$mRed = $hRgb['red'] / 255;
		$mGreen = $hRgb['green'] / 255;
		$mBlue = $hRgb['blue'] / 255;
		$fMax = max($mRed, $mGreen, $mBlue);
		$fMin = min($mRed, $mGreen, $mBlue);

		$fBri = ($fMax + $fMin) / 2;
		//$fBri = 0.30 * $mRed + 0.59 * $mGreen + 0.11 * $mBlue;
		$fDiff = $fMax - $fMin;
		$fHue = 0;
		if (0 == $fDiff) {
			$fHue = $fSat = 0;
		} else {
			$fSat = $fDiff / (1 - abs(2 * $fBri - 1));
			switch ($fMax) {
				case $mRed:
					$fHue = 60 * fmod((($mGreen - $mBlue) / $fDiff), 6);
					if ($mBlue > $mGreen) {
						$fHue += 360;
					}
					break;
				case $mGreen:
					$fHue = 60 * (($mBlue - $mRed) / $fDiff + 2);
					break;
				case $mBlue:
					$fHue = 60 * (($mRed - $mGreen) / $fDiff + 4);
					break;
			}
		}
		return [
			'hue' => (int) (($fHue / 360) * 65535),
			'sat' => (int) ($fSat * 255),
			'bri' => (int) ($fBri * 255),
		];
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
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setConfiguration('doNotRepeatEvent', 1);
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
		$cmd->setValue($luminosity_id);
		$cmd->setConfiguration('doNotRepeatEvent', 1);
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
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setConfiguration('doNotRepeatEvent', 1);
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
		$cmd->setEqLogic_id($this->getId());
		$cmd->setValue($color_id);
		$cmd->save();

		$alert_id = null;
		if ($this->getLogicalId() != 'group0') {
			$cmd = $this->getCmd(null, 'alert_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('alert_state');
				$cmd->setName(__('Etat Alerte', __FILE__));
				$cmd->setIsVisible(0);
			}
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setConfiguration('doNotRepeatEvent', 1);
			$cmd->save();
			$alert_id = $cmd->getId();
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
				$cmd->setEventOnly(1);
				$cmd->setEqLogic_id($this->getId());
				$cmd->setConfiguration('doNotRepeatEvent', 1);
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
		$cmd->setConfiguration('doNotRepeatEvent', 1);
		$cmd->save();

		if ($this->getLogicalId() == 'group0') {
			$scenes_id = array();
			foreach (self::getPhilipsHue()->getScenes() as $scene) {
				if (!$scene->isActive()) {
					continue;
				}
				$scenes_id[$scene->getId()] = $scene->getId();
				$name = $scene->getName();
				$name = trim(substr($name, 0, -13));
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
		$hue = philipsHue::getPhilipsHue();
		switch ($eqLogic->getConfiguration('category')) {
			case 'light':
				$command = new \Phue\Command\SetLightState($eqLogic->getConfiguration('id'));
				$command->transitionTime($transistion_time);
				$command->on(true);
				break;
			case 'group':
				$command = new \Phue\Command\SetGroupState($eqLogic->getConfiguration('id', 0));
				$command->transitionTime($transistion_time);
				$command->on(true);
				break;
			default:
				return;
		}
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
					$state = $eqLogic->getCmd(null, 'luminosity_state');
					if (is_object($state) && $state->execCmd(null, 2) == 0) {
						$command->saturation(0);
						$command->hue(0);
					}
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
					$parameter = philipsHue::rgb2hsb(philipsHue::hex2rgb($_options['color']));
					$command->brightness($parameter['bri']);
					$command->saturation($parameter['sat']);
					$command->hue($parameter['hue']);
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