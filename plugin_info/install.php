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
	try {
		philipsHue::getPhilipsHue();
	} catch (Exception $e) {
		
	}
	$cron = cron::byClassAndFunction('philipsHue', 'pull');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('philipsHue');
		$cron->setFunction('pull');
		$cron->setEnable(1);
		$cron->setDeamon(1);
		$cron->setDeamonSleepTime(1);
		$cron->setSchedule('* * * * *');
		$cron->setTimeout(1440);
		$cron->save();
	}
}

function philipsHue_update() {
	$cron = cron::byClassAndFunction('philipsHue', 'pull');
	if (!is_object($cron)) {
		$cron = new cron();
	}
	$cron->setClass('philipsHue');
	$cron->setFunction('pull');
	$cron->setEnable(1);
	$cron->setDeamon(1);
	$cron->setDeamonSleepTime(1);
	$cron->setTimeout(1440);
	$cron->setSchedule('* * * * *');
	$cron->save();
	$cron->stop();
	foreach (philipsHue::byType('philipsHue') as $philipsHue) {
		if ($philipsHue->getConfiguration('device') == '') {
			$philipsHue->setConfiguration('device', $philipsHue->getConfiguration('model'));
		}
		if($philipsHue->getConfiguration('bridge') == 1){
			$philipsHue->setConfiguration('bridge',1);
		}
		if(strpos($philipsHue->getLogicalId(),'-1') === false){
			$philipsHue->setLogicalId($philipsHue->getLogicalId().'-1');
		}
		$philipsHue->save(true);
		$cmd = $philipsHue->getCmd('info', 'alert_state');
		if (is_object($cmd)) {
			$cmd->setConfiguration('repeatEventManagement','never');
			$cmd->save();
		}
		
		$cmd = $philipsHue->getCmd('info', 'rainbow_state');
		if (is_object($cmd)) {
			$cmd->setConfiguration('repeatEventManagement','never');
			$cmd->save();
		}
		
		$cmd = $philipsHue->getCmd('info', 'isReachable');
		if (is_object($cmd)) {
			$cmd->setConfiguration('repeatEventManagement','never');
			$cmd->save();
		}
	}
	if(config::byKey('bridge_ip','philipsHue') != '' && config::byKey('bridge_ip1','philipsHue') == ''){
		config::save('bridge_ip1',config::byKey('bridge_ip','philipsHue'),'philipsHue');
	}
	if(config::byKey('bridge_username','philipsHue') != '' && config::byKey('bridge_username1','philipsHue') == ''){
		config::save('bridge_username1',config::byKey('bridge_username','philipsHue'),'philipsHue');
	}
}

function philipsHue_remove() {
	$cron = cron::byClassAndFunction('philipsHue', 'pull');
	if (is_object($cron)) {
		$cron->remove();
	}
}

?>
