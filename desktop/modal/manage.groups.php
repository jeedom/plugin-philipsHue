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


if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
?>
<a class="btn btn-success" id="bt_addGroups"><i class="fa fa-plus"></i> {{Ajouter un groupe}}</a>
<table id="table_Groups" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th>{{ID GROUPE}}</th>
            <th>{{NOM}}</th>
            <th>{{LAMPES}}</th>
            <th>{{ACTION}}</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>

<script>
	$("#bt_addGroups").on('click', function(event) {
    	addGroupToTable();
	});
	$('#table_Groups').delegate('.save', 'click', function() {
    	//alert( $('.input-sm').value());
    	var lights = [];
    	$(this).closest('tr').find('input[data-l1key="lamps"]').each(function () {
			if(this.checked){
				lights.push($(this).data("l2key").substring(1));
			}
		});
    	$.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
            data: {
                action: "saveGroup",
                id: $(".li_eqLogic").attr('data-eqLogic_id'),
                lamps: lights,
                idgroup: $(this).closest('tr').find('.input-sm[data-l1key="idgroup"]').val(),
                name: $(this).closest('tr').find('.input-sm[data-l1key="name"]').val()
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error, $('#div_showClassAlert'));
            },
            success: function(data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_showClassAlert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                loadGroups($(".li_eqLogic").attr('data-eqLogic_id'));
            }
        });
    });
    $('#table_Groups').delegate('.delete', 'click', function() {
    	$.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
            data: {
                action: "deleteGroup",
                id: $(".li_eqLogic").attr('data-eqLogic_id'),
                idgroup: $(this).closest('tr').find('.input-sm[data-l1key="idgroup"]').val()
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error, $('#div_showClassAlert'));
            },
            success: function(data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_showClassAlert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                loadGroups($(".li_eqLogic").attr('data-eqLogic_id'));
            }
        });
    });

	//alert('id'+$(".li_eqLogic").attr('data-eqLogic_id'));
    loadGroups($(".li_eqLogic").attr('data-eqLogic_id'));


    function loadGroups(id) {
    	$.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
            data: {
                action: "loadGroups",
                id: id
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error, $('#div_showClassAlert'));
            },
            success: function(data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    return;
                }
                var lamps='';
		    	$.ajax({// fonction permettant de faire de l'ajax
		            type: "POST", // methode de transmission des données au fichier php
		            url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
		            data: {
		                action: "associateBridge",
		                id: $(".li_eqLogic").attr('data-eqLogic_id'),
		                adresse_ip: $('#addr').val()
		            },
		            dataType: 'json',
		            error: function(request, status, error) {
		                handleAjaxError(request, status, error, $('#div_showClassAlert'));
		            },
		            success: function(data2) { // si l'appel a bien fonctionné
		                if (data2.state != 'ok') {
		                    $('#div_showClassAlert').showAlert({message: data2.result, level: 'danger'});
		                    return;
		                }
		               var tr = '';
		                $('#table_Groups tbody').empty();
		                for (var i in data.result.groups) {
		                	tr += '<tr data-group="' + data.result.groups[i]['group'] + '">';
		                    tr += '<td>';
		                    tr += '<input class="groupAttr form-control input-sm" data-l1key="idgroup" value="'+data.result.groups[i]['id']+'" disabled>';
		                    tr += '</td>';
		                    tr += '<td>';
		                    tr += '<input class="groupAttr form-control input-sm" data-l1key="name" value="'+data.result.groups[i]["name"]+'">';
		                    tr += '</td>';
		                    tr += '<td>';
		                    var lamps = '';
		                    jQuery.each(data2.result.cmd, function(key, val){
				                if(val.type=="lamp"){
				                lamps += '<label class="checkbox-inline">';
				                if($.inArray(val.id.substring(1), data.result.groups[i]["lamps"]) > -1){
				                	lamps += '<input type="checkbox" class="groupAttr" data-l1key="lamps" data-l2key="'+val.id+'" checked/>'+val.name;
				                }else{
				                	lamps += '<input type="checkbox" class="groupAttr" data-l1key="lamps" data-l2key="'+val.id+'" />'+val.name;
				                }
				                lamps += '</label>';
				                }
				            });
		                    tr += lamps;
		                    tr += '</td>';
		                    tr += '<td>';
		                    tr += '<a class="btn btn-success btn-xs pull-right save">Sauvegarder</a>';
		                    tr += '<a class="btn btn-alert btn-xs pull-right delete">Supprimer</a>';
		                    tr += '</td>';
		                    tr += '</tr>';
		                }
		                $('#table_Groups tbody').append(tr);
		            }
		            
		        });
                
                
		}
                
        });
    }
    function addGroupToTable() {
    	var lamps='';
    	$.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php", // url du fichier php
            data: {
                action: "associateBridge",
                id: $(".li_eqLogic").attr('data-eqLogic_id'),
                adresse_ip: $('#addr').val()
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error, $('#div_showClassAlert'));
            },
            success: function(data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_showClassAlert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                jQuery.each(data.result.cmd, function(key, val){
                if(val.type=="lamp"){
                lamps += '<label class="checkbox-inline">';
                lamps += '<input type="checkbox" class="groupAttr" data-l1key="lamps" data-l2key="'+val.id+'" />'+val.name;
                lamps += '</label>';
                }
                });
                var tr = '<tr data-group="">';
			    tr += '<td class="id"></td>';
			    tr += '<td class="name">';
			    tr += '<input class="groupAttr form-control input-sm" data-l1key="name" >';
			    tr += '</td>';
			    tr += '<td class="lights">';
			    tr += lamps;
			    tr += '</td>';
			    tr += '<td>';
		        tr += '<a class="btn btn-success btn-xs pull-right save">Sauvegarder</a>';
			    tr += '</td>';
			    tr += '</tr>';
			    $('#table_Groups tbody').append(tr);
            }
        });
	    
	    
	    
	}
</script>

