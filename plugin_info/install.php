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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function philipsHue_install() {
}

/**
 * Migration of generic types (PR #64) for existing eq.
 * Only change default values, not changed done by the user.
 */
function philipsHue_migrateGenericTypes() {
	foreach (eqLogic::byType('philipsHue') as $eqLogic) {
		// logicalId => [old expected type (null = empty), new type (null = delete)]
		$migrations = array(
			'luminosity_state' => array('LIGHT_STATE',  'LIGHT_BRIGHTNESS'),
			'transition'       => array('LIGHT_SLIDER', null),
			'color_temp_state' => array(null,           'LIGHT_COLOR_TEMP'),
			'color_temp'       => array(null,           'LIGHT_SET_COLOR_TEMP'),
		);
		foreach ($migrations as $logicalId => $types) {
			$cmd = $eqLogic->getCmd(null, $logicalId);
			if (!is_object($cmd)) {
				continue;
			}
			$current = $cmd->getGeneric_type();
			$old = $types[0];
			$new = $types[1];
			// only modify if the generic type is still the default value from the plugin
			if (($old === null && $current !== null && $current !== '') || ($old !== null && $current !== $old)) {
				continue;
			}
			$cmd->setGeneric_type($new);
			$cmd->save();
			log::add('philipsHue', 'info', 'Migration type générique ' . $logicalId . ' (' . $eqLogic->getHumanName() . ') : ' . ($current ?: 'vide') . ' => ' . ($new ?: 'vide'));
		}
	}
}

function philipsHue_update() {
	$cron = cron::byClassAndFunction('philipsHue', 'pull');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
	foreach (eqLogic::byType('philipsHue') as $eqLogic) {
		foreach (array('rainbow_on', 'rainbow_off', 'alert_off', 'alert_on', 'animation', 'isReachable') as $cid) {
			$cmd = $eqLogic->getCmd(null, $cid);
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
	}
	philipsHue_migrateGenericTypes();
}

function philipsHue_remove() {
}
