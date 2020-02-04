<?php
defined("ABSPATH") or die("");

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.system.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.package.pagination.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.dialog.php');

$is_freelancer_plus = (DUP_PRO_License_U::getLicenseType() >= DUP_PRO_License_Type::Freelancer);
$display_brand = false;

if (isset($_REQUEST['create_from_temp'])) {
    //Takes temporary package and inserts it into the package table
	$package = DUP_PRO_Package::get_temporary_package(false);
	if ($package != null) {
		$package->save();
	}
	unset($_REQUEST['create_from_temp']);
}

$system_global = DUP_PRO_System_Global_Entity::get_instance();

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'stop-build') {
		$package_id		 = (int) $_REQUEST['action-parameter'];
		DUP_PRO_LOG::trace("stop build of $package_id");
		$action_package	 = DUP_PRO_Package::get_by_id($package_id);
		if ($action_package != null) {
			DUP_PRO_LOG::trace("set $action_package->ID for cancel");
			$action_package->set_for_cancel();
		} else {
			DUP_PRO_LOG::trace("could not find package so attempting hard delete. Old files may end up sticking around although chances are there isnt much if we couldnt nicely cancel it.");
			$result = DUP_PRO_Package::force_delete($package_id);
			($result) ? DUP_PRO_LOG::trace("Hard delete success") : DUP_PRO_LOG::trace("Hard delete failure");
		}
	} else if ($_REQUEST['action'] == 'clear-messages') {
		$system_global->clear_recommended_fixes();
		$system_global->save();
	}
}

$pending_cancelled_package_ids = DUP_PRO_Package::get_pending_cancellations();
$qryResult			= $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}duplicator_pro_packages` ORDER BY id DESC", ARRAY_A);
$qryStatusActive	= $wpdb->get_results("SELECT status FROM `{$wpdb->base_prefix}duplicator_pro_packages` WHERE status < 100 and status > 0", ARRAY_A);
$totalElements		= count($qryResult);
$statusActive		= count($qryStatusActive);

//Report All Packages sizes:
$totalSize = 0;
foreach ($qryResult as $row) {
	$Package = DUP_PRO_Package::get_from_json($row['package']);
	if (is_object($Package)) {
		$pack_archive_size	 = $Package->Archive->Size;
	} else {
		$pack_archive_size	 = 0;
	}
	$totalSize = $totalSize + $pack_archive_size;
}


$pager		= new DUP_PRO_Package_Pagination();
$per_page	= $pager->get_per_page();
if ($totalElements > $per_page) {
	$current_page	= ($statusActive >= 1) ? 1 : $pager->get_pagenum();
	$offset			= ( $current_page - 1 ) * $per_page;
	$qryResult		= $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}duplicator_pro_packages` ORDER BY id DESC LIMIT ${offset}, ${per_page} ", ARRAY_A);
}

$global = DUP_PRO_Global_Entity::get_instance();
$active_package_present = DUP_PRO_Package::is_active_package_present();

$orphan_info = DUP_PRO_Server::getOrphanedPackageInfo();
$orphan_display_msg = ($orphan_info['count'] > 3   ? 'display: block' : 'display: none');

$recommended_text_fix_present = false;
$user_id = get_current_user_id();
$package_ui_created = is_numeric(get_user_meta($user_id,'duplicator_pro_created_format',true)) ? get_user_meta($user_id,'duplicator_pro_created_format',true) : 1; //Old option was $global->package_ui_created

if (count($system_global->recommended_fixes) > 0) {
	foreach ($system_global->recommended_fixes as $fix) {
		/* @var $fix DUP_PRO_Recommended_Fix */
		if (in_array($fix->recommended_fix_type, array(
            DUP_PRO_Recommended_Fix_Type::Text,
            DUP_PRO_Recommended_Fix_Type::QuickFix
        ), true) !== false) {
			$recommended_text_fix_present = true;
		}
	}
}

if (isset($_GET['dpro_show_error'])) {
	$recommended_text_fix_present = true;
	// $system_global->add_recommended_text_fix('Test Error', 'Test fix recommendation');

    $system_global->add_recommended_quick_fix('Activate DUP Archive', 'TEST: Switch to <i><b>DUP</b></i> archive. Click on button to fix this!', 'global : { archive_build_mode:3}');

    $system_global->add_recommended_quick_fix('Activate ZIP Archive', 'TEST: Switch to <i><b>ZIP</b></i> archive. Click on button to fix this!', 'global : { archive_build_mode:2}');

    $system_global->add_recommended_quick_fix('Activate SHELL Archive', 'TEST: Switch to <i><b>Shell ZIP</b></i> archive. Click on button to fix this!', 'global : { archive_build_mode:1}');
    //special:{stuck_5percent_pending_fix:1}
    $system_global->add_recommended_quick_fix('Test Fix', 'Let\'s fix something special' , 'special:{stuck_5percent_pending_fix:1}');
}

$max_pack_store = isset($global->max_default_store_files) ? $global->max_default_store_files : 0;
$delete_nonce = wp_create_nonce('duplicator_pro_package_delete');
$gift_nonce = wp_create_nonce('DUP_PRO_CTRL_Package_toggleGiftFeatureButton');
?>

<style>
    a.disabled { color:gray; }
    a.disabled:hover { color: gray!important; background:#e0e0e0 !important;}
    input#dpro-chk-all {margin:0;padding:0 0 0 5px;}
    button.dpro-btn-selected {border:1px solid #000 !important; background-color:#dfdfdf !important;}
    div.dpro-build-msg {padding:10px; border:1px solid #e5e5e5; border-radius: 3px; margin:0 0 0 0; text-align: center; font-size: 14px; line-height:20px;}
    div.dpro-build-msg button {display:block; margin-top:10px !important; font-weight:bold;}
	div.dpro-build-msg div.status-hdr {font-size:18px; font-weight:bold}
	button.dpro-btn-stop {width:150px !important}
	.remote-data-pass{position:relative;}
	.remote-data-fail{position:relative;}
	.remote-data-pass:after {content:attr(data-badge); position:absolute; top:-5px; right:-8px; font-size:.5em; background:#8CB9E6; width:6px; height:6px; border-radius:50%;}
	.remote-data-fail:after {content:attr(data-badge); position:absolute; top:-5px; right:-8px; font-size:.5em; background:#BB1506; width:6px; height:6px; border-radius:50%;}
	.error-icon {color:#BB1506}
	div.dpro-quick-start {font-style:italic; font-size: 13px; line-height: 18px; margin-top: 15px}
	 
	 /*Auto configuration*/
	 ul.dpro-auto-conf {margin-top:0; list-style-type:none}
	 ul.dpro-auto-conf li {margin-left:15px}

    /* Table package details */
    table.dpro-pktbl td.dpro-list-nopackages {text-align:center; padding:50px 0 80px 0; font-size:20px}
    table.dpro-pktbl {word-break:break-all;}
	table.dpro-pktbl tfoot th{font-size:12px}
    table.dpro-pktbl th {white-space:nowrap !important;}
    table.dpro-pktbl td.pack-name {width:100%}
    table.dpro-pktbl input[name="delete_confirm"] {margin-left:15px}
    table.dpro-pktbl td.run {border-left:4px solid #608E64;}
    table.dpro-pktbl td.fail {border-left:4px solid #d54e21;}
    table.dpro-pktbl td.pass {border-left:4px solid #2ea2cc;}
    table.dpro-pktbl div#dpro-progress-bar-area {width:300px; margin:5px auto 0 auto;}
	div.dpro-paged-nav {text-align:right}
	/* Table package rows */
    tr.dpro-pkinfo td {white-space:nowrap; padding:8px 20px 10px 10px; min-height:20px; vertical-align:middle}
	tr.dpro-pkinfo td sup {font-size:11px; vertical-align: baseline; position: relative; top: -0.6em; font-style:italic;}
	tr.dpro-pkinfo td div.progress-error {font-size:13px; color:#555;}
    tr.dpro-pkinfo td.get-btns {text-align:center; padding:3px 8px 5px 0 !important; white-space:nowrap;}
	tr.dpro-pkinfo td.get-btns button {width:100px; padding:0; margin:2px 0 0 0; box-shadow:none}
	tr.dpro-pkinfo td.get-btns-transfer {text-align:center; padding:3px 8px 5px 0 !important; white-space:nowrap;}
	tr.dpro-pkinfo td.get-btns-transfer button {width:75px; padding:0; margin:2px 0 0 0}
	button.dpro-store-btn {width:35px !important} 
	div#dpro-error-orphans { <?php echo $orphan_display_msg; ?> }
	div.dpro-pack-status-info {float:left; font-style:italic; font-size:11px;}
	div.dpro-dlg-remote-endpoints span {font-size:13px}

	/*Download button menu*/
	nav.dpro-dnload-menu {display:inline-block;}
	nav.dpro-dnload-menu-items {display:none; position:absolute; z-index:1000; padding:7px; border:1px solid #999; border-radius:4px; background-color:#fff; min-width:125px; text-align:left; line-height:30px;}
	nav.dpro-dnload-menu-items div{padding:2px 2px 2px 8px}
	nav.dpro-dnload-menu-items div:hover{background-color:#efefef; border-radius:4px; cursor:pointer}

	/*Hamburger button menu*/
	nav.dpro-bar-menu {display:inline-block;}
	nav.dpro-bar-menu-items {display:none; margin:0 -105px; position:absolute; z-index:1000; padding:7px; border:1px solid #999; border-radius:4px; background-color:#fff; min-width:125px; text-align:left; line-height:30px;}
	nav.dpro-bar-menu-items div{padding:2px 2px 2px 8px}
	nav.dpro-bar-menu-items div:hover{background-color:#efefef; border-radius:4px; cursor:pointer}

    #btn-logs-gift{background-color: #af5e52;color:#fff;display:none}
    #btn-logs-gift:hover{border-color: #222;}
</style>

<div id='dpro-error-orphans' class="error">
	<p>
		<?php
			$orphan_msg  = DUP_PRO_U::__('There are currently (%1$s) orphaned package files taking up %2$s of space.  These package files are no longer visible in the packages list below and are safe to remove.') . '<br/>';
			$orphan_msg .= DUP_PRO_U::__('Go to: Tools > Diagnostics > Stored Data > look for the [Delete Package Orphans] button for more details.') . '<br/>';
			$orphan_msg .= '<a href=' . self_admin_url('admin.php?page=duplicator-pro-tools&tab=diagnostics') . '>' . DUP_PRO_U::__('Take me there now!') . '</a>';
			printf($orphan_msg,	$orphan_info['count'], DUP_PRO_U::byteSize($orphan_info['size']) );
		?>
		<br/>
	</p>
</div>

<form id="form-duplicator" method="post">
<input type="hidden" id="action" name="action" />
<input type="hidden" id="action-parameter" name="action-parameter" />
<?php wp_nonce_field( 'dpro_package_form_nonce' ); ?>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
	<tr>
		<td>
			<select id="dup-pack-bulk-actions">
				<option value="-1" selected="selected"><?php DUP_PRO_U::_e("Bulk Actions") ?></option>
				<option value="delete" title="<?php DUP_PRO_U::_e("Delete selected package(s)") ?>"><?php DUP_PRO_U::_e("Delete") ?></option>
			</select>
			<input type="button" id="dup-pack-bulk-apply" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Pack.ConfirmDelete()">
			<span class="btn-separator"></span>

			<a href="javascript:void(0)" class="button  grey-icon" title="<?php DUP_PRO_U::_e("Get Help") ?>" onclick="jQuery('#contextual-help-link').trigger('click')">
				<i class="fa fa-question-circle grey-icon"></i>
			</a>
			<a href="admin.php?page=duplicator-pro-settings&tab=package" class="button grey-icon" title="<?php DUP_PRO_U::_e("Settings") ?>"><i class="fa fa-gear"></i></a>
			<a href="admin.php?page=duplicator-pro-tools&tab=templates" class="button" title="<?php DUP_PRO_U::_e("Templates") ?>"><i class="fa fa-clone"></i></a>
			<?php if ($global->profile_idea) : ?>
				<a href="admin.php?page=duplicator-pro-tools&tab=import" id="btn-logs-dialog" class="button grey-icon" title="<?php DUP_PRO_U::_e("Import") ?>"><i class="fa fa-download"></i></a>
			<?php endif; ?>
			<?php
         
            // DISPLAY GIFT BUTTON
            if (defined('DUPLICATOR_PRO_GIFT_THIS_RELEASE') && DUPLICATOR_PRO_GIFT_THIS_RELEASE === true) :
                if(is_null($global->dupHidePackagesGiftFeatures) ? true : (!DUPLICATOR_PRO_GIFT_THIS_RELEASE === $global->dupHidePackagesGiftFeatures)) :
            ?>
			<a href="javascript:void(0);" class="button gift-icon" id="btn-logs-gift" title="<?php DUP_PRO_U::_e("New Features!") ?>"><i class="fa fa-gift"></i> <?php DUP_PRO_U::_e("New!") ?></a>
            <?php
                endif;
            endif;
            ?>
		</td>
		<td>
			<div class="btnnav">
			<span><i class="fa fa-archive"></i> <?php _e("Packages"); ?></span>
			<a id="dup-pro-create-new" onClick="return DupPro.Pack.CreateNew(this);" href="<?php echo $edit_package_url; ?>" class="add-new-h2 <?php echo ($active_package_present ? 'disabled' : ''); ?>"><?php DUP_PRO_U::_e('Create New'); ?></a>
			</div>
		</td>
	</tr>
</table>

<div id="dup-pro-fixes" class="error" style="display: <?php echo $recommended_text_fix_present ? 'block' : 'none' ?>">
<?php
	if ($recommended_text_fix_present) {
		echo '<p>';
		echo '<b style="font-size:18px">'.DUP_PRO_U::__('Duplicator Pro').' </b><br/>';
		echo '<b>'.DUP_PRO_U::__('Configuration Error(s) Detected:').' </b>';
		echo DUP_PRO_U::_e('Please perform the following actions below then build package again.');
		echo '</p>';
		echo '<ul class="dpro-auto-conf">';
        $is_quick_fix = false;
		foreach ($system_global->recommended_fixes as $fix) {
			if ($fix->recommended_fix_type == DUP_PRO_Recommended_Fix_Type::Text) {
				echo "<li><i class='fa fa-question-circle' data-tooltip='{$fix->error_text}'></i>&nbsp; {$fix->parameter1} </li>";
			}
            else if ($fix->recommended_fix_type == DUP_PRO_Recommended_Fix_Type::QuickFix) {
                $is_quick_fix = true;
				echo "<li id='quick-fix-{$fix->id}' class='quick-fix-list'>"
                . "<table width='100%' id='quick-fix-{$fix->id}-table'>"
                    . "<tr>"
                        . "<td width='13%' valign='middle' style='text-align:center' id='quick-fix-{$fix->id}-action'>"
                            . "<button id='quick-fix-{$fix->id}-button' onclick='return DupPro.Pack.QuickFix(this, {{$fix->parameter2}})' type='button' class='button' data-id='{$fix->id}' data-toggle='#quick-fix-{$fix->id}'>"
                                . "<i class='fa fa-wrench' aria-hidden='true'></i>&nbsp; "
                                .DUP_PRO_U::__('Resolve This')
                            ."</button>"
                        . "</td>"
                        . "<td valign='middle' id='quick-fix-{$fix->id}-message'>"
                            . "<i id='quick-fix-{$fix->id}-info' class='fa fa-question-circle' data-tooltip='{$fix->error_text}'></i>&nbsp; {$fix->parameter1}"
                        . "</td>"
                    . "</tr>"
                . "</table>"
                . "</li>";
			}
		}
		echo "</ul>";
		echo "<div style='margin-left:3px'><a href='#' onclick='DupPro.Pack.ClearMessages();'>".DUP_PRO_U::__('Clear').'</a></div>';
	}
?>
</div>

<!-- ====================
LIST ALL PACKAGES -->
<table class="widefat dpro-pktbl">
<thead>
	<tr>
		<th><input type="checkbox" id="dpro-chk-all"  title="<?php DUP_PRO_U::_e("Select all packages") ?>" style="margin-left:15px" onClick="DupPro.Pack.SetDeleteAll()" /></th>
		<th style='padding-right:25px'><?php DUP_PRO_U::_e("Type") ?></th>
        <?php if($display_brand===true && $is_freelancer_plus): ?>
            <th><?php DUP_PRO_U::_e("Brand") ?></th>
        <?php endif; ?>
		<th style='padding-right:25px'><?php DUP_PRO_U::_e("Created") ?></th>
		<th style='padding-right:25px'><?php DUP_PRO_U::_e("Size") ?></th>
		<th><?php DUP_PRO_U::_e("Name") ?></th>
        <th style="text-align:center;" colspan="3"><?php DUP_PRO_U::_e("Package") ?></th>
	</tr>
</thead>

<?php if ($totalElements == 0) : ?>
	<tr>
		<td colspan="7" class="dpro-list-nopackages">
			<br/>
			<i class="fa fa-archive"></i>
			<?php DUP_PRO_U::_e("No Packages Found."); ?><br/>
			<?php DUP_PRO_U::_e("Click the 'Create New' button to build a package."); ?>
			<div class="dpro-quick-start">
				<?php DUP_PRO_U::_e("New to Duplicator?"); ?><br/>
				<a href="https://snapcreek.com/duplicator/docs/quick-start/" target="_blank">
					<?php DUP_PRO_U::_e("Check out the 'Quick Start' guide!"); ?>
				</a>
			</div>
			<div style="height:75px">&nbsp;</div>
		</td>
	</tr>
<?php endif; ?>	

<?php
$rowCount = 0;
$rows = $qryResult;
$pack_dbonly  = false;
$txt_dbonly  = DUP_PRO_U::__('Database Only');

foreach ($rows as $row) {

	$Package = DUP_PRO_Package::get_from_json($row['package']);
	if (is_object($Package)) {
		$pack_name			 = $Package->Name;
		$pack_archive_size	 = $Package->Archive->Size;
		$pack_namehash		 = $Package->NameHash;
		$pack_dbonly		 = $Package->Archive->ExportOnlyDB;
		$pack_format		 = strtolower($Package->Archive->Format);
        $brand               = (isset($Package->Brand) && !empty($Package->Brand) && is_string($Package->Brand) ? $Package->Brand : 'unknown');
	} else {
		$pack_archive_size	 = 0;
		$pack_name			 = 'unknown';
		$pack_namehash		 = 'unknown';
        $brand               = 'unknown';
	}

	//Links
	$uniqueid = "{$row['name']}_{$row['hash']}";
	$detail_id = "duplicator-detail-row-{$rowCount}";
	$css_alt = ($rowCount % 2 != 0) ? '' : 'alternate';

	$remote_display		= $Package->contains_non_default_storage();
	$storage_problem	= (($Package->Status == DUP_PRO_PackageStatus::STORAGE_CANCELLED) || ($Package->Status == DUP_PRO_PackageStatus::STORAGE_FAILED));
	$archive_exists		= ($Package->get_local_package_file(DUP_PRO_Package_File_Type::Archive, true) != null);
	$installer_exists	= ($Package->get_local_package_file(DUP_PRO_Package_File_Type::Installer, true) != null);
	$archive_exists_txt = ($archive_exists) ? '' : DUP_PRO_U::__("No local files, click for more info...");
	$package_type_style = '';
	$progress_error		= '';
	$remote_style       = '';
	if ($remote_display) {
		$remote_style = ($storage_problem) ? 'remote-data-fail' : 'remote-data-pass';
	}

	$archive_name = basename($Package->Archive->getURL());
	$arc_url = $Package->Archive->getURL();
	$js_arc_params = "'{$archive_name}', '{$arc_url}'";

	switch($Package->Type) {
		case DUP_PRO_PackageType::MANUAL:
			$package_type_string = DUP_PRO_U::__('Manual');
			break;
		case DUP_PRO_PackageType::SCHEDULED:
			$package_type_string = DUP_PRO_U::__('Schedule');
			break;
		case DUP_PRO_PackageType::RUN_NOW:
			$package_type_style = 'style="padding-top:8px"';
			$package_type_string = '<span>' . DUP_PRO_U::__('Schedule') . ' <sup>R</sup><span>';
			break;
		default:
			$package_type_string = DUP_PRO_U::__('Unknown');
			break;
	}
	?>

	<?php if (($row['status'] >= 100) || ($storage_problem)) : ?>
		<!-- COMPLETE -->
		<tr class="dpro-pkinfo <?php echo $css_alt ?>" id="duppro-packagerow-<?php echo $row['id']; ?>">
			<td class="pass"><input name="delete_confirm" type="checkbox" id="<?php echo $row['id']; ?>" /></td>
			<td <?php echo $package_type_style; ?>><?php echo $package_type_string . " <sup>{$pack_format}</sup>"; ?></td>
            <?php if($display_brand===true && $is_freelancer_plus): ?>
            <td class='brand-name'>
				<?php echo $brand; ?>
			</td>
            <?php endif; ?>
			<td><?php echo DUP_PRO_Package::format_created_date($row['created'], $package_ui_created); ?></td>
			<td><?php echo DUP_PRO_U::byteSize($pack_archive_size); ?></td>
			<td class='pack-name'>
				<?php echo ($pack_dbonly) ? "{$pack_name} <sup title='{$txt_dbonly}'>DB</sup>" : $pack_name ; ?>
			</td>
			<td class="get-btns">
				<!-- MENU DOWNLOAD -->
				<nav class="dpro-dnload-menu">
					<?php if ($archive_exists) : ?>
						<button <?php DUP_PRO_UI::echoDisabled(!$archive_exists); echo " title='{$archive_exists_txt}'"; ?> class="dpro-dnload-menu-btn button no-select" type="button">
							<i class="fa fa-download"></i> <?php DUP_PRO_U::_e("Download") ?>
						</button>
					<?php else : ?>
						<button <?php echo " title='{$archive_exists_txt}'"; ?> class="button no-select" type="button" style="color:#999" onclick="DupPro.Pack.DownloadNotice()">
							<i class="fa fa-info-circle"></i> <?php DUP_PRO_U::_e("Download") ?>
						</button>
					 <?php endif; ?>
					<nav class="dpro-dnload-menu-items">
						<div onClick="DupPro.Pack.DownloadFile(<?php echo $js_arc_params ?>); DupPro.Pack.DownloadPackageFile(0, <?php echo $Package->ID; ?>); jQuery(this).parent().hide();" >
							<span title="<?php if(!$archive_exists){DUP_PRO_U::_e("Download not accessible from here");} ?>">
								<i class="fa <?php echo ($archive_exists && $installer_exists  ? 'fa-download' : 'fa-exclamation-triangle') ?>"></i> <?php DUP_PRO_U::_e("Both Files") ?>
							</span>
						</div>
						<div onClick="DupPro.Pack.DownloadPackageFile(0, <?php echo $Package->ID; ?>);" >
							<span title="<?php if(!$installer_exists){DUP_PRO_U::_e("Download not accessible from here");} ?>">
								<i class="fa <?php echo ($installer_exists ? 'fa-bolt' : 'fa-exclamation-triangle') ?>"></i> <?php DUP_PRO_U::_e("Installer") ?>
							</span>
						</div>
						<div onClick="DupPro.Pack.DownloadFile(<?php echo $js_arc_params ?>);  jQuery(this).parent().hide();">
							<span title="<?php if(!$archive_exists){DUP_PRO_U::_e("Download not accessible from here");} ?>">
								<i class="fa <?php echo ($archive_exists ? 'fa-file-archive-o' : 'fa-exclamation-triangle') ?>"></i> 
									<?php echo DUP_PRO_U::__("Archive") . " ({$pack_format})" ?>
							</span>
						</div>
					</nav>
				</nav>

				<!-- MENU BAR -->
				<nav class="dpro-bar-menu">
					<button type="button" class="dpro-store-btn button no-select dpro-bar-menu-btn " title="<?php DUP_PRO_U::_e("More Items") ?>">
						<i class="fa fa-bars <?php echo ($remote_style);?>"></i>
					</button>
					<nav class="dpro-bar-menu-items">
						<div onClick="DupPro.Pack.OpenPackDetail(<?php echo "$Package->ID"; ?>);">
							<span><i class="fa fa-archive" ></i> <?php DUP_PRO_U::_e("Details") ?></span>
						</div>
						<div onClick="DupPro.Pack.OpenPackTransfer(<?php echo "$Package->ID"; ?>);">
							<span><i class="fa fa-refresh" ></i> <?php DUP_PRO_U::_e("Transfer") ?></span>
						</div>
						<!-- REMOTE STORE BUTTON -->
						<?php if ($storage_problem) : ?>
							<div onClick="DupPro.Pack.ShowRemote(<?php echo "$Package->ID, '$Package->NameHash'"; ?>);" title="<?php DUP_PRO_U::_e("Error during storage transfer.") ?>">
								<span><i class="fa fa-exclamation-triangle error-icon"></i> <?php DUP_PRO_U::_e("Storage") ?></span>
							</div>
						<?php elseif ($remote_display) : ?>
							<div onClick="DupPro.Pack.ShowRemote(<?php echo "$Package->ID, '$Package->Name'"; ?>);" >
								<span ><i class="fa fa-database" ></i> <?php DUP_PRO_U::_e("Storage") ?></span>
							</div>
						<?php else : ?>
							<div style="color:#999" title="<?php DUP_PRO_U::_e("No Remote Storages") ?>">
								<span><i class="fa fa-database" ></i> <?php DUP_PRO_U::_e("Storage") ?></span>
							</div>
						<?php endif; ?>
					</nav>
				</nav>
			</td>
		</tr>
	<?php
	// NOT COMPLETE
	else :

		if ($row['status'] < DUP_PRO_PackageStatus::COPIEDPACKAGE) {
			// In the process of building
			$size		 = 0;
			$tmpSearch	 = glob(DUPLICATOR_PRO_SSDIR_PATH_TMP."/{$pack_namehash}_*");

			if (is_array($tmpSearch)) {
				$result	 = @array_map('filesize', $tmpSearch);
				$size	 = array_sum($result);
			}
			$pack_archive_size = $size;
		}

		// If its in the pending cancels consider it stopped
		$status = $row['status'];
		$id = (int) $row['id'];

		if (in_array($id, $pending_cancelled_package_ids)) {
			$status = DUP_PRO_PackageStatus::PENDING_CANCEL;
		}

		if ($status >= 0) {
			$progress_css = 'run';
			if ($status >= 75) {
				$stop_button_text	 = DUP_PRO_U::__('Stop Transfer');
				$progress_html		 = "<i class='fa fa-refresh fa-spin'></i> <span id='status-progress-{$id}'>0</span>%"
					."<span style='display:none' id='status-{$id}'>{$status}</span>";
			} else if ($status > 0) {
				$stop_button_text	 = DUP_PRO_U::__('Stop Build');
				$progress_html		 = "<i class='fa fa-gear fa-spin'></i> <span id='status-{$id}'>{$status}</span>%";
			} else {
				// In a pending state
				$stop_button_text	 = DUP_PRO_U::__('Cancel Pending');
				$progress_html		 = " <span style='display:none' id='status-{$id}'>{$status}</span>";
			}
		} else {
			/** FAILURES AND CANCELLATIONS * */
			$progress_css = 'fail';

			if ($status == DUP_PRO_PackageStatus::ERROR) {
				$progress_error = '<div class="progress-error"><i class="fa fa-exclamation-triangle"></i> <a href="#" onclick="DupPro.Pack.OpenPackDetail('.$Package->ID.'); return false;">'.DUP_PRO_U::__('Error Processing')."</a></div><span style='display:none' id='status-$id'>$status</span>";
			} else if ($status == DUP_PRO_PackageStatus::BUILD_CANCELLED) {
				$progress_error = '<div class="progress-error"><i class="fa fa-exclamation-triangle"></i> '.DUP_PRO_U::__('Build Cancelled')."</div><span style='display:none' id='status-$id'>$status</span>";
			} else if ($status == DUP_PRO_PackageStatus::PENDING_CANCEL) {
				$progress_error = '<div class="progress-error"><i class="fa fa-exclamation-triangle"></i> '.DUP_PRO_U::__('Cancelling Build')."</div><span style='display:none' id='status-$id'>$status</span>";
			} else if ($status == DUP_PRO_PackageStatus::REQUIREMENTS_FAILED) {
				$progress_error = '<div class="progress-error"><i class="fa fa-exclamation-triangle"></i> '.DUP_PRO_U::__('Requirements Failed')."</div><span style='display:none' id='status-$id'>$status</span>";
			}
		}
		?>

		<tr class="dpro-pkinfo  <?php echo $css_alt ?>" id="duppro-packagerow-<?php echo $row['id']; ?>">
			<?php if ($status >= 0) : ?>
			   <td class="<?php echo $progress_css ?>"><input name="delete_confirm" type="checkbox" id="<?php echo $row['id']; ?>" /></td>
			<?php else : ?>
				<td class="<?php echo $progress_css ?>"><input name="delete_confirm" type="checkbox" id="<?php echo $row['id']; ?>" /></td>
			<?php endif; ?>
			<td><?php echo (($Package->Type == DUP_PRO_PackageType::MANUAL) ? DUP_PRO_U::__('Manual') : DUP_PRO_U::__('Schedule')); ?></td>
			<td><?php echo DUP_PRO_Package::format_created_date($row['created'], $package_ui_created); ?></td>
			<td><?php echo $Package->get_display_size(); ?></td>
			<td class='pack-name'>
				<?php	echo ($pack_dbonly) ? "{$pack_name} <sup title='{$txt_dbonly}'>DB</sup>" : $pack_name ; ?>
			</td>
			<td class="get-btns-transfer" colspan="3">
				<?php if ($status >= 75) : ?>
					<button id="<?php echo "{$uniqueid}_{$global->installer_base_name}" ?>" <?php DUP_PRO_UI::echoDisabled(!$installer_exists); ?> class="button no-select" onClick="DupPro.Pack.DownloadPackageFile(0, <?php echo $Package->ID; ?>); return false;">
						<i class="fa <?php echo ($installer_exists ? 'fa-bolt' : 'fa-exclamation-triangle') ?>"></i> <?php DUP_PRO_U::_e("Installer") ?>
					</button>
					<button id="<?php echo "{$uniqueid}_archive.zip" ?>" <?php DUP_PRO_UI::echoDisabled(!$archive_exists); ?> class="button no-select"  onClick="location.href = '<?php echo $Package->Archive->getURL(); ?>'; return false;">
						<i class="fa <?php echo ($archive_exists ? 'fa-file-archive-o' : 'fa-exclamation-triangle') ?>"></i> <?php DUP_PRO_U::_e("Archive") ?>
					</button>
				<?php else : ?>
					<?php if ($status == 0): ?>
						<button onClick="DupPro.Pack.StopBuild(<?php echo $row['id']; ?>); return false;" class="button button-large dpro-btn-stop">
							<i class="fa fa-close"></i> &nbsp; <?php echo $stop_button_text; ?>
						</button>
					<?php else: ?>
						   <?php echo $progress_error; ?>
					<?php endif;?>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ($status == 0) : ?>
			<!--   NO DISPLAY -->
		<?php elseif ($status > 0) : ?>
			<tr>
				<td colspan="8" class="run <?php echo $css_alt ?>">
					<div class="wp-filter dpro-build-msg">

						<?php if ($status < 75) : ?>
							<!-- BUILDING PROGRESS-->
							<div id='dpro-progress-status-message-build'>
								<?php
									echo "<div class='status-hdr'>" . DUP_PRO_U::__("Building Package") . " {$progress_html}</div>";
									echo '<small>' .	DUP_PRO_U::__("Please allow it to finish before creating another one.") . '</small>'
								?> <br/>
							</div>
						<?php else : ?>
							<!-- TRANSFER PROGRESS -->
							<div id='dpro-progress-status-message-transfer'>
								<?php
									echo "<div class='status-hdr'>" . DUP_PRO_U::__("Transferring Package") . " {$progress_html}</div>";
									echo '<small id="dpro-progress-status-message-transfer-msg">' . DUP_PRO_U::__("Getting Transfer State...") . '</span>'
								?> <br/>
							</div>
						<?php endif; ?>

						<script>
							jQuery(document).ready(function($)
							{
								DupPro.UI.AnimateProgressBar('dpro-progress-bar');
							});
						</script>
						<div id="dpro-progress-bar-area">
							<div id="dpro-progress-bar"></div>
						</div>
						<button onClick="DupPro.Pack.StopBuild(<?php echo $row['id']; ?>); return false;" class="button button-large dpro-btn-stop">
							<i class="fa fa-close"></i> &nbsp; <?php echo $stop_button_text; ?>
						</button>
					</div>
				</td>
			</tr>
		<?php else: ?>
			<!--   NO DISPLAY -->
		<?php endif; ?>

	<?php endif; ?>
	<?php
	$rowCount++;
}
?>
<tfoot>
	<tr>
		<th colspan="6">
			<div class="dpro-pack-status-info">
				<?php  if ($max_pack_store < $totalElements && $max_pack_store != 0) :?>
					<?php	echo DUP_PRO_U::__("Note: max package retention enabled") ; ?>
					<i class="fa fa-question-circle"
						data-tooltip-title="<?php DUP_PRO_U::_e("Storage Packages:"); ?>"
						data-tooltip='<?php DUP_PRO_U::_e("The number of packages to keep is set at [{$max_pack_store}]. To change this setting go to "
							. 'Duplicator Pro &gt; Storage &gt; Default &gt; Max Packages and change the value, otherwise this note can be ignored.'); ?>'></i>
				<?php endif; ?>
			</div>
			<div style="float:right">
				<?php
					echo DUP_PRO_U::__("Total Size")	. ': ' . DUP_PRO_U::byteSize($totalSize).  ' &nbsp;|&nbsp; ';
					echo '<i>' . DUP_PRO_U::__("Time")	. ': <span id="dpro-clock-container"></span></i>';
				?>
			</div>
		</th>
	</tr>
</tfoot>
</table>
</form>

<?php if($totalElements > $per_page) : ?>
<form id="form-duplicator-nav" method="post">
	<?php wp_nonce_field( 'dpro_package_form_nonce' ); ?>
	<div class="dpro-paged-nav tablenav">
		<?php if ($statusActive > 0) : ?>
			<div id="dpro-paged-progress" style="padding-right: 10px">
				<i class="fa fa-circle-o-notch fa-spin fa-lg fa-fw"></i>
				<i><?php DUP_PRO_U::_e('Paging disabled during build...');?></i>
			</div>
		<?php else : ?>
			<div id="dpro-paged-buttons">
				<?php echo $pager->display_pagination($totalElements,$per_page); ?>
			</div>
		<?php endif; ?>
	</div>
</form>
<?php else : ?>
<div style="float:right; padding:10px 5px">
	<?php echo 	$totalElements . '&nbsp;' . DUP_PRO_U::__("items");	?>
</div>
<?php endif; ?>


<!-- ==========================================
THICK-BOX DIALOGS: -->
<?php
	$remoteDlg = new DUP_PRO_UI_Dialog();
	$remoteDlg->width	= 650;
	$remoteDlg->height	= 350;
	$remoteDlg->title	= DUP_PRO_U::__('Remote Storage Locations');
	$remoteDlg->message	= DUP_PRO_U::__('Loading Please Wait...');
	$remoteDlg->initAlert();

	$alert1 = new DUP_PRO_UI_Dialog();
	$alert1->title		= DUP_PRO_U::__('Bulk Action Required');
	$alert1->message	= DUP_PRO_U::__('Please select an action from the "Bulk Actions" drop down menu!');
	$alert1->initAlert();
	
	$alert2 = new DUP_PRO_UI_Dialog();
	$alert2->title		= DUP_PRO_U::__('Selection Required');
	$alert2->message	= DUP_PRO_U::__('Please select at least one package to delete!');
	$alert2->initAlert();

    $alert3 = new DUP_PRO_UI_Dialog();
	$alert3->title		= DUP_PRO_U::__('Alert!');
	$alert3->message	= DUP_PRO_U::__('A package is being processed. Retry later.');
	$alert3->initAlert();

    $alert4 = new DUP_PRO_UI_Dialog();
    $alert4->title		= DUP_PRO_U::__('ERROR!');
    $alert4->message	= DUP_PRO_U::__('Got an error or a warning: undefined');
    $alert4->initAlert();

    $alert5 = new DUP_PRO_UI_Dialog();
    $alert5->title		= $alert4->title;
    $alert5->message	= DUP_PRO_U::__('Failed to get details.');
    $alert5->initAlert();

    $alert6 = new DUP_PRO_UI_Dialog();
	$alert6->height	= 300;
    $alert6->title		= DUP_PRO_U::__('Download Status');
    $alert6->message	= DUP_PRO_U::__("No package files are available for direct download from this server using the 'Download' button. Please use the "
							. "<b><i class='fa fa-bars small-fa'></i> More Items &#10095; Storage option</b> to get the package from its non-default stored location.<br/><br/>"
							. "<small><i>- To enable the direct download button be sure the local default storage type is enabled when creating a package. <br/><br/>"
							. "- If the Storage &#10095; Default &#10095; 'Max Packages' is set then packages will be removed but the entry will still be visible.</i></small>");
    $alert6->initAlert();

	$confirm1 = new DUP_PRO_UI_Dialog();
	$confirm1->title			 = DUP_PRO_U::__('Delete Packages?');
	$confirm1->message			 = DUP_PRO_U::__('Are you sure, you want to delete the selected package(s)?');
	$confirm1->message			.= '<br/>';
	$confirm1->message			.= DUP_PRO_U::__('<small><i>Note: This action removes only packages located on this server.  If a remote package was created then it will not be removed or affected.</i></small>');
	$confirm1->progressText      = DUP_PRO_U::__('Removing Packages, Please Wait...');
	$confirm1->jsCallback		 = 'DupPro.Pack.Delete()';
	$confirm1->initConfirm();
?>
<script>
jQuery(document).ready(function($)
{

DupPro.Pack.StorageTypes =
{
	local: 0,
	dropbox: 1,
	ftp: 2,
	gdrive: 3,
	s3: 4,
	sftp: 5,
	onedrive: 6
}

DupPro.Pack.CreateNew = function(e){
    var $this = $(e);
    if ($this.hasClass('disabled')) {
        <?php $alert3->showAlert(); ?>
        return false;
    }
}

DupPro.Pack.QuickFix = function(e, pharams){
    console.log(pharams);
    console.log(e);
    var $this = $(e),
        toggle = $this.attr('data-toggle'),
        id = $this.attr('data-id'),
        fix = $(toggle),
        button = {
            loading : function(){
                $this.prop('disabled',true)
                        .addClass('disabled')
                        .html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> <?php DUP_PRO_U::_e('Please Wait...')?>');
            },
            reset : function(){
                $this.prop('disabled',false)
                        .removeClass('disabled')
                        .html("<i class='fa fa-wrench' aria-hidden='true'></i>&nbsp; <?php DUP_PRO_U::_e('Resolve This')?>");
            }
        },
        error = {
            message : function(text){
                fix.find(toggle + '-message').append("<div style='color:#cc0000' id='" + toggle.replace('#','') + "-error'><i class='fa fa-exclamation-triangle'></i>&nbsp; " + text + "</div>");
            },
            remove : function(){
                if($(toggle + "-error")) $(toggle + "-error").remove();
            }
        };

    error.remove();
    button.loading();
    
    $.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		data: {
            action : 'duplicator_pro_quick_fix',
            setup : pharams,
            id : id
        }
	}).done(function(data){
        console.log(data);
        if(data.error === false)
        {
            fix.remove();

            // If there is no fixes and notifications - remove container
            if(typeof data.recommended_fixes != 'undefined')
            {
                if(data.recommended_fixes == 0)
                {
                    $("#dup-pro-fixes").remove();
                }
            }
        }
        else
        {
            button.reset();
            error.message(data.error);
        }
    }).fail(function(data, x){
        button.reset();
        error.message('<?php DUP_PRO_U::_e('Unexpected Error!')?>');
        console.log(data);
        console.log(x);
    });
}

DupPro.Pack.DownloadFile = function(file, url)
{
	var link = document.createElement('a');
	link.className = "dpro-dnload-menu-item";
	link.target = "_blank";
	link.download = file;
	link.href= url;
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
	return false;
};

DupPro.Pack.DownloadNotice = function()
{
	<?php $alert6->showAlert(); ?>
	return false;
};


//DOWNLOAD MENU
$('button.dpro-dnload-menu-btn').click(function(e)
{
	$('nav.dpro-bar-menu-items').hide();
	var $menu = $(this).parent().find('nav.dpro-dnload-menu-items');

	if ($menu.is(':visible') ) {
		$menu.hide();
	}  else {
		$('nav.dpro-dnload-menu-items').hide();
		$menu.show(200);
	}
	return false;
});

//BAR MENU
$('button.dpro-bar-menu-btn').click(function(e)
{
	$('nav.dpro-dnload-menu-items').hide();
	var $menu = $(this).parent().find('nav.dpro-bar-menu-items');

	if ($menu.is(':visible') ) {
		$menu.hide();
	}  else {
		$('nav.dpro-bar-menu-items').hide();
		$menu.show(200);
	}
	return false;
});

$(document).click(function(e)
{
	var className = e.target.className;
	if (className != 'dpro-menu-x') {
		$('nav.dpro-dnload-menu-items').hide();
		$('nav.dpro-bar-menu-items').hide();
	}
});

$( "nav.dpro-dnload-menu-items div" ).each(function() { $(this).addClass('dpro-menu-x');});
$( "nav.dpro-dnload-menu-items div span" ).each(function() { $(this).addClass('dpro-menu-x');});
$( "nav.dpro-bar-menu-items div" ).each(function() { $(this).addClass('dpro-menu-x');});
$( "nav.dpro-bar-menu-items div span" ).each(function() { $(this).addClass('dpro-menu-x');});

/*	Creats a comma seperate list of all selected package ids  */
DupPro.Pack.GetDeleteList = function()
{
	var arr = new Array;
	var count = 0;
	$("input[name=delete_confirm]").each(function() {
		if (this.checked) {
			arr[count++] = this.id;
		}
	});
	var list = arr.join(',');
	return list;
}

/*	Creats a comma seperate list of all selected package ids  */
DupPro.Pack.GetDeleteList = function()
{
	var arr = new Array;
	var count = 0;
	$("input[name=delete_confirm]").each(function() {
		if (this.checked) {
			arr[count++] = this.id;
		}
	});
	var list = arr.join(',');
	return list;
}


/*	Provides the correct confirmation items when deleting packages */
DupPro.Pack.ConfirmDelete = function()
{
	$('#dpro-dlg-confirm-delete-btns input').removeAttr('disabled');
	if ($("#dup-pack-bulk-actions").val() != "delete") {
		<?php $alert1->showAlert(); ?>
		return;
	}

	var list = DupPro.Pack.GetDeleteList();
	if (list.length == 0) {
		<?php $alert2->showAlert(); ?>
		return;
	}
	<?php $confirm1->showConfirm(); ?>
}


/*	Removes all selected package sets with ajax call  */
DupPro.Pack.Delete = function()
{
	var list = DupPro.Pack.GetDeleteList();
	var pageCount = $('#current-page-selector').val();
	var pageItems = $('input[name="delete_confirm"]');

	$.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		data: {action: 'duplicator_pro_package_delete', duplicator_pro_delid: list, nonce: '<?php echo $delete_nonce; ?>'},
		success: function(data) {
			//Increment back a page-set if no items are left
			if ($('#form-duplicator-nav').length) {
				if (pageItems.length == list.split(",").length)
					$('#current-page-selector').val(pageCount -1);
				$('#form-duplicator-nav').submit();
			} else {
				$('#form-duplicator').submit();
			}
		}
	});
}


/* Toogles the Bulk Action Check boxes */
DupPro.Pack.SetDeleteAll = function()
{
	var state = $('input#dpro-chk-all').is(':checked') ? 1 : 0;
	$("input[name=delete_confirm]").each(function() {
		this.checked = (state) ? true : false;
	});
}


/* Stops the build from running */
DupPro.Pack.StopBuild = function(packageID)
{
	$('#action').val('stop-build');
	$('#action-parameter').val(packageID);
	$('#form-duplicator').submit();
}


/* Clears and auto-detection messages */
DupPro.Pack.ClearMessages = function()
{
	$('#action').val('clear-messages');
	$('#form-duplicator').submit();
}


/*	Redirects to the packages detail screen using the package id */
DupPro.Pack.OpenPackDetail = function(id)
{
	window.location.href = '?page=duplicator-pro&action=detail&tab=detail&id=' + id;
}

/*	Redirects to the packages detail screen using the package id */
DupPro.Pack.OpenPackTransfer = function(id)
{
	window.location.href = '?page=duplicator-pro&action=detail&tab=transfer&id=' + id;
}

/* Shows remote storage location dialogs */
DupPro.Pack.ShowRemote = function(package_id, name)
{

	$('nav.dpro-bar-menu-items').hide();
	<?php $remoteDlg->showAlert(); ?>
	var data = {action: 'duplicator_pro_get_storage_details', package_id: package_id};

	$.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		timeout: 10000000,
		data: data,
		complete: function() {},
		success: function(data) {
			if (! data.succeeded) {
				var text = "<?php DUP_PRO_U::_e('Got an error or a warning'); ?>: " + data.message;
                <?php $alert4->showAlert(); ?>
                $("#<?php echo $alert4->getID(); ?>_message").html(text);
				return false;
			}
			var info = '<div class="dpro-dlg-remote-endpoints">';

			for (storage_provider_key in data.storage_providers) {

				var store = data.storage_providers[storage_provider_key];
				var styling = "margin-bottom:14px";
				var failed_string = "";
				var cancelled_string = "";

				if(store.failed) {
					failed_string = " (<?php DUP_PRO_U::_e('failed'); ?>)";
					styling += ";color:#A62426";
				}

				if(store.cancelled) {
					cancelled_string = " (<?php DUP_PRO_U::_e('cancelled'); ?>)";
					styling += ";color:#A62426";
				}

				switch (parseInt(store.storage_type)) {
					//LOCAL
					case DupPro.Pack.StorageTypes.local:
						if ((store.id != -2)) {
							info += "<div style='" + styling + "'>";
							info += "<b><i class='fa fa-server'></i> <?php DUP_PRO_U::_e('Local Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
							info += "<span><?php DUP_PRO_U::_e('Location'); ?>: " + store.storage_location_string + "</span><br/>";
							info += "</div>";
						}
					break;
					//FTP
					case DupPro.Pack.StorageTypes.ftp:
						var ftp_url = "<a href='" + encodeURI(store.storage_location_string) + "' target='_blank'>" + store.storage_location_string + "</a>";
						info += "<div style='" + styling + "'>";
						info += "<b><i class='fa fa-cloud'></i> <?php DUP_PRO_U::_e('FTP Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
						info += "<span>Server: " + store.ftp_server + "</span><br/>";
						info += "<span>Location: " + ftp_url + "</span><br/>";
						info += "</div>";
					break;
                                        //SFTP
					case DupPro.Pack.StorageTypes.sftp:
						var sftp_url = "<a href='" + encodeURI(store.storage_location_string) + "' target='_blank'>" + store.storage_location_string + "</a>";
						info += "<div style='" + styling + "'>";
						info += "<b><i class='fa fa-cloud'></i> SFTP Endpoint: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
						info += "<span>Server: " + store.sftp_server + "</span><br/>";
						info += "<span>Location: " + sftp_url + "</span><br/>";
						info += "</div>";
					break;
					//DROPBOX
					case DupPro.Pack.StorageTypes.dropbox:
						var dbox_url = "<a href='" + store.storage_location_string + "' target='_blank'>" + store.storage_location_string + "</a>";
						info += "<div style='" + styling + "'>";
						info += "<b><i class='fa fa-dropbox'></i> <?php DUP_PRO_U::_e('Dropbox Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
						info += "<span>Location: " + dbox_url + "</span><br/>";
						info += "</div>";
					break;
					//GDRIVE
					case DupPro.Pack.StorageTypes.gdrive:
						//var gdrive_url = "<a href='" + store.gdrive_storage_url + "' target='_blank'>" + store.storage_location_string + "</a>";
						var gdrive_url = store.storage_location_string;
						info += "<div style='" + styling + "'>";
						info += "<b><i class='fa fa-cloud'></i> <?php DUP_PRO_U::_e('Google Drive Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
						info += "<span>Location: " + gdrive_url + "</span><br/>";
						info += "</div>";
					break;
					//S3
					case DupPro.Pack.StorageTypes.s3:
						info += "<div style='" + styling + "'>";
						info += "<b> <i class='fa fa-cloud'></i> <?php DUP_PRO_U::_e('Amazon S3 Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
						info += "<span>Location: " + store.storage_location_string + "</span><br/>";
						info += "</div>";
					break;
					//ONEDRIVE
					case DupPro.Pack.StorageTypes.onedrive:
                        info += "<div style='" + styling + "'>";
                        info += "<b> <i class='fa fa-cloud'></i> <?php DUP_PRO_U::_e('OneDrive Endpoint'); ?>: '" + store.name + failed_string + cancelled_string + "'</b><br/>";
                        info += "<span>Location: " + store.storage_location_string +"</span><br/>";
                        info += "</div>";
					break;
				}
			}

			info += '</div>';
			$('#TB_window .dpro-dlg-alert-txt').html(info);
		},
		error: function(data) {
            <?php $alert5->showAlert(); ?>
			console.log(data);
		}
	});
	return false;
};


/*  Virtual states that UI uses for easier tracking of the three general states a package can be in*/
DupPro.Pack.ProcessingStats =
{
	PendingCancellation: -3,
	Pending: 0,
	Building: 1,
	Storing: 2,
	Finished: 3,
}


DupPro.Pack.packageCount = -1;
DupPro.Pack.setIntervalID = -1;

DupPro.Pack.SetUpdateInterval = function(period)
{
	console.log('setting interval to '+ period);
	if(DupPro.Pack.setIntervalID != -1) {
		clearInterval(DupPro.Pack.setIntervalID);
		DupPro.Pack.setIntervalID = -1
	}
	DupPro.Pack.setIntervalID = setInterval(DupPro.Pack.UpdateUnfinishedPackages, period * 1000);
}

$('#btn-logs-gift').on('click touchstart',function(e)
{
    e.preventDefault();

    var $this = $(this),
        href = 'admin.php?page=duplicator-pro-settings&subtab=profile',
        data = {
            action : 'DUP_PRO_CTRL_Package_toggleGiftFeatureButton',
            nonce : '<?php echo $gift_nonce; ?>',
            hide_gift_btn : true
        };

    $.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		data: data
	}).done(function(data) {
        window.location.href = href;
    }).fail(function(data) {
        DupPro.Pack.SetUpdateInterval(60);
        console.log(data);
    });
});

DupPro.Pack.UpdateUnfinishedPackages = function()
{
	var data = {action: 'duplicator_pro_get_package_statii'}

	$.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		timeout: 10000000,
		data: data,
		complete: function() { },
		success: function(data) {
			var activePackagePresent = false;

			if(DupPro.Pack.packageCount == -1) {
				DupPro.Pack.packageCount = data.length
			} else {
				if(DupPro.Pack.packageCount != data.length) {
					window.location = window.location.href;
				}
			}

			for (package_info_key in data) {
				var package_info = data[package_info_key];
				var statusSelector = '#status-' + package_info.ID;
				var packageRowSelector = '#duppro-packagerow-' + package_info.ID;
				var packageSizeSelector = packageRowSelector + ' td:nth-child(4)';
				var current_value_string = $(statusSelector).text();
				var current_value = parseInt(current_value_string);
				var currentProcessingState;

				if(current_value == -3) {
					currentProcessingState = DupPro.Pack.ProcessingStats.PendingCancellation;
				}
				else if(current_value == 0) {
					currentProcessingState = DupPro.Pack.ProcessingStats.Pending;
				}
				else if ((current_value >= 0) && (current_value < 75)) {
					currentProcessingState = DupPro.Pack.ProcessingStats.Building;
				}
				else if ((current_value >= 75) && (current_value < 100)) {
					currentProcessingState = DupPro.Pack.ProcessingStats.Storing;
				}
				else {
					// Has to be negative(error) or 100 - both mean complete
					currentProcessingState = DupPro.Pack.ProcessingStats.Finished;
				}
				if(currentProcessingState == DupPro.Pack.ProcessingStats.Pending) {
					if(package_info.status != 0) {
						window.location = window.location.href;
					}
				}
				else if (currentProcessingState == DupPro.Pack.ProcessingStats.Building) {
					if ((package_info.status >= 75) || (package_info.status < 0)) {
						// Transitioned to storing so refresh
						window.location = window.location.href;
						break;
					} else {

						activePackagePresent = true;
						$(statusSelector).text(package_info.status);
						$(packageSizeSelector).hide().fadeIn(1000).text(package_info.size);
					}
				} else if (currentProcessingState == DupPro.Pack.ProcessingStats.Storing) {
					if ((package_info.status == 100) || (package_info.status < 0)) {
						// Transitioned to storing so refresh
						window.location = window.location.href;
						break;
					} else {
						activePackagePresent = true;
						$('#dpro-progress-status-message-transfer-msg').html(package_info.status_progress_text);
						var statusProgressSelector = '#status-progress-' + package_info.ID;
						$(statusProgressSelector).text(package_info.status_progress);
						console.log("status progress: " + package_info.status_progress);
					}
				} else if(currentProcessingState == DupPro.Pack.ProcessingStats.PendingCancellation) {
					if((package_info.status == -2) || (package_info.status == -4)) {
						// refresh when its gone to cancelled
						window.location = window.location.href;
					}
				} else if(currentProcessingState == DupPro.Pack.ProcessingStats.Finished) {
					// IF something caused the package to come out of finished refresh everything (has to be out of finished or error state)
					if((package_info.status != 100) && (package_info.status > 0))
					{
						window.location = window.location.href;
					}
				}
			}

			if (activePackagePresent) {
				$('#dup-pro-create-new').addClass('disabled');
				DupPro.Pack.SetUpdateInterval(10);
			} else {
				$('#dup-pro-create-new').removeClass('disabled');
				// Kick refresh down to 60 seconds if nothing is being actively worked on
				DupPro.Pack.SetUpdateInterval(60);
			}
		},
		error: function(data) {
			DupPro.Pack.SetUpdateInterval(60);
			console.log(data);
		}
	});
};

//Init
DupPro.UI.Clock(DupPro._WordPressInitTime);
DupPro.Pack.UpdateUnfinishedPackages();
$('#btn-logs-gift').slideDown(700);

});
</script>
