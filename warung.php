<?php
/*
Plugin Name: Warung
Plugin URI: http://warung.org/
Description: Simple shop plugins.
Author: Hendra Setiawan
Version: 1.0
Author URI: http://hendra.org/
*/

// version check
global $wp_version;

$exit_msg = 'Warung, require WordPress 2.6 or newer.
<a href="http://codex.wordpress.org/
Upgrading_WordPress">Please update!</a>';

if (version_compare($wp_version, "2.6", "<")) {
    exit($exit_msg);
}

// includes
function warung_class_loader($n) {
    $theClass = dirname(__FILE__) . '/includes/' . $n . '.php';
    if (file_exists($theClass) && include_once($theClass)) {
        return TRUE;
    } else {
        //trigger_error("The class '$class' or the file '$theClass' failed to spl_autoload  ", E_USER_WARNING);
        return FALSE;
    }
}

spl_autoload_register('warung_class_loader');


// instantiate required class
$warung = new Warung();
$warungAdmin = new WarungAdmin($warung);

if (isset($warung)) {

    // call init methods
    register_activation_hook(__FILE__, array(&$warung,'install'));

    // sessions and params processing
    add_action('init', array(&$warung,'init'));

    // admin menu/page
    add_action('admin_menu', array(&$warungAdmin, 'admin_menu'));

    // widget
    add_action('widgets_init', create_function('', 'return register_widget("WarungCartWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("WarungFeaturedContentWidget");'));

    // css and JS
    add_action('wp_print_scripts', array(&$warung, 'init_scripts'));
    add_action('wp_print_styles', array(&$warung, 'init_styles'));
} else {
    exit("Fail initialize warung class");
}


?>