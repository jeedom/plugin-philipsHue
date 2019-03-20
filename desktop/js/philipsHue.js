
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


$('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').on('change', function () {
  if($('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value() == ''){
    $('#img_device').attr("src",'plugins/philipsHue/plugin_info/philipsHue_icon.png');
    return;
  }
  $.ajax({
    type: "POST",
    url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php",
    data: {
      action: "getImageModel",
      model: $('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value(),
    },
    dataType: 'json',
    global: false,
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
        return;
      }
      if(data.result != false){
        $('#img_device').attr("src",'plugins/philipsHue/core/config/devices/'+data.result);
      }else{
        $('#img_device').attr("src",'plugins/philipsHue/plugin_info/philipsHue_icon.png');
      }
    }
  });
});

$('#bt_syncEqLogic').off('click').on('click',function(){
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
})

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}};
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
  tr += '</td>';
  tr += '<td>';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" />{{Afficher}}</label>';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" />{{Historiser}}</label>';
  tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
  }
  tr += '</td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
  if (isset(_cmd.type)) {
    $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
  }
  jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
