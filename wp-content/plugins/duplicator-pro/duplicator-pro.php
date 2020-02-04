<?php
defined("ABSPATH") or die("");
/** ================================================================================
  Plugin Name: Duplicator Pro
  Plugin URI: http://snapcreek.com/
  Description: Create, schedule and transfer a copy of your WordPress files and database. Duplicate and move a site from one location to another quickly.
  Version: 3.7.3.1
  Author: Snap Creek
  Author URI: http://snapcreek.com
  License: GPLv2 or later

  Copyright 2011-2017  Snapcreek LLC

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  ================================================================================ */

require_once(dirname(__FILE__) . "/define.php");
require_once(DUPLICATOR_PRO_PLUGIN_PATH  . '/lib/snaplib/snaplib.all.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.string.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.date.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.zip.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.license.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.upgrade.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.crypt.blowfish.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.system.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.profilelogs.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/package/class.pack.runner.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.constants.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.db.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.alert.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.logging.php');


define('EDD_DUPPRO_STORE_URL', 'https://snapcreek.com');
define('EDD_DUPPRO_ITEM_NAME', 'Duplicator Pro');
$dpro_license_key = get_option(DUP_PRO_Constants::LICENSE_KEY_OPTION_NAME, '');


if (!empty($dpro_license_key)) {
      
    $global = DUP_PRO_Global_Entity::get_instance();
    
    // RSR TODO: only init this if not an overide key and we are active
    if(($global !== null) && 
        (!DUP_PRO_License_U::isValidOvrKey($dpro_license_key)) && 
        ($global->license_status !== DUP_PRO_License_Status::Invalid) &&
        ($global->license_status !== DUP_PRO_License_Status::Unknown)) {
    
        if (!class_exists('EDD_SL_Plugin_Updater')) {
            require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/lib/edd/EDD_SL_Plugin_Updater.php');
        }
        
        // Don't bother checking updates if license key isn't filled in since that will just create unnecessary traffic
        $dpro_edd_opts = array('version' => DUPLICATOR_PRO_VERSION,
                                'license' => $dpro_license_key,
                                'item_name' => EDD_DUPPRO_ITEM_NAME,
                                'author' => 'Snap Creek Software',
                                'cache_time' => DUP_PRO_Constants::EDD_API_CACHE_TIME,
                                'wp_override' => true);

        $edd_updater   = new EDD_SL_Plugin_Updater(EDD_DUPPRO_STORE_URL, __FILE__, $dpro_edd_opts, DUP_PRO_Constants::PLUGIN_SLUG);

        if (!empty($_REQUEST['dup_pro_clear_updater_cache'])) {
            $edd_updater->clear_version_cache();
        }
    }
}

// Only start the package runner and tracing once it's been confirmed that everything has been installed
if (get_option('duplicator_pro_plugin_version') == DUPLICATOR_PRO_VERSION) {
    DUP_PRO_Package_Runner::init();

    $dpro_global_obj  = DUP_PRO_Global_Entity::get_instance();

    // Important - Needs to be outside of is_admin for proper measuring of background processes
    if (($dpro_global_obj !== null) && ($dpro_global_obj->trace_profiler_on)) {
        $profileLogsEntity = DUP_PRO_Profile_Logs_Entity::get_instance();
        if ($profileLogsEntity != null) {
            DUP_PRO_LOG::setProfileLogs($profileLogsEntity->profileLogs);
            DUP_PRO_LOG::trace("set profile logs");
        }
    }
}

if (is_admin() === true) {

    if (!empty($_REQUEST['dup_pro_clear_schedule_failure'])) {
		$system_global = DUP_PRO_System_Global_Entity::get_instance();
        $system_global->schedule_failed = false;
        $system_global->save();
	}

    if (!defined('WP_MAX_MEMORY_LIMIT')) {
        define('WP_MAX_MEMORY_LIMIT', '256M');
    }

    @ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);

    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.global.entity.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.package.template.entity.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.viewstate.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.notice.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.server.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/package/class.pack.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.json.entity.base.php');
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/packages/screen.php');
	
    //Controllers
    require_once (DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/class.web.services.php');
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.schedule.php');
    require_once (DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.package.php');
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.tools.php');


    /** ========================================================
     * ACTIVATE/DEACTIVE/UPDATE HOOKS
     * =====================================================  */
    register_activation_hook(__FILE__, 'duplicator_pro_activate');
    register_deactivation_hook(__FILE__, 'duplicator_pro_deactivate');

    function duplicator_pro_environment_checks()
    {
        require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/environment/class.environment.checker.php');
        
        $env_checker=new DUP_PRO_Environment_Checker();
        
        $status = $env_checker->check();
        
        $messages=$env_checker->getHelperMessages();
        
        if(!$status) {
            if(!empty($messages)) {
                $msg_str='';
                foreach ($messages as $id => $msgs) {
                    foreach ($msgs as $key => $msg) {
                        $msg_str .='<br/>' . $msg;
                    }
                }
                die($msg_str);
            }
        }
    }
    
    /**
     * Activation Hook:
     * Hooked into `register_activation_hook`.  Routines used to activate the plugin
     *
     * @access global
     * @return null
     */
    function duplicator_pro_activate()
    {
        global $wpdb;
        
        $current_version = get_option("duplicator_pro_plugin_version");
        
		error_log('activate');

        //Only update database on version update        
        if (DUPLICATOR_PRO_VERSION != $current_version)
        {
			error_log('before environmental checks');

            if ($current_version === false) {
                // Only do the environment checks th first time we are activating
                duplicator_pro_environment_checks();
            }

			error_log('after environmental checks');
            
            $table_name = $wpdb->base_prefix . "duplicator_pro_packages";

            //PRIMARY KEY must have 2 spaces before for dbDelta to work
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			   id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			   name VARCHAR(250) NOT NULL,
			   hash VARCHAR(50) NOT NULL,
			   status INT(11) NOT NULL,
			   created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			   owner VARCHAR(60) NOT NULL,
			   package MEDIUMBLOB NOT NULL,
			   PRIMARY KEY  (id),
			   KEY hash (hash))";

            require_once(DUPLICATOR_PRO_WPROOTPATH.'wp-admin/includes/upgrade.php');

			error_log('before dbdelta');

            @dbDelta($sql);

			error_log('after dbdelta');

            DUP_PRO_JSON_Entity_Base::init_table();
            DUP_PRO_Global_Entity::initialize_plugin_data();
            DUP_PRO_Secure_Global_Entity::initialize_plugin_data();
            DUP_PRO_System_Global_Entity::initialize_plugin_data();
            DUP_PRO_Package_Template_Entity::create_default();
            DUP_PRO_Package_Template_Entity::create_manual();

			error_log('after table insertion');

            if ($current_version === false) {
				error_log('current version is null');
                duplicator_pro_activate_new_install();
            } else if(version_compare($current_version, DUPLICATOR_PRO_VERSION, '<')){
                DUP_PRO_Upgrade_U::PerformUpgrade($current_version, DUPLICATOR_PRO_VERSION);
            } else {
				error_log("version isn't null nor is it before the new version");
			}
        }

        //WordPress Options Hooks
        if (update_option('duplicator_pro_plugin_version', DUPLICATOR_PRO_VERSION) === false) {
            DUP_PRO_LOG::trace("Couldn't update duplicator_pro_plugin_version so deleting it.");

            delete_option('duplicator_pro_plugin_version');

            if (update_option('duplicator_pro_plugin_version', DUPLICATOR_PRO_VERSION) === false) {
                DUP_PRO_LOG::trace("Still couldn’t update the option!");
            } else {
                DUP_PRO_LOG::trace("Option updated.");
            }
        }

        //Setup All Directories
        DUP_PRO_U::initStorageDirectory();
    }

    /**
     * Activation New:
     * Called only for first time install of plugin
     *
     * @access global
     * @return null
     */
    function duplicator_pro_activate_new_install()
    {
        $global                      = DUP_PRO_Global_Entity::get_instance();
  
        $global->save();
    }

    /**
     * Plugins Loaded:
     * Hooked into `plugin_loaded`.  Called once any activated plugins have been loaded.
     *
     * @access global
     * @return null
     */
    function duplicator_pro_plugins_loaded()
    {
        if (DUPLICATOR_PRO_VERSION != get_option("duplicator_pro_plugin_version")) {
            duplicator_pro_activate();
        }
        load_plugin_textdomain(DUP_PRO_Constants::PLUGIN_SLUG, FALSE, dirname(plugin_basename(__FILE__)).'/lang/');

        duplicator_pro_patched_data_initialization();
    }

    /**
     * Data Patches:
     * Handles data that needs to be initialized because of fixes etc
     *
     * @access global
     * @return null
     */
    function duplicator_pro_patched_data_initialization()
    {
        $global = DUP_PRO_Global_Entity::get_instance();
        $global->configure_dropbox_transfer_mode();

        if ($global->initial_activation_timestamp == 0) {
            $global->initial_activation_timestamp = time();
            $global->save();
        }
    }

    /**
     * Deactivation Hook:
     * Hooked into `register_deactivation_hook`.  Routines used to deactivate the plugin
     * For uninstall see uninstall.php  WordPress by default will call the uninstall.php file
     *
     * @access global
     * @return null
     */
    function duplicator_pro_deactivate()
    {
        //Logic has been added to uninstall.php
    }

    /**
     * Footer Hook:
     * Hooked into `admin_footer`.  Returns display elements for the admin footer area
     *
     * @access global
     * @return string A footer element for downloading a link
     */
	function duplicator_pro_admin_footer() 
	{
		$global = DUP_PRO_Global_Entity::get_instance();

		$trace_on = get_option('duplicator_pro_trace_log_enabled', false);
		$txt_trace_on	= DUP_PRO_U::__("Turn Off");
        $profiling_on = $global->trace_profiler_on;

        if($profiling_on) {
            $txt_trace_on .= ' ' . DUP_PRO_U::__('(P)');
        }

		$txt_trace_title  = DUP_PRO_U::__('TRACE LOG OPTIONS');
		$txt_trace_read  = DUP_PRO_U::__('View');
		$txt_trace_load  = DUP_PRO_U::__("Download") . ' (' . DUP_PRO_LOG::getTraceStatus() . ')';
        $txt_trace_zero  = DUP_PRO_U::__("Download") . ' (0B)';
        $txt_clear_trace = DUP_PRO_U::__('Clear');
        $url             = wp_nonce_url('admin.php?page=duplicator-pro-settings&_logging_mode=off&action=trace', 'duppro-settings-general-edit', '_wpnonce');
        $html            = <<<HTML
			<style>p#footer-upgrade {display:none}</style>
			<div id='dpro-monitor-trace-area'>
				<b>{$txt_trace_title}</b><br/>
				<a class='button button-small' href="admin.php?page=duplicator-pro-tools&tab=diagnostics&section=log" target="_duptracelog"><i class="fa fa-file-text"></i> {$txt_trace_read}</a>
                <a class='button button-small' onclick='DupPro.UI.ClearTraceLog();jQuery("#dup_pro_trace_txt").html("$txt_trace_zero");'><i class="fa fa-remove"></i> {$txt_clear_trace}</a>
				<a class='button button-small' onclick="var actionLocation = ajaxurl + '?action=duplicator_pro_get_trace_log';location.href = actionLocation;"><i class="fa fa-download"></i> <span id='dup_pro_trace_txt'>{$txt_trace_load}</span></a>
				<a class='button button-small' href='{$url}' onclick='window.location.reload();'><i class="fa fa-power-off"></i> {$txt_trace_on}</a>
			</div>
HTML;
        if ($trace_on) echo $html;
    }

    /** ========================================================
     * ACTION HOOKS
     * =====================================================  */
    $web_services = new DUP_PRO_Web_Services();
    $web_services->init();
	$GLOBALS['CTRLS_DUP_PRO_CTRL_Tools']	= new DUP_PRO_CTRL_Tools();
	$GLOBALS['CTRLS_DUP_PRO_CTRL_Package']	= new DUP_PRO_CTRL_Package();
    $GLOBALS['CTRLS_DUP_PRO_CTRL_Schedule']	= new DUP_PRO_CTRL_Schedule();

    add_action('plugins_loaded', 'duplicator_pro_plugins_loaded');
    add_action('plugins_loaded', 'duplicator_pro_wpfront_integrate');
    add_action('admin_init', 'duplicator_pro_init');

    if (isset($_REQUEST['page']) && DUP_PRO_STR::contains($_REQUEST['page'], 'duplicator-pro')) {
        add_action('admin_footer', 'duplicator_pro_admin_footer');
    }

    add_action('wp_ajax_DUP_PRO_UI_ViewState_SaveByPost', array('DUP_PRO_UI_ViewState', 'saveByPost'));

    if (DUP_PRO_MU::isMultisite()) {
        add_action('network_admin_menu', 'duplicator_pro_menu');
        add_action('network_admin_notices', array('DUP_PRO_UI_Notice', 'showReservedFilesNotice'));
        add_action('network_admin_notices', array('DUP_PRO_UI_Alert', 'licenseAlertCheck'));
        add_action('network_admin_notices', array('DUP_PRO_UI_Alert', 'failedScheduleCheck'));
    } else {
        add_action('admin_menu', 'duplicator_pro_menu');
        add_action('admin_notices', array('DUP_PRO_UI_Notice', 'showReservedFilesNotice'));
        add_action('admin_notices', array('DUP_PRO_UI_Alert', 'licenseAlertCheck'));
        add_action('admin_notices', array('DUP_PRO_UI_Alert', 'failedScheduleCheck'));
    }

    /**
     * Action Hook:
     * User role editor integration
     *
     * @access global
     * @return null
     */
    function duplicator_pro_wpfront_integrate()
    {
        $global = DUP_PRO_Global_Entity::get_instance();

        if ($global->wpfront_integrate) {
            do_action('wpfront_user_role_editor_duplicator_pro_init', array('export', 'manage_options', 'read'));
        }
    }

    /**
     * Action Hook:
     * Hooked into `admin_init`.  Init routines for all admin pages
     *
     * @access global
     * @return null
     */
    function duplicator_pro_init()
    {
        // wp_doing_ajax introduced in WP 4.7
		if (!function_exists('wp_doing_ajax') || ( ! wp_doing_ajax() ) ) {
			// CSS
			wp_register_style('dup-pro-jquery-ui', DUPLICATOR_PRO_PLUGIN_URL.'assets/css/jquery-ui.css', null, "1.11.2");
			wp_register_style('dup-pro-font-awesome', DUPLICATOR_PRO_PLUGIN_URL.'assets/css/font-awesome.min.css', null, '4.3.0');
			wp_register_style('dup-pro-plugin-style', DUPLICATOR_PRO_PLUGIN_URL.'assets/css/style.css', null, DUPLICATOR_PRO_VERSION);
			wp_register_style('dup-pro-parsley', DUPLICATOR_PRO_PLUGIN_URL.'assets/css/parsley.css', null, '2.0.6');
			wp_register_style('dup-pro-parsley', DUPLICATOR_PRO_PLUGIN_URL.'assets/css/parsley.css', null, '2.0.6');
			wp_register_style('dup-pro-jquery-qtip', DUPLICATOR_PRO_PLUGIN_URL.'assets/js/jquery.qtip/jquery.qtip.min.css', null, '2.2.1');
			wp_register_style('dup-pro-formstone', DUPLICATOR_PRO_PLUGIN_URL.'assets/js/formstone/bundle.css', null, '1.3.1');
			//JS
			wp_register_script('dup-pro-handlebars', DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/handlebars.min.js', array('jquery'), '4.0.10');
			wp_register_script('parsley', DUPLICATOR_PRO_PLUGIN_URL.'assets/js/parsley.min.js', array('jquery'), '2.0.6');
			wp_register_script('dup-pro-jquery-qtip', DUPLICATOR_PRO_PLUGIN_URL.'assets/js/jquery.qtip/jquery.qtip.min.js', array('jquery'), '2.2.1');
			wp_register_script('dup-pro-formstone', DUPLICATOR_PRO_PLUGIN_URL.'assets/js/formstone/bundle.js', array('jquery'), '2.2.1');
		}
    }

    /**
     * Action Hook:
     * Hooked into `admin_menu`.  Loads all of the admin menus for DupPro
     *
     * @access global
     * @return null
     */
    function duplicator_pro_menu()
    {
        $wpfront_caps_translator = 'wpfront_user_role_editor_duplicator_pro_translate_capability';
        $icon_svg                = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iQXJ0d29yayIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIyMy4yNXB4IiBoZWlnaHQ9IjIyLjM3NXB4IiB2aWV3Qm94PSIwIDAgMjMuMjUgMjIuMzc1IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyMy4yNSAyMi4zNzUiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGZpbGw9IiM5Q0ExQTYiIGQ9Ik0xOC4wMTEsMS4xODhjLTEuOTk1LDAtMy42MTUsMS42MTgtMy42MTUsMy42MTRjMCwwLjA4NSwwLjAwOCwwLjE2NywwLjAxNiwwLjI1TDcuNzMzLDguMTg0QzcuMDg0LDcuNTY1LDYuMjA4LDcuMTgyLDUuMjQsNy4xODJjLTEuOTk2LDAtMy42MTUsMS42MTktMy42MTUsMy42MTRjMCwxLjk5NiwxLjYxOSwzLjYxMywzLjYxNSwzLjYxM2MwLjYyOSwwLDEuMjIyLTAuMTYyLDEuNzM3LTAuNDQ1bDIuODksMi40MzhjLTAuMTI2LDAuMzY4LTAuMTk4LDAuNzYzLTAuMTk4LDEuMTczYzAsMS45OTUsMS42MTgsMy42MTMsMy42MTQsMy42MTNjMS45OTUsMCwzLjYxNS0xLjYxOCwzLjYxNS0zLjYxM2MwLTEuOTk3LTEuNjItMy42MTQtMy42MTUtMy42MTRjLTAuNjMsMC0xLjIyMiwwLjE2Mi0xLjczNywwLjQ0M2wtMi44OS0yLjQzNWMwLjEyNi0wLjM2OCwwLjE5OC0wLjc2MywwLjE5OC0xLjE3M2MwLTAuMDg0LTAuMDA4LTAuMTY2LTAuMDEzLTAuMjVsNi42NzYtMy4xMzNjMC42NDgsMC42MTksMS41MjUsMS4wMDIsMi40OTUsMS4wMDJjMS45OTQsMCwzLjYxMy0xLjYxNywzLjYxMy0zLjYxM0MyMS42MjUsMi44MDYsMjAuMDA2LDEuMTg4LDE4LjAxMSwxLjE4OHoiLz48L3N2Zz4=';
		
		/* @var $global DUP_PRO_Global_Entity */
		$global = DUP_PRO_Global_Entity::get_instance();

        //Main Menu
        $perms_txt = 'export';
        $perms     = apply_filters($wpfront_caps_translator, $perms_txt);

        $main_menu = add_menu_page('Duplicator Plugin', 'Duplicator Pro', $perms, DUP_PRO_Constants::PLUGIN_SLUG, 'duplicator_pro_get_menu', $icon_svg);

		$lang  = DUP_PRO_U::__('Packages');
        $page_packages = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG, 'duplicator_pro_get_menu');
        $GLOBALS['DUP_PRO_Package_Screen'] = new DUP_PRO_Package_Screen($page_packages);

		$perms_txt = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms_txt);

		$lang  = DUP_PRO_U::__('Schedules');
        $page_schedules = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$SCHEDULES_SUBMENU_SLUG, 'duplicator_pro_get_menu');

		$lang  = DUP_PRO_U::__('Storage');
        $page_storage = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$STORAGE_SUBMENU_SLUG, 'duplicator_pro_get_menu');

		//$lang  = DUP_PRO_U::__('Templates');
        //$page_templates = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$TEMPLATES_SUBMENU_SLUG, 'duplicator_pro_get_menu');

		$lang = DUP_PRO_U::__('Tools');
        $page_tools = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG,  'duplicator_pro_get_menu');

		$lang  = DUP_PRO_U::__('Settings');
        $page_settings = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG, 'duplicator_pro_get_menu');
		
		if ($global->debug_on) {
			$lang = DUP_PRO_U::__('Debug');
			$page_debug = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, $lang, $lang, $perms, DUP_PRO_Constants::$DEBUG_SUBMENU_SLUG, 'duplicator_pro_get_menu');
			add_action('admin_print_scripts-' . $page_debug, 'duplicator_pro_scripts');
			add_action('admin_print_styles-'  . $page_debug, 'duplicator_pro_styles');
		}
		
        //Apply Scripts
        add_action('admin_print_scripts-'.$page_packages, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-'.$page_schedules, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-'.$page_storage, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-'.$page_settings, 'duplicator_pro_scripts');
        //add_action('admin_print_scripts-'.$page_templates, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-'.$page_tools, 'duplicator_pro_scripts');

        //Apply Styles
        add_action('admin_print_styles-'.$page_packages, 'duplicator_pro_styles');
        add_action('admin_print_styles-'.$page_schedules, 'duplicator_pro_styles');
        add_action('admin_print_styles-'.$page_storage, 'duplicator_pro_styles');
        add_action('admin_print_styles-'.$page_settings, 'duplicator_pro_styles');
       // add_action('admin_print_styles-'.$page_templates, 'duplicator_pro_styles');
        add_action('admin_print_styles-'.$page_tools, 'duplicator_pro_styles');
    }

    /**
     * Menu Redirect:
     * Redirects the clicked menu item to the correct location
     *
     * @access global
     * @return null
     */
    function duplicator_pro_get_menu()
    {
        $current_page = isset($_REQUEST['page']) ? esc_html($_REQUEST['page']) : DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG;

        switch ($current_page) {
            case DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG: include('views/packages/controller.php');
                break;
            case DUP_PRO_Constants::$SCHEDULES_SUBMENU_SLUG: include('views/schedules/controller.php');
                break;
            case DUP_PRO_Constants::$STORAGE_SUBMENU_SLUG: include('views/storage/controller.php');
                break;
            case DUP_PRO_Constants::$TEMPLATES_SUBMENU_SLUG: include('views/templates/controller.php');
                break;
            case DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG: include('views/tools/controller.php');
                break;
            case DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG: include('views/settings/controller.php');
                break;
			 case DUP_PRO_Constants::$DEBUG_SUBMENU_SLUG: include('debug/main.php');
                break;

            default:
                DUP_PRO_LOG::traceObject("Error current page doesnt show up", $_REQUEST);
        }
    }

    /**
     * Enqueue Scripts:
     * Loads all required javascript libs/source for DupPro
     *
     * @access global
     * @return null
     */
    function duplicator_pro_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('parsley');
        wp_enqueue_script('dup-pro-jquery-qtip');
		wp_enqueue_script('dup-pro-formstone');
    }

    /**
     * Enqueue CSS Styles:
     * Loads all CSS style libs/source for DupPro
     *
     * @access global
     * @return null
     */
    function duplicator_pro_styles()
    {
        wp_enqueue_style('dup-pro-jquery-ui');
        wp_enqueue_style('dup-pro-font-awesome');
        wp_enqueue_style('dup-pro-parsley');
        wp_enqueue_style('dup-pro-plugin-style');
        wp_enqueue_style('dup-pro-jquery-qtip');
		wp_enqueue_style('dup-pro-formstone');
    }

    /** ========================================================
     * FILTERS
     * =====================================================  */   
	if(is_multisite()) {
        add_filter('network_admin_plugin_action_links', 'duplicator_pro_manage_link', 10, 2);
        add_filter('network_admin_plugin_row_meta', 'duplicator_pro_meta_links', 10, 2);
    } else {
        add_filter('plugin_action_links', 'duplicator_pro_manage_link', 10, 2);
        add_filter('plugin_row_meta', 'duplicator_pro_meta_links', 10, 2);
    }

    /**
     * Plugin MetaData:
     * Adds the manage link in the plugins list 
     *
     * @access global
     * @return string The manage link in the plugins list 
     */
    function duplicator_pro_manage_link($links, $file)
    {
        static $this_plugin;

        if (!$this_plugin) {
            $this_plugin = plugin_basename(__FILE__);
        }

        if ($file == $this_plugin) {
            $url           = DUP_PRO_U::getMenuPageURL(DUP_PRO_Constants::PLUGIN_SLUG, false);
            $settings_link = "<a href='$url'>".DUP_PRO_U::__('Manage').'</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    /**
     * Plugin MetaData:
     * Adds links to the plugins manager page
     *
     * @access global
     * @return string The meta help link data for the plugins manager
     */
    function duplicator_pro_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        if ($file == $plugin) {
            $help_url = DUP_PRO_U::getMenuPageURL(DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG, false);
            $links[]  = sprintf('<a href="%1$s" title="%2$s">%3$s</a>', $help_url, DUP_PRO_U::__('Get Help'), DUP_PRO_U::__('Help'));

            return $links;
        }
        return $links;
    }	
}
