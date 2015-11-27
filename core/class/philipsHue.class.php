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

	/*     * ***********************Methode static*************************** */

	public static function getPhilipsHue() {
		return new \Phue\Client(config::byKey('bridge_ip', 'philipsHue'), 'newdeveloper');
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
					throw new Exception(__('Tous les ids de lampe pour les groupes doivent être de chiffre : ', __FILE__) . $group_config);

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

	public static function syncBridge() {
		if (config::byKey('bridge_ip', 'philipsHue') == '') {
			throw new Exception(__('L\'adresse du bridge ne peut etre vide', __FILE__));
		}
		$hue = self::getPhilipsHue();
		try {
			$hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
			throw new Exception(__('Impossible de joindre le bridge', __FILE__));
		}
		if (!$hue->sendCommand(new \Phue\Command\IsAuthorized)) {
			try {
				$hue->sendCommand(
					new \Phue\Command\CreateUser('newdeveloper')
				);
			} catch (\Phue\Transport\Exception\LinkButtonException $e) {
				throw new Exception(__('Veuillez appuyer sur le bouton du bridge pour autoriser la connexion', __FILE__));
			}
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
		self::cron15();
	}

	public static function cron15($_eqLogic_id = null) {
		foreach (eqLogic::byType('philipsHue') as $eqLogic) {
			if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
				continue;
			}
			if ($eqLogic->getIsEnable() == 0) {
				continue;
			}
			if ($eqLogic->getConfiguration('category') == 'group' && $eqLogic->getConfiguration('id') == 0) {
				continue;
			}
			try {
				$changed = false;
				$hue = philipsHue::getPhilipsHue();
				switch ($eqLogic->getConfiguration('category')) {
					case 'light':
						$obj = $hue->getLights()[$eqLogic->getConfiguration('id')];
						break;
					case 'group':
						$obj = $hue->getGroups()[$eqLogic->getConfiguration('id')];
						break;
					default:
						return;
				}

				$cmd = $eqLogic->getCmd(null, 'state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($obj->isOn());
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'luminosity_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($obj->getBrightness());
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'color_state');
				if (is_object($cmd)) {
					$color = array(
						'hue' => $obj->getHue(),
						'sat' => $obj->getSaturation(),
						'bri' => $obj->getBrightness(),
					);
					$value = $cmd->formatValue('#' . philipsHue::rgb2hex(philipsHue::hsb2rgb($color)));
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'alert_state');
				if (is_object($cmd)) {
					$value = ($obj->getAlert() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}

				$cmd = $eqLogic->getCmd(null, 'rainbow_state');
				if (is_object($cmd)) {
					$value = ($obj->getEffect() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
					}
				}
				$mc = cache::byKey('philipsHueWidgetmobile' . $eqLogic->getId());
				$mc->remove();
				$mc = cache::byKey('philipsHueWidgetdashboard' . $eqLogic->getId());
				$mc->remove();
				$eqLogic->refreshWidget();
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

	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
		$mc = cache::byKey('philipsHueWidget' . $_version . $this->getId());
		if ($mc->getValue() != '') {
			return preg_replace("/" . preg_quote(self::UIDDELIMITER) . "(.*?)" . preg_quote(self::UIDDELIMITER) . "/", self::UIDDELIMITER . mt_rand() . self::UIDDELIMITER, $mc->getValue());
		}
		$vcolor = 'cmdColor';
		if ($_version == 'mobile') {
			$vcolor = 'mcmdColor';
		}
		$parameters = $this->getDisplay('parameters');
		$cmdColor = ($this->getPrimaryCategory() == '') ? jeedom::getConfiguration('eqLogic:category:default:' . $vcolor) : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		if (is_array($parameters) && isset($parameters['background_cmd_color'])) {
			$cmdColor = $parameters['background_cmd_color'];
		}
		$replace = array(
			'#id#' => $this->getId(),
			'#info#' => (isset($info)) ? $info : '',
			'#name#' => $this->getName(),
			'#eqLink#' => ($this->hasRight('w')) ? $this->getLinkToConfiguration() : '#',
			'#text_color#' => $this->getConfiguration('text_color'),
			'#cmdColor#' => $cmdColor,
			'#background_color#' => $this->getBackgroundColor($_version),
			'#hideThumbnail#' => 0,
			'#object_name#' => '',
			'#version#' => $_version,
			'#style#' => '',
			'#category#' => $this->getConfiguration('category') . $this->getConfiguration('id'),
			'#uid#' => 'philipsHue' . $this->getId() . self::UIDDELIMITER . mt_rand() . self::UIDDELIMITER,
		);

		if ($_version == 'dview' || $_version == 'mview') {
			$object = $this->getObject();
			$replace['#name#'] = (is_object($object)) ? $object->getName() . ' - ' . $replace['#name#'] : $replace['#name#'];
		}
		if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
			$replace['#name#'] = '';
		}
		if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
			$replace['#name#'] = '';
		}

		foreach ($this->getCmd() as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			if ($cmd->getType() == 'info') {
				$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd(null, 2);
				if ($cmd->getSubType() == 'numeric' && $replace['#' . $cmd->getLogicalId() . '#'] === '') {
					$replace['#' . $cmd->getLogicalId() . '#'] = 0;
				}
				$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
			}
		}
		$html = template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'philipsHue', 'philipsHue'));
		cache::set('networksWidget' . $_version . $this->getId(), $html, 0);
		return $html;
	}

	/*     * *********************Methode d'instance************************* */

	public function postSave() {
		if ($this->getConfiguration('category') != 'group' || $this->getConfiguration('id') != 0) {
			$cmd = $this->getCmd(null, 'refresh');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('refresh');
				$cmd->setName(__('Rafraîchir', __FILE__));
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'on');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('on');
			$cmd->setName(__('On', __FILE__));
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
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		if ($this->getConfiguration('category') != 'group' || $this->getConfiguration('id') != 0) {
			$cmd = $this->getCmd(null, 'state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('state');
				$cmd->setName(__('Etat On Off', __FILE__));
			}
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'luminosity');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('luminosity');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Luminosité', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('slider');
		$cmd->setConfiguration('minValue', '0');
		$cmd->setConfiguration('maxValue', '255');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		if ($this->getConfiguration('category') != 'group' || $this->getConfiguration('id') != 0) {
			$cmd = $this->getCmd(null, 'luminosity_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('luminosity_state');
				$cmd->setName(__('Etat Luminosité', __FILE__));
			}
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'color');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('color');
			$cmd->setName(__('Couleur', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('color');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		if ($this->getConfiguration('category') != 'group' || $this->getConfiguration('id') != 0) {
			$cmd = $this->getCmd(null, 'color_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('color_state');
				$cmd->setName(__('Etat Couleur', __FILE__));
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setUnite('');
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'alert_on');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('alert_on');
			$cmd->setName(__('Alerte On', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'alert_off');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('alert_off');
			$cmd->setName(__('Alerte Off', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		if ($this->getConfiguration('category') != 'group') {
			$cmd = $this->getCmd(null, 'alert_state');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('alert_state');
				$cmd->setName(__('Etat Alerte', __FILE__));
			}
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setEventOnly(1);
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		if ($this->getConfiguration('model') != "LWB004") {
			$cmd = $this->getCmd(null, 'rainbow_on');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('rainbow_on');
				$cmd->setName(__('Arc en ciel On', __FILE__));
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();

			$cmd = $this->getCmd(null, 'rainbow_off');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setLogicalId('rainbow_off');
				$cmd->setName(__('Arc en ciel Off', __FILE__));
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();

			if ($this->getConfiguration('category') != 'group') {
				$cmd = $this->getCmd(null, 'rainbow_state');
				if (!is_object($cmd)) {
					$cmd = new philipsHueCmd();
					$cmd->setLogicalId('rainbow_state');
					$cmd->setIsVisible(1);
					$cmd->setName(__('Etat Arc en ciel', __FILE__));
				}
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setEventOnly(1);
				$cmd->setEqLogic_id($this->getId());
				$cmd->save();
			}
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
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		if ($this->getConfiguration('id') == 0 && $this->getConfiguration('category') == 'group') {
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
				$command->brightness($_options['slider']);
				break;
			case 'color':
				$parameter = philipsHue::rgb2hsb(philipsHue::hex2rgb($_options['color']));
				$command->brightness($parameter['bri']);
				$command->saturation($parameter['sat']);
				$command->hue($parameter['hue']);
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
				philipsHue::cron15($eqLogic->getId());
				return;
		}
		$hue->sendCommand($command);
		sleep(1);
		philipsHue::cron15($eqLogic->getId());

		/*     * **********************Getteur Setteur*************************** */
	}
}
?>