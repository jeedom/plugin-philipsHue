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
$philipsHue->setCache('current_animate', 1);
log::add('philipsHue', 'debug', __('[philipsHue/jeeAnimation] Lancement de l\'animation sur', __FILE__) . $philipsHue->getEqType_name() . __(' avec comme options ', __FILE__) . print_r($_GET, true));
try {
	switch (init('animation')) {
		case 'sunset':
		$scenario = array(
			array('hue' => 12750, 'sat' => 0, 'bri' => 254, 'transition' => 0, 'sleep' => 0),
			array('hue' => 12750, 'sat' => 254, 'bri' => 254, 'transition' => intval(init('duration', 720) * 0.58), 'sleep' => intval(init('duration', 720) * 0.58)),
			array('hue' => 65280, 'sat' => 254, 'bri' => 128, 'transition' => intval(init('duration', 720) * 0.33), 'sleep' => intval(init('duration', 720) * 0.33)),
			array('hue' => 46920, 'sat' => 254, 'bri' => 0, 'transition' => intval(init('duration', 720) * 0.09), 'sleep' => intval(init('duration', 720) * 0.09)),
		);
		break;
		case 'sunrise':
		$scenario = array(
			array('hue' => 46920, 'sat' => 254, 'bri' => 0, 'transition' => 0, 'sleep' => 0),
			array('hue' => 65280, 'sat' => 254, 'bri' => 128, 'transition' => intval(init('duration', 720) * 0.09), 'sleep' => intval(init('duration', 720) * 0.09)),
			array('hue' => 12750, 'sat' => 254, 'bri' => 254, 'transition' => intval(init('duration', 720) * 0.33), 'sleep' => intval(init('duration', 720) * 0.33)),
			array('hue' => 12750, 'sat' => 0, 'bri' => 254, 'transition' => intval(init('duration', 720) * 0.58), 'sleep' => intval(init('duration', 720) * 0.58)),
		);
		break;
		case 'adaptive_lighting':
		$sun_info = date_sun_info(strtotime('now'), floatval(config::byKey('info::latitude')),floatval(config::byKey('info::longitude')));
		if(strtotime('now') < $sun_info['sunrise']){
			$pourcent_sun = 0;
		}else if(strtotime('now') > $sun_info['sunset']){
			$pourcent_sun = 0;
		}else if(strtotime('now') < $sun_info['transit']){
			$pourcent_sun =  (strtotime('now') - $sun_info['sunrise']) / ($sun_info['transit'] - $sun_info['sunrise']);
			$pourcent_sun = $pourcent_sun;
		}else{
			$pourcent_sun =  ($sun_info['sunset'] - strtotime('now')) / ($sun_info['sunset'] - $sun_info['transit']);
			$pourcent_sun = 1 - $pourcent_sun;
		}
		$max_color_temp = 500;
		$min_color_temp = 153;
		$max_brightness = 254;
		$min_brightness = 1;
		
		if(strtotime('now') > $sun_info['sunset'] || strtotime('now') < $sun_info['sunrise']){
			$scenario = array(
				array('colorTemp' => $min_color_temp,'bri' => $min_brightness, 'transition' => 0, 'sleep' => $sun_info['sunrise'] - strtotime('now'))
			);
		}else{
			$scenario = array(
				array('colorTemp' => intval(($max_color_temp - $min_color_temp)*$pourcent_sun+$min_color_temp), 'bri' =>  intval(($max_brightness - $min_brightness)*$pourcent_sun+$min_brightness), 'transition' => 0, 'sleep' => 0)
			);
		}
		if(strtotime('now') < $sun_info['transit']){
			$scenario[] = array('colorTemp' => $max_color_temp,'bri' => $max_brightness, 'transition' => $sun_info['transit'] - strtotime('now'), 'sleep' => $sun_info['transit'] - strtotime('now'));
		}
		$scenario[] = array('colorTemp' => $min_color_temp,'bri' => $min_brightness, 'transition' => $sun_info['sunset'] - strtotime('now'), 'sleep' => $sun_info['sunset'] - strtotime('now'));
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
		if(isset($action['hue'])){
			$command->hue($action['hue']);
		}
		if(isset($action['sat'])){
			$command->saturation($action['sat']);
		}
		if(isset($action['colorTemp'])){
			$command->colorTemp($action['colorTemp']);
		}
		$hue->sendCommand($command);
		sleep($action['sleep']);
	}
	$philipsHue->setCache('current_animate', 0);
	log::add('philipsHue', 'debug', __('Fin de l\'animation', __FILE__));
} catch (Exception $e) {
	$philipsHue->setCache('current_animate', 0);
	log::add('philipsHue', 'error', '[philipsHue/jeeAnimation] ' . $e->getMessage());
}
