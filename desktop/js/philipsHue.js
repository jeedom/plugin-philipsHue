
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


$('#bt_touchLink').off('click').on('click', function () {
  var inputOptions = [];
  for(let i =1;i<= nb_philipsHue_bridge;i++){
    inputOptions.push({value : i,text : "{{Bridge }}"+i});
  }
  bootbox.prompt({
    title: "Activer le touchlink sur le pont ?",
    value : inputOptions[0].value,
    inputType: 'select',
    inputOptions:inputOptions,
    callback: function (bridge_id) {
      if(bridge_id === null){
        return;
      }
      $.ajax({
        type: "POST",
        url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php",
        data: {
          action: "setTouchLink",
          model: bridge_id,
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
          $('#div_alert').showAlert({message: '{{Touchlink activé sur le bridge}} : '+bridge_id, level: 'success'});
        }
      });
    }
  });
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').on('change', function () {
  if($('select.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value() == ''){
    $('#img_device').attr("src",'plugins/philipsHue/plugin_info/philipsHue_icon.png');
    return;
  }
  $.ajax({
    type: "POST",
    url: "plugins/philipsHue/core/ajax/philipsHue.ajax.php",
    data: {
      action: "getImageModel",
      model: $('select.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value(),
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
      window.location.reload();
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
  console.log(_cmd);
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td style="min-width:50px;width:70px;">';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += '</td>';
  tr += '<td style="min-width:300px;width:350px;">';
  tr += '<div class="row">';
  tr += '<div class="col-xs-7">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
  tr += '<option value="">{{Aucune}}</option>';
  tr += '</select>';
  tr += '</div>';
  tr += '<div class="col-xs-5">';
  tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
  tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
  tr += '</div>';
  tr += '</div>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td style="min-width:120px;width:140px;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId">';
  tr += '</td>';
  tr += '<td style="min-width:120px;width:140px;">';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></div> ';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></div> ';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></div>';
  tr += '</td>';
  tr += '<td style="min-width:180px;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  var tr = $('#table_cmd tbody tr').last();
  jeedom.eqLogic.builSelectCmd({
    id:  $('.eqLogicAttr[data-l1key=id]').value(),
    filter: {type: 'info'},
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'});
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result);
      tr.setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType(tr, init(_cmd.subType));
    }
  });
}
