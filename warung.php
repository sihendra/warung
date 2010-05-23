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
    add_action('wp_print_scripts', 'init_scripts');
    add_action('wp_print_styles', 'init_styles');
} else {
    exit ("Class Warung does not exist! Check warung_class exists");
}

function warung_init() {
    session_start();
    register_sidebar_widget('Warung Cart', 'warung_cart');
    add_filter('the_content', 'filter_content');
    // save cookie
    //updateShippingInfo();
    process_params();
    //var_dump($_REQUEST);
    //var_dump($_SESSION["wCart"]);
}

function init_scripts() {
    global $warung;
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery_form', $warung->pluginUrl.'scripts/jquery.form.js',array('jquery'));
    wp_enqueue_script('jquery_validaton', $warung->pluginUrl.'scripts/jquery.validate.js',array('jquery'));
    wp_enqueue_script('warung_js',$warung->pluginUrl.'scripts/warung.js',array('jquery'));
}
function init_styles() {
    global $warung;
    wp_enqueue_style('warung_style', $warung->pluginUrl.'style/warung.css');
}

//######################
//# USER DATA/SHIPPING #
//######################

function getShippingInfo() {
    $info = getDefaultShippingInfo();
    if (!isset($_COOKIE["wCartShipping"])) {
        // create default
        setcookie("wCartShipping", serialize($info), time()+60*60*24*30);
    } else {
        $info = unserialize($_COOKIE[wCartShipping]);
    }

    return $info;
}

function get_shipping_options($name='') {
    global $warung;
    $options = $warung->get_options();
    $shipping_options = $options["shipping_options"];

    if (!empty ($name)) {
        return $shipping_options[$name];
    } else {
        return $shipping_options;
    }
}

function get_shipping_info($shipping_name, $city_name) {
    $ret;
    global $warung;
    if (!empty($shipping_name) && !empty($city_name)) {
        $cities = get_shipping_options($shipping_name);
        if (!empty($cities)) {
            $cities = $warung->warung_parse_nameval_options($cities);
            foreach ($cities as $c) {
                if ($c->kota == $city_name) {
                    $ret = $c;
                }
            }
        }
    }
    return $ret;
}

function getDefaultShippingInfo() {
    global $warung;
    $options = $warung->get_options();
    $shipping_options = $options["shipping_options"];

    //pick the first one for now, we currently dont have the default checkbox in shipping
    $shipping_name = key(current($shipping_options));
    // find default shipping city
    $shipping_cities = $warung->warung_parse_nameval_options($shipping_option[$shipping_name]);
    $shipping_city = '';
    foreach ($shipping_cities as $c) {
        if ($c->default) {
            $shipping_city = $c->city;
        }
    }

    echo "SHIPPINGL:".$shipping_name;
    echo "SHIPPING CITY:".$shipping_city;

    return array("shipping_name"=>$shipping_name, "shipping_city"=> $shipping_city);

}

function updateShippingInfo() {
    $shipping_name = $_REQUEST["wcart_shipping_name"];
    $shipping_city = $_REQUEST["wcart_shipping_city"];


    $info = getDefaultShippingInfo();
    if (!isset($_COOKIE["wCartShipping"])) {
        // create default
        setcookie("wCartShipping", serialize($info), time()+60*60*24*30);
    } else {
        $info = unserialize($_COOKIE[wCartShipping]);
    }

    if (isset($_COOKIE["wCartUserInfo"]) && !empty($_COOKIE["wCartUserInfo"])) {

    } else {
        // create new
        $info = array("shipping_name"=>$shipping_name, "shipping_city"=> $shipping_city);
        $_COOKIE["wCartUserInfo"] = $info;
        //$_SESSION["wCartUserInfo"]
    }

}

function process_params() {
    global $warung;

    // update cart
    if (isset($_POST['add_to_cart'])) {
        $added_product = $warung->warung_get_product_by_id($_POST['product_id']);
        $added_product = formatForSession($added_product, $_POST["product_option"]);

        warung_add_to_cart($added_product);
    } else if (!empty($_REQUEST["wc_update"])) {
        foreach($_REQUEST as $key=>$val) {
            if (strpos($key,'qty_') !== false) {
                //echo $key.'->'.$val;
                $tok = explode('_',$key);
                if (count($tok) == 2) {
                    warung_update_cart($tok[1], $val);
                }
            }
        }
    } else if (!empty($_REQUEST["wc_rem"])) {
        warung_update_cart($_REQUEST["wc_rem"],0);
    } else if (!empty($_REQUEST["wc_clear"])) {
        warung_empty_cart();
    } else if (!empty($_REQUEST["scheckout"])) {
        update_shipping();
    }
}

function get_order_summary($isAdminView=false) {
    global $warung;
    ob_start();

    $harga_per_kg = 0;
    if (!empty($_SESSION['wCartShipping'])) {
        $harga_per_kg = $_SESSION['wCartShipping']['harga_per_kg'];
    }

    ?>
<div id="order-summary">
    <fieldset>
        <legend>Keranjang Belanja</legend>
        <table id="wcart-detailed">
            <tr><th>Item</th><th>Berat</th><th>Harga</th><th>Jumlah</th><th>Total</th></tr>
                <?
                foreach ($_SESSION["wCart"] as $p) {
                    //name|price[|type]
                    $pr = '';
                    extract($p);
                    $total += $p['quantity'] * $p['price'];
                    $remove_page = add_parameter($current_page, array("wc_rem"=>$cart_id));
                    $total_weight += $weight;
                    ?>
            <tr>
                <td><?=$name?></td>
                <td><?=$warung->formatWeight($weight)?></td>
                <td><?=$warung->formatCurrency($price)?></td>
                <td><?=$quantity?></td>
                <td><?=$warung->formatCurrency($quantity * $price)?></td>
            </tr>

                    <?
                }
                ?>
            <tr><td colspan="2">&nbsp;</td><td colspan="2">Total sebelum ongkos kirim</td><td><?=$warung->formatCurrency($total)?></td></tr>
                <?
                if(!empty($harga_per_kg)) {
                    ?>
            <tr><td colspan="2">&nbsp;</td><td colspan="2">Total setelah ongkos kirim</td><td><?=$warung->formatCurrency($total+$harga_per_kg*$total_weight)?></td></tr>
                    <?
                }

                ?>
        </table>
        <? if (!$isAdminView) { ?>
        <p><a href="<?=$warung->get_checkout_url()?>">Edit</a></p>
        <? } ?>
    </fieldset>
    <fieldset>
        <legend>Informasi Pengiriman</legend>

        <!-- info pelanggan -->
        <table>
                <?

                foreach ($_SESSION['wCartShipping'] as $k=>$v) {
                    if ($k == 'city') {
                        $arr = explode(';',$v);
                        $v = $arr[0];
                    }
                    if (!$isAdminView) {
                        if ($k == 'harga_per_kg') {
                            continue;
                        }
                    }
                    ?>
            <tr><td><?=$k?></td><td>&nbsp;:&nbsp;</td><td><?=$v?></td></tr>
                    <?
                }
                ?>
        </table>
        <? if (!$isAdminView) { ?>
        <p><a href="<?=$warung->get_shipping_url()?>">Edit</a></p>
        <? } ?>
    </fieldset>
</div>
    <?

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function send_order() {
    if (!empty($_SESSION['wCart']) && isset($_SESSION['wCartShipping'])) {
        //extract($_SESSION['wCartShipping']);
        $to = get_option("admin_email");
        $subject = 'Order '.mt_rand(10, 9999);
        $message = get_order_summary(true);
        //echo get_order_summary();
        $headers = "Content-type: text/html;\r\n";
        $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";
        return mail($to, $subject, $message, $headers);
    }

    return false;
}

function update_shipping() {
    extract($_REQUEST);

    $arr = explode(';',$scity);
    $info = get_shipping_info($sshipping, $arr[0]);

    $_SESSION['wCartShipping'] = array(
            'email'=>$semail,
            'phone'=>$sphone,
            'shipping'=>$sshipping,
            'name'=>$sname,
            'address'=>$saddress,
            'city'=>$scity,
            'additional_info'=>$sadditional_info,
            'harga_per_kg'=>$info->harga_per_kg
    );

    //var_dump ($_SESSION['wCartShipping']);
}

function add_parameter($url, $param) {
    $ret=$url;
    $qstr='';
    $i=0;
    foreach ($param as $key=>$value) {
        if ($i++ == 0) {
            $qstr .= $key.'='.$value;
        } else {
            $qstr .= '&'.$key.'='.$value;
        }
    }
    if (strpos($url,'?')) {
        $ret = $url.'&'.$qstr;
    } else {
        $ret = $url.'?'.$qstr;
    }

    return $ret;
}

function form_selected ($selname, $value) {
    if ($selname == $value) {
        return 'selected="selected"';
    }
    return '';
}

function form_select($name, $arr, $selected, $callback='') {
    $ret = '<select name="'.$name.'">';
    if (empty($callback)) {
        foreach ($arr as $k=>$v) {
            $ret .= '<option value="'.$k.'" '.form_selected($selected, $k).'>'.$v.'</option>';
        }
    } else {
        foreach ($arr as $k=>$v) {
            $r = call_user_func($callback, $k, $v);
            if (empty($selected) && isset($r['default'])) {
                $selected = $r['value'];
            }
            $ret .= '<option value="'.$r['value'].'" '.form_selected($selected, $r['value']).'>'.$r['name'].'</option>';
        }
    }
    $ret .= '</select>';
    return $ret;
}

function kv_callback($k, $v) {
    return array('value'=>$v, 'name'=>$v);
}

function city_callback($k, $v) {
    $arr = array ('value'=> $v->kota.';'.$v->harga_per_kg, 'name' => $v->kota);
    if (isset($v->default)) {
        $arr['default'] = true;
    }
    return $arr;
}

function show_shipping_form() {
    global $warung;
    ob_start();

    $shippings = $warung->get_shipping_services();
    $shipping = '';
    $city = '';

    if(count($shippings) == 1 && empty($_SESSION['wCartShipping']['shipping'])) {
        $shipping = $shippings[0];
    }

    if (!isset($_SESSION['wCartShipping'])) {

        $_SESSION['wCartShipping'] = array(
                'email'=>'',
                'phone'=>'',
                'shipping'=>$shipping,
                'name'=>'',
                'address'=>'',
                'city'=>$city,
                'additional_info'=>''
        );

    }

    //var_dump($_SESSION['wCartShipping']);

    extract($_SESSION['wCartShipping']);

    $cities=array();
    if (!empty($shipping)) {
        $cities = $warung->get_shipping_cities($shipping);
    }

    $co_page = $warung->get_checkout_url();

    ?>
<div id="shipping_form">
    <form method="POST" name="wCart_shipping_form">
        <input type="hidden" name="step" value="3"/>

        <fieldset>
            <legend >Info Pemesan</legend>
            <table>
                <tr><td><label for="">Email</label></td><td>&nbsp;</td><td><input type="text" name="semail" value="<?=$email?>"/></td></tr>
                <tr><td><label for="">Telepon</label></td><td>&nbsp;</td><td><input type="text" name="sphone" value="<?=$phone?>"/></td></tr>
                <tr><td><label for="">Jasa Pengiriman</label></td><td>&nbsp;</td><td><?=form_select('sshipping', $shippings, $shipping, 'kv_callback')?></td></tr>
            </table>
        </fieldset>
        <fieldset>
            <legend>Info pengiriman</legend>
            <table>
                <tr><td><label for="">Nama Penerima</label></td><td>&nbsp;</td><td><input type="text" name="sname" value="<?=$name?>"/></td></tr>
                <tr><td><label for="">Alamat</label></td><td>&nbsp;</td><td><textarea name="saddress" rows="3" cols="60"><?=$address?></textarea></td></tr>
                <tr><td><label for="">Kota</label></td><td>&nbsp;</td><td>
                            <?=form_select('scity', $cities, $city, 'city_callback')?></td></tr>
                <tr><td><label for="">Info Tambahan</label></td><td>&nbsp;</td><td><textarea name="sadditional_info" rows="3" cols="60"><?=$additional_info?></textarea></td></tr>
                <!--<tr><td colspan="2">&nbsp;</td><td><input type="submit" name="scheckout" value="Lanjut"/></td></tr>-->
            </table>

        </fieldset>
        <input type="submit" name="scheckout" value="Lanjut"/>
    </form>
</div>
    <?

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function show_address_form() {
    ob_start();

    $shippings = get_shipping_options();

    if (isset($_SESSION['wCartShipping'])) {

        extract($_SESSION['wCartShipping']);
        ?>
<form method="POST">
    <div id="shipping_form">
        <fieldset>
            <legend>Info pengiriman</legend>
            <table>
                <tr><td><label for="">Nama Penerima</label></td><td>&nbsp;</td><td><input type="text" name="name" value="<?=$name?>"/></td></tr>
                <tr><td><label for="">Alamat</label></td><td>&nbsp;</td><td><textarea name="address" rows="3" cols="150"><?=$address?></textarea></td></tr>
                <tr><td><label for="">Kota</label></td><td>&nbsp;</td><td>
                        <select name="city">
                            <option value="jakarta">Jakarta</option>
                            <option value="semarang">Semarang</option>
                        </select></td></tr>
                <tr><td colspan="2">&nbsp;</td><td><input type="submit" name="submit" value="Kirim"/></td></tr>
            </table>
        </fieldset>
    </div>
</form>
        <?

    } else {
        ?>
<div>pilih checkout page</div>
        <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;

}

function show_confirmation_form() {
    ob_start();
    if (isset($_SESSION['wCartShipping'])) {
        ?>
<div style="padding: 10px;">
    <form method="POST">
        <input type="hidden" name="step" value="4"/>
        <input type="submit" name="send_order" value="Kirim Pesanan"/>
    </form>
</div>
        <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function get_cart_summary() {
    $ret = array();
    if (isset($_SESSION["wCart"]) && count($_SESSION["wCart"]) > 0) {

        $harga_per_kg = 0;
        if (!empty($_SESSION['wCartShipping'])) {
            $harga_per_kg = $_SESSION['wCartShipping']['harga_per_kg'];
        }

        $total_weight = 0;
        $total_price = 0;
        $total_items = 0;
        $total_ongkir = 0;

        foreach ($_SESSION["wCart"] as $p) {
            //name|price[|type]
            extract($p);
            $total_price += $p['quantity'] * $p['price'];
            $total_weight += $weight;
            $total_items += $p['quantity'];
        }

        $ret['total_price'] = $total_price;
        $ret['total_ongkir'] = $total_weight * $harga_per_kg;
        $ret['total_items'] = $total_items;
    }

    return $ret;
}

function show_detailed_cart() {
    global $warung;

    ob_start();

    // show cart
    if (isset($_SESSION["wCart"]) && count($_SESSION["wCart"]) > 0) {

        $harga_per_kg = 0;
        if (!empty($_SESSION['wCartShipping'])) {
            $harga_per_kg = $_SESSION['wCartShipping']['harga_per_kg'];
        }

        $current_page = get_permalink();
        $co_page = $warung->get_checkout_url();
        $clear_page = add_parameter($current_page, array("wc_clear"=>"1"));
        $shipping_page = add_parameter($co_page, array("step"=>"2"));

        sort($_SESSION["wCart"]);

        $total_weight = 0;
        $total = 0;
        $ongkir = 0;
        ?>
<form method="POST">
    <table id="wcart-detailed">
        <tr><th>Item</th><th>Berat</th><th>Harga</th><th>Jumlah</th><th>Total</th></tr>
                <?
                foreach ($_SESSION["wCart"] as $p) {
                    //name|price[|type]
                    $pr = '';
                    extract($p);
                    $total += $p['quantity'] * $p['price'];
                    $remove_page = add_parameter($current_page, array("wc_rem"=>$cart_id));
                    $total_weight += $weight;
                    ?>
        <tr>
            <td><?=$name?></td>
            <td><?=$warung->formatWeight($weight)?></td>
            <td><?=$warung->formatCurrency($price)?></td>
            <td><input type="text" name="qty_<?=$cart_id?>" value="<?=$quantity?>" size="1"/></td>
            <td><?=$warung->formatCurrency($quantity * $price)?></td>
            <td><a id="urlbuton" href="<?=$remove_page?>">Remove</a></td>
        </tr>

                    <?
                }
                ?>
        <tr><td colspan="3"></td><td><input type="submit" name="wc_update" value="Update"/></td><td>&nbsp;</td></tr>
        <tr><td colspan="3">&nbsp;</td><td>Total sebelum ongkos kirim</td><td><?=$warung->formatCurrency($total)?></td></tr>
                <?
                if(!empty($harga_per_kg)) {
                    ?>
        <tr><td colspan="3">&nbsp;</td><td>Total setelah ongkos kirim</td><td><?=$warung->formatCurrency($total+$harga_per_kg*$total_weight)?></td></tr>
                    <?
                }

                ?>
    </table>

    <div id="wcart_co">
        <a href="<?=$co_page?>">Kembali Berbelanja</a>&nbsp|&nbsp;<a href="<?=$shipping_page?>">Lanjut ke Pemesanan &gt;&gt;</a>
    </div>
</form>
        <?

    } else {
        echo '<p id="status">Kosong</p>';
    }

    $ret = ob_get_contents();
    ob_end_clean();


    return $ret;
}


function warung_cart($args=array()) {
    global $post;
    global $warung;
    extract($args);

    echo $before_widget;
    echo $before_title .'Keranjang Belanja'. $after_title;

    // show cart

    $cart_sumary = get_cart_summary();

    if (!empty($cart_sumary)) {
        $options = $warung->get_options();
        $co_page = get_permalink($options['checkout_page']);
        $clear_page = add_parameter(get_permalink($post->ID), array("wc_clear"=>"1"));

        extract($cart_sumary);

        echo '<div id="wcart"><p>Ada '.$total_items.' Item</p><p>Total: '.$warung->formatCurrency($total_price).'</p></div>';
        echo '<div id="wcart_co"><a href="'.$co_page.'">Checkout</a>&nbsp|&nbsp<a href="'.$clear_page.'">Clear</a></div>';
    } else {
        echo '<p id="status">Kosong</p>';
    }
    echo $after_widget;
}

function warung_update_cart($id, $qtt) {
    if (!empty($_SESSION["wCart"])) {

        foreach($_SESSION["wCart"] as $i => $p) {
            if ($id == $p['cart_id']) {
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
        if ($product["cart_id"] == $p['cart_id']) {
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

function warung_empty_cart() {
    unset($_SESSION["wCart"]);
    //unset($_SESSION['wCartShipping']);
}

function formatForSession($product, $opt_id = -1) {
    global $warung;
    $ret = array();

    if (!empty($product)) {
        if ($opt_id != -1) {
            $opt = $warung->warung_get_selected_option($product, $opt_id);
            if (!empty($opt)) {
                $ret["cart_id"] = $product["id"].'-'.$opt->id;
                $ret["id"] = $product["id"];
                $ret['name'] = $product["name"].' - '.$opt->name;
                $ret['price'] = $opt->price;
                $ret["weight"] = $opt->weight;
                $ret['quantity'] = 1;

                $ret['opt_name'] = $opt->name;
                $ret['opt_id'] = $opt->id;
            }
        } else {
            $ret["cart_id"] = $product["id"];
            $ret["id"] = $product["id"];
            $ret['name'] = $product["name"];
            $ret['price'] = $product["price"];
            $ret["weight"] = $product["weight"];
            $ret['quantity'] = 1;
        }
    }

    return $ret;
}

function filter_content($content) {
    global $post;

    global $warung;
    $options = $warung->get_options();
    $co_page = $options['checkout_page'];

    //var_dump($_SESSION['wCartShipping']);
    //var_dump($_REQUEST);
    //var_dump($_SESSION);
    //var_dump($_SESSION['wCart']);

//    var_dump ($post);// == $co_page;
//    var_dump ($co_page);

    //echo 'postid:'.$post->ID;
    ob_start();


    if ($post->ID == $co_page) {

        $step = $_REQUEST['step'];

        if (empty($step)) {
            $step = 1;
        }

        if (!empty($_SESSION['wCart'])) {
            if ($step==1) {
                echo show_detailed_cart();
            } else if ($step==2) {
                echo show_shipping_form();
            } else if ($step==3) {
                echo get_order_summary();
//                echo show_detailed_cart();
//                echo show_shipping_form();
                echo show_confirmation_form();
            } else if ($step==4 && !empty($_REQUEST['send_order'])) {
                if (send_order()) {
                    ?>
<span>Terima kasih pesanan anda sudah kami terima. Kami akan menghubungi anda secepatnya. <a href="http://www.warungsprei.com">Kembali berbelanja</a></span>
                    <?
                    warung_empty_cart();
                } else {
                    ?>
<span>Maaf kami blm dpt memproses pesanan anda. Silahkan coba bbrp saat lagi.</span>
                    <?
                }
            }
        } else {
            ?>
<span class="error">Keranjang belanja kosong. Silahkan pilih produk yang akan anda beli.</span>
            <?
        }
        $content = ob_get_contents();
    } else {
        // check is this post contains product informations

        $product = $warung->warung_get_product_by_id($post->ID);

        if (!empty($product) && !is_search()) {

            echo '<div>';

            echo '<form method="POST">';
            echo '<input type="hidden" name="product_id" value="'.$product["id"].'">';
            if (!empty($product["option_value"])) {
                echo '<select name="product_option">';
                foreach($product["option_value"] as $po) {
                    echo '<option value="'.$po->id.'">'.$po->name.'@'.$warung->formatCurrency($po->price).'</option>';
                }
                echo "</select>";
            } else {
                echo '<h2>'.$warung->formatCurrency($product["price"]).'<h2>';
            }

            $options = $warung->get_options();
            echo '<input type="submit" name="add_to_cart" value="'.$options["add_to_cart"].'"/>';
            echo '</form>';

            echo '</div>';


            $content .= ob_get_contents();

        }
    }

    ob_clean();

    return $content;

}

?>