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

if (php_sapi_name() != 'cli' || isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc'])) {
	header("Status: 404 Not Found");
	header('HTTP/1.0 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not Found</h1>";
	echo "The page that you have requested could not be found.";
	exit();
}
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
if (isset($argv)) {
	foreach ($argv as $arg) {
		$argList = explode('=', $arg);
		if (isset($argList[0]) && isset($argList[1])) {
			$_GET[$argList[0]] = $argList[1];
		}
	}
}

if (init('id') == '') {
	log::add('philipsHue', 'error', __('[philipsHue/jeeAnimation] L\'id ne peut etre vide', __FILE__));
	die();
}
$philipsHue = philipsHue::byId(init('id'));
if (!is_object($philipsHue)) {
	log::add('philipsHue', 'error', __('[philipsHue/jeeAnimation] L\'équipement est introuvable : ', __FILE__) . init('id'));
	die();
}
if ($philipsHue->getEqType_name() != 'philipsHue') {
	log::add('philipsHue', 'error', __('[philipsHue/jeeAnimation] Cet équipement n\'est pas de type philipsHue : ', __FILE__) . $philipsHue->getEqType_name());
	die();
}
log::add('philipsHue', 'debug', __('[philipsHue/jeeAnimation] Lancement de l\'animation sur', __FILE__) . $philipsHue->getEqType_name() . __(' avec comme options ', __FILE__) . print_r($_GET, true));
try {
	switch (init('animation')) {
		case 'sunset':
			$scenario = array(
				array('hue' => 38375, 'sat' => 0, 'bri' => 254, 'transition' => 0, 'sleep' => 0),
				array('hue' => 15191, 'sat' => 0, 'bri' => 200, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 13390, 'sat' => 0, 'bri' => 150, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 9980, 'sat' => 0, 'bri' => 100, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 9977, 'sat' => 0, 'bri' => 50, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 9977, 'sat' => 0, 'bri' => 1, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
			);
			break;
		case 'sunrise':
			$scenario = array(
				array('hue' => 9977, 'sat' => 0, 'bri' => 1, 'transition' => 0, 'sleep' => 0),
				array('hue' => 9977, 'sat' => 0, 'bri' => 50, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 9980, 'sat' => 0, 'bri' => 100, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 13390, 'sat' => 0, 'bri' => 150, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 15191, 'sat' => 0, 'bri' => 200, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
				array('hue' => 38375, 'sat' => 0, 'bri' => 254, 'transition' => intval(init('duration', 720) * 0.2), 'sleep' => intval(init('duration', 720) * 0.2)),
			);
			break;
		default:
			throw new Exception(__('Aucune animation correspondante', __FILE__));
	}
	if (!isset($scenario) || !is_array($scenario) || count($scenario) == 0) {
		throw new Exception(__('Aucune action à faire', __FILE__));
	}
	$hue = philipsHue::getPhilipsHue();
	foreach ($scenario as $action) {
		log::add('philipsHue', 'debug', __('Lancement de ', __FILE__) . print_r($action, true));
		$command = new \Phue\Command\SetLightState($philipsHue->getConfiguration('id'));
		$command->transitionTime($action['transition']);
		$command->on(true);
		$command->brightness($action['bri']);
		$command->hue($action['hue']);
		$command->saturation($action['sat']);
		$hue->sendCommand($command);
		sleep($action['sleep']);
	}
	log::add('philipsHue', 'debug', __('Fin de l\'animation', __FILE__));
} catch (Exception $e) {
	log::add('philipsHue', 'error', '[philipsHue/jeeAnimation] ' . $e->getMessage());
}
