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
    add_action('wp_print_scripts', array(&$warung, 'init_scripts'));
    add_action('wp_print_styles', array(&$warung, 'init_styles'));
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

//######################
//# USER DATA/SHIPPING #
//######################

function get_shipping() {
    global $warung;

    $shippings = $warung->get_shipping_services();
    $shipping = '';
    $city = '';

    if(count($shippings) == 1) {
        $shipping = $shippings[0];
    }

    $tmp_info = array(
            'email'=>'',
            'phone'=>'',
            'shipping'=>$shipping,
            'name'=>'',
            'address'=>'',
            'city'=>$city,
            'additional_info'=>''
    );


    if (isset($_SESSION['wCartShipping'])) {
        $tmp_info = unserialize(stripslashes($_SESSION['wCartShipping']));
    } else if (isset($_COOKIE['wCartShipping'])) {
        $tmp_info = unserialize(stripslashes($_COOKIE['wCartShipping']));
    }

    // --- get this city shipping price etc from options --
    $sh_info = $warung->get_shipping_info($tmp_info['shipping'], $tmp_info['city']);
    if (isset($sh_info)) {
        foreach ($sh_info as $k=>$v) {
            $tmp_info [$k] = $v;
        }
        //var_dump($tmp_info);
    }

    return $tmp_info;

}

function process_params() {
    global $warung;

    // update cart
    if (isset($_POST['add_to_cart'])) {
        $added_product = $warung->warung_get_product_by_id($_POST['product_id']);
        $added_product = formatForSession($added_product, $_POST["product_option"]);

        warung_add_to_cart($added_product);
    } if




    (isset($_POST['wcart_ordernow'])) {
        $added_product = $warung->warung_get_product_by_id($_POST['product_id']);
        $added_product = formatForSession($added_product, $_POST["product_option"]);

        warung_add_to_cart($added_product);


        //echo 'ububur'.$warung->get_shipping_url();
        //echo 'ububu'.get_permalink($warung->get_checkout_page());
        header("Location: ".get_permalink($warung->get_checkout_page()));
        exit;
        //header("Location: ".get_permalink($warung->get_checkout_page()));
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

function get_order_summary($isAdminView=false, $isEmailView=false) {
    global $warung;
    ob_start();

    $harga_per_kg = -1;
    $sh = get_shipping();
    if (isset($sh['harga_per_kg'])) {
        $harga_per_kg = $sh['harga_per_kg'];
    }

    ?>
<div id="order-summary">
        <?
        echo show_detailed_cart(false);
        if (!$isEmailView) {
        ?>
    <p><a href="<?=$warung->get_checkout_url()?>" class="wcart_button_url">Edit</a></p>
            <?
        }
    ?>
    <div><h2>Informasi Pengiriman</h2></div>

    <!-- info pelanggan -->
    <table>
            <?

            if ($isAdminView) {
                foreach ($sh as $k=>$v) {
            ?>
        <tr><td><?=$k?></td><td>&nbsp;:&nbsp;</td><td><?=$v?></td></tr>
                    <?
                }
            } else {
                extract($sh);
        ?>
        <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td><?=$email?></td></tr>
        <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td><?=$phone?></td></tr>
        <tr><td>Jasa Pengiriman</td><td>&nbsp;:&nbsp;</td><td><?=$shipping?></td></tr>
        <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td><?=$name?></td></tr>
        <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td><?=$address?></td></tr>
        <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td><?=$city?></td></tr>
        <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td><?=$additional_info?></td></tr>
                <?
            }

    ?>
    </table>
    <? if (!$isEmailView) { ?>
    <p><a href="<?=$warung->get_shipping_url()?>" class="wcart_button_url">Edit</a></p>
        <? } ?>
</div>
    <?

    if ($isEmailView && !$isAdminView) {
    ?>
<div>
    Terima kasih atas pesanan anda. Kami akan segera menghubungi anda tentang ketersediaan barang.
</div>
    <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function send_order($email_pemesan) {
    if (!empty($_SESSION['wCart']) && isset($_COOKIE['wCartShipping'])) {
        //extract($_SESSION['wCartShipping']);
        $to = get_option("admin_email");
        $subject = 'Order '.mt_rand(10, 9999);
        $message = get_order_summary(true, true);
        //echo get_order_summary();
        $headers = "Content-type: text/html;\r\n";
        $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";
        $ret = mail($to, $subject, $message, $headers);

        mail($email_pemesan, $subject, get_order_summary(false, true), $headers);

        return $ret;
    }

    return false;
}

function update_shipping() {
    global $warung;

    extract($_REQUEST);

    $tmp = array(
            'email'=>$semail,
            'phone'=>$sphone,
            'shipping'=>$sshipping,
            'name'=>$sname,
            'address'=>$saddress,
            'city'=>$scity,
            'additional_info'=>$sadditional_info,
    );

    $_SESSION['wCartShipping'] = serialize($tmp);
    setcookie("wCartShipping", serialize($tmp), time()+60*60*24*30); // save 1 month

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
    $arr = array ('value'=> $v->kota, 'name' => $v->kota);
    if (isset($v->default)) {
        $arr['default'] = true;
    }
    return $arr;
}

function show_shipping_form() {
    global $warung;
    ob_start();

    $shippings = $warung->get_shipping_services();

    $tmp_info = get_shipping();
    extract($tmp_info);


    $cities=array();
    if (!empty($shipping)) {
        $cities = $warung->get_shipping_cities($shipping);
    }

    $co_page = $warung->get_checkout_url();

    ?>
<div><h2>Informasi Pengiriman</h2></div>
<div id="wCart_shipping_form">
    <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2">
        <input type="hidden" name="step" value="3"/>


        <div class="wCart_form_row">
            <label for="semail">Email *</label>
            <input type="text" name="semail" value="<?=$email?>"/>
        </div>
        <div class="wCart_form_row">
            <label for="sphone">Telepon *</label>
            <input type="text" name="sphone" value="<?=$phone?>"/>
        </div>
        <div class="wCart_form_row">
            <label for="sshipping">Jasa Pengiriman</label>
    <?=form_select('sshipping', $shippings, $shipping, 'kv_callback')?>
        </div>

        <div class="wCart_form_row">
            <label for="sname">Nama Penerima *</label>
            <input type="text" name="sname" value="<?=$name?>"/></div>
        <div class="wCart_form_row">
            <label for="saddress">Alamat *</label>
            <textarea name="saddress"><?=$address?></textarea></div>
        <div class="wCart_form_row">
            <label for="scity">Kota</label>
    <?=form_select('scity', $cities, $city, 'city_callback')?>
        </div>
        <div class="wCart_form_row">
            <label for="sadditional_info">Info Tambahan</label>
            <textarea name="sadditional_info"><?=$additional_info?></textarea>
        </div>

        <div class="wCart_form_row">
            <input type="hidden" name="step" value="2"/>
            <input type="submit" name="scheckout" class="submit" value="Lanjut"/>
        </div>


    </form>
</div>
    <?

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function show_confirmation_form() {
    ob_start();
    $sh = get_shipping();
    if (isset($sh)) {
        ?>
<div style="padding: 10px;">
    <form method="POST" id="wCart_confirmation">
        <input type="hidden" name="step" value="3"/>
        <input type="submit" name="send_order" value="Pesan"/>
    </form>
</div>
        <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function get_cart_summary($round_weight=true) {
    $ret = array();
    if (isset($_SESSION["wCart"]) && count($_SESSION["wCart"]) > 0) {

        $harga_per_kg = -1;
        $free_kg = 0;

        $sh = get_shipping();
        if (isset($sh['harga_per_kg'])) {
            $harga_per_kg = $sh['harga_per_kg'];
        }
        if (isset($sh['free_kg'])) {
            $free_kg = $sh['free_kg'];
        }

        $total_weight = 0;
        $total_price = 0;
        $total_items = 0;
        $total_ongkir = 0;
        $total_free_kg = 0;

        $cart_entry = array();
        foreach ($_SESSION["wCart"] as $p) {
            //name|price[|type]
            extract($p);
            if($round_weight) {
                $weight = round($weight);
            }

            $total_price += $p['quantity'] * $p['price'];
            $total_weight += $p['quantity'] * $weight;
            $total_items += $p['quantity'];
            if ($free_kg >= $weight) {
                $total_free_kg += $p['quantity'] * $weight;
            } else {
                $total_free_kg += $p['quantity'] * $free_kg;
            }

            // copy session to temp var
            $tmp = $p;
            $tmp['total_price'] = $p['quantity'] * $p['price'];

            array_push($cart_entry, $tmp);
        }
        sort($cart_entry);
        $ret['cart_entry']=$cart_entry;

        $ret['total_price'] = $total_price;
        $ret['total_ongkir'] = ($total_weight - $total_free_kg) * $harga_per_kg;
        $ret['total_items'] = $total_items;
    }

    return $ret;
}

function show_detailed_cart($showUpdateForm=true) {
    global $warung;

    ob_start();

    // show cart

    $cs = get_cart_summary();
    $cart_entry;
    if(!empty($cs)) {
        $cart_entry = $cs['cart_entry'];
    }

    if (!empty($cart_entry)) {

        $current_page = get_permalink();
        $co_page = $warung->get_checkout_url();
        $clear_page = add_parameter($current_page, array("wc_clear"=>"1"));
        $shipping_page = $warung->get_shipping_url();
        $home_page = get_option("home");

        ?>
<div><h2>Keranjang Belanja</h2></div>
<div id="wcart-detailed-div">
            <?

            if ($showUpdateForm) {
            ?>
    <form method="POST">
                    <?
                }
        ?>
        <table id="wcart-detailed">
            <tr><th>Item</th><th>Berat</th><th>Harga</th><th>Jumlah</th><th>Total</th></tr>
                    <?
                    foreach ($cart_entry as $p) {
                        //name|price[|type]
                        extract($p);
                        $remove_page = add_parameter($current_page, array("wc_rem"=>$cart_id));
                        $prod_info = $warung->warung_get_product_by_id($id);
            ?>
            <tr>
                <td>
                    <div>
                    <div id="wcart_item_thumbnail"><img src="<?=$prod_info["thumbnail"]?>" alt="<?=$name?>"/></div>
                    <div id="wcart_pinfo"><?=$name?></div>
                    </div>
                </td>
                <td><?=$warung->formatWeight($weight)?></td>
                <td><?=$warung->formatCurrency($price)?></td>
                <td><? if ($showUpdateForm) { ?>
                    <input type="text" name="qty_<?=$cart_id?>" value="<?=$quantity?>" size="1"/>
                                    <? } else {
                                    echo $quantity;
            } ?>
                </td>
                <td><?=$warung->formatCurrency($total_price)?></td>
                <!--<td><a id="urlbuton" href="<?=$remove_page?>">Remove</a></td>-->
            </tr>

                        <?
                    }

                    if ($showUpdateForm) {
            ?>
            <tr><td colspan="3" class="wcart-td-footer">&nbsp</td><td class="wcart-td-footer"><input type="submit" name="wc_update" value="Update" title="Klik tombol ini jika ingin mengupdate jumlah barang"/></td><td class="wcart-td-footer">&nbsp;</td></tr>
            <? } ?>
            <tr><td colspan="4" class="wcart-td-footer">Total Sebelum Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?=$warung->formatCurrency($cs['total_price'])?></span></td></tr>
            <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?=$warung->formatCurrency($cs['total_price']+$cs['total_ongkir'])?></span></td></tr>
        </table>
        
        
                <?
                if ($showUpdateForm) {
            ?>
            <div id="wcart_detailed_nav">
                <a href="<?=$home_page?>" class="wcart_button_url">Kembali Berbelanja</a> atau isi form di bawah ini jika ingin lanjut ke pemesanan.
            </div>

    </form>
                <?
            }
        ?>
</div>
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

    $cartImage = $warung->pluginUrl."images/cart.png";
    $co_page = $warung->get_checkout_url();
    $clear_page = add_parameter(get_option("home"), array("wc_clear"=>"1"));

    echo $before_widget;
    echo $before_title .'<a href="'.$co_page.'"><img src="'.$cartImage.'" alt="shopping cart"/> Keranjang Belanja</a>'. $after_title;
    //echo '<div id="wcart_icon"><img src="'.$cartImage.'" alt="shopping cart"/></div>';
    // show cart

    $cart_sumary = get_cart_summary();

    if (!empty($cart_sumary)) {

        extract($cart_sumary);

        echo '<div id="wcart">Ada '.$total_items.' Item ('.$warung->formatCurrency($total_price).')</div>';
        echo '<div id="wcart_co"><a href="'.$clear_page.'">Clear</a></div>';
    } else {
        echo '<div id="status">Kosong</div>';
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

    if (!empty($product)) {
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

    $co_page = $warung->get_checkout_page();
    $home_url = get_option("home");


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
                echo show_shipping_form();
            } else if ($step==2) {
                echo "Jika data sudah benar, klik tombol 'Pesan' di bawah, atau jika masih ada yang salah klik tombol 'Edit' untuk membenarkan";
                echo get_order_summary();
                echo show_confirmation_form();
            } else if ($step==3) {
                $sh = get_shipping();
                $email_pemesan = $sh['email'];
                if (send_order($email_pemesan)) {
                    
                    ?>
<div>
    <p>Terima kasih, pesanan anda sudah kami terima. Detail pesanan juga kami kirim ke '<?=$email_pemesan?>'.</p>
    <p>Kami akan segera menghubungi anda tentang informasi pembayaran dan ketersediaan barang.</p>
</div>
<div><a href="<?=$home_url?>" class="wcart_button_url">Kembali berbelanja &gt;&gt;</a></div>
                    <?
                    warung_empty_cart();
                } else {
                    ?>
<span>Maaf kami belum dapat memproses pesanan anda. Silahkan coba beberapa saat lagi.</span>
                    <?
                }
            }
        } else {
            ?>
<span class="error">Keranjang belanja kosong. Silahkan pilih produk yang akan anda beli. Lalu klik 'Pesan Sekarang'.</span>
            <?
        }
        $content = ob_get_contents();
    } else {
        // check is this post contains product informations

        $product = $warung->warung_get_product_by_id($post->ID);

        if (!empty($product) && !is_search()) {
            ?>
<div id="wCart_add_2_cart">
    <form method="POST">
        <input type="hidden" name="product_id" value="<?=$product["id"]?>">
                    <?
                    if (!empty($product["option_value"])) {
                        $isRadioOption = false;
                        if ($isRadioOption) {
                            foreach($product["option_value"] as $po) {
                                $checked = "";
                                if (isset($po->default)) {
                                    $checked = "checked=checked";
                                }
                        ?><p>
        <input type="radio" name="product_option" value="<?=$po->id?>" <?=$checked?>/>
        <?=$po->name.'@'.$warung->formatCurrency($po->price)?>
                                </p><?
                            }
                        } else {
                    ?>

        <select name="product_option">
                                <?
                                foreach($product["option_value"] as $po) {
                                    $selected = "";
                                    if (isset($po->default)) {
                                        $selected='selected="selected"';
                                    }
                        ?>
            <option value="<?=$po->id?>" <?=$selected?>><?=$po->name.'@'.$warung->formatCurrency($po->price)?></option>
                                    <?
                                }
                    ?>
        </select>
                            <?
                        }
                    } else {
                ?>
        <h2><?=$warung->formatCurrency($product["price"])?></h2>
                        <?
                    }
                    $options = $warung->get_options();
            ?>
        <!--<input type="submit" name="add_to_cart" value="<?=$options["add_to_cart"]?>"/>-->
        <input type="submit" name="wcart_ordernow" value="Pesan Sekarang!"/>
    </form>
</div>

            <?
            $content .= ob_get_contents();

        }
    }

    ob_clean();

    return $content;

}

?>