<?php
/* @var $global DUP_PRO_Global_Entity */

defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.secure.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.brand.entity.php');

$global  = DUP_PRO_Global_Entity::get_instance();
$sglobal = DUP_PRO_Secure_Global_Entity::getInstance();

$nonce_action		= 'duppro-settings-package';
$action_updated		= null;
$action_response	= DUP_PRO_U::__("Package Settings Saved");

?>

<style>    
    input#package_mysqldump_path_found {margin-top:5px}
    div.dup-feature-found {padding:0; color: green;}
    div.dup-feature-notfound {padding:5px; color: maroon; width:600px;}
	select#package_ui_created {font-family: monospace}
	input#_package_mysqldump_path {width:500px}
	#dpro-ziparchive-mode-st, #dpro-ziparchive-mode-mt {height: 28px; padding-top:5px; display: none}
	div.engine-radio {float: left; min-width: 100px}
	div.engine-radio-disabled {}
	div.engine-sub-opts {padding:10px 0 10px 25px; }
	div#engine-details-match-message {display:none; margin: -5px 0 20px 220px; border: 1px solid silver; padding:5px 8px 5px 8px; background: #dfdfdf; border-radius: 5px; width:650px}
	

	table#archive-build-schedule {display:none}
	span#archive-build-schedule-icon {display:none}
</style>

<?php
$section = isset($_GET['sub']) ? $_GET['sub'] : 'basic';
$txt_gen = DUP_PRO_U::__("Basic Settings");
$txt_adv = DUP_PRO_U::__("Advanced Settings");
$txt_brd = DUP_PRO_U::__("Installer Branding");
$spacer = ' &nbsp;|&nbsp; ';
$url = 'admin.php?page=duplicator-pro-settings&tab=package';

switch ($section) {

	//BASIC SETTINGS
	case 'basic':
		echo "
		<div class='dpro-sub-tabs'>
			<b>{$txt_gen}</b>{$spacer}
			<a href='{$url}&sub=advanced'>{$txt_adv}</a>
			<span>{$spacer}<a href='{$url}&sub=brand'>{$txt_brd}</a></span>
		</div>";
		include ('inc.basic.php');
	break;

	//ADVANDED SETTINGS
	case 'advanced':
		echo "
		<div class='dpro-sub-tabs'>
			<a href='{$url}&sub=basic'>{$txt_gen}</a>{$spacer}
			<b>{$txt_adv}</b>
			<span>{$spacer}<a href='{$url}&sub=brand'>{$txt_brd}</a></span>
		</div>";
		include ('inc.advanced.php');
	break;

	//BRANDING SETTINGS
	case 'brand':
		echo "
		<div class='dpro-sub-tabs'>
			<a href='{$url}&sub=basic'>{$txt_gen}</a>{$spacer}
			<a href='{$url}&sub=advanced'>{$txt_adv}</a>
			<span>{$spacer}<b>{$txt_brd}</b></span>
		</div>";

		$brand_list_url		= 'admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=list';
		$brand_edit_url		= 'admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit';

		$view = isset($_REQUEST['view']) ? SnapLibUtil::sanitize($_REQUEST['view']) : 'list';
		include($view == 'edit'
				? 'inc.brand.edit.php'
				: 'inc.brand.list.php');
	break;

}
?>

<script>
	DupPro.Settings.Brand = new Object();
</script>


