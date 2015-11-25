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
require_once dirname(__FILE__) . '/../../3rdparty/Phue-master/vendor/autoload.php';
require_once dirname(__FILE__) . '/../../3rdparty/color_conversion.php';

class philipsHue extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public static function associateBridge($_options) {
		if ($_POST['adresse_ip'] == '') {
            throw new Exception(__('L\'adresse ne peut etre vide',__FILE__));
        }
		
		$hue = new \Phue\Client($_POST['adresse_ip'], 'newdeveloper');
		try {
		    $hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
		    echo 'There was a problem accessing the bridge';
		}
		$isAuthenticated = $hue->sendCommand(new \Phue\Command\IsAuthorized);
		if($isAuthenticated==False){
		try {
		    $hue->sendCommand(
		        new \Phue\Command\CreateUser('newdeveloper')
		    );
		
		    echo 'You are now authenticated';
		} catch (\Phue\Transport\Exception\LinkButtonException $e) {
		    echo 'The link button was not pressed!';
		}
		}
		$devices=array();
		$i=1;
		foreach ($hue->getLights() as $lightId => $light) {
			$devices[$i]['id']='l'.$lightId;
			$devices[$i]['type']='lamp';
			$devices[$i]['name']='lampe:'.$light->getName();	
			$i++;
		}
		foreach ($hue->getGroups() as $groupId => $group) {
			$devices[$i]['id']='g'.$groupId;
			$devices[$i]['type']='group';
			$devices[$i]['name']='groupe:'.$group->getName();	
			$i++;
		}
	
		
	return $devices;
    }

	public static function loadGroups($_options) {
		$ip="";
		foreach (eqLogic::byType('philipsHue') as $philipsHue) {
			$id=$philipsHue->getId();
			if($_POST['id']==$id){
				$ip=$philipsHue->getConfiguration('addr');
			}
		}
		if ($ip == '') {
            throw new Exception(__('L\'adresse ne peut etre vide',__FILE__));
        }
		$hue = new \Phue\Client($ip, 'newdeveloper');
		try {
		    $hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
		    echo 'There was a problem accessing the bridge';
		}
		$isAuthenticated = $hue->sendCommand(new \Phue\Command\IsAuthorized);
		if($isAuthenticated==False){
		try {
		    $hue->sendCommand(
		        new \Phue\Command\CreateUser('newdeveloper')
		    );
		
		    echo 'You are now authenticated';
		} catch (\Phue\Transport\Exception\LinkButtonException $e) {
		    echo 'The link button was not pressed!';
		}
		}
		$groups=array();
		$i=1;
		foreach ($hue->getGroups() as $groupId => $group) {
			$groups[$i]['id']=$groupId;
			$groups[$i]['name']=$group->getName();
			$groups[$i]['lamps']=$group->getLightIds();	
			$i++;
		}
	
		
	return $groups;
    }

	public static function saveGroup($_options) {
		$ip="";
		foreach (eqLogic::byType('philipsHue') as $philipsHue) {
			$id=$philipsHue->getId();
			if($_POST['id']==$id){
				$ip=$philipsHue->getConfiguration('addr');
			}
		}
		if ($ip == '') {
            throw new Exception(__('L\'adresse ne peut etre vide',__FILE__));
        }
		$hue = new \Phue\Client($ip, 'newdeveloper');
		try {
		    $hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
		    echo 'There was a problem accessing the bridge';
		}
		$isAuthenticated = $hue->sendCommand(new \Phue\Command\IsAuthorized);
		if($isAuthenticated==False){
		try {
		    $hue->sendCommand(
		        new \Phue\Command\CreateUser('newdeveloper')
		    );
		
		    echo 'You are now authenticated';
		} catch (\Phue\Transport\Exception\LinkButtonException $e) {
		    echo 'The link button was not pressed!';
		}
		}
		if (isset($_POST["idgroup"]) && !empty($_POST["idgroup"])) {
			$idgroup=$_POST["idgroup"];
			$group = $hue->getGroups()[$idgroup];
			$group->setName($_POST['name']);
			$group->setLights($_POST['lamps']);
		}else{
			$groupId = $hue->sendCommand(new \Phue\Command\CreateGroup($_POST['name'], $_POST['lamps']));
		}
		
	return true;
    }

	public static function deleteGroup($_options) {
		$ip="";
		foreach (eqLogic::byType('philipsHue') as $philipsHue) {
			$id=$philipsHue->getId();
			if($_POST['id']==$id){
				$ip=$philipsHue->getConfiguration('addr');
			}
		}
		if ($ip == '') {
            throw new Exception(__('L\'adresse ne peut etre vide',__FILE__));
        }
		$hue = new \Phue\Client($ip, 'newdeveloper');
		try {
		    $hue->sendCommand(new \Phue\Command\Ping);
		} catch (\Phue\Transport\Exception\ConnectionException $e) {
		    echo 'There was a problem accessing the bridge';
		}
		$isAuthenticated = $hue->sendCommand(new \Phue\Command\IsAuthorized);
		if($isAuthenticated==False){
		try {
		    $hue->sendCommand(
		        new \Phue\Command\CreateUser('newdeveloper')
		    );
		
		    echo 'You are now authenticated';
		} catch (\Phue\Transport\Exception\LinkButtonException $e) {
		    echo 'The link button was not pressed!';
		}
		}
		if (isset($_POST["idgroup"]) && !empty($_POST["idgroup"])) {
			$idgroup=$_POST["idgroup"];
			$group = $hue->getGroups()[$idgroup];
			$group->delete();
		}
		
	return true;
    }

    public static function pull($_eqLogic_id = null) {
    	foreach (eqLogic::byType('philipsHue') as $philipsHue) {
    		if ($_eqLogic_id != null && $_eqLogic_id != $philipsHue->getId()) {
				continue;
			}
			if ($philipsHue->getIsEnable() == 0) {
				continue;
			}
			
			try {
				$changed = false;
				
				$id=array();
				$value=array();
				$type="";
				if(substr($philipsHue->getConfiguration('device'), 0,1)=="l"){
					$type="lamp";
				}else{
					$type="group";
				}
				$philipsHue_id = substr($philipsHue->getConfiguration('device'),1);
				$philipsHue_ip = $philipsHue->getConfiguration('addr');
				$hue = new \Phue\Client($philipsHue_ip, 'newdeveloper');
				//$hue = new Hue($philipsHue_ip, 'newdeveloper' );
		        if($type=="lamp") {
		        	$philipsHue_type = "lights";
					$obj=$hue->getLights();
		        }else if($type=="group") {
		        	$philipsHue_type = "groups";
					$obj=$hue->getGroups();
		        }
				
				$cmd_state = $philipsHue->getCmd(null, 'state');
				if (is_object($cmd_state)) {
					if($obj[$philipsHue_id]->isOn()==""){
						$state=0;
					}else{
						$state=1;
					}
					if ($state != $cmd_state->execCmd(null, 2)) {
						$cmd_state->setCollectDate('');
						$cmd_state->event($state);
						$changed = true;
					}
				}
				$cmd_etat_luminosite = $philipsHue->getCmd(null, 'etat_luminosite');
				if (is_object($cmd_etat_luminosite)) {
					$etat_luminosite = $obj[$philipsHue_id]->getBrightness();
					if ($etat_luminosite != $cmd_etat_luminosite->execCmd(null, 2)) {
						$cmd_etat_luminosite->setCollectDate('');
						$cmd_etat_luminosite->event($etat_luminosite);
						$changed = true;
					}
				}
				$cmd_etat_saturation = $philipsHue->getCmd(null, 'etat_saturation');
				if (is_object($cmd_etat_saturation)) {
					$etat_saturation = $obj[$philipsHue_id]->getSaturation();
					if ($etat_saturation != $cmd_etat_saturation->execCmd(null, 2)) {
						$cmd_etat_saturation->setCollectDate('');
						$cmd_etat_saturation->event($etat_saturation);
						$changed = true;
					}
				}
				$cmd_etat_color = $philipsHue->getCmd(null, 'etat_color');
				if (is_object($cmd_etat_color)) {
					$bri=$obj[$philipsHue_id]->getBrightness();
	                $hex=xyBriToRgb($obj[$philipsHue_id]->getXY()['x'],$obj[$philipsHue_id]->getXY()['y'],$bri);
					$etat_color = $hex;
					if ($etat_color != $cmd_etat_color->execCmd(null, 2)) {
						$cmd_etat_color->setCollectDate('');
						$cmd_etat_color->event($etat_color);
						$changed = true;
					}
				}
				$cmd_etat_alert = $philipsHue->getCmd(null, 'etat_alert');
				if (is_object($cmd_etat_alert)) {
					$state_alert=$obj[$philipsHue_id]->getAlert();
					if($state_alert=="none"){
	            		$etat_alert=0;
	            	}else{
	            		$etat_alert=1;
	            	}
					if ($etat_alert != $cmd_etat_alert->execCmd(null, 2)) {
						$cmd_etat_alert->setCollectDate('');
						$cmd_etat_alert->event($etat_alert);
						$changed = true;
					}
				}
				$cmd_etat_rainbow = $philipsHue->getCmd(null, 'etat_rainbow');
				if (is_object($cmd_etat_rainbow) && $obj[$philipsHue_id]->getModelId()<>"LWB004") {
					$state_rainbow=$obj[$philipsHue_id]->getEffect();
	            	if($state_rainbow=="none"){
	            		$etat_rainbow=0;
	            	}else{
	            		$etat_rainbow=1;
	            	}
					if ($etat_rainbow != $cmd_etat_rainbow->execCmd(null, 2)) {
						$cmd_etat_rainbow->setCollectDate('');
						$cmd_etat_rainbow->event($etat_rainbow);
						$changed = true;
					}
				}
				if ($changed) {
					$philipsHue->refreshWidget();
				}
			} catch (Exception $e) {
				if ($_eqLogic_id != null) {
					log::add('philipsHue', 'error', $e->getMessage());
				} 
			}	
		}
    }
	
	public function toHtml($_version = 'dashboard') {
        if ($this->getIsEnable() != 1) {
            return '';
        }
		if (!$this->hasRight('r')) {
			return '';
		}
		$_version = jeedom::versionAlias($_version);
		$vcolor = 'cmdColor';
		if ($version == 'mobile') {
			$vcolor = 'mcmdColor';
		}
		$cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		if (is_array($parameters) && isset($parameters['background_cmd_color'])) {
			$cmdColor = $parameters['background_cmd_color'];
		}
		if(substr($this->getConfiguration('device'), 0,1)=="l"){
			$type="lamp";
		}else{
			$type="group";
		}
		$replace = array(
			'#id#' => $this->getId(),
			'#info#' => (isset($info)) ? $info : '',
			'#name#' => $this->getName(),
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#text_color#' => $this->getConfiguration('text_color'),
			'#background_color#' => $this->getBackgroundColor($_version),
			'#cmdColor#' => $cmdColor,
			'#hideThumbnail#' => 0,
			'#type#' => $type,
			'#object_name#' => '',
		);
        
		if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowObjectNameOnView', 0) == 0) {
			$object = $this->getObject();
			$replace['#object_name#'] = (is_object($object)) ? '(' . $object->getName() . ')' : '';
		}
		if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
			$replace['#name#'] = '';
		}
		if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
			$replace['#name#'] = '';
		}
		$cmd_state = $this->getCmd(null, 'state');
		if (is_object($cmd_state)) {
			$replace['#etat#'] = $cmd_state->execCmd(null, 2);
			$replace['#etat_id#'] = $cmd_state->getId();
		}
		$cmd_etat_luminosite = $this->getCmd(null, 'etat_luminosite');
		if (is_object($cmd_etat_luminosite)) {
			$replace['#etat_luminosite#'] = $cmd_etat_luminosite->execCmd(null, 2);
			$replace['#etat_luminosite_id#'] = $cmd_etat_luminosite->getId();
		}
		$cmd_etat_saturation = $this->getCmd(null, 'etat_saturation');
		if (is_object($cmd_etat_saturation)) {
			$replace['#etat_saturation#'] = $cmd_etat_saturation->execCmd(null, 2);
			$replace['#etat_saturation_id#'] = $cmd_etat_saturation->getId();
		}
		$cmd_etat_color = $this->getCmd(null, 'etat_color');
		if (is_object($cmd_etat_color)) {
			$replace['#etat_color#'] = $cmd_etat_color->execCmd(null, 2);
			$replace['#etat_color_id#'] = $cmd_etat_color->getId();
		}
		$cmd_etat_alert = $this->getCmd(null, 'etat_alert');
		if (is_object($cmd_etat_alert)) {
			$replace['#etat_alerte#'] = $cmd_etat_alert->execCmd(null, 2);
			$replace['#etat_alerte_id#'] = $cmd_etat_alert->getId();
		}
		$cmd_etat_rainbow = $this->getCmd(null, 'etat_rainbow');
		if (is_object($cmd_etat_rainbow)) {
			$replace['#etat_rainbow#'] = $cmd_etat_rainbow->execCmd(null, 2);
			$replace['#etat_rainbow_id#'] = $cmd_etat_rainbow->getId();
		}
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}
		$parameters = $this->getDisplay('parameters');
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $replace['#' . $key . '#'] = $value;
            }
        }
		return template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), 'philipsHue', 'philipsHue'));
    }

    /*     * *********************Methode d'instance************************* */

	public function postSave() {
		
		$on = $this->getCmd(null, 'on');
		if (!is_object($on)) {
			$on = new philipsHueCmd();
			$on->setLogicalId('on');
			$on->setIsVisible(1);
			$on->setName(__('On', __FILE__));
		}
		$on->setType('action');
		$on->setSubType('other');
		$on->setConfiguration('request', 'on');
		$on->setEqLogic_id($this->getId());
		$on->save();
		
		$off = $this->getCmd(null, 'off');
		if (!is_object($off)) {
			$off = new philipsHueCmd();
			$off->setLogicalId('off');
			$off->setIsVisible(1);
			$off->setName(__('Off', __FILE__));
		}
		$off->setType('action');
		$off->setSubType('other');
		$off->setConfiguration('request', 'off');
		$off->setEqLogic_id($this->getId());
		$off->save();
		
		$state = $this->getCmd(null, 'state');
		if (!is_object($state)) {
			$state = new philipsHueCmd();
			$state->setLogicalId('state');
			$state->setIsVisible(1);
			$state->setName(__('Etat On Off', __FILE__));
		}
		$state->setType('info');
		$state->setSubType('binary');
		$state->setUnite('');
		$state->setEventOnly(1);
		$state->setConfiguration('data', 'etat');
		$state->setConfiguration('request', 'etat');
		$state->setEqLogic_id($this->getId());
		$state->save();
		
		$luminosite = $this->getCmd(null, 'luminosite');
		if (!is_object($luminosite)) {
			$luminosite = new philipsHueCmd();
			$luminosite->setLogicalId('luminosite');
			$luminosite->setIsVisible(1);
			$luminosite->setName(__('Luminosité', __FILE__));
		}
		$luminosite->setType('action');
		$luminosite->setSubType('slider');
		$luminosite->setConfiguration('request', 'luminosite');
		$luminosite->setConfiguration('parameters', '#slider#');
		$luminosite->setConfiguration('minValue', '0');
		$luminosite->setConfiguration('maxValue', '255');
		$luminosite->setEqLogic_id($this->getId());
		$luminosite->save();
		
		$etat_luminosite = $this->getCmd(null, 'etat_luminosite');
		if (!is_object($etat_luminosite)) {
			$etat_luminosite = new philipsHueCmd();
			$etat_luminosite->setLogicalId('etat_luminosite');
			$etat_luminosite->setIsVisible(1);
			$etat_luminosite->setName(__('Etat Luminosité', __FILE__));
		}
		$etat_luminosite->setType('info');
		$etat_luminosite->setSubType('numeric');
		$etat_luminosite->setUnite('');
		$etat_luminosite->setEventOnly(1);
		$etat_luminosite->setConfiguration('data', 'etat_luminosite');
		$etat_luminosite->setConfiguration('request', 'etat_luminosite');
		$etat_luminosite->setEqLogic_id($this->getId());
		$etat_luminosite->save();
		
		$saturation = $this->getCmd(null, 'saturation');
		if (!is_object($saturation)) {
			$saturation = new philipsHueCmd();
			$saturation->setLogicalId('saturation');
			$saturation->setIsVisible(1);
			$saturation->setName(__('Saturation', __FILE__));
		}
		$saturation->setType('action');
		$saturation->setSubType('slider');
		$saturation->setConfiguration('request', 'saturation');
		$saturation->setConfiguration('parameters', '#slider#');
		$saturation->setConfiguration('minValue', '0');
		$saturation->setConfiguration('maxValue', '255');
		$saturation->setEqLogic_id($this->getId());
		$saturation->save();
		
		$etat_saturation = $this->getCmd(null, 'etat_saturation');
		if (!is_object($etat_saturation)) {
			$etat_saturation = new philipsHueCmd();
			$etat_saturation->setLogicalId('etat_saturation');
			$etat_saturation->setIsVisible(1);
			$etat_saturation->setName(__('Etat Saturation', __FILE__));
		}
		$etat_saturation->setType('info');
		$etat_saturation->setSubType('numeric');
		$etat_saturation->setUnite('');
		$etat_saturation->setEventOnly(1);
		$etat_saturation->setConfiguration('data', 'etat_saturation');
		$etat_saturation->setConfiguration('request', 'etat_saturation');
		$etat_saturation->setEqLogic_id($this->getId());
		$etat_saturation->save();
		
		$color = $this->getCmd(null, 'color');
		if (!is_object($color)) {
			$color = new philipsHueCmd();
			$color->setLogicalId('color');
			$color->setIsVisible(1);
			$color->setName(__('Couleur', __FILE__));
		}
		$color->setType('action');
		$color->setSubType('color');
		$color->setConfiguration('request', 'color');
		$color->setConfiguration('parameters', '#color#');
		$color->setEqLogic_id($this->getId());
		$color->save();
		
		$etat_color = $this->getCmd(null, 'etat_color');
		if (!is_object($etat_color)) {
			$etat_color = new philipsHueCmd();
			$etat_color->setLogicalId('etat_color');
			$etat_color->setIsVisible(1);
			$etat_color->setName(__('Etat Couleur', __FILE__));
		}
		$etat_color->setType('info');
		$etat_color->setSubType('string');
		$etat_color->setUnite('');
		$etat_color->setEventOnly(1);
		$etat_color->setConfiguration('data', 'etat_color');
		$etat_color->setConfiguration('request', 'etat_color');
		$etat_color->setEqLogic_id($this->getId());
		$etat_color->save();
		
		$alert_on = $this->getCmd(null, 'alert_on');
		if (!is_object($alert_on)) {
			$alert_on = new philipsHueCmd();
			$alert_on->setLogicalId('alert_on');
			$alert_on->setIsVisible(1);
			$alert_on->setName(__('Alerte On', __FILE__));
		}
		$alert_on->setType('action');
		$alert_on->setSubType('other');
		$alert_on->setConfiguration('request', 'alert_on');
		$alert_on->setEqLogic_id($this->getId());
		$alert_on->save();
		
		$alert_off = $this->getCmd(null, 'alert_off');
		if (!is_object($alert_off)) {
			$alert_off = new philipsHueCmd();
			$alert_off->setLogicalId('alert_off');
			$alert_off->setIsVisible(1);
			$alert_off->setName(__('Alerte Off', __FILE__));
		}
		$alert_off->setType('action');
		$alert_off->setSubType('other');
		$alert_off->setConfiguration('request', 'alert_off');
		$alert_off->setEqLogic_id($this->getId());
		$alert_off->save();
		
		$etat_alert = $this->getCmd(null, 'etat_alert');
		if (!is_object($etat_alert)) {
			$etat_alert = new philipsHueCmd();
			$etat_alert->setLogicalId('etat_alert');
			$etat_alert->setIsVisible(1);
			$etat_alert->setName(__('Etat Alerte', __FILE__));
		}
		$etat_alert->setType('info');
		$etat_alert->setSubType('binary');
		$etat_alert->setUnite('');
		$etat_alert->setEventOnly(1);
		$etat_alert->setConfiguration('data', 'etat_alert');
		$etat_alert->setConfiguration('request', 'etat_alert');
		$etat_alert->setEqLogic_id($this->getId());
		$etat_alert->save();
		
		$rainbow_on = $this->getCmd(null, 'rainbow_on');
		if (!is_object($rainbow_on)) {
			$rainbow_on = new philipsHueCmd();
			$rainbow_on->setLogicalId('rainbow_on');
			$rainbow_on->setIsVisible(1);
			$rainbow_on->setName(__('Arc en ciel On', __FILE__));
		}
		$rainbow_on->setType('action');
		$rainbow_on->setSubType('other');
		$rainbow_on->setConfiguration('request', 'rainbow_on');
		$rainbow_on->setEqLogic_id($this->getId());
		$rainbow_on->save();
		
		$rainbow_off = $this->getCmd(null, 'rainbow_off');
		if (!is_object($rainbow_off)) {
			$rainbow_off = new philipsHueCmd();
			$rainbow_off->setLogicalId('rainbow_off');
			$rainbow_off->setIsVisible(1);
			$rainbow_off->setName(__('Arc en ciel Off', __FILE__));
		}
		$rainbow_off->setType('action');
		$rainbow_off->setSubType('other');
		$rainbow_off->setConfiguration('request', 'rainbow_off');
		$rainbow_off->setEqLogic_id($this->getId());
		$rainbow_off->save();
		
		$etat_rainbow = $this->getCmd(null, 'etat_rainbow');
		if (!is_object($etat_rainbow)) {
			$etat_rainbow = new philipsHueCmd();
			$etat_rainbow->setLogicalId('etat_rainbow');
			$etat_rainbow->setIsVisible(1);
			$etat_rainbow->setName(__('Etat Arc en ciel', __FILE__));
		}
		$etat_rainbow->setType('info');
		$etat_rainbow->setSubType('binary');
		$etat_rainbow->setUnite('');
		$etat_rainbow->setEventOnly(1);
		$etat_rainbow->setConfiguration('data', 'etat_rainbow');
		$etat_rainbow->setConfiguration('request', 'etat_rainbow');
		$etat_rainbow->setEqLogic_id($this->getId());
		$etat_rainbow->save();
	}
}

class philipsHueCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preSave() {
        if ($this->getConfiguration('request') == '') {
            throw new Exception(__('La requete ne peut etre vide',__FILE__));
        }
    }

    public function execute($_options = null) {
    	$philipsHue = $this->getEqLogic();
        $philipsHue_id = substr($philipsHue->getConfiguration('device'),1);
		$philipsHue_ip = $philipsHue->getConfiguration('addr');
		$hue = new \Phue\Client($philipsHue_ip, 'newdeveloper');
		//$hue = new Hue($philipsHue_ip, 'newdeveloper' );
        if(substr($philipsHue->getConfiguration('device'), 0,1)=="l") {
        	$philipsHue_type = "lights";
			$obj=$hue->getLights();
        }else if(substr($philipsHue->getConfiguration('device'), 0,1)=="g") {
        	$philipsHue_type = "groups";
			$obj=$hue->getGroups();
        }

            $parameters = $this->getConfiguration('parameters');
			if ($this->type == 'action') {
				switch ($this->subType) {
                    case 'slider':
						$type=$this->getConfiguration('request');
                        $parameters = str_replace('#slider#', $_options['slider'], $parameters);
                        switch ($type) {
                            case 'luminosite':
								$response=$obj[$philipsHue_id]->setOn(true);
								$response=$obj[$philipsHue_id]->setBrightness($parameters);
                                break;
                            case 'saturation':
								$response=$obj[$philipsHue_id]->setOn(true);
								$response=$obj[$philipsHue_id]->setSaturation($parameters);
                                break;
                            default:
                                
                                break;
                        }
                        break;
					case 'color':
						$response=$obj[$philipsHue_id]->setOn(true);
						$type=$this->getConfiguration('request');
                        $parameters = str_replace('#color#', $_options['color'], $parameters);
						$color2=setHexCode2( $parameters );
						if($philipsHue_type=="lights"){
							$command = new \Phue\Command\SetLightState($obj[$philipsHue_id]);
						}else{
							$command = new \Phue\Command\SetGroupState($obj[$philipsHue_id]);
						}
						//$command->brightness($color2['bri'])
						//        ->xy($color2['xy'][0],$color2['xy'][1]);
						$command->xy($color2['xy'][0],$color2['xy'][1]);
						$response=$hue->sendCommand($command);
						break;
                    default:
						$type=$this->getConfiguration('request');
						switch ($type) {
							case 'on':
								if($obj[$philipsHue_id]->isOn()==""){
									$response=$obj[$philipsHue_id]->setOn(true);
								}
								break;
							case 'off':
								if($obj[$philipsHue_id]->isOn()<>""){
									if($obj[$philipsHue_id]->getModelId()<>"LWB004" && $obj[$philipsHue_id]->getModelId()<>""){
										$response=$obj[$philipsHue_id]->setEffect('none');
									}
									$response=$obj[$philipsHue_id]->setAlert('none');
									$response=$obj[$philipsHue_id]->setOn(false);
								}
								break;
							case 'alert_on':
								$state=$obj[$philipsHue_id]->getAlert();
            					if($state=="none"){
            						$response=$obj[$philipsHue_id]->setOn(true);
									$response=$obj[$philipsHue_id]->setAlert('lselect');
            					}
								break;
							case 'alert_off':
								$state=$obj[$philipsHue_id]->getAlert();
            					if($state<>"none"){
									$response=$obj[$philipsHue_id]->setAlert('none');
								}
								break;
							case 'rainbow_on':
								$state=$obj[$philipsHue_id]->getEffect();
                				if($state=="none"){
									$response=$obj[$philipsHue_id]->setOn(true);
									$response=$obj[$philipsHue_id]->setEffect('colorloop');
								}
								break;
							case 'rainbow_off':
								$state=$obj[$philipsHue_id]->getEffect();
                				if($state<>"none"){
									$response=$obj[$philipsHue_id]->setEffect('none');
								}
								break;
							default:
								
								break;
						}
						break;
                }
				$eqLogic_philipsHue = $this->getEqLogic();
        		philipsHue::pull($eqLogic_philipsHue->getId());
            }else{
        		$eqLogic_philipsHue = $this->getEqLogic();
        		philipsHue::pull($eqLogic_philipsHue->getId());
			}
       
        return $response;
    

    /*     * **********************Getteur Setteur*************************** */
}
}
?>