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
			$eqLogic->setConfiguration('type', $light->getType());
			$eqLogic->setConfiguration('softwareVersion', $light->getSoftwareVersion());
			$eqLogic->save();
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
						$changed = true;
					}
				}

				$cmd = $eqLogic->getCmd(null, 'luminosity_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($obj->getBrightness());
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
						$changed = true;
					}
				}

				$cmd = $eqLogic->getCmd(null, 'saturation_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue($obj->getSaturation());
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
						$changed = true;
					}
				}

				$cmd = $eqLogic->getCmd(null, 'color_state');
				if (is_object($cmd)) {
					$value = $cmd->formatValue(self::xyBriToRgb($obj->getXY()['x'], $obj->getXY()['y'], $obj->getBrightness()));
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
						$changed = true;
					}
				}

				$cmd = $eqLogic->getCmd(null, 'alert_state');
				if (is_object($cmd)) {
					$value = ($obj->getAlert() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
						$changed = true;
					}
				}

				$cmd = $eqLogic->getCmd(null, 'rainbow_state');
				if (is_object($cmd)) {
					$value = ($obj->getEffect() == "none") ? 0 : 1;
					if ($value != $cmd->execCmd(null, 2)) {
						$cmd->setCollectDate('');
						$cmd->event($value);
						$changed = true;
					}
				}

				if ($changed) {
					$eqLogic->refreshWidget();
				}
			} catch (Exception $e) {
				if ($_eqLogic_id != null) {
					log::add('philipsHue', 'error', $e->getMessage());
				}
			}
		}
	}

	public static function xyBriToRgb($x, $y, $bri) {
		$hex_RGB = "";
		$hex_value = "";
		if (isset($x) && isset($y) && isset($bri) && $y != 0) {
			$z = 1.0 - $x - $y;
			$Y = $bri / 255.0;
			$X = ($Y / $y) * $x;
			$Z = ($Y / $y) * $z;
			$r = $X * 1.612 - $Y * 0.203 - $Z * 0.302;
			$g = -$X * 0.509 + $Y * 1.412 + $Z * 0.066;
			$b = $X * 0.026 - $Y * 0.072 + $Z * 0.962;
			$r = ($r <= 0.0031308) ? 12.92 * $r : (1.0 + 0.055) * pow($r, (1.0 / 2.4)) - 0.055;
			$g = ($g <= 0.0031308) ? 12.92 * $g : (1.0 + 0.055) * pow($g, (1.0 / 2.4)) - 0.055;
			$b = ($b <= 0.0031308) ? 12.92 * $b : (1.0 + 0.055) * pow($b, (1.0 / 2.4)) - 0.055;
			$maxValue = max($r, $g, $b);
			$r /= $maxValue;
			$g /= $maxValue;
			$b /= $maxValue;
			$r = $r * 255;if ($r < 0) {$r = 255;}
			$hex_value = dechex($r);
			if (strlen($hex_value) < 2) {$hex_value = "0" . $hex_value;}
			$hex_RGB .= $hex_value;
			$g = $g * 255;if ($g < 0) {$g = 255;}
			$hex_value = dechex($g);
			if (strlen($hex_value) < 2) {$hex_value = "0" . $hex_value;}
			$hex_RGB .= $hex_value;
			$b = $b * 255;if ($b < 0) {$b = 255;}
			$hex_value = dechex($b);
			if (strlen($hex_value) < 2) {$hex_value = "0" . $hex_value;}
			$hex_RGB .= $hex_value;
			return "#" . $hex_RGB;
		} else {
			return '#FFFFFF';
		}
	}

	public static function setHexCode2($rgb) {
		if (substr($rgb, 0, 1) == "#") {
			$rgb = substr($rgb, 1);
		}
		if (strlen($rgb) != 6) {
			return false;
		}
		if (strlen($rgb) == 6) {
			$r = hexdec(substr($rgb, 0, 2)) / 255;
			$g = hexdec(substr($rgb, 2, 2)) / 255;
			$b = hexdec(substr($rgb, 4, 2)) / 255;
			if ($r > 0.04045) {$rf = pow(($r + 0.055) / (1.0 + 0.055), 2.4);} else { $rf = $r / 12.92;}
			if ($r > 0.04045) {$gf = pow(($g + 0.055) / (1.0 + 0.055), 2.4);} else { $gf = $g / 12.92;}
			if ($r > 0.04045) {$bf = pow(($b + 0.055) / (1.0 + 0.055), 2.4);} else { $bf = $b / 12.92;}
			$x = $rf * 0.649926 + $gf * 0.103455 + $bf * 0.197109;
			$y = $rf * 0.234327 + $gf * 0.743075 + $bf * 0.022598;
			$z = $rf * 0.000000 + $gf * 0.053077 + $bf * 1.035763;
			if (($x + $y + $z) == 0) {
				$cx = 0;
				$cy = 0;
			} else {
				$cx = $x / ($x + $y + $z);
				$cy = $y / ($x + $y + $z);
				if (is_nan($cx)) {$cx = 0;}
				if (is_nan($cy)) {$cy = 0;}
			}
			$xy[0] = $cx;
			$xy[1] = $cy;
			$arrData['bri'] = intval($y * 254);
			$arrData['xy'] = $xy;
		} else {
			$arrData['ct'] = 150;
			$arrData['bri'] = 254;
		}
		return $arrData;
	}

	/*     * *********************Méthodes d'instance************************* */

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
		$cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
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
			'#type#' => $this->getConfiguration('type'),
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

		$cmd = $this->getCmd(null, 'saturation');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('saturation');
			$cmd->setName(__('Saturation', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('slider');
		$cmd->setConfiguration('minValue', '0');
		$cmd->setConfiguration('maxValue', '255');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'saturation_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setLogicalId('saturation_state');
			$cmd->setName(__('Etat Saturation', __FILE__));
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEventOnly(1);
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

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
		switch ($this->getLogicalId()) {
			case 'on':
				$obj->setOn(true);
				break;
			case 'off':
				if ($eqLogic->getConfiguration('model') != "LWB004") {
					$obj->setEffect('none');
				}
				$obj->setAlert('none');
				$obj->setOn(false);
				break;
			case 'luminosity':
				$obj->setOn(true);
				$obj->setBrightness($_options['slider']);
				break;
			case 'saturation':
				$obj->setOn(true);
				$obj->setSaturation($_options['slider']);
				break;
			case 'color':
				$obj->setOn(true);
				if ($eqLogic->getConfiguration('category') == 'light') {
					$command = new \Phue\Command\SetLightState($obj);
				} else {
					$command = new \Phue\Command\SetGroupState($obj);
				}
				$parameter = philipsHue::setHexCode2($_options['color']);
				$command->xy($parameter['xy'][0], $parameter['xy'][1]);
				$hue->sendCommand($command);
				break;
			case 'alert_on':
				if ($obj->getAlert() == "none") {
					$response = $obj->setOn(true);
					$response = $obj->setAlert('lselect');
				}
				break;
			case 'alert_off':
				if ($obj->getAlert() != "none") {
					$response = $obj->setAlert('none');
				}
				break;
			case 'rainbow_on':
				if ($obj->getEffect() == "none") {
					$response = $obj->setOn(true);
					$response = $obj->setEffect('colorloop');
				}
				break;
			case 'rainbow_off':
				if ($obj->getEffect() != "none") {
					$obj->setEffect('none');
				}
				break;
		}
		philipsHue::cron15($eqLogic->getId());

		/*     * **********************Getteur Setteur*************************** */
	}
}
?>