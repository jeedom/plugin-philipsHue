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

global $listCmdPhilipsHue;
$listCmdPhilipsHue = array(
    array(
        'name' => 'Couleur',
        'configuration' => array(
            'request' => 'rgb',
            'parameters' => 'rgb=#color#&bri=254',
        ),
        'type' => 'action',
        'subType' => 'color',
        'description' => 'Changement de la couleur',
        'version' => '0.1',
        'required' => '',
    ),
    array(
        'name' => 'On',
        'configuration' => array(
            'request' => 'on',
            'parameters' => '1',
        ),
        'type' => 'action',
        'subType' => 'other',
        'description' => 'Allumer',
        'version' => '0.1',
        'required' => '',
    ),
    array(
        'name' => 'Off',
        'configuration' => array(
            'request' => 'off',
            'parameters' => '0',
        ),
        'type' => 'action',
        'subType' => 'other',
        'description' => 'Eteindre',
        'version' => '0.1',
        'required' => '',
    ),
    array(
        'name' => 'Luminosité',
        'configuration' => array(
            'request' => 'bri',
            'parameters' => 'bri=#slider#',
        ),
        'type' => 'action',
        'subType' => 'slider',
        'description' => 'Réglage de la luminosité',
        'version' => '0.1',
        'required' => '',
    )
);
?>
