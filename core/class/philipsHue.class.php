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
require_once dirname(__FILE__) . '/pHueApi.class.php';

class philipsHue extends eqLogic {
	/*     * *************************Attributs****************************** */

	private static $_hue = array();
	public static $_encryptConfigKey = array('bridge_username1', 'bridge_username2');

	/*     * ***********************Methode static*************************** */

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'philipsHue';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder('philipsHue') . '/deamon.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid((int) trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				if (trim(file_get_contents($pid_file)) != '') {
					shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
				}
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start() {
		log::remove(__CLASS__ . '_update');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$philipsHue_path = realpath(dirname(__FILE__) . '/../../resources/philipsHued');
		chdir($philipsHue_path);
		$cmd = 'sudo /usr/bin/node ' . $philipsHue_path . '/philipsHued.js';
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('philipsHue'));
		$cmd .= ' --socketport ' . config::byKey('socketport', 'philipsHue');
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/philipsHue/core/php/jeephilipsHue.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey('philipsHue');
		$cmd .= ' --cycle ' . config::byKey('cycle', 'philipsHue');
		$cmd .= ' --pid ' . jeedom::getTmpFolder('philipsHue') . '/deamon.pid';
		$bridges = array();
		for ($i = 1; $i <= config::byKey('nbBridge', 'philipsHue'); $i++) {
			if (config::byKey('bridge_ip' . $i, 'philipsHue') == '') {
				continue;
			}
			$bridges[$i] = array('ip' => config::byKey('bridge_ip' . $i, 'philipsHue'), 'key' => config::byKey('bridge_username' . $i, 'philipsHue', 'newdeveloper'));
		}
		$cmd .= ' --bridges ' . escapeshellarg(json_encode($bridges));
		log::add('philipsHue', 'info', 'Lancement démon philipsHue : ' . $cmd);
		$result = exec($cmd . ' >> ' . log::getPathToLog('philipsHued') . ' 2>&1 &');
		$i = 0;
		while ($i < 15) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 14) {
			log::add('philipsHue', 'error', 'Impossible de lancer le démon philipsHued, vérifiez le log', 'unableStartDeamon');
			return false;
		}
		message::removeAll('philipsHue', 'unableStartDeamon');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder('philipsHue') . '/deamon.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('philipsHued.js');
		system::fuserk(config::byKey('socketport', 'philipsHue'));
	}

	public static function getPhilipsHue($_bridge_number = 1) {
		if (!isset(self::$_hue[$_bridge_number]) || self::$_hue[$_bridge_number] === null) {
			if (config::byKey('bridge_ip' . $_bridge_number, 'philipsHue') == '' || config::byKey('bridge_ip' . $_bridge_number, 'philipsHue') == '-') {
				return null;
			}
			self::$_hue[$_bridge_number] = new pHueApi(config::byKey('bridge_ip' . $_bridge_number, 'philipsHue'), config::byKey('bridge_username' . $_bridge_number, 'philipsHue'));
		}
		return self::$_hue[$_bridge_number];
	}

	public static function createUser($_bridge_number = 1) {
		if (config::byKey('bridge_ip' . $_bridge_number, 'philipsHue') == '') {
			throw new Exception(__('L\'adresse du bridge ne peut etre vide', __FILE__));
		}
		$hue = new pHueApi(config::byKey('bridge_ip' . $_bridge_number, 'philipsHue'));
		event::add('jeedom::alert', array(
			'level' => 'warning',
			'message' => __('Veuillez appuyer sur le bouton du bridge', __FILE__),
			'ttl' => 60000
		));
		$response = $hue->generateClientKey();
		if (isset($response[0]['error']) || !isset($response[0]['success'])) {
			throw new Exception(__('Impossible de créer l\'utilisateur, pressez vous bien le bouton du pont bridge ?', __FILE__));
		}
		config::save('bridge_username' . $_bridge_number, $response[0]['success']['username'], 'philipsHue');
		config::save('bridge_clientkey' . $_bridge_number, $response[0]['success']['clientkey'], 'philipsHue');
	}

	public static function serviceSuffix($_count){
		return ($_count < 2) ? '' : ' '.$_count;
	}

	public static function syncBridge($_bridge_number = 1) {
		if (config::byKey('bridge_username' . $_bridge_number, 'philipsHue') == '') {
			self::createUser($_bridge_number);
		}
		$hue = self::getPhilipsHue($_bridge_number);
		$devices = $hue->device();
		if (!is_array($devices)) {
			self::createUser($_bridge_number);
			self::$_hue = null;
			$hue = self::getPhilipsHue($_bridge_number);
			$devices = $hue->device();
		}
		foreach ($devices['data'] as $device) {
			$type = $device['services'][0]['rtype'];
			$modelId = $device['product_data']['model_id'];
			log::add('philipsHue', 'debug', 'Found device type ' . $type . ' model : ' . $modelId . ' => ' . json_encode($device));
			$id = $device['id'];
			$eqLogic = self::byLogicalId($id, 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = self::byLogicalId($type . str_replace(array('/lights/'), '', $device['id_v1']) . '-' . $_bridge_number, 'philipsHue');
				if (is_object($eqLogic)) {
					$eqLogic->setLogicalId($id);
					$eqLogic->save();
				}
			}
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($id);
				if (!isset($device['metadata']['name']) || $device['metadata']['name'] == '') {
					$eqLogic->setName($id);
				} else {
					$eqLogic->setName($device['metadata']['name']);
				}
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('device', $modelId);
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('category', $type);
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->setConfiguration('modelName', $device['product_data']['product_name']);
			$eqLogic->setConfiguration('softwareVersion', $device['product_data']['software_version']);
			foreach ($device['services'] as $service) {
				$eqLogic->setConfiguration('service_' . $service['rtype'], $service['rid']);
			}
			try {
				$eqLogic->save();
			} catch (Exception $e) {
				$eqLogic->setName($eqLogic->getName().' '.config::genKey(4));
				$eqLogic->save();
			}

			$cmd = $eqLogic->getCmd('action', 'refresh');
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setName(__('Rafraichir', __FILE__));
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setIsVisible(1);
				$cmd->setLogicalId('refresh');
			}
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->save();

			$num_button = 1;
			$service_count = array(
				'button' => 0,
				'motion' => 0,
				'contact' => 0,
				'tamper' => 0,
				'light_level' => 0,
				'temperature' => 0,
				'relative_rotary' => 0,
				'zigbee_connectivity' => 0,
				'light' => 0,
			);
			foreach ($device['services'] as $service) {
				if ($service['rtype'] == 'button') {
					$service_count['button']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Bouton ', __FILE__).self::serviceSuffix($service_count['button']) . $num_button);
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('string');
					$cmd->setConfiguration('category', 'button');
					$cmd->setConfiguration('repeatEventManagement', 'always');
					$cmd->save();
					$num_button++;
				}
				if ($service['rtype'] == 'motion') {
					$service_count['motion']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Présence', __FILE__).self::serviceSuffix($service_count['motion']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->setConfiguration('category', 'motion');
					$cmd->setGeneric_type('PRESENCE');
					$cmd->save();

					$cmd = $eqLogic->getCmd('info', 'enabled');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Etat', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('enabled');
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->save();
					$enabled_info_id = $cmd->getId(); 

					$cmd = $eqLogic->getCmd('action', 'enable');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Activer', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId('enable');
						$cmd->setTemplate('dashboard','binarySwitch');
						$cmd->setTemplate('mobile','binarySwitch');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->setValue($enabled_info_id);
					$cmd->save();

					$cmd = $eqLogic->getCmd('action', 'disable');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Désactiver', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId('disable');
						$cmd->setTemplate('dashboard','binarySwitch');
						$cmd->setTemplate('mobile','binarySwitch');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->setValue($enabled_info_id);
					$cmd->save();
				}


				if ($service['rtype'] == 'contact') {
					$service_count['contact']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Porte', __FILE__).self::serviceSuffix($service_count['contact']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->setConfiguration('category', 'contact');
					$cmd->setGeneric_type('OPENING');
					$cmd->save();
				}

				if ($service['rtype'] == 'tamper') {
					$service_count['tamper']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Trafiqué', __FILE__).self::serviceSuffix($service_count['tamper']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->setConfiguration('category', 'tamper');
					$cmd->save();
				}
				if ($service['rtype'] == 'light_level') {
					$service_count['light_level']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Luminosité', __FILE__).self::serviceSuffix($service_count['light_level']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('numeric');
					$cmd->setConfiguration('category', 'light_level');
					$cmd->setGeneric_type('BRIGHTNESS');
					$cmd->save();
				}
				if ($service['rtype'] == 'temperature') {
					$service_count['temperature']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Température', __FILE__).self::serviceSuffix($service_count['temperature']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('numeric');
					$cmd->setUnite('°C');
					$cmd->setConfiguration('category', 'temperature');
					$cmd->setGeneric_type('TEMPERATURE');
					$cmd->save();

					$cmd = $eqLogic->getCmd('info', 'enabled');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Etat', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('enabled');
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->save();

					$cmd = $eqLogic->getCmd('action', 'enable');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Activer', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('enable');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->save();

					$cmd = $eqLogic->getCmd('action', 'disable');
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Désactiver', __FILE__));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('disable');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->save();
				}
				if ($service['rtype'] == 'relative_rotary') {
					$service_count['relative_rotary']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Rotation', __FILE__).self::serviceSuffix($service_count['relative_rotary']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(1);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('numeric');
					$cmd->setConfiguration('category', 'relative_rotary');
					$cmd->save();
				}
				if ($service['rtype'] == 'zigbee_connectivity') {
					$service_count['zigbee_connectivity']++;
					$cmd = $eqLogic->getCmd('info', $service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Connecté', __FILE__).self::serviceSuffix($service_count['zigbee_connectivity']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId($service['rid']);
					}
					$cmd->setType('info');
					$cmd->setSubtype('string');
					$cmd->setConfiguration('category', 'zigbee_connectivity');
					$cmd->save();
				}
				if ($service['rtype'] == 'light') {
					$service_count['light']++;
					$light = $hue->light($service['rid']);
					$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('state','light',$service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Etat', __FILE__).self::serviceSuffix($service_count['light']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setGeneric_type('LIGHT_STATE');
						$cmd->setLogicalId('state');
					}
					$cmd->setType('info');
					$cmd->setSubtype('binary');
					$cmd->setConfiguration('service_light',$service['rid']);
					$cmd->save();
					$cmd_state_id = $cmd->getId();

					$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('on','light',$service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('On', __FILE__).self::serviceSuffix($service_count['light']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('on');
						$cmd->setGeneric_type('LIGHT_ON');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->setConfiguration('service_light',$service['rid']);
					$cmd->setValue($cmd_state_id);
					$cmd->save();

					$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('off','light',$service['rid']);
					if (!is_object($cmd)) {
						$cmd = new philipsHueCmd();
						$cmd->setName(__('Off', __FILE__).self::serviceSuffix($service_count['light']));
						$cmd->setEqLogic_id($eqLogic->getId());
						$cmd->setIsVisible(0);
						$cmd->setLogicalId('off');
						$cmd->setGeneric_type('LIGHT_OFF');
					}
					$cmd->setType('action');
					$cmd->setSubtype('other');
					$cmd->setConfiguration('service_light',$service['rid']);
					$cmd->setValue($cmd_state_id);
					$cmd->save();

					if (isset($light['data'][0]['dimming'])) {
						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('luminosity_state','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Etat Luminosité ', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(0);
							$cmd->setLogicalId('luminosity_state');
							$cmd->setGeneric_type('LIGHT_STATE');
						}
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',100);
						$cmd->setType('info');
						$cmd->setSubtype('numeric');
						$cmd->setUnite('%');
						$cmd->setConfiguration('historizeRound',0);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
						$cmd_luminosity_state_id = $cmd->getId();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('luminosity','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Luminosité', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('luminosity');
							$cmd->setGeneric_type('LIGHT_SLIDER');
							$cmd->setTemplate('dashboard','light');
							$cmd->setTemplate('mobile','light');
						}
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',100);
						$cmd->setType('action');
						$cmd->setSubtype('slider');
						$cmd->setValue($cmd_luminosity_state_id);
						$cmd->setUnite('%');
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('transition_state','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Transition status', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(0);
							$cmd->setLogicalId('transition_state');
						}
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',1800);
						$cmd->setType('info');
						$cmd->setSubtype('numeric');
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
						$cmd_transistion_state_id = $cmd->getId();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('transition','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Transition', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(0);
							$cmd->setLogicalId('transition');
							$cmd->setGeneric_type('LIGHT_SLIDER');
						}
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',1800);
						$cmd->setType('action');
						$cmd->setSubtype('slider');
						$cmd->setValue($cmd_transistion_state_id);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
					}

					if (isset($light['data'][0]['color'])) {
						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('color_state','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Etat Couleur', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('color_state');
							$cmd->setGeneric_type('LIGHT_COLOR');
						}
						$cmd->setType('info');
						$cmd->setSubtype('string');
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
						$cmd_color_state_id = $cmd->getId();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('color','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Couleur', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('color');
							$cmd->setGeneric_type('LIGHT_SET_COLOR');
						}
						$cmd->setType('action');
						$cmd->setSubtype('color');
						$cmd->setValue($cmd_color_state_id);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
					}

					if (isset($light['data'][0]['color_temperature'])) {
						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('color_temp_state','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Etat Couleur temp', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(0);
							$cmd->setLogicalId('color_temp_state');
						}
						$cmd->setConfiguration('minValue',$light['data'][0]['color_temperature']['mirek_schema']['mirek_minimum']);
						$cmd->setConfiguration('maxValue',$light['data'][0]['color_temperature']['mirek_schema']['mirek_maximum']);
						$cmd->setType('info');
						$cmd->setSubtype('numeric');
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
						$cmd_temp_state_id = $cmd->getId();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('color_temp','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Couleur temp', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('color_temp');
						}
						$cmd->setConfiguration('minValue',$light['data'][0]['color_temperature']['mirek_schema']['mirek_minimum']);
						$cmd->setConfiguration('maxValue',$light['data'][0]['color_temperature']['mirek_schema']['mirek_maximum']);
						$cmd->setType('action');
						$cmd->setSubtype('slider');
						$cmd->setValue($cmd_temp_state_id);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
					}
					
					if (isset($light['data'][0]['effects']['effect_values'])) {
						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('effect_status','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Effet état', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(0);
							$cmd->setLogicalId('effect_status');
						}
						$cmd->setType('info');
						$cmd->setSubtype('string');
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
						$effect_status_id = $cmd->getId();

						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('effect','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Effet ', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('effect');
						}
						$cmd->setType('action');
						$cmd->setSubtype('select');
						$select = '';
						foreach ($light['data'][0]['effects']['effect_values'] as $effect) {
							$select .= $effect . '|' . $effect . ';';
						}
						$select = trim($select, ';');
						$cmd->setConfiguration('listValue', $select);
						$cmd->setValue($effect_status_id);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
					}

					if (isset($light['data'][0]['alert']['action_values'])) {
						$cmd = $eqLogic->getCmdByLogicalIdAndServiceId('alert','light',$service['rid']);
						if (!is_object($cmd)) {
							$cmd = new philipsHueCmd();
							$cmd->setName(__('Alerte', __FILE__).self::serviceSuffix($service_count['light']));
							$cmd->setEqLogic_id($eqLogic->getId());
							$cmd->setIsVisible(1);
							$cmd->setLogicalId('alert');
						}
						$cmd->setType('action');
						$cmd->setSubtype('select');
						$select = '';
						foreach ($light['data'][0]['alert']['action_values'] as $alert) {
							$select .= $alert . '|' . $alert . ';';
						}
						$select = trim($select, ';');
						$cmd->setConfiguration('listValue', $select);
						$cmd->setConfiguration('service_light',$service['rid']);
						$cmd->save();
					}
				}
			}
		}
		$rooms = $hue->room();
		foreach ($rooms['data'] as $room) {
			log::add('philipsHue', 'debug', 'Found room ' . $room['id'] . ' => ' . json_encode($room));
			if(!isset($room['metadata']) || $room['metadata']['name'] == ''){
				continue;
			}
			$eqLogic = self::byLogicalId($room['id'], 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($room['id']);
				$eqLogic->setName($room['metadata']['name']);
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
				$object = jeeObject::byName($room['metadata']['name']);
				if (is_object($object)) {
					$eqLogic->setObject_id($object->getId());
				}
			}
			foreach ($room['services'] as $service) {
				$eqLogic->setConfiguration('service_' . $service['rtype'], $service['rid']);
			}
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('device', 'ROOM');
			$eqLogic->setConfiguration('category', 'room');
			$eqLogic->setConfiguration('id', $room['id']);
			try {
				$eqLogic->save();
			} catch (Exception $e) {
				$eqLogic->setName($eqLogic->getName().' '.config::genKey(4));
				$eqLogic->save();
			}
			$eqLogic->createDefaultCmd();
		}

		$zones = $hue->zone();
		foreach ($zones['data'] as $zone) {
			log::add('philipsHue', 'debug', 'Found zone ' . $zone['id'] . ' => ' . json_encode($zone));
			if(!isset($zone['metadata']) || $zone['metadata']['name'] == ''){
				continue;
			}
			$eqLogic = self::byLogicalId($zone['id'], 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($zone['id']);
				$eqLogic->setName($zone['metadata']['name']);
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
			}
			foreach ($zone['services'] as $service) {
				$eqLogic->setConfiguration('service_' . $service['rtype'], $service['rid']);
			}
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('device', 'ZONE');
			$eqLogic->setConfiguration('category', 'zone');
			$eqLogic->setConfiguration('id', $zone['id']);
			try {
				$eqLogic->save();
			} catch (Exception $e) {
				$eqLogic->setName($eqLogic->getName().' '.config::genKey(4));
				$eqLogic->save();
			}
			$eqLogic->createDefaultCmd();
		}

		$grouped_lights = $hue->grouped_light();
		foreach ($grouped_lights['data'] as $grouped_light) {
			log::add('philipsHue', 'debug', 'Found group light ' . $grouped_light['id'] . ' => ' . json_encode($grouped_light));
			if(!isset($grouped_light['metadata']) || $grouped_light['metadata']['name'] == ''){
				continue;
			}
			$eqLogic = self::byLogicalId($grouped_light['id'], 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($grouped_light['id']);
				$eqLogic->setName($grouped_light['metadata']['name']);
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
			}
			foreach ($grouped_light['services'] as $service) {
				$eqLogic->setConfiguration('service_' . $service['rtype'], $service['rid']);
			}
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('device', 'GROUPED_LIGHT');
			$eqLogic->setConfiguration('category', 'grouped_light');
			$eqLogic->setConfiguration('id', $grouped_light['id']);
			try {
				$eqLogic->save();
			} catch (Exception $e) {
				$eqLogic->setName($eqLogic->getName().' '.config::genKey(4));
				$eqLogic->save();
			}
			$eqLogic->createDefaultCmd();
		}

		$scenes = $hue->scene();
		foreach ($scenes['data'] as $scene) {
			$eqLogic = self::byLogicalId($scene['group']['rid'], 'philipsHue');
			if (!is_object($eqLogic)) {
				continue;
			}
			$cmd = $eqLogic->getCmd('action', $scene['id']);
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setName(__('Scene ', __FILE__) . $scene['metadata']['name']);
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setIsVisible(1);
				$cmd->setLogicalId($scene['id']);
			}
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setConfiguration('category', 'scene');
			try {
				$cmd->save();
			} catch (Exception $e) {
				$cmd->setName($cmd->getName().' - '.$scene['id']);
				$cmd->save();
			}
		}
      	
      	$scenes = $hue->smart_scene();
		foreach ($scenes['data'] as $scene) {
			$eqLogic = self::byLogicalId($scene['group']['rid'], 'philipsHue');
			if (!is_object($eqLogic)) {
				continue;
			}
			$cmd = $eqLogic->getCmd('action', $scene['id']);
			if (!is_object($cmd)) {
				$cmd = new philipsHueCmd();
				$cmd->setName(__('Smart Scene ', __FILE__) . $scene['metadata']['name']);
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setIsVisible(1);
				$cmd->setLogicalId($scene['id']);
			}
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setConfiguration('category', 'smart_scene');
			$cmd->save();
			try {
				$cmd->save();
			} catch (Exception $e) {
				$cmd->setName($cmd->getName().' - '.$scene['id']);
				$cmd->save();
			}
		}
		philipsHue::syncState($_bridge_number);
	}

	public static function cron15() {
		for ($i = 1; $i <= config::byKey('nbBridge', 'philipsHue'); $i++) {
			if (config::byKey('bridge_ip' . $i, 'philipsHue') == '') {
				continue;
			}
			$hue = self::getPhilipsHue($i);
			$zigbee_connectivities = $hue->zigbee_connectivity();
			if(isset($zigbee_connectivities['data']) && is_array($zigbee_connectivities['data']) && count($zigbee_connectivities['data']) > 0){
				foreach ($zigbee_connectivities['data'] as $zigbee_connectivity) {
					$eqLogic = self::byLogicalId($zigbee_connectivity['owner']['rid'], 'philipsHue');
					if (!is_object($eqLogic)) {
						continue;
					}
					$eqLogic->checkAndUpdateCmd($zigbee_connectivity['id'], $zigbee_connectivity['status']);
				}
			}
			$devices_power = $hue->device_power();
			if(isset($devices_power['data']) && is_array($devices_power['data']) && count($devices_power['data']) > 0){
				foreach ($devices_power['data'] as $device_power) {
					$eqLogic = self::byLogicalId($device_power['owner']['rid'], 'philipsHue');
					if (!is_object($eqLogic)) {
						continue;
					}
					$eqLogic->batteryStatus($device_power['power_state']['battery_level']);
				}
			}
		}
	}

	public static function syncState($_bridge_number = 1, $_datas = null) {
		if ($_datas == null) {
			$hue = self::getPhilipsHue($_bridge_number);
			$_datas = @array_merge_recursive($hue->light(), $hue->motion(), $hue->temperature());
		}
		log::add('philipsHue', 'debug', 'Received message for bridge : ' . $_bridge_number . ' => ' . json_encode($_datas));
		$states = array();
		foreach ($_datas['data'] as $data) {
			if (isset($data['type']) && ($data['type'] == 'scene' || $data['type'] == 'smart_scene') && isset($data['status']['active']) && ($data['status']['active'] == 'static' || $data['status']['active'] == 'dynamic_palette')) {				$cmd = cmd::byLogicalId($data['id'], 'action');
				if(is_object($cmd[0])) {
					$eqLogic = $cmd[0]->getEqLogic();
					if(is_object($eqLogic) && $eqLogic->getEqType_name() == 'philipsHue') {
						$eqLogic->checkAndUpdateCmd('current_scene', $data['id']);
					}
				}
			} 
			if (!isset($data['owner']['rid'])) {
				continue;
			}
			$eqLogic = self::byLogicalId($data['owner']['rid'], 'philipsHue');
          		if (!is_object($eqLogic) || $eqLogic->getIsEnable() == 0) {
				continue;
			}
          	
			$to_cache = array();
			if (isset($data['enabled'])) {
				$eqLogic->checkAndUpdateCmd('enabled', $data['enabled']);
			}
			if (isset($data['motion'])) {
				$eqLogic->checkAndUpdateCmd($data['id'], $data['motion']['motion']);
			}
			if (isset($data['light'])) {
				$eqLogic->checkAndUpdateCmd($data['id'], $data['light']['light_level']);
			}
			if (isset($data['temperature'])) {
				$eqLogic->checkAndUpdateCmd($data['id'], $data['temperature']['temperature']);
			}
			if (isset($data['button'])) {
				$eqLogic->checkAndUpdateCmd($data['id'], $data['button']['last_event']);
			}
			if (isset($data['contact_report'])) {
				$eqLogic->checkAndUpdateCmd($data['id'],  ($data['contact_report']['state'] == 'contact'));
			}
			if (isset($data['tamper_reports'])) {
				$eqLogic->checkAndUpdateCmd($data['id'], ($data['tamper_reports']['state'] == 'tampered'));
			}
			if (isset($data['relative_rotary'])) {
				$direction = ($data['relative_rotary']['last_event']['rotation']['direction'] == 'counter_clock_wise') ? 1 : - 1;
				$eqLogic->checkAndUpdateCmd($data['id'], $direction * $data['relative_rotary']['last_event']['rotation']['steps']);
			}
			if (isset($data['on']['on'])) {
				$states[$data['owner']['rid']] = $data['on']['on'];
				$eqLogic->checkAndUpdateCmd($eqLogic->getCmdByLogicalIdAndServiceId('state','light',$data['id']), $data['on']['on']);
				if (!isset($data['dimming'])) {
					$data['dimming'] = array();
				}
				if (!$data['on']['on']) {
					$data['dimming']['brightness'] = 0;
				} elseif (!isset($data['dimming']['brightness'])) {
					$data['dimming']['brightness'] = $eqLogic->getCache('previous_luminosity');
				}
			}
			if (isset($data['dimming']['brightness'])) {
				if ($data['dimming']['brightness'] < 1) {
					$data['dimming']['brightness'] = 0;
				}
				if (isset($states[$data['owner']['rid']]) && !$states[$data['owner']['rid']]) {
					$data['dimming']['brightness'] = 0;
				}
				$eqLogic->checkAndUpdateCmd($eqLogic->getCmdByLogicalIdAndServiceId('luminosity_state','light',$data['id']), $data['dimming']['brightness']);
				if ($data['dimming']['brightness'] != 0) {
					$to_cache['previous_luminosity'] = $data['dimming']['brightness'];
				}
				if (!isset($data['color']['xy'])) {
					$data['color']['xy'] = array('x' => $eqLogic->getCache('previous_color_x'), 'y' => $eqLogic->getCache('previous_color_y'));
				}
			}
			if (isset($data['color_temperature']['mirek'])) {
				$eqLogic->checkAndUpdateCmd($eqLogic->getCmdByLogicalIdAndServiceId('color_temp_state','light',$data['id']), $data['color_temperature']['mirek']);
			}
			if (isset($data['color']['xy']) && $data['color']['xy']['x'] !== '' && $data['color']['xy']['y'] !== '') {
				if (!isset($data['dimming']['brightness'])) {
					$data['dimming']['brightness'] = $eqLogic->getCache('previous_luminosity');
				}
				if ($data['dimming']['brightness'] != 0) {
					$to_cache['previous_color_x'] = $data['color']['xy']['x'];
					$to_cache['previous_color_y'] = $data['color']['xy']['y'];
					$rgb = pHueApi::convertXYToRGB($data['color']['xy']['x'], $data['color']['xy']['y'], $data['dimming']['brightness'] * 2.55);
					$eqLogic->checkAndUpdateCmd($eqLogic->getCmdByLogicalIdAndServiceId('color_state','light',$data['id']), '#' . sprintf('%02x', $rgb['red']) . sprintf('%02x', $rgb['green']) . sprintf('%02x', $rgb['blue']));
				}
			}
			if (isset($data['effects']['status'])) {
				$eqLogic->checkAndUpdateCmd($eqLogic->getCmdByLogicalIdAndServiceId('effect_status','light',$data['id']), $data['effects']['status']);
			}
			if (count($to_cache) > 0) {
				$eqLogic->setCache($to_cache);
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

	public function getImgFilePath() {
		if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $this->getConfiguration('device') . '.png')) {
			return $this->getConfiguration('device') . '.png';
		}
		return false;
	}

	public function getImage() {
		if(method_exists($this,'getCustomImage')){
			$customImage = $this->getCustomImage();
				if($customImage !== null){
						return $customImage;
				}
	        }
		$imgpath = $this->getImgFilePath();
		if ($imgpath === false) {
			return 'plugins/philipsHue/plugin_info/philipsHue_icon.png';
		}
		return 'plugins/philipsHue/core/config/devices/' . $imgpath;
	}

	public function getCmdByLogicalIdAndServiceId($_logical_id,$_service_type,$_service_id){
		$cmds = $this->getCmd(null,$_logical_id,null,true);
		if(count($cmds) == 0){
			return null;
		}
		if(count($cmds) == 1){
			return $cmds[0];
		}
		foreach ($cmds as $cmd) {
			if($cmd->getConfiguration('service_'.$_service_type) == $_service_id){
				return $cmd;
			}
		}
		return $cmds[0];
	}

	public function createDefaultCmd(){
		$cmd = $this->getCmd('info', 'state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Etat', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('state');
			$cmd->setGeneric_type('LIGHT_STATE');
		}
		$cmd->setType('info');
		$cmd->setSubtype('binary');
		$cmd->save();
		$cmd_state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'on');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('On', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('on');
			$cmd->setGeneric_type('LIGHT_ON');
		}
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setValue($cmd_state_id);
		$cmd->save();

		$cmd = $this->getCmd('action', 'off');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Off', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('off');
			$cmd->setGeneric_type('LIGHT_OFF');
		}
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setValue($cmd_state_id);
		$cmd->save();

		$cmd = $this->getCmd('info', 'luminosity_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Etat Luminosité', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('luminosity_state');
			$cmd->setGeneric_type('LIGHT_STATE');
		}
		$cmd->setConfiguration('minValue',0);
		$cmd->setConfiguration('maxValue',100);
		$cmd->setType('info');
		$cmd->setSubtype('numeric');
		$cmd->setUnite('%');
		$cmd->setConfiguration('historizeRound',0);
		$cmd->save();
		$cmd_luminosity_state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'luminosity');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Luminosité', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->setLogicalId('luminosity');
			$cmd->setGeneric_type('LIGHT_SLIDER');
			$cmd->setTemplate('dashboard','light');
			$cmd->setTemplate('mobile','light');
		}
		$cmd->setConfiguration('minValue',0);
		$cmd->setConfiguration('maxValue',100);
		$cmd->setType('action');
		$cmd->setSubtype('slider');
		$cmd->setValue($cmd_luminosity_state_id);
		$cmd->setUnite('%');
		$cmd->save();

		$cmd = $this->getCmd('info', 'transition_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Transition status', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('transition_state');
		}
		$cmd->setConfiguration('minValue',0);
		$cmd->setConfiguration('maxValue',1800);
		$cmd->setType('info');
		$cmd->setSubtype('numeric');
		$cmd->save();
		$cmd_transistion_state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'transition');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Transition', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('transition');
			$cmd->setGeneric_type('LIGHT_SLIDER');
		}
		$cmd->setConfiguration('minValue',0);
		$cmd->setConfiguration('maxValue',1800);
		$cmd->setType('action');
		$cmd->setSubtype('slider');
		$cmd->setValue($cmd_transistion_state_id);
		$cmd->save();

		$cmd = $this->getCmd('info', 'color_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Etat Couleur', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->setLogicalId('color_state');
			$cmd->setGeneric_type('LIGHT_COLOR');
		}
		$cmd->setType('info');
		$cmd->setSubtype('string');
		$cmd->save();
		$cmd_color_state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'color');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Couleur', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->setLogicalId('color');
			$cmd->setGeneric_type('LIGHT_SET_COLOR');
		}
		$cmd->setType('action');
		$cmd->setSubtype('color');
		$cmd->setValue($cmd_color_state_id);
		$cmd->save();

		$cmd = $this->getCmd('info', 'color_temp_state');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Etat Couleur temp', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->setLogicalId('color_temp_state');
		}
		$cmd->setConfiguration('minValue',153);
		$cmd->setConfiguration('maxValue',500);
		$cmd->setType('info');
		$cmd->setSubtype('numeric');
		$cmd->save();
		$cmd_temp_state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'color_temp');
		if (!is_object($cmd)) {
			$cmd = new philipsHueCmd();
			$cmd->setName(__('Couleur temp', __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->setLogicalId('color_temp');
		}
		$cmd->setConfiguration('minValue',153);
		$cmd->setConfiguration('maxValue',500);
		$cmd->setType('action');
		$cmd->setSubtype('slider');
		$cmd->setValue($cmd_temp_state_id);
		$cmd->save();
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
		if ($this->getLogicalId() == 'transition') {
			$transition = $eqLogic->getCmd(null, 'transition_state');
			if (is_object($transition)) {
				$transition->event($_options['slider']);
			}
			return;
		}
		if ($this->getLogicalId() == 'refresh') {
			philipsHue::syncState($eqLogic->getConfiguration('bridge'));
			return;
		}
		$hue = philipsHue::getPhilipsHue($eqLogic->getConfiguration('bridge'));
		if (in_array($this->getLogicalId(), array('enable', 'disable'))) {
			if ($eqLogic->getConfiguration('service_motion') != '') {
				$data = array('enabled' => ($this->getLogicalId() == 'enable'));
				log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $eqLogic->getConfiguration('service_motion') . ' => ' . json_encode($data));
				$result = $hue->motion($eqLogic->getConfiguration('service_motion'), $data);
				usleep(100000);
				if (isset($result['errors']) && count($result['errors']) > 0) {
					throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
				}
			}
			if ($eqLogic->getConfiguration('service_temperature') != '') {
				$data = array('enabled' => ($this->getLogicalId() == 'enable'));
				log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $eqLogic->getConfiguration('service_temperature') . ' => ' . json_encode($data));
				$result = $hue->temperature($eqLogic->getConfiguration('service_temperature'), $data);
				usleep(100000);
				if (isset($result['errors']) && count($result['errors']) > 0) {
					throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
				}
			}
			return;
		}
		$transistion_time = null;
		if (isset($_options['transition'])) {
			$transistion_time = (int) $_options['transition'] * 1000;
		} else {
			$transition = $eqLogic->getCmd(null, 'transition_state');
			if (is_object($transition)) {
				$transistion_time = $transition->execCmd(null, 2);
				$transistion_time = (float) $transistion_time * 1000;
			}
		}

		if ($this->getConfiguration('category') == 'scene') {
			$data = array(
				'recall' => array('action' => 'dynamic_palette')
			);
			if($transistion_time !== null){
				$data['recall']['duration'] = $transistion_time;
			}
			/*$speed = $eqLogic->getCmd(null, 'speed_state');
			if (is_object($speed)) {
				$data['speed'] = $speed->execCmd(null, 2);
			}*/
			$transistion_time = ($transistion_time == 0) ? 0 : $transistion_time * 1000;
			log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $this->getLogicalId() . ' => ' . json_encode($data));
			$result = $hue->scene($this->getLogicalId(), $data);
			usleep(100000);
			if (isset($result['errors']) && count($result['errors']) > 0) {
				throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
			}
			return;
		}
      
      	if ($this->getConfiguration('category') == 'smart_scene') {
			$data = array(
				'recall' => array('action' => 'activate')
			);
			log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $this->getLogicalId() . ' => ' . json_encode($data));
			$result = $hue->smart_scene($this->getLogicalId(), $data);
			usleep(100000);
			if (isset($result['errors']) && count($result['errors']) > 0) {
				throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
			}
			return;
		}
		

		$data = array();
		if($transistion_time !== null && $transistion_time > 0){
			$data['dynamics'] = array('duration' => $transistion_time);
		}
		if ($this->getLogicalId() != 'off') {
			$data['on'] = array('on' => true);
		} else {
			$data['on'] = array('on' => false);
		}

		switch ($this->getLogicalId()) {
			case 'luminosity':
				if ($_options['slider'] == 0) {
					$data['on'] = array('on' => false);
				} else {
					$data['dimming'] = array('brightness' => (int) $_options['slider']);
				}
				break;
			case 'color_temp':
				$data['color_temperature'] = array('mirek' => (int) $_options['slider']);
				break;
			case 'color':
				if ($_options['color'] == '#000000') {
					$data['on'] = array('on' => false);
				} else {
					list($r, $g, $b) = str_split(str_replace('#', '', $_options['color']), 2);
					$xyb = pHueApi::convertRGBToXY(hexdec($r), hexdec($g), hexdec($b));
					$data['color'] = array('xy' => array('x' => $xyb['x'], 'y' => $xyb['y']));
					//$data['dimming'] = array('brightness' => $xyb['bri']);
				}
				break;
			case 'effect':
				$data['effects'] = array('effect' => $_options['select']);
				break;
			case 'alert':
				$data['alert'] = array('action' => $_options['select']);
				break;
			case 'on':
				if($eqLogic->getCache('previous_luminosity',null) != null){
					$data['dimming'] = array('brightness' => (int) $eqLogic->getCache('previous_luminosity'));
				}
				break;
		}
		if (isset($data['dimming']['brightness']) && $data['dimming']['brightness'] > 100) {
			$data['dimming']['brightness'] = 100;
		}
		if ($eqLogic->getConfiguration('category') == 'room' || $eqLogic->getConfiguration('category') == 'grouped_light' || $eqLogic->getConfiguration('category') == 'zone') {
			log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $eqLogic->getConfiguration('service_grouped_light') . ' => ' . json_encode($data));
			try {
				$hue->grouped_light($eqLogic->getConfiguration('service_grouped_light'), $data);
			} catch (\Throwable $th) {
				try {
					usleep(500000);
					$hue->grouped_light($eqLogic->getConfiguration('service_grouped_light'), $data);
				} catch (\Throwable $th) {
					sleep(3);
					$hue->grouped_light($eqLogic->getConfiguration('service_grouped_light'), $data);
				}
			}
		}else{
			$light_service = $this->getConfiguration('service_light',$eqLogic->getConfiguration('service_light'));
            log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $light_service . ' => ' . json_encode($data));
			try {
				$hue->light($light_service, $data);
			} catch (\Throwable $th) {
				try {
					usleep(500000);
					$hue->light($light_service, $data);
				} catch (\Throwable $th) {
					sleep(3);
					$hue->light($light_service, $data);
				}
			} 
        }
		usleep(100000);
	}

	/*     * **********************Getteur Setteur*************************** */
}
