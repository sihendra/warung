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

if (class_exists("Warung")) {
    $warung = new Warung();
    if (isset($warung)) {
        register_activation_hook(__FILE__, array(&$warung,'install'));
    }
    add_action('init', 'warung_init');
} else {
    exit ("Class Warung does not exist!");
}

function warung_init() {
    session_start();
    register_sidebar_widget('Warung Cart', 'warung_cart');
}

function warung_cart($args=array()) {
    extract($args);

    echo $before_widget;
    echo $before_title .'Keranjang Belanja'. $after_title;

    // update cart
    if (isset($_POST['product'])) {
        // product = name|price[|type]
        // get name
        $added_product = formatForSession($_POST['product']);

        if (isset($_SESSION["wCart"])) {
            $exists = false;
            foreach($_SESSION["wCart"] as $i => $p) {
                echo $added_product['name'].' vs '.$p['name'];
                if ($added_product['name'] == $p['name']) {
                    // increase quantity
                    $p['quantity'] += 1;
                    unset($_SESSION["wCart"][$i]);
                    array_push($_SESSION["wCart"], $p);
                    $exists = true;
                }
            }

            if (!$exists) {
                echo 'ditambahin';
                array_push($_SESSION["wCart"],$added_product);
            } else {
                sort($_SESSION["wCart"]);
            }

        } else {
            $_SESSION["wCart"] = array();
        }
    }

    // show cart
    if (!isset($_SESSION["wCart"])) {
        $_SESSION["wCart"] = array();
    }
    print_r($_SESSION["wCart"]);
    ?>
    <p>Jumlah item: <?php echo count($_SESSION["wCart"])?></p>
    <?
    if (count($_SESSION["wCart"])) {
        foreach ($_SESSION["wCart"] as $p) {
            echo $p["name"].' = '.$p["quantity"]."<br/>";
        }
    }
    echo $after_widget;
}

function formatForSession($str) {
    $ret = array();

    $tmp = explode('|',$str);
    if (count($tmp) == 3) {
        $ret['name'] = $tmp[0].'-'.$tmp[2];
        $ret['price'] = $tmp[1];
        $ret['quantity'] = 1;
    } else if (count($tmp) == 2) {
        $ret['name'] = $tmp[0];
        $ret['price'] = $tmp[1];
        $ret['quantity'] = 1;
    }

    return $ret;
}

?>