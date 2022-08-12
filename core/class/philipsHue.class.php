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
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

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
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
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
			self::$_hue[$_bridge_number] = new pHueApi(config::byKey('bridge_ip' . $_bridge_number, 'philipsHue'), config::byKey('bridge_username' . $_bridge_number, 'philipsHue', 'newdeveloper'));
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

	public static function syncBridge($_bridge_number = 1) {
		$hue = self::getPhilipsHue($_bridge_number);
		$devices = $hue->device();
		if (isset($devices['errors']) && count($devices['errors']) > 0) {
			self::createUser($_bridge_number);
			self::$_hue = null;
			$hue = self::getPhilipsHue($_bridge_number);
			$devices = $hue->device();
		}
		if (isset($devices['errors']) && count($devices['errors']) > 0) {
			throw new Exception(__('Erreur lors de la requetes sur le pont hue :', __FILE__) . ' ' . json_encode($devices['errors']));
		}
		foreach ($devices['data'] as $device) {
			$type = $device['services'][0]['rtype'];
			$modelId = $device['product_data']['model_id'];
			log::add('philipsHue', 'debug', 'Found device type ' . $type . ' model : ' . $modelId . ' => ' . json_encode($device));
			if (count(self::devicesParameters($modelId)) == 0) {
				log::add('philipsHue', 'warning', 'No configuration found for device : ' . $modelId . ' => ' . json_encode($device));
				$modelId = 'default_color';
				log::add('philipsHue', 'warning', 'Use generic configuration : ' . $modelId);
			}
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
				$eqLogic->setConfiguration('device', $modelId);
			}
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('category', $type);
			$eqLogic->setConfiguration('id', $id);
			$eqLogic->setConfiguration('modelName', $device['product_data']['product_name']);
			$eqLogic->setConfiguration('type', $type);
			$eqLogic->setConfiguration('softwareVersion', $device['product_data']['software_version']);
			foreach ($device['services'] as $service) {
				$eqLogic->setConfiguration('service_' . $service['rtype'], $service['rid']);
			}
			$eqLogic->save();
		}

		$groups = $hue->grouped_light();
		foreach ($groups['data'] as $group) {
			log::add('philipsHue', 'debug', 'Found group ' . $group['id'] . ' => ' . json_encode($group));
			$eqLogic = self::byLogicalId($group['id'], 'philipsHue');
			if (!is_object($eqLogic)) {
				$eqLogic = self::byLogicalId('group' . str_replace(array('/groups/'), '', $group['id_v1']) . '-' . $_bridge_number, 'philipsHue');
				if (is_object($eqLogic)) {
					$eqLogic->setLogicalId($group['id']);
					$eqLogic->save();
				}
			}
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($group['id']);
				$eqLogic->setName($group['id']);
				$eqLogic->setEqType_name('philipsHue');
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setConfiguration('bridge', $_bridge_number);
			$eqLogic->setConfiguration('device', 'GROUP');
			$eqLogic->setConfiguration('category', 'group');
			$eqLogic->setConfiguration('id', $group['id']);
			$eqLogic->save();
		}
		self::deamon_start();
	}

	public static function syncState($_bridge_number = 1, $_data = null) {
		$hue = self::getPhilipsHue($_bridge_number);
		if ($_data == null) {
			$lights = $hue->light();
		} else {
			$lights = $_data;
		}
		foreach ($lights['data'] as $light) {
			$eqLogic = self::byLogicalId($light['owner']['rid'], 'philipsHue');
			if (!is_object($eqLogic) || $eqLogic->getIsEnable() == 0) {
				continue;
			}
			if (isset($light['on']['on'])) {
				$eqLogic->checkAndUpdateCmd('state', $light['on']['on'], false);
				if (!$light['on']['on']) {
					$light['dimming']['brightness'] = 0;
				}
			}
			if (isset($light['dimming']['brightness'])) {
				$eqLogic->checkAndUpdateCmd('luminosity_state', $light['dimming']['brightness'], false);
			}
			if (isset($light['color_temperature']['mirek'])) {
				$eqLogic->checkAndUpdateCmd('color_temp_state', $light['color_temperature']['mirek'], false);
			}
			if (isset($light['color']['xy'])) {
				$rgb = pHueApi::convertXYToRGB($light['color']['xy']['x'], $light['color']['xy']['y'], $light['dimming']['brightness']);
				$color = '#' . sprintf('%02x', $rgb['red']) . sprintf('%02x', $rgb['green']) . sprintf('%02x', $rgb['blue']);
				$eqLogic->checkAndUpdateCmd('color_state', $color, false);
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
				$cmd->setEqLogic_id($this->getId());
				$cmd->setIsVisible(0);
				$cmd->setLogicalId('isReachable');
			}
			$cmd->setConfiguration('repeatEventManagement', 'never');
			$cmd->setType('info');
			$cmd->setSubtype('binary');
			$cmd->save();

			$cmd = $this->getCmd('info', 'alert_state');
			if (is_object($cmd)) {
				$cmd->remove();
			}

			$cmd = $this->getCmd('info', 'rainbow_state');
			if (is_object($cmd)) {
				$cmd->remove();
			}

			$cmd = $this->getCmd('action', 'luminosity');
			if (is_object($cmd)) {
				$cmd->setConfiguration('maxValue', 100);
				$cmd->save();
			}
		}
	}

	public function applyModuleConfiguration() {
		$this->setConfiguration('applyDevice', $this->getConfiguration('device'));
		$this->save(true);
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
		$hue = philipsHue::getPhilipsHue($eqLogic->getConfiguration('bridge'));

		$transition = $eqLogic->getCmd(null, 'transition_state');
		$transistion_time = 0;
		if (is_object($transition)) {
			$transistion_time = $transition->execCmd(null, 2);
			if ($transistion_time !== 0) {
				$transition->event(0);
			}
		}
		$transistion_time = ($transistion_time == 0) ? 0 : $transistion_time * 1000;
		$data = array();

		if ($this->getLogicalId() != 'off') {
			$data['dynamics'] = array('duration' => $transistion_time);
			$data['on'] = array('on' => true);
		} else {
			$data['on'] = array('on' => false);
		}
		if ($this->getLogicalId() != 'animation' && $eqLogic->getCache('current_animate', 0) == 1) {
			$eqLogic->stopAnimation();
		}
		switch ($this->getLogicalId()) {
			case 'animation':
				$eqLogic->animation($_options['title'], $_options['message']);
				return;
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
					$data['dimming'] = array('brightness' => $xyb['bri']);
				}
				break;
		}
		log::add('philipsHue', 'debug', 'Execution of ' . $this->getHumanName() . ' ' . $eqLogic->getConfiguration('service_light') . ' => ' . json_encode($data));
		$result = $hue->light($eqLogic->getConfiguration('service_light'), $data);
		usleep(100000);
		if (isset($result['errors']) && count($result['errors']) > 0) {
			throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
