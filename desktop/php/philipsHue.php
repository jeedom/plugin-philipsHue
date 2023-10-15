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
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br />
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_syncEqLogic">
				<i class="fas fa-sync-alt"></i>
				<br />
				<span>{{Synchroniser}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br />
				<span>{{Configuration}}</span>
			</div>
			<?php
			$jeedomVersion  = jeedom::version() ?? '0';
			$displayInfo = version_compare($jeedomVersion, '4.4.0', '>=');
			if ($displayInfo) {
				echo '<div class="cursor eqLogicAction info" data-action="createCommunityPost">';
				echo '<i class="fas fa-ambulance"></i><br>';
				echo '<span>{{Community}}</span>';
				echo '</div>';
			}
			?>
		</div>
		<legend><i class="far fa-lightbulb"></i> {{Mes Philips Hue}}</legend>
		<div class="input-group" style="margin:5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i>
				</a><a class="btn hidden roundedRight" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
			</div>
		</div>
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				if ($eqLogic->getImgFilePath() !== false) {
					echo '<img class="lazy" src="plugins/philipsHue/core/config/devices/' . $eqLogic->getImgFilePath() . '"/>';
				} else {
					echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				}
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hidden hiddenAsCard displayTableRight">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Équipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Équipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default eqLogicAction btn-sm roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- <div class="row">
				<div class="col-sm-6"> -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement philipsHue}}" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
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
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>
							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Modèle}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Sélectionner le modèle d'équipement Philips Hue à piloter}}"></i></sup>
								</label>
								<div class="col-sm-6">
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
								<label class="col-sm-4 control-label">{{Pont}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Pont Philips Hue sur lequel l'équipement est connecté}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" disabled data-l1key="configuration" data-l2key="bridge">
										<?php
										for ($i = 1; $i <= config::byKey('nbBridge', 'philipsHue'); $i++) {
											echo '<option value="' . $i . '">{{Pont}} ' . $i . '</option>';
										}
										?>
									</select>
								</div>
							</div>
							<!-- </fieldset>
						</form> -->
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="category" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Identifiant}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="id" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom du modèle}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="modelName" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Version du logiciel}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="softwareVersion" style="font-size : 1em"></span>
								</div>
							</div>
							<br>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Visuel}}</label>
								<div class="col-sm-7">
									<center>
										<img src="<?php echo $plugin->getPathImgIcon() ?>" data-original=".jpg" id="img_device" class="img-responsive" style="max-width:160px;" onerror="this.src='<?php echo $plugin->getPathImgIcon() ?>'" />
									</center>
								</div>
							</div>
						</div>

					</fieldset>
				</form>
				<hr>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br />
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="width:450px;">{{Nom}}</th>
								<th style="width:150px;">{{Type}}</th>
								<th style="min-width:350px">{{Logical ID}}</th>
								<th>{{Options}}</th>
								<th>{{Valeur}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	</div>
</div>

<?php include_file('desktop', 'philipsHue', 'js', 'philipsHue'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>