<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('philipsHue');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_syncEqLogic">
				<i class="fas fa-sync-alt"></i>
				<br/>
				<span>{{Synchroniser}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes Philips Hue}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				if ($eqLogic->getImgFilePath() !== false) {
					echo '<img class="lazy" src="plugins/philipsHue/core/config/devices/' . $eqLogic->getImgFilePath() . '"/>';
				} else {
					echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				}
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default eqLogicAction btn-sm roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<div class="row">
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
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
											foreach (jeeObject::all() as $object) {
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
									<label class="col-lg-4 control-label"></label>
									<div class="col-lg-8">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-4 control-label">{{Toujours allumé}}</label>
									<div class="col-lg-2">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="alwaysOn" /></label>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<legend>{{Informations}}</legend>
						<form class="form-horizontal">
							<fieldset>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Modèle}}</label>
									<div class="col-sm-8">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device">
											<option value="">Aucun</option>
											<?php
											$groups = array();
											foreach (philipsHue::devicesParameters() as $key => $info) {
												echo '<option value="' . $key . '">[' . $key . '] ' . $info['name'] . '</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-3 control-label">{{Catégorie}}</label>
									<div class="col-lg-2">
										<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="category" style="font-size : 1em"></span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-3 control-label">{{Type}}</label>
									<div class="col-lg-2">
										<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="type" style="font-size : 1em"></span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-3 control-label">{{ID}}</label>
									<div class="col-lg-2">
										<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="id" style="font-size : 1em"></span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-3 control-label">{{Non model}}</label>
									<div class="col-lg-2">
										<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="modelName" style="font-size : 1em"></span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-3 control-label">{{Version du logiciel}}</label>
									<div class="col-lg-2">
										<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="softwareVersion" style="font-size : 1em"></span>
									</div>
								</div>
								<center>
									<img src="<?php echo $plugin->getPathImgIcon() ?>" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='<?php echo $plugin->getPathImgIcon() ?>'"/>
								</center>
							</fieldset>
						</form>
					</div>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<legend>Commandes</legend>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
		
	</div>
</div>

<?php include_file('desktop', 'philipsHue', 'js', 'philipsHue');?>
<?php include_file('core', 'plugin.template', 'js');?>
