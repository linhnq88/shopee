<?php
defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.package.template.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
$is_freelancer_plus = (DUP_PRO_License_U::getLicenseType() >= DUP_PRO_License_Type::Freelancer);

global $wp_version;
global $wpdb;

$nonce_action = 'duppro-template-edit';

$was_updated = false;
$package_template_id = isset($_REQUEST['package_template_id']) ? esc_html($_REQUEST['package_template_id']) : -1;
$package_templates = DUP_PRO_Package_Template_Entity::get_all();
$package_template_count = count($package_templates);

$view_state = DUP_PRO_UI_ViewState::getArray();
$ui_css_archive = (isset($view_state['dup-template-archive-panel']) && $view_state['dup-template-archive-panel']) ? 'display:block' : 'display:none';
$ui_css_install = (isset($view_state['dup-template-install-panel']) && $view_state['dup-template-install-panel']) ? 'display:block' : 'display:none';

if ($package_template_id == -1) {
	$package_template	 = new DUP_PRO_Package_Template_Entity();
} else {
	$package_template	 = DUP_PRO_Package_Template_Entity::get_by_id($package_template_id);
	DUP_PRO_LOG::traceObject("getting template $package_template_id", $package_template);
}

if (isset($_REQUEST['action'])) {
	check_admin_referer($nonce_action);
	if ($_REQUEST['action'] == 'save') {
		if (isset($_REQUEST['_database_filter_tables'])) {
			$package_template->database_filter_tables = implode(',', $_REQUEST['_database_filter_tables']);
		} else {
			$package_template->database_filter_tables = '';
		}

		$package_template->archive_filter_dirs	 = isset($_REQUEST['_archive_filter_dirs']) ? DUP_PRO_Archive::parseDirectoryFilter($_REQUEST['_archive_filter_dirs']) : '';
		$package_template->archive_filter_exts	 = isset($_REQUEST['_archive_filter_exts']) ? DUP_PRO_Archive::parseExtensionFilter($_REQUEST['_archive_filter_exts']) : '';
		$package_template->archive_filter_files	 = isset($_REQUEST['_archive_filter_files']) ? DUP_PRO_Archive::parseFileFilter($_REQUEST['_archive_filter_files']) : '';
        $package_template->filter_sites = !empty($_REQUEST['_mu_exclude']) ? $_REQUEST['_mu_exclude'] : '';

		DUP_PRO_LOG::traceObject('request', $_REQUEST);

		// Checkboxes don't set post values when off so have to manually set these
		$package_template->set_post_variables($_REQUEST);
		$package_template->save();
		$was_updated		 = true;
	} else if ($_REQUEST['action'] == 'copy-template') {
		$source_template_id = $_REQUEST['duppro-source-template-id'];

		if ($source_template_id != -1) {
			$package_template->copy_from_source_id($source_template_id);
			$package_template->save();
		}
	}
}

$installer_pass = (base64_decode($package_template->installer_opts_secure_pass)) ? base64_decode($package_template->installer_opts_secure_pass) : '';
$installer_cpnldbaction = isset($package_template->installer_opts_cpnl_db_action) ? $package_template->installer_opts_cpnl_db_action : 'create';
$uploads = wp_upload_dir();
$upload_dir = DUP_PRO_U::safePath($uploads['basedir']);
$content_path = defined('WP_CONTENT_DIR') ? DUP_PRO_U::safePath(WP_CONTENT_DIR) : '';
?>

<style>
    table.dpro-edit-toolbar select {float:left}
	table.form-table td {padding:2px;}
	table.form-table th {padding:5px; font-weight: normal}
    div#dpro-notes-add {float:right; margin:-4px 2px 4px 0;}
    div.dpro-template-general {margin:8px 0 10px 0}
    div.dpro-template-general label {font-weight: bold}
    div.dpro-template-general input, textarea {width:100%}
	b.dpro-hdr {display:block; font-size:14px;  margin:3px 0 10px 0; padding:3px 0 3px 0; border-bottom: 1px solid #dfdfdf}
	form#dpro-template-form textarea, input[type="text"], input[type="password"] {width:100%}

	/*ARCHIVE*/
	div#dup-exportdb-items-checked, div#dup-exportdb-items-off {min-height:275px;}
	div#dup-exportdb-items-checked {padding:0 5px 5px 5px; max-width:800px}
    textarea#_archive_filter_dirs {width:100%; height:75px}
    textarea#_archive_filter_files {width:100%; height:75px}
    input#_archive_filter_exts {width:100%}
    div.dup-quick-links {font-size:11px; float:right; display:inline-block; margin-bottom:2px; font-style:italic}
	table#dup-dbtables td {padding:2px;vertical-align: top}
	ul#parsley-id-multiple-_database_filter_tables {display:none}

    /* Multisite */
    table.mu-mode td {padding: 10px}
    table.mu-opts td {padding: 10px}
    select.mu-selector {height:175px !important; width:300px}
    button.mu-push-btn {padding: 5px; width:40px; font-size:14px}

	/*INSTALLER */
	div.tabs-panel {max-height:350px !important}
	ul.add-menu-item-tabs li, ul.category-tabs li {padding:3px 30px 5px}
	div.secure-pass-area {display:none}
	input#_installer_opts_secure_pass, input#_installer_opts_secure_pass2{width:300px; margin: 3px 0 5px 0}
	label.secure-pass-lbl {display:inline-block; width:125px}
	div#dup-template-install-panel div.tabs-panel{min-height:150px}
</style>


<form id="dpro-template-form" data-parsley-validate data-parsley-ui-enabled="true" action="<?php echo $edit_template_url; ?>" method="post">
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="dpro-template-form-action" name="action" value="save">
<input type="hidden" name="package_template_id" value="<?php echo $package_template->id; ?>">

<!-- ====================
SUB-TABS -->
<?php if ($was_updated) : ?>
	<div class="notice notice-success is-dismissible dpro-wpnotice-box"><p><?php DUP_PRO_U::_e('Template Updated'); ?></p></div>
<?php endif; ?>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
	<tr>
		<td>
			<?php if ($package_template_count > 0) : ?>
				<select name="duppro-source-template-id">
					<option value="-1" selected="selected"><?php _e("Copy From"); ?></option>
					<?php foreach ($package_templates as $copy_package_template) :
						if($copy_package_template->id != $package_template->id) : ?>
						<option value="<?php echo $copy_package_template->id ?>"><?php echo $copy_package_template->name; ?></option>
					<?php
						endif;
						endforeach;
					?>
				</select>
				<input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Template.Copy()">
			<?php else : ?>
				<select disabled="disabled"><option value="-1" selected="selected"><?php _e("Copy From"); ?></option></select>
				<input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Template.Copy()"  disabled="disabled">
			<?php endif; ?>
		</td>
		<td>
			<div class="btnnav">
				<a href="<?php echo $templates_tab_url; ?>" class="add-new-h2"><i class="fa fa-clone"></i> <?php DUP_PRO_U::_e('Templates'); ?></a>
				<?php if ($package_template_id == -1) : ?>
					<span><?php DUP_PRO_U::_e('Add New') ?></span>
				<?php else : ?>
					<a href="admin.php?page=duplicator-pro-tools&tab=templates&inner_page=edit" class="add-new-h2"><?php DUP_PRO_U::_e("Add New"); ?></a>
				<?php endif; ?>
			</div>
		</td>
	</tr>
</table>
<hr class="dpro-edit-toolbar-divider"/>

<div class="dpro-template-general">
	<label><?php _e("Package Name"); ?>:</label>

	<input type="text" id="template-name" name="name" data-parsley-errors-container="#template_name_error_container" data-parsley-required="true" value="<?php echo $package_template->name; ?>" autocomplete="off">
	<div id="template_name_error_container" class="duplicator-error-container"></div>

	<label><?php _e("Notes"); ?>:</label> <br/>
	<textarea id="template-notes" name="notes" style="height:50px"><?php echo $package_template->notes; ?></textarea>
</div>

<!-- ===============================
ARCHIVE -->
<div class="dup-box">
<div class="dup-box-title">
	<i class="fa fa-file-archive-o"></i> <?php DUP_PRO_U::_e('Archive') ?>
	<div class="dup-box-arrow"></div>
</div>
<div class="dup-box-panel" id="dup-template-archive-panel" style="<?php echo $ui_css_archive ?>">

	<!-- =================
	FILES -->
	<b class="dpro-hdr"><i class="fa fa-files-o"></i> <?php DUP_PRO_U::_e('FILES'); ?></b>

	<input id="archive_export_onlydb" type="checkbox" <?php DUP_PRO_UI::echoChecked($package_template->archive_export_onlydb) ?> name="archive_export_onlydb"   onclick="DupPro.Template.ExportOnlyDB()"  />
	<label for="archive_export_onlydb"><?php _e("Archive Only the Database"); ?></label> <br/>

	<div id="dup-exportdb-items-off">

		<input id="archive_filter_on" type="checkbox" <?php DUP_PRO_UI::echoChecked($package_template->archive_filter_on) ?> name="archive_filter_on" />
		<label for="archive_filter_on"><?php _e("Enable File Filter"); ?></label>
		<br/>

		<label><?php _e("Directories"); ?>:</label>
		<div class='dup-quick-links'>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">[<?php DUP_PRO_U::_e("root path") ?>]</a>
			<?php if (! empty($content_path)) :?>
				<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo rtrim(WP_CONTENT_DIR, '/'); ?>')">[<?php DUP_PRO_U::_e("wp-content") ?>]</a>
			<?php endif; ?>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo rtrim($upload_dir, '/'); ?>')">[<?php DUP_PRO_U::_e("wp-uploads") ?>]</a>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo DUP_PRO_U::safePath(WP_CONTENT_DIR); ?>/cache')">[<?php DUP_PRO_U::_e("cache") ?>]</a>
			<a href="javascript:void(0)" onclick="jQuery('#_archive_filter_dirs').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
		</div>
		<textarea name="_archive_filter_dirs" id="_archive_filter_dirs" placeholder="/full_path/exclude_path1;/full_path/exclude_path2;">
			<?php echo str_replace(";", ";\n", esc_textarea($package_template->archive_filter_dirs)) ?>
		</textarea>
		<br/>

		<label><?php _e("Extensions"); ?>:</label>
		<div class='dup-quick-links'>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeExts('avi;mov;mp4;mpeg;mpg;swf;wmv;aac;m3u;mp3;mpa;wav;wma')">[<?php DUP_PRO_U::_e("media") ?>]</a>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeExts('zip;rar;tar;gz;bz2;7z')">[<?php DUP_PRO_U::_e("archive") ?>]</a>
			<a href="javascript:void(0)" onclick="jQuery('#_archive_filter_exts').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
		</div>
		<input type="text" name="_archive_filter_exts" id="_archive_filter_exts" value="<?php echo $package_template->archive_filter_exts; ?>" placeholder="ext1;ext2;ext3">
		<br/>

		<label><?php _e("Files"); ?>:</label>
		<div class='dup-quick-links'>
			<a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeFilePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">[<?php DUP_PRO_U::_e("file path") ?>]</a>
			<a href="javascript:void(0)" onclick="jQuery('#_archive_filter_files').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
		</div>
		<textarea name="_archive_filter_files" id="_archive_filter_files" placeholder="/full_path/exclude_file_1.ext;/full_path/exclude_file2.ext"><?php echo str_replace(";", ";\n", esc_textarea($package_template->archive_filter_files)) ?></textarea>
	</div>
	<br/>

	<!-- DB ONLY ENABLED -->
	<div id="dup-exportdb-items-checked">
		<?php
			DUP_PRO_U::_e("<b>Overview:</b><br/> This advanced option excludes all files from the archive.  Only the database and a copy of the installer.php "
			. "will be included in the archive.zip file. The option can be used for backing up and moving only the database.");

			echo '<br/><br/>';

			DUP_PRO_U::_e("<b><i class='fa fa-exclamation-circle'></i> Notice:</b><br/>  Installing only the database over an existing site may have unintended consequences.  "
			 . "Be sure to know the state of your system before installing the database without the associated files. ");

			echo '<br/><br/>';

			DUP_PRO_U::_e("For example, if you have WordPress 4.6 on this site and you copy this sites database to a host that has WordPress 4.8 files then the source code of the files "
				. " will not be in sync with the database causing possible errors.");

			echo '<br/><br/>';

			DUP_PRO_U::_e("This can also be true of plugins and themes.   When moving only the database be sure to know the database will be compatible with ALL source code files."
			. "  Please use this advanced feature with caution!");
		?>
		<br/><br/>
	</div>

	<!-- =================
	DATABASE -->
	<b class="dpro-hdr"><i class="fa fa-table"></i> <?php DUP_PRO_U::_e('DATABASE'); ?></b>
	<input type="checkbox" id="_datbase_filter_on" <?php DUP_PRO_UI::echoChecked($package_template->database_filter_on) ?> name="_database_filter_on" />
	<label for="_datbase_filter_on"><?php DUP_PRO_U::_e("Enable Table Filters"); ?></label>
	<i class="fa fa-question-circle"
		data-tooltip-title="<?php DUP_PRO_U::_e("Database Table Filters:"); ?>"
		data-tooltip="<?php DUP_PRO_U::_e('Checked tables will not be added to the database script.  Excluding certain tables can possibly cause your site or plugins to not work correctly after install!'); ?>">
	</i><br/><br/>

	<div id="dup-db-filter-items">
		<a href="javascript:void(0)" id="dball" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', true).trigger('click');">[ <?php DUP_PRO_U::_e('Include All'); ?> ]</a> &nbsp;
		<a href="javascript:void(0)" id="dbnone" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', false).trigger('click');">[ <?php DUP_PRO_U::_e('Exclude All'); ?> ]</a>
		<div style="font-family: Calibri; white-space: nowrap">
			<?php
			$tables = $wpdb->get_results("SHOW FULL TABLES FROM `" . DB_NAME . "` WHERE Table_Type = 'BASE TABLE' ", ARRAY_N);

			$num_rows = count($tables);
			echo '<table id="dup-dbtables"><tr><td>';
			$next_row = round($num_rows / 3, 0);
			$counter = 0;
			$tableList = explode(',', $package_template->database_filter_tables);
			foreach ($tables as $table) {
				if (in_array($table[0], $tableList)) {
					$checked = 'checked="checked"';
					$css	 = 'text-decoration:line-through';
				} else {
					$checked = '';
					$css	 = '';
				}
				echo "<label for='_database_filter_tables-{$table[0]}' style='{$css}'>".
				"<input class='checkbox dbtable' $checked type='checkbox' name='_database_filter_tables[]' id='_database_filter_tables-{$table[0]}' value='{$table[0]}' onclick='DupPro.Template.ExcludeTable(this)' />&nbsp;{$table[0]}".
				"</label><br />";
				$counter++;
				if ($next_row <= $counter) {
					echo '</td><td valign="top">';
					$counter = 0;
				}
			}
			echo '</td></tr></table>';
			?>
		</div><br/>
	</div>

	<?php DUP_PRO_U::_e("Compatibility Mode"); ?>
	<i class="fa fa-question-circle"
		data-tooltip-title="<?php DUP_PRO_U::_e("Legacy Support:"); ?>"
		data-tooltip="<?php DUP_PRO_U::_e('This option is not available as a template setting.  It can only be used when creating a new package.  Please see the FAQ for a full overview of using this feature.'); ?>">
	</i><br/>
	<i><?php
			$url = "<a href='https://snapcreek.com/duplicator/docs/faqs-tech/#faq-trouble-090-q' target='_blank'>" . DUP_PRO_U::__('FAQ details') . "</a>";
			DUP_PRO_U::_e(sprintf("Not enabled for template settings. Please see the full %s", $url) );
		?>
	</i>

	<!-- For now not including in filters since don't want to encourage use with schedules since filtering creates incomplete multisite -->
	<?php if(false && is_multisite() && (DUP_PRO_License_U::getLicenseType() === DUP_PRO_License_Type::BusinessGold)) : ?>
	<!-- ===================
	 MULTI-SITE:  -->
	<div style="margin-top: 30px">

		<b class="dpro-hdr"><i class="fa fa-columns"></i> <?php DUP_PRO_U::_e('MULTISITE'); ?></b>

		<table class="mu-opts">
			<tr>
				<td>
					<b><?php DUP_PRO_U::_e("Excluded Sub-Sites"); ?>:</b><br/>
					<select name="_mu_exclude[]" id="mu-exclude" multiple="true" class="mu-selector">
						<?php

						foreach($package_template->filter_sites as $site_id){
							$site_details = get_blog_details($site_id);
							echo "<option value='{$site_id}'>{$site_details->blogname}</option>";
						}
						?>
					</select>
				</td>
				<td>
					<button type="button" id="mu-include-btn" class="mu-push-btn"><i class="fa fa-chevron-right"></i></button><br/>
					<button type="button" id="mu-exclude-btn" class="mu-push-btn"><i class="fa fa-chevron-left"></i></button>
				</td>
				<td>
					<b><?php DUP_PRO_U::_e("Included Sub-Sites"); ?>:</b><br/>
					<select name="_mu_include[]" id="mu-include" multiple="true" class="mu-selector">
						<?php
						$sites = DUP_PRO_MU::getSubsites();
						foreach($sites as $site) {
							if(!in_array($site->id, $package_template->filter_sites)) {
								  echo "<option value='{$site->id}'>{$site->name}</option>";
							}
						}
						?>
					</select>
				</td>
			</tr>
		</table>

		<div class="dpro-panel-optional-txt" style="text-align: left">
			<?php DUP_PRO_U::_e("This section allows you to control which sub-sites of a multisite network you want to include within your package.  The 'Included Sub-Sites' will also be available to choose from at install time."); ?> <br/>
			<?php DUP_PRO_U::_e("By default all packages are include.  The ability to exclude sub-sites are intended to help shrink your package if needed."); ?>
		</div>
	</div>
	<?php endif; ?>
</div>
</div><br />


<!-- ===============================
INSTALLER -->
<div class="dup-box">
<div class="dup-box-title">
	<i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e('Installer') ?>
	<div class="dup-box-arrow"></div>
</div>
<div class="dup-box-panel" id="dup-template-install-panel" style="<?php echo $ui_css_install ?>">

	<div class="dpro-panel-optional-txt">
		<b><?php DUP_PRO_U::_e('All values in this section are'); ?> <u><?php DUP_PRO_U::_e('optional'); ?></u>.</b> <br/>
		<?php DUP_PRO_U::_e("These fields can be pre-filled at install time but are not required here."); ?>
	</div>

	<table>
		<tr>
			<td style="vertical-align: top"><b><?php DUP_PRO_U::_e("Security") ?></b></td>
			<td>
			<?php if($is_freelancer_plus) : ?>
				<input type="checkbox" name="_installer_opts_secure_on" id="_installer_opts_secure_on" <?php echo ($package_template->installer_opts_secure_on) ? "checked='checked'" : ""; ?> onclick="DupPro.Template.ToggleInstallerPassword()" />
				<label for="_installer_opts_secure_on"><?php DUP_PRO_U::_e("Enable Password Protection") ?></label>
				<i class="fa fa-question-circle"
				   data-tooltip-title="<?php DUP_PRO_U::_e("Password Protection:"); ?>"
				   data-tooltip="<?php DUP_PRO_U::_e('Enabling this option will allow for basic password protection on the installer. Before running the installer the '
						   . 'password below must be entered before proceeding with an install.  This password is a general deterrent and should not be substituted for properly '
						   . 'keeping your files secure.'); ?>"></i>
				<br/>
				<div class="secure-pass-area">
					<label class="secure-pass-lbl"><?php DUP_PRO_U::_e("Password") ?>:</label>
					<input type="password" name="_installer_opts_secure_pass" id="_installer_opts_secure_pass" value="<?php echo $installer_pass ?>"  maxlength="50" /><br/>
					<label class="secure-pass-lbl"><?php DUP_PRO_U::_e("Confirm") ?>:</label>
					<input type="password" name="_installer_opts_secure_pass2" id="_installer_opts_secure_pass2" value="<?php echo $installer_pass ?>"  maxlength="50" />
				</div><br/>
			<?php else : ?>
				<input type="checkbox" name="_installer_opts_secure_on" id="_installer_opts_secure_on" style="display:none" />
				<input type="hidden" name="_installer_opts_secure_pass" id="_installer_opts_secure_pass" value="" />
				<label>
					<a href="https://snapcreek.com/duplicator/docs/faqs/#faq-presale-035-q" target="_blank" class="upgrade-link"><?php DUP_PRO_U::_e("Enable Password Protection");?></a>
				</label>
				<i class="fa fa-question-circle"
					data-tooltip-title="<?php DUP_PRO_U::_e("Installer Password:"); ?>"
					data-tooltip="<?php DUP_PRO_U::_e('The install has the ability to be password protected.  This feature requires Freelancer and above licese to enable.'); ?>">
				 </i>
			<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td style="width:130px"><b><?php DUP_PRO_U::_e("Branding") ?></b></td>
			<td>
				<?php
					$brands = DUP_PRO_Brand_Entity::get_all();
					if($is_freelancer_plus) :
				?>
					<select name="installer_opts_brand" id="installer_opts_brand" onchange="DupPro.Template.BrandChange();">
						<?php
						$active_brand_id = 0;
						foreach ($brands as $i=>$brand) :
							if($brand->active) $active_brand_id = $brand->id;
						?>
							<option value="<?php echo $brand->id; ?>" title="<?php echo esc_attr(esc_html($brand->notes)); ?>"<?php echo ((isset($_REQUEST['inner_page']) && $_REQUEST['inner_page'] == 'edit') ? $package_template->installer_opts_brand : $brand->active)==$brand->id ? ' selected' : ''; ?>>
								<?php echo $brand->name; ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php
					$preview_url = array(
						get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default" ),
						get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id={$active_brand_id}" )
					);
					?>
					<a href="<?php echo $preview_url[$active_brand_id > 0 ? 1 : 0]; ?>" target="_blank" class="button" id="brand-preview"><?php DUP_PRO_U::_e("Preview"); ?></a> &nbsp;
					<i class="fa fa-question-circle"
					   data-tooltip-title="<?php DUP_PRO_U::_e("Choose Brand:"); ?>"
					   data-tooltip="<?php DUP_PRO_U::_e('This option changes the branding of the installer file.  Click the preview button to see the selected style.'); ?>"></i>
				<?php else : ?>
					<a href="admin.php?page=duplicator-pro-settings&tab=package&sub=brand" class="upgrade-link"><?php DUP_PRO_U::_e("Enable Branding"); ?></a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<br/>


	<!-- ===================
	STEP1 TABS -->
	<div data-dpro-tabs="true">
		<ul>
			<li><?php DUP_PRO_U::_e('Basic') ?></li>
			<li id="dpro-cpnl-tab-lbl"><?php DUP_PRO_U::_e('cPanel') ?></li>
		</ul>

		<!-- ===================
		TAB1: Basic -->
		<div>
			 <table class="form-table">
				<tr>
					<td colspan="2">
						<b class="dpro-hdr"><?php DUP_PRO_U::_e('MySQL Server'); ?></b>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e("Host"); ?></th>
					<td><input type="text" placeholder="localhost" name="installer_opts_db_host" value="<?php echo $package_template->installer_opts_db_host; ?>"></td>
				</tr>
				<tr valign="top">
					<th><label><?php _e("Database"); ?></label></th>
					<td><input type="text" placeholder="<?php DUP_PRO_U::_e('valid database name'); ?>" name="installer_opts_db_name" value="<?php echo $package_template->installer_opts_db_name; ?>"></td>
				</tr>
				<tr valign="top">
					<th><label><?php _e("User"); ?></label></th>
					<td><input type="text" placeholder="<?php DUP_PRO_U::_e('valid database user'); ?>" name="installer_opts_db_user" value="<?php echo $package_template->installer_opts_db_user; ?>"></td>
				</tr>
			</table>
		</div>

		<!-- ===================
		TAB2: cPanel -->
		<div style="height:550px !important">
			<table class="form-table">
				<tr valign="top">
					<td colspan="2"><b class="dpro-hdr"><?php DUP_PRO_U::_e('cPanel Login'); ?></b></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php DUP_PRO_U::_e("Automation"); ?></label></th>
					<td>
						<input type="checkbox" name="installer_opts_cpnl_enable" id="installer_opts_cpnl_enable" <?php DUP_PRO_UI::echoChecked($package_template->installer_opts_cpnl_enable); ?> >
						<label for="installer_opts_cpnl_enable">Auto Select cPanel</label>
						<i class="fa fa-question-circle" data-tooltip-title="Auto Select cPanel:" data-tooltip="<?php DUP_PRO_U::_e('Enabling this options will automatically select the cPanel tab when step one of the installer is shown.');?>" ></i>
							&nbsp; &nbsp;
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php DUP_PRO_U::_e("Host"); ?></label></th>
					<td><input type="text" name="installer_opts_cpnl_host" value="<?php echo $package_template->installer_opts_cpnl_host; ?>"  placeholder="<?php DUP_PRO_U::_e('valid cpanel host address'); ?>"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php DUP_PRO_U::_e("User"); ?></label></th>
					<td><input type="text" name="installer_opts_cpnl_user" value="<?php echo $package_template->installer_opts_cpnl_user; ?>"  placeholder="<?php DUP_PRO_U::_e('valid cpanel user login'); ?>"></td>
				</tr>
				<tr>
					<td colspan="2">
						<b class="dpro-hdr"><?php DUP_PRO_U::_e('MySQL Server'); ?></b>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e("Action"); ?></label></th>
					<td>
						<select name="installer_opts_cpnl_db_action" id="cpnl-dbaction">
							<option value="create" <?php echo ($installer_cpnldbaction == 'create') ? 'selected' : ''; ?>>Create A New Database</option>
							<option value="empty"  <?php echo ($installer_cpnldbaction == 'empty')  ? 'selected' : ''; ?>>Connect to Existing Database and Remove All Data</option>
							<!--option value="rename">Connect to Existing Database and Rename Existing Tables</option-->
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e("Host"); ?></label></th>
					<td><input type="text" name="installer_opts_cpnl_db_host" value="<?php echo $package_template->installer_opts_cpnl_db_host; ?>" placeholder="<?php DUP_PRO_U::_e('localhost'); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e("Database"); ?></label></th>
					<td><input type="text" name="installer_opts_cpnl_db_name" value="<?php echo $package_template->installer_opts_cpnl_db_name; ?>" placeholder="<?php DUP_PRO_U::_e('valid database name'); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e("User"); ?></label></th>
					<td><input type="text" name="installer_opts_cpnl_db_user" value="<?php echo $package_template->installer_opts_cpnl_db_user; ?>" placeholder="<?php DUP_PRO_U::_e('valid database user'); ?>" /></td>
				</tr>
			</table>
		</div>
	</div><br/>
	<small><?php DUP_PRO_U::_e("All other inputs can be entered at install time.") ?></small>
	<br/><br/>

</div>
</div><br/>

<button class="button button-primary" type="submit"><?php DUP_PRO_U::_e('Save Template'); ?></button>
</form>

<?php
    $alert1 = new DUP_PRO_UI_Dialog();
    $alert1->title		= DUP_PRO_U::__('Transfer Error');
    $alert1->message	= DUP_PRO_U::__('You can\'t exclude all sites!');
    $alert1->initAlert();
?>

<script>
jQuery(document).ready(function($) {

	/* When installer brand changes preview button is updated */
	DupPro.Template.BrandChange = function()
	{
		var $brand	= $("#installer_opts_brand");
		var $id		= $brand.val();
		var $url    = new Array();

		<?php if(is_multisite()) : ?>
			$url = [
				'<?php echo network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default" ); ?>',
				'<?php echo network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id=" ); ?>' + $id ];
		<?php else: ?>
			$url = [
				'<?php echo get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default" ); ?>',
				'<?php echo get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id=" ); ?>' + $id ];
		<?php endif; ?>

		$("#brand-preview").attr( 'href', $url[ $id > 0 ? 1 : 0 ] );
	};

	/* Enables strike through on excluded DB table */
	DupPro.Template.ExcludeTable = function (check)
	{
		var $cb = $(check);
		if ($cb.is(":checked")) {
			$cb.closest("label").css('textDecoration', 'line-through');
		} else {
			$cb.closest("label").css('textDecoration', 'none');
		}
	}

	/* Enables visual for Database Only check */
	DupPro.Template.ExportOnlyDB = function ()
	{
		$('#dup-exportdb-items-off, #dup-exportdb-items-checked').hide();
		$("#archive_export_onlydb").is(':checked')
			? $('#dup-exportdb-items-checked').show()
			: $('#dup-exportdb-items-off').show();
	};

	/* Formats file directory path name on seperate line of textarea */
	DupPro.Template.AddExcludePath = function (path)
	{
		var text = $("#_archive_filter_dirs").val() + path + ';\n';
		$("#_archive_filter_dirs").val(text);
	};

	/* Appends a path to the extention filter  */
	DupPro.Template.AddExcludeExts = function (path)
	{
		var text = $("#_archive_filter_exts").val() + path + ';';
		$("#_archive_filter_exts").val(text);
	};

	/* Formats file path name on seperate line of textarea */
	DupPro.Template.AddExcludeFilePath = function (path)
	{
		var text = $("#_archive_filter_files").val() + path + '/file.ext;\n';
		$("#_archive_filter_files").val(text);
	};

	/* Used to duplicate a template */
	DupPro.Template.Copy = function()
	{
		$("#dpro-template-form-action").val('copy-template');
		$("#dpro-template-form").parsley().destroy();
		$("#dpro-template-form").submit();
	};

	/* Shows/Hides the password information */
	DupPro.Template.ToggleInstallerPassword = function ()
	{
		if ($('#_installer_opts_secure_on').is(':checked')) {
			$('.secure-pass-area').show();
			$('#_installer_opts_secure_pass, #_installer_opts_secure_pass2').attr('required', 'true');
			$('#_installer_opts_secure_pass').attr('data-parsley-equalto', '#_installer_opts_secure_pass2');
		} else {
			$('.secure-pass-area').hide();
			$('#_installer_opts_secure_pass, #_installer_opts_secure_pass2').removeAttr('required');
			$('#_installer_opts_secure_pass').removeAttr('data-parsley-equalto');
		}
	};

	//INIT
	$('#template-name').focus();
	$('#_archive_filter_dirs').val($('#_archive_filter_dirs').val().trim());
	//Default to cPanel tab if used
	$('#cpnl-enable').is(":checked") ? $('#dpro-cpnl-tab-lbl').trigger("click") : null;
	DupPro.Template.ToggleInstallerPassword();
	DupPro.Template.ExportOnlyDB();
	DupPro.Template.BrandChange();

    //MU-Transfer buttons
    $('#mu-include-btn').click(function() {
        return !$('#mu-exclude option:selected').remove().appendTo('#mu-include');
    });

    $('#mu-exclude-btn').click(function() {
        var include_all_count = $('#mu-include option').length;
        var include_selected_count = $('#mu-include option:selected').length;

        if(include_all_count > include_selected_count) {
            return !$('#mu-include option:selected').remove().appendTo('#mu-exclude');
		} else {
            <?php $alert1->showAlert(); ?>
        }
    });

});
</script>