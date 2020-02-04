<?php
defined("ABSPATH") or die("");
DUP_PRO_U::hasCapability('manage_options');

global $wpdb;
$global  = DUP_PRO_Global_Entity::get_instance();

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');

$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'diagnostics';

?>

<style>
	div.dpro-sub-tabs {padding: 10px 0 10px 0; font-size: 14px}
</style>

<div class="wrap">
    <?php duplicator_pro_header(DUP_PRO_U::__("Tools")) ?>

    <h2 class="nav-tab-wrapper">
		<a href="?page=duplicator-pro-tools&tab=diagnostics" class="nav-tab <?php echo ($current_tab == 'diagnostics') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Diagnostics'); ?></a>
        <a href="?page=duplicator-pro-tools&tab=templates" class="nav-tab <?php echo ($current_tab == 'templates') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Templates'); ?></a>
		<?php if ($global->profile_idea) : ?>
			<a href="?page=duplicator-pro-tools&tab=import" class="nav-tab <?php echo ($current_tab == 'import') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Import'); ?></a>
		<?php endif;?>        
    </h2> 	

    <?php
    switch ($current_tab)
    {
		case 'import': include(dirname(__FILE__) . '/import.php');
            break;
		case 'templates': include(dirname(__FILE__) . '/templates/main.php');
            break;
		case 'diagnostics': include(dirname(__FILE__) . '/diagnostics/main.php');
            break;
    }
    ?>
</div>
