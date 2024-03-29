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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>
<form class="form-horizontal">
	<fieldset>
		<?php
		for($i=1;$i<=config::byKey('nbBridge','philipsHue');$i++){
			echo '<div class="form-group">';
			echo '<label class="col-sm-3 control-label">{{Adresse IP du pont}} '.$i.'</label>';
			echo '<div class="col-sm-7">';
			echo '<input type="text" class="configKey form-control" data-l1key="bridge_ip'.$i.'" />';
			echo '</div>';
			echo '</div>';
		}
		?>
	</div>
</fieldset>
</form>

<script>
function philipsHue_postSaveConfiguration(){
	$.ajax({
		type: "POST",
		url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php",
		data: {
			action: "syncPhilipsHue",
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alert').showAlert({message: '{{Synchronisation réussie}}', level: 'success'});
		}
	});
}
</script>
