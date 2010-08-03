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
}

//######################
//# USER DATA/SHIPPING #
//######################

function get_shipping() {
    global $warung;

    $city = '';
    $shippings = $warung->get_shipping_options();

    if (! empty($shippings)) {
        $city = $shippings->getDefaultCity();
    }


    $tmp_info = array(
            'email'=>'',
            'phone'=>'',
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

    // reset old version session/cookies data
    $tmp_city = $tmp_info['city'];
    if (! isset($tmp_city->name) && isset($shippings)) {
        $tmp_info['city'] = &$shippings->getDefaultCity();
        $tmp_info['address'] = '';
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
    } if (isset($_POST['wcart_ordernow'])) {
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

function get_order_summary($isAdminView=false, $isEmailView=false, $v=array()) {
    global $warung;
    ob_start();

    $harga_per_kg = -1;
    $sh = get_shipping();
    $cs = get_cart_summary();
    $so = $warung->get_shipping_options();

    if ($isEmailView) {
        extract($sh);
        ?>
        <div>
            <p><?=$name?>, kami sudah menerima pesanan anda. Untuk pembayaran silahkan transfer ke salah satu nomor rekening berikut sebesar <b><?=$warung->formatCurrency($cs['total_price']+$cs['total_ongkir'])?></b>:
            <ul>
            <li>BCA: 5800106950 a.n. Hendra Setiawan</li>
            <li>Mandiri: 1270005578586 a.n. Hendra Setiawan</li>
            </ul>
            <br/>
            Setelah pembayaran dilakukan harap lakukan konfirmasi pembayaran agar pesanan dapat segera kami proses.
            Konfirmasi dapat dilakukan dengan cara me-reply email pemesanan ini atau menghubungi kami di:
            <ul>
                <li>HP: 08888142879, 081808815325 </li>
                <li>Email: info@warungsprei.com</li>
                <li>YM: reni_susanto, go_to_hendra</li>
            </ul>
            <br/>
            <br/>
            Terima Kasih,<br/>
            Warungsprei.com<br/>
            -----------------------------------
            <br/>
            <?
            // ####### show detailed cart
            echo show_detailed_cart(false);

            // ####### show shipping info
            ?>
            <br/>
            <br/>
            <!--shipping info-->
            <div><h2>Informasi Pengiriman</h2></div>
            <table>
            <?
            
            // show limited info
            $total_weight = $cs['total_weight'];
            $ss = $so->getCheapestServices($city->id, $total_weight);

            ?>
                <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td><?=$email?></td></tr>
                <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td><?=$phone?></td></tr>
                <tr><td>Jasa Pengiriman</td><td>&nbsp;:&nbsp;</td><td><?=$ss->name?></td></tr>
                <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td><?=$name?></td></tr>
                <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td><?=$address?></td></tr>
                <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td><?=$city->name?></td></tr>
                <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td><?=$additional_info?></td></tr>
            <?
            if ($isAdminView) {
            ?>
                <tr><td>Harga Per Kg</td><td>&nbsp;:&nbsp;</td><td><?=$cs['harga_per_kg']?></td></tr>
            <?
            }
            ?>
            </table>
        </div>
        <?
    } else {
        ?>
        <div id="order-summary">
        <?
            // ####### show cart
            echo show_detailed_cart(false);
            ?>
            <p><a href="<?=$warung->get_checkout_url()?>#w_cart" class="wcart_button_url">Edit</a></p>
            <br/>
            <br/>
            <!--shipping info-->
            <div><h2>Informasi Pengiriman</h2></div>
            <table>
            <?
            // show limited info

            extract($sh);

            $total_weight = $cs['total_weight'];
            $ss = $so->getCheapestServices($city->id, $total_weight);

            ?>
                <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td><?=$email?></td></tr>
                <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td><?=$phone?></td></tr>
                <tr><td>Jasa Pengiriman</td><td>&nbsp;:&nbsp;</td><td><?=$ss->name?></td></tr>
                <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td><?=$name?></td></tr>
                <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td><?=$address?></td></tr>
                <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td><?=$city->name?></td></tr>
                <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td><?=$additional_info?></td></tr>
            <?
                if ($isAdminView) {
            ?>
                <tr><td>Harga Per Kg</td><td>&nbsp;:&nbsp;</td><td><?=$cs['harga_per_kg']?></td></tr>
            <?
            }
            ?>
            </table>
            <p><a href="<?=$warung->get_checkout_url()?>#w_shipping" class="wcart_button_url">Edit</a></p>
        </div>
        <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function send_order($bccAdmin=false) {
    global $warung;

    if (!empty($_SESSION['wCart']) && isset($_COOKIE['wCartShipping'])) {
        $sh = get_shipping();
        $email_pemesan = $sh['email'];

        $admin_email = get_option("admin_email");
        $order_id = mt_rand(10, 9999);
        $subject = "[Warungsprei.com] Pemesanan #" . $order_id;
        $admin_message = get_order_summary(true, true, array("order_id"=>$order_id));
        $customer_message = get_order_summary(false, true, array("order_id"=>$order_id));
        //echo get_order_summary();
        $headers = "Content-type: text/html;\r\n";
        $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";

        // send email to admin
        $ret = mail($admin_email, "[Admin] ".$subject, $admin_message, $headers);
        // send to pemesan bcc admin
        if ($bccAdmin) {
            $headers .= "Bcc: ".$admin_email."\r\n";
        }
        mail($email_pemesan, $subject, $customer_message, $headers);

        $home_url = get_option("home");

        if ($ret) {
            ?>
            <div class="wcart_info">
                <p>Informasi pemesanan juga sudah kami kirim ke <b>'<?=$email_pemesan?>'.</b></p>
            </div>
<div class="wcart_general_container">
            <?
            echo $customer_message;
            ?>
</div>
            <div><br/><a href="<?=$home_url?>" class="wcart_button_url">Kembali berbelanja &gt;&gt;</a></div>
            <?
            
            warung_empty_cart();
        } else {
        ?>
            <div class="wcart_general_container">
                Maaf kami belum dapat memproses pesanan anda. Silahkan coba beberapa saat lagi.<br/>
                Untuk pemesanan dapat langsung dikirim via SMS ke: 08888142879 atau 081808815325.<br/>
                Klik <a href="<?=$warung->get_checkout_url()?>" class="wcart_button_url">di sini</a> untuk melihat daftar pesanan anda.
            </div>
        <?
        }


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
            'name'=>$sname,
            'address'=>$saddress,
            'city'=>$scity,
            'additional_info'=>$sadditional_info,
    );

    // get city info
    $s = $warung->get_shipping_options();
    $tmp['city'] = $s->getCityById($scity);


    $_SESSION['wCartShipping'] = serialize($tmp);
    setcookie("wCartShipping", serialize($tmp), time()+60*60*24*30); // save 1 month

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

function form_select($name, $arr, $selected, $callback='', $isArrayOfObject=false, $style='') {
    $ret = '<select id="'.$name.'" name="'.$name.'" '.$style.'><option value="--- Please Select ---">--- Please Select ---</option>';
    if (empty($callback)) {
        foreach ($arr as $k=>$v) {
            $ret .= '<option value="'.$k.'" '.form_selected($selected, $k).'>'.$v.'</option>';
        }
    } else {
        if ($isArrayOfObject) {
            foreach ($arr as $v) {
                $r = call_user_func($callback, $v);
                if (empty($selected) && isset($r['default'])) {
                    $selected = $r['value'];
                }
                $ret .= '<option value="'.$r['value'].'" '.form_selected($selected, $r['value']).'>'.$r['name'].'</option>';
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
    }
    $ret .= '</select>';
    return $ret;
}

function kv_callback($k, $v) {
    return array('value'=>$v, 'name'=>$v);
}

function city_callback($c) {
    $arr = array ('value'=> $c->id, 'name' => $c->name);
    if (isset($v->default)) {
        $arr['default'] = true;
    }
    return $arr;
}

function show_shipping_form() {
    global $warung;
    ob_start();

    $tmp_info = get_shipping();
    extract($tmp_info);

    $cities=array();
    $so = $warung->get_shipping_options();
    if (!empty($so)) {
        $cities = $so->getCities();
    }

    $co_page = $warung->get_checkout_url();

    ?>
        <div class="wcart_shipping_container">
<div><a name="w_shipping"/><h2>Informasi Pengiriman</h2></div>
<div id="wCart_shipping_form">
    <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?=$warung->get_shipping_url()?>">
        <div class="wCart_form_row">
            <label for="semail">Email *</label>
            <input type="text" name="semail" value="<?=$email?>"/>
        </div>
        <div class="wCart_form_row">
            <label for="sphone">HP (handphone) *</label>
            <input type="text" name="sphone" value="<?=$phone?>"/>
        </div>
        <div class="wCart_form_row">
            <label for="sname">Nama Penerima *</label>
            <input type="text" name="sname" value="<?=$name?>"/></div>
        <div class="wCart_form_row">
            <label for="saddress">Alamat *</label>
            <textarea name="saddress"><?=$address?></textarea></div>
        <div class="wCart_form_row">
            <label for="scity">Kota</label>
    <?=form_select('scity', $cities, $city->id, 'city_callback', true)?>
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
        </div>
    <?

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function show_confirmation_form() {
    global $warung;
    ob_start();
    $sh = get_shipping();
    if (isset($sh)) {
        ?>
<div style="padding: 10px;">
    <form method="POST" id="wCart_confirmation" action="<?=$warung->get_order_url()?>">
        <input type="submit" name="send_order" value="Pesan"/>
    </form>
</div>
        <?
    }

    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function get_cart_summary($round_weight=true, $ceil_price=true) {

    global $warung;
    $ret = array();
    if (isset($_SESSION["wCart"]) && count($_SESSION["wCart"]) > 0) {

        $total_weight = 0;
        $total_price = 0;
        $total_items = 0;
        $total_ongkir = 0;

        // discount
        $total_free_kg = 0;

        // ######## hitung sblm ongkir
        $cart_entry = array();
        foreach ($_SESSION["wCart"] as $p) {
            //name|price[|type]
            extract($p);
            
            $total_price += $p['quantity'] * $p['price'];
            $total_weight += $p['quantity'] * $weight;
            $total_items += $p['quantity'];

            // copy session to temp var
            $tmp = $p;
            $tmp['total_price'] = $p['quantity'] * $p['price'];

            array_push($cart_entry, $tmp);
        }

        if($round_weight) {
            $total_weight = round($total_weight);
        }


        // ########### Hitung setelah Ongkir
        $harga_per_kg = 0;
        $free_kg = 0;
        $service;

        $sh = get_shipping();
        $city = $sh['city'];
        if (! empty($city) && isset($city->id)) {
            // find ongkir
            $so = $warung->get_shipping_options();
            if (! empty($so)) {
                $service = $so->getCheapestServices($city->id, $total_weight);
                if (! empty ($service)) {

                    $harga_per_kg = $service->price;
                    if ($ceil_price) {
                        $harga_per_kg = Utils::ceilToHundred($harga_per_kg);
                    }

                    // find free KG
                    if (isset($service->free_weight)) {
                        $free_kg = $service->free_weight;

                        $tw = 0;
                        foreach ($_SESSION["wCart"] as $p) {
                            //name|price[|type]
                            extract($p);

                            if (isset ($weight_discount)) {
                                $weight = $weight_discount;
                            }

                            if ($free_kg >= $weight) {
                                $total_free_kg += $weight * $quantity;
                            } else {
                                $total_free_kg += $free_kg * $quantity;
                            }
                        }

                        if($round_weight) {
                            $total_free_kg = round($total_free_kg );
                        }

                    } // free kg

                }
            }
        }

        sort($cart_entry);
        $ret['cart_entry']=$cart_entry;
        $ret['total_weight'] = $total_weight;
        $ret['harga_per_kg'] = $harga_per_kg;
        $ret['total_price'] = $total_price;
        $ret['total_items'] = $total_items;
        
        // discount
        $ret['total_free_kg'] = $total_free_kg;

        // summary
        $ret['total_ongkir'] = 0;
        if ($harga_per_kg > 0) {
            if ($total_free_kg > 0) {
                // discount
                $ret['total_ongkir'] = ($total_weight - $total_free_kg) * $harga_per_kg;
            } else {
                $ret['total_ongkir'] = $total_weight * $harga_per_kg;
            }
        }
       
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

    ?>
<div class="wcart_detailed_cart_container">
    <?

    if (!empty($cart_entry)) {

        $current_page = get_permalink();
        $co_page = $warung->get_checkout_url();
        $clear_page = add_parameter($current_page, array("wc_clear"=>"1"));
        $shipping_page = $warung->get_shipping_url();
        $home_page = get_option("home");

        ?>
<div><a name="w_cart"/><h2>Keranjang Belanja</h2></div>
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
            <? 
            if (isset($cs['total_ongkir'])) {

            ?>
            <tr><td colspan="4" class="wcart-td-footer">Ongkos Kirim (<?=$warung->formatWeight($cs['total_weight']-$cs['total_free_kg'])?>) <?if (! empty($cs['total_free_kg'])) {?>(bedcover saja, sprei gratis)<?} ?></td><td class="wcart-td-footer"><span class="wcart_total"><?=$warung->formatCurrency($cs['total_ongkir'])?></span></td></tr>
            <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?=$warung->formatCurrency($cs['total_price']+$cs['total_ongkir'])?></span></td></tr>
            <? } ?>
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
?></div><?
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

        echo '<div id="wcart"><a href="'.$co_page.'">Ada '.$total_items.' Item ('.$warung->formatCurrency($total_price).')</a></div>';
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
        if (isset($opt_id) && $opt_id != -1) {
            $opt = $warung->warung_get_selected_option($product, $opt_id);
            if (!empty($opt)) {
                $ret["cart_id"] = $product["id"].'-'.$opt->id;
                $ret["id"] = $product["id"];
                $ret['name'] = $product["name"].' - '.$opt->name;
                $ret['price'] = $opt->price;
                $ret["weight"] = $opt->weight;
                $ret['quantity'] = 1;

                if (isset ($opt->weight_discount) ) {
                    $ret["weight_discount"] = $opt->weight_discount;
                }

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
            if (isset ($product->weight_discount) ) {
                $ret["weight_discount"] = $product->weight_discount;
            }
        }
    }

    return $ret;
}

function filter_content($content) {
    global $post;
    global $warung;

    $co_page = $warung->get_checkout_page();
    $shipping_sim_page = $warung->get_shipping_simulation_page();
    $home_url = get_option("home");

    ob_start();

    if ($post->ID == $co_page) {

        $step = $_REQUEST['step'];

        if (empty($step)) {
            $step = 1;
        }

        if (!empty($_SESSION['wCart'])) {
            if ($step==1) {
                ?><div class="wcart_general_container"><?
                echo show_detailed_cart();
                echo show_shipping_form();
                ?></div><?
            } else if ($step==2) {
                ?><div class="wcart_general_container"><?
                echo "Jika data sudah benar, klik tombol 'Pesan' di bawah, atau jika masih ada yang salah klik tombol 'Edit' untuk membenarkan";
                echo get_order_summary();
                echo show_confirmation_form();
                ?></div><?
            } else if ($step==3) {
                send_order();
            }
        } else {
            ?>
        <span class="error">Keranjang belanja kosong. Silahkan <a href="<?=$home_url?>" class="wcart_button_url">Pilih Produk</a> yang akan anda beli. Lalu klik 'Pesan Sekarang'.</span>
            <?
        }
        $content = ob_get_contents();

    } else if ( $post->ID == $shipping_sim_page) {
        $s = $warung->get_shipping_options();
        if ( !empty($s) ) {
            $cities=array();
            $cities = $s->getCities();
            $city = $_REQUEST["wc_sim_city"];
            $wc_weight = $_REQUEST["wc_sim_weight"];
            if (empty($wc_weight)) {
                $wc_weight = 1;
            }

            $resp = array();
            if (isset($_REQUEST["wc_sim_city"])) {
                $s_cid = $_REQUEST["wc_sim_city"];
                $s_weight = $_REQUEST["wc_sim_weight"];
                $s_cheap = $s->getCheapestServices($s_cid, $s_weight);
                if (! empty ($s_cheap)) {
                    array_push($resp, '<strong>'.$s_cheap->name.': '.$warung->formatCurrency(Utils::ceilToHundred($s_cheap->price) * $s_weight).' ('.$warung->formatCurrency(Utils::ceilToHundred($s_cheap->price)). '/Kg) (paling murah)</strong>');
                }
                $s_serv = $s->getServiceByCityAndWeight($s_cid, $s_weight);
                foreach ($s_serv as $sss) {
                   if ($sss != $s_cheap) {
                       array_push($resp, $sss->name.': '.$warung->formatCurrency(Utils::ceilToHundred($sss->price) * $s_weight).' ('.$warung->formatCurrency(Utils::ceilToHundred($sss->price)). '/Kg)');
                   }
                }
            }


            ?>
            <?
            if (! empty($resp)) {
                ?>
        <div class="wcart_info">
                <?
                foreach ($resp as $r) {
                    ?><?=$r?><br/><?
                }
                ?>
        </div>
                <?
            }?>
        <div id="wCart_shipping_form" >
            
            <form action="" method="POST">
                <div class="wCart_form_row">
                    <label for="wc_sim_city">Kota Tujuan</label>
                    <?=form_select('wc_sim_city', $cities, $city, 'city_callback', true)?>
                </div>
                <div class="wCart_form_row">
                    <label for="wc_sim_weight">Berat (Kg)</label>
                    <input id="wc_sim_weight" type="text" name="wc_sim_weight" value="<?=$wc_weight?>"/>
                </div>
                <div class="wCart_form_row">
                    <label for="wc_sim_weight">&nbsp;</label>
                    <input type="submit" value="Cek Ongkos Kirim"/>
                </div>
            </form>
        </div>
            <?
        } else {

        }

        $content .= ob_get_contents();
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

        <select name="product_option" class="wcart_price">
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