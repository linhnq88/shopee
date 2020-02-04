<?php
defined("ABSPATH") or die("");

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/lib/snaplib/class.snaplib.u.url.php');

$profile_url = DUP_PRO_U::getMenuPageURL(DUP_PRO_Constants::$SCHEDULES_SUBMENU_SLUG, false);
$schedules_tab_url = SnapLibURLU::appendQueryValue($profile_url, 'tab', 'schedules');
$edit_schedule_url = SnapLibURLU::appendQueryValue($schedules_tab_url, 'inner_page', 'edit');
$inner_page = isset($_REQUEST['inner_page']) ? esc_html($_REQUEST['inner_page']) : 'schedules';
new DUP_PRO_CTRL_Schedule();
switch ($inner_page)
{
    case 'schedules': include('schedule.list.php');
        break;
    case 'edit': include('schedule.edit.php');
        break;
}
?>