<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$eqLogics = eqLogic::byType('philipsHue');
sendVarToJS('eqType', 'philipsHue');
?>

<div class="row row-overflow">
  <div class="col-lg-2 col-md-3 col-sm-4">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
        <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" id="bt_manageGroups"><i class="fa fa-bulb"></i> {{Gestion des groupes}}</a>
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
     </ul>
   </div>
 </div>
 <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend>{{Mes Philips Hue}}
   </legend>
   <div class="eqLogicThumbnailContainer">
     <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
	echo "<center>";
	echo '<img src="plugins/philipsHue/doc/images/philipsHue_icon.png" height="105" width="95" />';
	echo "</center>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
	echo '</div>';
}
?>
   </div>
 </div>
 <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
  <div class="row">
    <div class="col-sm-6">
      <form class="form-horizontal">
        <fieldset>
          <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
          <div class="form-group">
            <label class="col-lg-4 control-label">{{Nom de l'équipement Philips Hue}}</label>
            <div class="col-lg-6">
              <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
              <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement philipsHue}}"/>
            </div>
          </div>
          <div class="form-group">
            <label class="col-lg-4 control-label" >{{Objet parent}}</label>
            <div class="col-lg-6">
              <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                <option value="">{{Aucun}}</option>
                <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
             </select>
           </div>
         </div>
         <div class="form-group">
          <label class="col-lg-4 control-label">{{Catégorie}}</label>
          <div class="col-lg-8">
            <?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
	echo '<label class="checkbox-inline">';
	echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
	echo '</label>';
}
?>
         </div>
       </div>
       <div class="form-group">
         <label class="col-md-4 control-label"></label>
         <div class="col-md-8">
          <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
          <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
        </div>
        </div>
        <div class="form-group">
        <label class="col-sm-4 control-label">{{Toujours allumé}}</label>
          <div class="col-sm-2">
           <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-l1key="configuration" data-l2key="alwaysOn"/>
         </div>
       </div>
     </fieldset>
   </form>
 </div>
 <div class="col-sm-6">
  <form class="form-horizontal">
    <fieldset>
      <legend>{{Informations}}</legend>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Catégorie}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="category" style="font-size : 1em"></span>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Type}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="type" style="font-size : 1em"></span>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{ID}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="id" style="font-size : 1em"></span>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Model}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="model" style="font-size : 1em"></span>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Non model}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="modelName" style="font-size : 1em"></span>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Version du logiciel}}</label>
        <div class="col-sm-2">
          <span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="softwareVersion" style="font-size : 1em"></span>
        </div>
      </div>
    </fieldset>
  </form>
</div>
</div>

<legend>Commandes</legend>
<table id="table_cmd" class="table table-bordered table-condensed">
  <thead>
    <tr>
      <th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
    </tr>
  </thead>
  <tbody>

  </tbody>
</table>

<form class="form-horizontal">
  <fieldset>
    <div class="form-actions">
      <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
      <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    </div>
  </fieldset>
</form>

</div>
</div>

<?php include_file('desktop', 'philipsHue', 'js', 'philipsHue');?>
<?php include_file('core', 'plugin.template', 'js');?>
