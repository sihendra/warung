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

$warung;
if (class_exists("Warung")) {
    $warung = new Warung();
    if (isset($warung)) {
        register_activation_hook(__FILE__, array(&$warung,'install'));
    }
    add_action('init', 'warung_init');
    add_action('admin_menu', array(&$warung, 'admin_menu'));
} else {
    exit ("Class Warung does not exist!");
}

function warung_init() {
    session_start();
    register_sidebar_widget('Warung Cart', 'warung_cart');
    add_filter('the_content', 'checkout');
}

function warung_cart($args=array()) {
    global $warung;
    extract($args);

    echo $before_widget;
    echo $before_title .'Keranjang Belanja'. $after_title;

    // update cart
    
    if (isset($_POST['add_to_cart'])) {
        // product = name|price[|type]
        // get name
        $added_product = $warung->warung_get_product_by_id($_POST['product_id']);
        $added_product = formatForSession($added_product, $_POST["product_option"]);

        warung_add_to_cart($added_product);
    } else if (!empty($_POST["update_cart"])) {
        warung_update_cart($_POST["product_name"], $_POST["product_quantity"]);
    }

    // show cart
    if (!isset($_SESSION["wCart"])) {
        $_SESSION["wCart"] = array();
    }
    if (count($_SESSION["wCart"]) > 0) {
        sort($_SESSION["wCart"]);

        $total = 0;        
        echo '<table id="wcart">';
        echo '<tr><th>Item</th><th>Berat</th><th>Jumlah</th><th>Harga</th></tr>';
        foreach ($_SESSION["wCart"] as $p) {
            //name|price[|type]
            $pr = '';
            extract($p);

            echo '<tr><td>'.$name.'</td>
                <td>'.$weight.'</td>
                <td>
                <form method="POST">
                <input type="text" name="product_quantity" value="'.$quantity.'" size="2"/>
                <input type="hidden" name="product_name" value="'.$name.'"/>
                <input type="submit" name="update_cart" value="update" style="display:none;"/>
                </form>
                </td>
                <td>'.$p['quantity'] * $p['price'].'</td></tr>';
            $total += $p['quantity'] * $p['price'];
        }
        echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
        echo '<tr><td>Total</td><td>&nbsp</td><td>&nbsp;</td><td>'.$total.'</td></tr>';
        echo '</table>';
        // checkout part
        global $warung;
        $options = $warung->get_options();
        $co_page = $options['checkout_page'];
        echo '<a href="'.get_permalink($co_page).'">Checkout</a>';

    } else {
        echo '<p>Kosong</p>';
    }
    echo $after_widget;
}

function warung_update_cart($name, $qtt) {
    if (!empty($_SESSION["wCart"])) {
        
        foreach($_SESSION["wCart"] as $i => $p) {
            if ($name == $p['name']) {
                // increase quantity
                $p['quantity'] = $qtt;

                unset($_SESSION["wCart"][$i]);
                if ($p['quantity'] > 0) {
                    array_push($_SESSION["wCart"],$p);
                }
                break;
            }
        }

    }

}

function warung_add_to_cart($product) {
    if (!isset($_SESSION["wCart"])) {
        $_SESSION["wCart"] = array();
    }

    $exists = false;
    foreach($_SESSION["wCart"] as $i => $p) {
        if ($product["name"] == $p['name']) {
            // increase quantity
            $p['quantity'] += 1;

            unset($_SESSION["wCart"][$i]);
            if ($p['quantity'] > 0) {
                array_push($_SESSION["wCart"],$p);
            }
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        // add new product
        array_push($_SESSION["wCart"],$product);
    }


}

function formatForSession($product, $opt_name = '') {
    global $warung;
    $ret = array();

    if (!empty($product)) {
        if (!empty($opt_name)) {
            $opt = $warung->warung_get_selected_option($product, $opt_name);
            if (!empty($opt)) {
                $ret["id"] = $product["id"];
                $ret['name'] = $product["code"].'-'.$opt_name;
                $ret['price'] = $opt->price;
                $ret["weight"] = $opt->weight;
                $ret['quantity'] = 1;
                $ret['option'] = $opt_name;
            }
        } else {
            $ret["id"] = $product["id"];
            $ret['name'] = $product["code"];
            $ret['price'] = $product["price"];
            $ret["weight"] = $product["weight"];
            $ret['quantity'] = 1;
        }
    }

    return $ret;
}

function checkout($content) {
    global $post;

    global $warung;
    $options = $warung->get_options();
    $co_page = $options['checkout_page'];

    ob_start();

    if ($post->ID == $co_page) {
        if (function_exists('insert_custom_cform')) {

            if (!empty($_SESSION['wCart'])) {

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
                        array('Items','fieldsetstart',0,0,0,0,0)
                        );


                $total = 0;
                foreach ( $_SESSION['wCart'] as $item )
                {
                    $totalprice = $item['quantity'] * $item['price'];
                    $formdata[] = array('item|'.$item['quantity'] . ' x  '. $item['name'] . ' Price: ' . $totalprice , 'hidden',0,0,0,0,0);
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
                ?>
                <span class="error">Keranjang belanja kosong. Silahkan pilih produk yang akan anda beli.</span>
                <?
            }
        } else {
            ?><span class="error"><a href="http://www.deliciousdays.com/cforms-plugin/">You must have CFormsII installed before you can use this email function.</a></span><?php
        }

        $content = ob_get_contents();
    } else {
        // check is this post contains product informations

        $product = $warung->warung_get_product_by_id($post->ID);

        if (!empty($product)) {

            echo '<div>';

            echo '<form method="POST">';
            echo '<input type="hidden" name="product_id" value="'.$product["id"].'">';
            if (!empty($product["option_value"])) {
                echo '<select name="product_option">';
                foreach($product["option_value"] as $po) {
                    echo '<option value="'.$po->name.'">'.$po->name.'@'.$warung->formatCurrency($po->price).'</option>';
                }
                echo "</select>";
            } else {
                echo '<h2>'.$warung->formatCurrency($product["price"]).'<h2>';
            }
            
            echo '<input type="submit" name="add_to_cart" value="Add to cart"/>';
            echo '</form>';

            echo '</div>';


            $content .= ob_get_contents();
            
        }
    }
    
    ob_clean();

    return $content;

}

// CForms2 API for Emailer
if (!function_exists('my_cforms_action()')) {
    function my_cforms_action($cformsdata)
    {
        $_SESSION['wCart'] = array();
    }
}

?>