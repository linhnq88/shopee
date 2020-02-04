<?php
defined("ABSPATH") or die("");
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   DUP_PRO
 * @link      https://snapcreek.com
 * @Copyright 2016 Snapcreek.com
 */
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN'))
{
    exit;
}
require_once 'define.php';
require_once 'classes/utilities/class.u.php';
require_once 'classes/utilities/class.u.low.php';

delete_option('duplicator_pro_plugin_version');

function DUP_PRO_deactivate_license()
{
    $license = get_option('duplicator_pro_license_key', '');

    if (empty($license) === false)
    {
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license' => $license,
            'item_name' => urlencode('Duplicator Pro')
        );

        // Call the custom API.
        $response = wp_remote_get(add_query_arg($api_params, 'https://snapcreek.com'));

        $response_string = print_r($response, true);
            
        DUP_PRO_Low_U::errLog("deactivate license response $response_string");
            
        // make sure the response came back okay
        if (is_wp_error($response))
        { 
            //DUP_PRO_LOG::traceObject("Error deactivating $license", $response);
            DUP_PRO_Low_U::errLog("error deactivating license $license");
            //return;
        }
        else
        {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            $license_data_string = print_r($license_data, true);

            DUP_PRO_Low_U::errLog("After deactivating license key license_data=$license_data_string");
        }
                                           
        // No error handling / reporting in this version - want it as simple as possible
    }
    else
    {
        DUP_PRO_Low_U::errLog('license key is empty on uninstall!');
    }
}

DUP_PRO_deactivate_license();

?>