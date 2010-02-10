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

// init
require_once 'warung_class.php';

function warungCart($args=array()) {
    extract($args);

    echo $before_widget;
    echo $before_title .'Keranjang Belanja'. $after_title;
    if (isset($_POST['product'])) {
        if (isset($_SESSION["warungCart"])) {
            $_SESSION["warungCart"] += 1;
        } else {
            $_SESSION["warungCart"] = 1;
        }
    }

    if (!isset($_SESSION["warungCart"])) {
        $_SESSION["warungCart"] = 0;
    }
    ?>
    <p>Jumlah item: <?php echo $_SESSION["warungCart"]?></p>
    <?
    echo $after_widget;
}

function my_init() {
    session_start();
    register_sidebar_widget('Warung Cart', 'warungCart');
}

if (class_exists("Warung")) {
    $warung = new Warung();
    if (isset($warung)) {
        register_activation_hook(__FILE__, array(&$warung,'install'));
    }
    add_action('init', 'my_init');
} else {
    exit ("Class Warung does not exist!");
}

?>