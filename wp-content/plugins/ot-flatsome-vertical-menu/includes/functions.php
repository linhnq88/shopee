<?php

function ot_fl_vm_get_option($key, $default = '')
{
    $options = get_option('ot_vm');
    $option = isset($options[$key]) ? $options[$key] : $default;
    return $option;
}

function ot_fl_vm_class($classes){
	$classes[] = 'ot-vertical-menu';
    $overlay = ot_fl_vm_get_option('show_overlay', 0);
    $sub_top = ot_fl_vm_get_option('sub_top', 0);
    $show_home = ot_fl_vm_get_option('show_home', 0);
    if($overlay){
	    $classes[] = 'ot-overplay';
    }
    if($sub_top){
	    $classes[] = 'ot-submenu-top';
    }
    if($show_home){
	    $classes[] = 'ot-menu-show-home';
    }
	return $classes;
}
add_filter('body_class', 'ot_fl_vm_class');
