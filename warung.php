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
    add_filter('the_content', 'checkout');
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

        if (!isset($_SESSION["wCart"])) {
            $_SESSION["wCart"] = array();
        }
        
        $exists = false;
        foreach($_SESSION["wCart"] as $i => $p) {
            if ($added_product['name'] == $p['name']) {
                if (isset($_POST['update']) && isset($_POST['quantity'])) {
                    // update quantity
                    $q = $_POST['quantity'];
                    if (!is_numeric($q)) {
                        $q = 1;
                    }
                    $p['quantity'] = $q;
                } else {
                    // increase quantity
                    $p['quantity'] += 1;
                }
                unset($_SESSION["wCart"][$i]);
                if ($p['quantity'] > 0) {
                    array_push($_SESSION["wCart"],$p);
                }
                $exists = true;
            }
        }

        if (!$exists) {
            // add new product
            array_push($_SESSION["wCart"],$added_product);
        }

        sort($_SESSION["wCart"]);

       
    }

    // show cart
    if (!isset($_SESSION["wCart"])) {
        $_SESSION["wCart"] = array();
    }
    if (count($_SESSION["wCart"])) {
        $total = 0;        
        echo '<table id="wcart">';
        echo '<tr><th>Item</th><th>Jumlah</th><th>Harga</th></tr>';
        foreach ($_SESSION["wCart"] as $p) {
            //name|price[|type]
            $pr = '';
            extract($p);

            if (isset($name)) {
                $pr = $name;
                if (isset($price)) {
                    $pr .= '|'.$price;
                    if (isset($type)) {
                        $pr .= '|'.$type;
                    }
                }
            }

            echo '<tr><td>'.$p["name"].'</td>
                <td>
                <form action="#" method="POST">
                <input type="text" name="quantity" value="'.$p["quantity"].'" size="2"/>
                <input type="hidden" name="product" value="'.$pr.'"/>
                <input type="submit" name="update" value="update" style="display:none;"/>
                </form>
                </td>
                <td>'.$p['quantity'] * $p['price'].'</td></tr>';
            $total += $p['quantity'] * $p['price'];
        }
        echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
        echo '<tr><td>Total</td><td>&nbsp;</td><td>'.$total.'</td></tr>';
        echo '</table>';
        // checkout part
        echo '<a href="'.get_permalink(2).'">Checkout</a>';

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

function checkout($content) {
    global $post;

    ob_start();
    
    if ($post->ID == 2) {
        if (function_exists('insert_custom_cform')) {

            $fields = array();

            // Need help? See your /wp-admin/admin.php?page=cforms/cforms-help.php
            $formdata = array(
                    array('Informasi Pembeli','fieldsetstart',0,0,0,0,0),
                    array('Nama','textfield',0,1,0,1,0),
                    array('Email','textfield',0,0,1,0,0),
                    array('Nomor Telepon','textfield',0,1,0,0,0),
                    array('Alamat','textfield',0,1,0,0,0),
                    array('Kodepos','textfield',0,1,0,0,0),
                    array('Kota','textfield',0,1,0,0,0),
                    array('Komentar lainnya','textarea',0,0,0,0,0),
                    array('','fieldsetend',0,0,0,0,0),
                    array('Item','fieldsetstart',0,0,0,0,0)
                    );

            $body = '';
            $total = 0;
            foreach ( $_SESSION['wCart'] as $item )
            {
                    $totalprice = $item['quantity'] * $item['price'];
                    $formdata[] = array( 'item|'.$item['quantity'] . ' x  '. $item['name'] . ' Price: ' . $totalprice , 'hidden',0,0,0,0,0);
                    $total += $totalprice;
            }
            $formdata[] = array('total|'.$total, 'hidden',0,0,0,0,0);
            $formdata[] = array('Attached to email.', 'textonly',0,0,0,0,0);
            $formdata[] = array('','fieldsetend',0,0,0,0,0);

            $i=0;
            foreach ( $formdata as $field ) {
                    $fields['label'][$i]        = $field[0];
                    $fields['type'][$i]         = $field[1];
                    $fields['isdisabled'][$i]   = $field[2];
                    $fields['isreq'][$i]        = $field[3];
                    $fields['isemail'][$i]      = $field[4];
                    $fields['isclear'][$i]      = $field[5];
                    $fields['isreadonly'][$i++] = $field[6];
            }


            insert_custom_cform($fields,'');
        } else {
            ?><span class="error"><a href="http://www.deliciousdays.com/cforms-plugin/">You must have CFormsII installed before you can use this email function.</a></span><?php
        }
    }
    
    $content .= ob_get_contents();
    ob_clean();

    return $content;

}

?>