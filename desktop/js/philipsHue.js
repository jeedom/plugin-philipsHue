
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

$(function() {
	 $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
    $('#bt_manageGroups').on('click', function() {
        $('#md_modal').dialog({title: "{{Gestion des groupes}}"});
        $('#md_modal').load('index.php?v=d&plugin=philipsHue&modal=manage.groups').dialog('open');
    });
    $("#associateBridge").on('click', function(event) {
    	associateBridge($(this).attr('data-eqLogic_id'),$('#addr').val());
    	return false;
	});
	$("#sel_device").on('change', function() {
        var optionSelected = $(this).find("option:selected").text();
        if(optionSelected!=''){
        	
        	$("#device").val(this.value);
        }
 	});
        
   });

function associateBridge(_philipsHueEq_id,adresse_ip) {
	$.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
        data: {
            action: "associateBridge",
            id: _philipsHueEq_id,
            adresse_ip: adresse_ip
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
           $('#sel_device').prop('disabled', false);
           $('#sel_device').empty();
            for (var i in data.result.cmd) {
            	
            	$('#sel_device').append(new Option(data.result.cmd[i].name,data.result.cmd[i].id));
            	if($('#device').val() ==""){
            		$('#device').val(data.result.cmd[i].id);
            		$('#type').val('Station');
            	}           	
                
            }
           
        }
    });
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="name">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name"></td>';
    tr += '<td class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType();
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span></td>';
    tr += '<td ><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="margin-top : 5px;" />';
    tr += '<textarea class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="parameters" style="margin-top : 5px;" placeholder="{{Parametres (JSON)}}" ></textarea>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}