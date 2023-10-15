
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
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<td>';
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td style="min-width:120px;width:140px;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId">';
  tr += '</td>';
  tr += '<td>';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration avancée}}"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  tr += '</td>';
  tr += '<td>';
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>';
  tr += '</td>';
 tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  var tr = $('#table_cmd tbody tr').last();
  jeedom.eqLogic.buildSelectCmd({
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
