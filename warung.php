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
function __autoload($n) {
    require_once 'includes/'.$n.'.php';
}

require_once 'warung_class.php';

if (class_exists("Warung")) {

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
} else {
    exit ("Class Warung does not exist! Check warung_class exists");
}

?>