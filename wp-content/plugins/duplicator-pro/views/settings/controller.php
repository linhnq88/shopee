<?php
defined("ABSPATH") or die("");

DUP_PRO_U::hasCapability('manage_options');

global $wpdb;
global $wp_version;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.dialog.php');

$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'general';
?>

<style>
	.narrow-input { width: 80px; }
	.wide-input {width: 400px; }
	i.description {font-size: 12px}
	table.form-table tr td { padding-top:15px; }
	td.dpro-license-type div {padding:5px 0 0 30px}
	td.dpro-license-type i.fa-check-square-o {display: inline-block; padding-right: 5px}
	td.dpro-license-type i.fa-square-o {display: inline-block; padding-right: 7px}
	td.dpro-license-type i.fa-question-circle {font-size:12px}
	div.sub-opts {padding:10px 0 5px 30px; }
	h3.title {padding:0; margin:5px 0 0 0}
	div.wrap form {padding-top: 15px}
	div.dpro-sub-tabs {padding: 10px 0 10px 0; font-size: 14px}
	p.dpro-save-submit {margin:10px 0px 0xp 5px;}
	p.description {max-width:700px}
</style>

<div class="wrap">
    <?php duplicator_pro_header(DUP_PRO_U::__("Settings")) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator-pro-settings&tab=general" class="nav-tab <?php echo ($current_tab == 'general') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('General'); ?></a> 
		<a href="?page=duplicator-pro-settings&tab=package" class="nav-tab <?php echo ($current_tab == 'package') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Packages'); ?></a> 		
		<a href="?page=duplicator-pro-settings&tab=schedule" class="nav-tab <?php echo ($current_tab == 'schedule') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Schedules'); ?></a> 	
        <a href="?page=duplicator-pro-settings&tab=storage" class="nav-tab <?php echo ($current_tab == 'storage') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Storage'); ?></a> 
        <a href="?page=duplicator-pro-settings&tab=licensing" class="nav-tab <?php echo ($current_tab == 'licensing') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Licensing'); ?></a> 
    </h2> 	

    <?php
	switch ($current_tab) {
        case 'general': include(dirname(__FILE__) . '/general/main.php');
            break;
		case 'package': include(dirname(__FILE__) . '/package/main.php');
            break; 
		case 'schedule': include(dirname(__FILE__) . '/schedule.php');
            break; 		
        case 'storage': include(dirname(__FILE__) . '/storage.php');
            break;              
        case 'licensing': include(dirname(__FILE__) . '/licensing.php');
            break;   
	}
    ?>
</div>
