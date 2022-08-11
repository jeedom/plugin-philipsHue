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
				array('x' => 95.05, 'y' => 100, 'bri' => 100, 'transition' => 0, 'sleep' => 0),
				array('x' => 63.06, 'y' => 85.59, 'bri' => 100, 'transition' => intval(init('duration', 720) * 0.58), 'sleep' => intval(init('duration', 720) * 0.58)),
				array('x' => 52.76, 'y' => 38.11, 'bri' => 50, 'transition' => intval(init('duration', 720) * 0.33), 'sleep' => intval(init('duration', 720) * 0.33)),
				array('x' => 0, 'y' => 0, 'bri' => 0, 'transition' => intval(init('duration', 720) * 0.09), 'sleep' => intval(init('duration', 720) * 0.09)),
			);
			break;
		case 'sunrise':
			$scenario = array(
				array('x' => 0, 'y' => 0, 'bri' => 0, 'transition' => 0, 'sleep' => 0),
				array('x' => 52.76, 'y' => 38.11, 'bri' => 50, 'transition' => intval(init('duration', 720) * 0.09), 'sleep' => intval(init('duration', 720) * 0.09)),
				array('x' => 95.05, 'y' => 100, 'bri' => 100, 'transition' => intval(init('duration', 720) * 0.33), 'sleep' => intval(init('duration', 720) * 0.33)),
				array('x' => 95.05, 'y' => 100, 'bri' => 100, 'transition' => intval(init('duration', 720) * 0.58), 'sleep' => intval(init('duration', 720) * 0.58)),
			);
			break;
		case 'adaptive_lighting':
			$sun_info = date_sun_info(strtotime('now'), floatval(config::byKey('info::latitude')), floatval(config::byKey('info::longitude')));
			if (strtotime('now') < $sun_info['sunrise']) {
				$pourcent_sun = 0;
			} else if (strtotime('now') > $sun_info['sunset']) {
				$pourcent_sun = 0;
			} else if (strtotime('now') < $sun_info['transit']) {
				$pourcent_sun =  (strtotime('now') - $sun_info['sunrise']) / ($sun_info['transit'] - $sun_info['sunrise']);
				$pourcent_sun = $pourcent_sun;
			} else {
				$pourcent_sun =  ($sun_info['sunset'] - strtotime('now')) / ($sun_info['sunset'] - $sun_info['transit']);
				$pourcent_sun = $pourcent_sun;
			}
			$max_color_temp = 153;
			$min_color_temp = 500;
			$max_brightness = 100;
			$min_brightness = 1;
			$scenario = array();
			if (strtotime('now') > $sun_info['sunset'] || strtotime('now') < $sun_info['sunrise']) {
				$scenario[] = 	array('colorTemp' => $color_temp, 'bri' => $brightness_temp, 'transition' => 0, 'sleep' => $sun_info['sunrise'] - strtotime('now'));
			} else {
				$scenario[] = array('colorTemp' => intval(($min_color_temp - $max_color_temp) * (1 - $pourcent_sun) + $max_color_temp), 'bri' =>  intval(($max_brightness - $min_brightness) * $pourcent_sun + $min_brightness), 'transition' => 0, 'sleep' => 0);
			}
			if (strtotime('now') < $sun_info['transit']) {
				$prev_temp = end($scenario)['colorTemp'];
				$prev_brightness = end($scenario)['bri'];
				$starttime = strtotime('now');
				while (true) {
					$duration = $sun_info['transit'] - $starttime;
					$color_temp = $max_color_temp;
					$brightness = $max_brightness;
					if ($duration > 6000) {
						$duration = 6000;
						$percent = (($starttime + $duration) - strtotime('now')) / ($sun_info['transit'] - strtotime('now'));
						$color_temp = intval((1 - $percent) * ($prev_temp - $color_temp) + $color_temp);
						$brightness = intval((1 - $percent) * ($prev_brightness - $brightness) + $brightness);
					}
					$scenario[] = array('colorTemp' => $color_temp, 'bri' => $brightness, 'transition' => $duration, 'sleep' => $duration);
					$starttime += 6500;
					if ($starttime >= $sun_info['transit']) {
						break;
					}
				}
			}

			if ($sun_info['sunset'] > strtotime('now')) {
				$prev_temp = end($scenario)['colorTemp'];
				$prev_brightness = end($scenario)['bri'];
				$starttime = strtotime('now');
				while (true) {
					$duration = $sun_info['sunset'] - $starttime;
					$color_temp = $min_color_temp;
					$brightness = $min_brightness;
					if ($duration > 6000) {
						$duration = 6000;
						$percent = (($starttime + $duration) - strtotime('now')) / ($sun_info['sunset'] - strtotime('now'));
						$color_temp = intval((1 - $percent) * ($prev_temp - $color_temp) + $color_temp);
						$brightness = intval((1 - $percent) * ($prev_brightness - $brightness) + $brightness);
					}
					$scenario[] = array('colorTemp' => $color_temp, 'bri' => $brightness, 'transition' => $duration, 'sleep' => $duration);
					$starttime += $duration;
					if ($starttime >= $sun_info['sunset']) {
						break;
					}
				}
			}
			break;
		default:
			throw new Exception(__('Aucune animation correspondante', __FILE__));
	}
	if (!isset($scenario) || !is_array($scenario) || count($scenario) == 0) {
		throw new Exception(__('Aucune action à faire', __FILE__));
	}
	$hue = philipsHue::getPhilipsHue();
	foreach ($scenario as $action) {
		log::add('philipsHue', 'debug', __('Execution de ', __FILE__) . json_encode($action));
		$data = array();
		if ($action['transition'] > 0) {
			$data['dynamics'] = array('duration' => $action['transition'] * 1000);
		}
		$data['on'] = array('on' => true);
		$data['dimming'] = array('brightness' => (int) $action['bri']);
		if (isset($action['colorTemp'])) {
			$data['color_temperature'] = array('mirek' => (int) $action['colorTemp']);
		}
		if (isset($action['x'])) {
			$data['color'] = array('xy' => array('x' => $action['x'], 'y' => $action['y']));
		}
		log::add('philipsHue', 'debug', $philipsHue->getConfiguration('service_light') . ' => ' . json_encode($data));
		$result = $hue->light($philipsHue->getConfiguration('service_light'), $data);
		if (isset($result['errors']) && count($result['errors']) > 0) {
			throw new Exception(__('Erreur d\'éxecution de la commande :', __FILE__) . ' ' . json_encode($result['errors']) . ' => ' . json_encode($data));
		}
		sleep($action['sleep']);
	}
	$philipsHue->setCache('current_animate', 0);
	log::add('philipsHue', 'debug', __('Fin de l\'animation', __FILE__));
} catch (Exception $e) {
	$philipsHue->setCache('current_animate', 0);
	log::add('philipsHue', 'error', '[philipsHue/jeeAnimation] ' . $e->getMessage());
}
