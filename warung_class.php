<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/

/**
 * Description of Warung
 *
 * @author hendra
 */
class Warung {
    public $pluginUrl;
    // name for our options in the DB
    public static $db_option = 'Warung_Options';


	function __construct() {
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
    }
    
    function init() {
		session_start();
	    //wp_register_sidebar_widget('Warung Cart', 'Warung Cart', 'warung_cart');
	    add_filter('the_content', array(&$this,'filter_content'));
	    // save cookie
	    //updateShippingInfo();
	    $this->process_params();
    }
	
	function process_params() {
	    // update cart
	    if (isset($_POST['add_to_cart'])) {
	        $added_product = $this->getProductById($_POST['product_id']);
	        $added_product = $this->formatForSession($added_product, $_POST["product_option"]);
	
	        $this->warung_add_to_cart($added_product);
	    } if (isset($_POST['wcart_ordernow'])) {
	        $added_product = $this->getProductById($_POST['product_id']);
	        $added_product = $this->formatForSession($added_product, $_POST["product_option"]);
	
	        $this->warung_add_to_cart($added_product);
	
			// redirect to checkoutpage
	        header("Location: ".get_permalink($this->get_checkout_page()));
	        exit;
	    } else if (!empty($_REQUEST["wc_update"])) {
	        foreach($_REQUEST as $key=>$val) {
	            if (strpos($key,'qty_') !== false) {
	                //echo $key.'->'.$val;
	                $tok = explode('_',$key);
	                if (count($tok) == 2) {
	                    $this->warung_update_cart($tok[1], $val);
	                }
	            }
	        }
	    } else if (!empty($_REQUEST["wc_rem"])) {
	        $this->warung_update_cart($_REQUEST["wc_rem"],0);
	    } else if (!empty($_REQUEST["wc_clear"])) {
	        $this->warung_empty_cart();
	    } else if (!empty($_REQUEST["scheckout"])) {
	        $this->update_shipping();
	    }
	}
	
	
	function formatForSession($product, $opt_id = -1) {
	    $ret = array();
	    
	
	    if (!empty($product)) {
	        if (isset($opt_id) && $opt_id != -1) {
	            $opt = $this->warung_get_selected_option($product, $opt_id);
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
	            if (isset ($product["weight_discount"]) ) {
	                $ret["weight_discount"] = $product["weight_discount"];
	            }
	        	if (isset ($product["price_discount"]) ) {
	                $ret["price_discount"] = $product["price_discount"];
	            }
	        }
	    }
	    
//	    var_dump($ret);
	
	    return $ret;
	}
	
	function update_shipping() {
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
	    $s = $this->get_shipping_options();
	    $tmp['city'] = $s->getCityById($scity);
	
	
	    $_SESSION['wCartShipping'] = serialize($tmp);
	    setcookie("wCartShipping", serialize($tmp), time()+60*60*24*30); // save 1 month
	
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
	
	
	function filter_content($content) {
	    global $post;
	
	    $co_page = $this->get_checkout_page();
	    $shipping_sim_page = $this->get_shipping_simulation_page();
	    $home_url = get_option("home");
	
	    ob_start();
	
	    if ($post->ID == $co_page) {
	
	        
			$step = 1;
	        if (!empty($_REQUEST['step'])) {
	        	$step = $_REQUEST['step'];
	        }
	
	        if (!empty($_SESSION['wCart'])) {
	            if ($step==1) {
	                ?><div class="wcart_general_container"><?
	                echo $this->show_detailed_cart();
	                echo $this->show_shipping_form();
	                ?></div><?
	            } else if ($step==2) {
	                ?><div class="wcart_general_container"><?
	                echo "Jika data sudah benar, klik tombol 'Pesan' di bawah, atau jika masih ada yang salah klik tombol 'Edit' untuk membenarkan";
	                echo $this->get_order_summary();
	                echo $this->show_confirmation_form();
	                ?></div><?
	            } else if ($step==3) {
	                $this->send_order();
	            }
	        } else {
	            ?>
	        <span class="error">Keranjang belanja kosong. Silahkan <a href="<?=$home_url?>" class="wcart_button_url">Pilih Produk</a> yang akan anda beli. Lalu klik 'Pesan Sekarang'.</span>
	            <?
	        }
	        $content = ob_get_contents();
	
	    } else if ( $post->ID == $shipping_sim_page) {
	        $s = $this->get_shipping_options();
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
	                    array_push($resp, '<strong>'.$s_cheap->name.': '.$this->formatCurrency(Utils::ceilToHundred($s_cheap->price) * $s_weight).' ('.$this->formatCurrency(Utils::ceilToHundred($s_cheap->price)). '/Kg) (paling murah)</strong>');
	                }
	                $s_serv = $s->getServiceByCityAndWeight($s_cid, $s_weight);
	                foreach ($s_serv as $sss) {
	                    if ($sss != $s_cheap) {
	                        array_push($resp, $sss->name.': '.$this->formatCurrency(Utils::ceilToHundred($sss->price) * $s_weight).' ('.$this->formatCurrency(Utils::ceilToHundred($sss->price)). '/Kg)');
	                    }
	                }
	            }
	
	
	            ?>
	            <?
	            if (! empty($resp)) {
	                ?>
	        <div class="wcart_shipping_sim_result">
	                <?
	                foreach ($resp as $r) {
	                    ?><?=$r?><br/><?
	                }
	                ?>
	        </div>
	                <?
	            }?>
	        <div class="wcart_shipping_sim" >
	            
	            <form method="POST">
	                <table>
	                    <tr>
	                        <td><label for="wc_sim_city">Kota Tujuan</label></td>
	                        <td><?=$this->form_select('wc_sim_city', $cities, $city, 'city_callback', true)?></td>
	                    </tr>
	                    <tr>
	                        <td><label for="wc_sim_weight">Berat (Kg)</label></td>
	                        <td><input id="wc_sim_weight" type="text" name="wc_sim_weight" value="<?=$wc_weight?>"/></td>
	                    </tr>
	                    <tr>
	                        <td>&nbsp;</td>
	                        <td><input type="submit" value="Cek Ongkos Kirim"/></td>
	                    </tr>
	                </table>
	            </form>
	        </div>
	            <?
	        } else {
	
	        }
	
	        $content .= ob_get_contents();
	    } else {
	        // check is this post contains product informations
	
	        $product = $this->getProductById($post->ID);
	        if (!empty($product) && !is_search()) {
	            if (isset($product["option_text"])) {
	                echo stripslashes($product["option_text"]);
	            }
	            
	            $disc_price = $product ['price_discount'];
	            ?>
	<div id="wCart_add_2_cart">
	    <form method="POST">
	        <input type="hidden" name="product_id" value="<?=$product["id"]?>">
	                    <?
	                    if (!empty($product["option_value"])) {
	                        $isRadioOption = true;
	                        if ($isRadioOption) {
	                            foreach($product["option_value"] as $po) {
	                                $checked = "";
	                                if (isset($po->default)) {
	                                    $checked = "checked=checked";
	                                }
	                        ?><p>
	        <input type="radio" name="product_option" value="<?=$po->id?>" <?=$checked?>/>
	        <?=$po->name.'@'.$this->formatCurrency($po->price)?>
	                                </p><?
	                            }
	                        } else {
	                    ?>
	
	        <select name="product_option" class="wcart_price" size="<?=max(1, sizeof($product["option_value"])/3)?>">
	                                <?
	                                foreach($product["option_value"] as $po) {
	                                    $selected = "";
	                                    if (isset($po->default)) {
	                                        $selected='selected="selected"';
	                                    }
	                        ?>
	            <option value="<?=$po->id?>" <?=$selected?>><?=$po->name.'@'.$this->formatCurrency($po->price)?></option>
	                                    <?
	                                }
	                    ?>
	        </select>
	                            <?
	                        }
	                    } else {
	                    	if (isset($disc_price)&&!empty($disc_price)) {
	                    		?>
	                    		<h2><s><?=$this->formatCurrency($disc_price)?></s></h2>
	                    		<h2><?=$this->formatCurrency($product["price"])?></h2>
	                    		<?
	                    	} else {
	                			?>
	        					<h2><?=$this->formatCurrency($product["price"])?></h2>
	                        	<?
	                    	}
	                    }
	                    $options = $this->get_options();
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
	
	
	function get_order_summary($isAdminView=false, $isEmailView=false, $v=array()) {
	    ob_start();
	
	    $harga_per_kg = -1;
	    $cs = $this->get_cart_summary();
	    $so = $this->get_shipping_options();
	    $sh = $so->getSavedShippingInfo();
	
	    if ($isEmailView) {
	        extract($sh);
	        ?>
	        <div>
	            <p><?=$name?>, kami sudah menerima pesanan anda. Untuk pembayaran silahkan transfer ke salah satu nomor rekening berikut sebesar <b><?=$this->formatCurrency($cs['total_price']+$cs['total_ongkir'])?></b>:
	            <ul>
	            <li>BCA: 5800106950 a.n. Hendra Setiawan</li>
	            <li>Mandiri: 1270005578586 a.n. Hendra Setiawan</li>
	            </ul>
	            <br/>
	            Setelah pembayaran dilakukan harap lakukan konfirmasi pembayaran agar pesanan dapat segera kami proses.
	            Konfirmasi dapat dilakukan dengan cara me-reply email pemesanan ini atau menghubungi kami di:
	            <ul>
	                <li>HP: 08889693342, 081808815325 </li>
	                <li>Email: info@warungsprei.com</li>
	                <li>YM: reni_susanto, warungsprei_hendra</li>
	            </ul>
	            <br/>
	            <br/>
	            Terima Kasih,<br/>
	            Warungsprei.com<br/>
	            -----------------------------------
	            <br/>
	            <?
	            // ####### show detailed cart
	            echo $this->show_detailed_cart(false);
	
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
	            echo $this->show_detailed_cart(false);
	            ?>
	            <p><a href="<?=$this->get_checkout_url()?>#w_cart" class="wcart_button_url">Edit</a></p>
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
	            <p><a href="<?=$this->get_checkout_url()?>#w_shipping" class="wcart_button_url">Edit</a></p>
	        </div>
	        <?
	    }
	
	    $ret = ob_get_contents();
	    ob_end_clean();
	
	    return $ret;
	}
	
	function send_order($bccAdmin=false) {
	
	    if (!empty($_SESSION['wCart']) && isset($_COOKIE['wCartShipping'])) {
	        $so = $this->get_shipping_options();
	        $sh = $so->getSavedShippingInfo();
	        $email_pemesan = $sh['email'];
	
	        $admin_email = get_option("admin_email");
	        $order_id = date('ymdH').mt_rand(10, 9999);
	        $subject = "[Warungsprei.com] Pemesanan #" . $order_id;
	        $admin_message = $this->get_order_summary(true, true, array("order_id"=>$order_id));
	        $customer_message = $this->get_order_summary(false, true, array("order_id"=>$order_id));
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
	                <p>Informasi pemesanan juga sudah kami kirim ke <b>'<?=$email_pemesan?>'.</b> Mohon periksa juga folder <b>'Junk'</b> jika tidak ada di inbox.</p>
	            </div>
	<div class="wcart_general_container">
	            <?
	            echo $customer_message;
	            ?>
	</div>
	            <div><br/><a href="<?=$home_url?>" class="wcart_button_url">Kembali berbelanja &gt;&gt;</a></div>
	            <?
	            
	            $this->warung_empty_cart();
	        } else {
	        ?>
	            <div class="wcart_general_container">
	                Maaf kami belum dapat memproses pesanan anda. Silahkan coba beberapa saat lagi.<br/>
	                Untuk pemesanan dapat langsung dikirim via SMS ke: 08888142879 atau 081808815325.<br/>
	                Klik <a href="<?=$this->get_checkout_url()?>" class="wcart_button_url">di sini</a> untuk melihat daftar pesanan anda.
	            </div>
	        <?
	        }
	
	
	        return $ret;
	    }
	
	    return false;
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
	            $ret .= '<option value="'.$k.'" '.$this->form_selected($selected, $k).'>'.$v.'</option>';
	        }
	    } else {
	        if ($isArrayOfObject) {
	            foreach ($arr as $v) {
	                $r = call_user_func($callback, $v);
	                if (empty($selected) && isset($r['default'])) {
	                    $selected = $r['value'];
	                }
	                $ret .= '<option value="'.$r['value'].'" '.$this->form_selected($selected, $r['value']).'>'.$r['name'].'</option>';
	            }
	        } else {
	            foreach ($arr as $k=>$v) {
	                $r = call_user_func($callback, $k, $v);
	                if (empty($selected) && isset($r['default'])) {
	                    $selected = $r['value'];
	                }
	                $ret .= '<option value="'.$r['value'].'" '.$this->form_selected($selected, $r['value']).'>'.$r['name'].'</option>';
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
	    ob_start();
	
	    $so = $this->get_shipping_options();
	    $tmp_info = $so->getSavedShippingInfo();
	    extract($tmp_info);
	
	    $cities=array();
	    
	    if (!empty($so)) {
	        $cities = $so->getCities();
	    }
	
	    $co_page = $this->get_checkout_url();
	
	    ?>
	        <div class="wcart_shipping_container">
	<div><a name="w_shipping"/><h2>Informasi Pengiriman</h2></div>
	<div id="wCart_shipping_form">
	    <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?=$this->get_shipping_url()?>">
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
	    <?=$this->form_select('scity', $cities, $city->id, array(&$this,'city_callback'), true)?>
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
	    ob_start();
	    ?>
	<div style="padding: 10px;">
	<form method="POST" id="wCart_confirmation" action="<?=$this->get_order_url()?>">
	    <input type="submit" name="send_order" value="Pesan"/>
	</form>
	</div>
	    <?
	
	    $ret = ob_get_contents();
	    ob_end_clean();
	
	    return $ret;
	}
	
	function get_cart_summary($round_weight=true, $ceil_price=true) {
	
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
	            
	            $total_price += $p['quantity'] * $price;
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
	
	        $so = $this->get_shipping_options();
	        if (!is_object($so)) {
	        	die('no shipping defined');
	        }
	        $sh = $so->getSavedShippingInfo();
	        $city = $sh['city'];
	        if (! empty($city) && isset($city->id)) {
	            // find ongkir
	            
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
	
	    ob_start();
	
	    // show cart
	
	    $cs = $this->get_cart_summary();
	    $cart_entry;
	    if(!empty($cs)) {
	        $cart_entry = $cs['cart_entry'];
	    }
	
	    ?>
	<div class="wcart_detailed_cart_container">
	    <?
	
	    if (!empty($cart_entry)) {
	
	        $current_page = get_permalink();
	        $co_page = $this->get_checkout_url();
	        $clear_page = Utils::addParameter($current_page, array("wc_clear"=>"1"));
	        $shipping_page = $this->get_shipping_url();
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
	            <tr><th>Item</th><th>Berat</th><th>Harga</th><th>Jumlah</th><th>Total</th><th>-</th></tr>
	                    <?
	                    foreach ($cart_entry as $p) {
	                        //name|price[|type]
	                        extract($p);
	                        $remove_page = Utils::addParameter($current_page, array("wc_rem"=>$cart_id));
	                        $prod_info = $this->getProductById($id);
	            ?>
	            <tr>
	                <td>
	                    <div>
	                    <div id="wcart_item_thumbnail"><img src="<?=$prod_info["thumbnail"]?>" alt="<?=$name?>"/></div>
	                    <div id="wcart_pinfo"><?=$name?></div>
	                    </div>
	                </td>
	                <td><?=$this->formatWeight($weight)?></td>
	                <td><?=$this->formatCurrency($price)?></td>
	                <td><? if ($showUpdateForm) { ?>
	                    <input type="text" name="qty_<?=$cart_id?>" value="<?=$quantity?>" size="1"/>
	                                    <? } else {
	                                    echo $quantity;
	            } ?>
	                </td>
	                <td><?=$this->formatCurrency($total_price)?> </td>
	                <?php if($showUpdateForm):?>
	                <td><a class="wcart_remove_item" href="<?=$remove_page?>"><div><span>(X)</span></div></a></td>
	                <? endif; ?>
	            </tr>
	
	                        <?
	                    }
	
	                    if ($showUpdateForm) {
	            ?>
	            <tr><td colspan="3" class="wcart-td-footer">&nbsp</td><td class="wcart-td-footer"><input type="submit" name="wc_update" value="Update" title="Klik tombol ini jika ingin mengupdate jumlah barang"/></td><td class="wcart-td-footer">&nbsp;</td></tr>
	            <? } ?>
	            <tr><td colspan="4" class="wcart-td-footer">Total Sebelum Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?=$this->formatCurrency($cs['total_price'])?></span></td></tr>
	            <? 
	            if (isset($cs['total_ongkir'])) {
	
	            ?>
	            <tr><td colspan="4" class="wcart-td-footer">Ongkos Kirim (<?=$this->formatWeight($cs['total_weight']-$cs['total_free_kg'])?>) <?if (! empty($cs['total_free_kg'])) {?>(bedcover saja, sprei gratis)<?} ?></td><td class="wcart-td-footer"><span class="wcart_total"><?=$this->formatCurrency($cs['total_ongkir'])?></span></td></tr>
	            <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?=$this->formatCurrency($cs['total_price']+$cs['total_ongkir'])?></span></td></tr>
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
	
	
	
	
	function parse_image_url($post) {
        
    }
	

    function install() {
        // set default options
        $this->get_options();
    }


    function init_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form');//, $this->pluginUrl.'scripts/jquery.form.js',array('jquery'));
        wp_enqueue_script('jquery_validaton', $this->pluginUrl.'scripts/jquery.validate.js',array('jquery'));
        wp_enqueue_script('warung_js',$this->pluginUrl.'scripts/warung.js',array('jquery'));
    }


    function init_styles() {
        wp_enqueue_style('warung_style', $this->pluginUrl.'style/warung.css');
    }

    function formatCurrency($price) {

        $options = $this->get_options();
        $currency = $options['currency'];

        return trim($currency).number_format($price,0,',','.');
    }

    function formatWeight($weight) {
        $options = $this->get_options();
        $weight_sign = $options['weight_sign'];

        return number_format($weight,1,',','.').' '.trim($weight_sign);
    }

    // -------------------------- OPTIONS ------------------------------

    function get_weight_sign() {
        $options = $this->get_options();
        return $options['weight_sign'];
    }

    function get_checkout_page() {
        $options = $this->get_options();
        return $options['checkout_page'];
    }

    function get_checkout_url() {
        return get_permalink($this->get_checkout_page());
    }

    function get_shipping_url() {
        return Utils::addParameter($this->get_checkout_url(), array("step"=>2));
    }

    function get_order_url() {
        return Utils::addParameter($this->get_checkout_url(), array("step"=>3));
    }

    function get_shipping_simulation_page() {
        $options = $this->get_options();
        return $options['shipping_sim_page'];
    }


    function get_options() {

        // default
        $def_page;
        foreach (get_pages() as $page) {
            $def_page = $page->ID;
            break;
        }
        $options = array(
                'currency' => 'Rp. ',
                'add_to_cart' => 'Add to Cart',
                'checkout_page' => $def_page,
                'shipping_sim_page' => '',
                'prod_options' => array(),
                'shipping_options' => '',
                'weight_sign' => 'Kg',
                'shipping_cities' => ''
        );


        // get from db
        $saved = get_option(Warung::$db_option);

        //print_r($saved);


        // assign them
        if (!empty($saved)) {
            foreach ($saved as $key => $option) {
                $options[$key] = $option;
            }
        }

        //print_r($options);

        // update if necessary
        if ($saved != $options) {
            update_option(Warung::$db_option, $options);
        }

        return $options;
    }

    function get_shipping_options() {
        $options = $this->get_options();
        $shipping_options = $options["shipping_options"];
        $cities = $options["shipping_cities"];
        $ret = null;

        if (! empty ($shipping_options) && ! empty ($cities)) {

            $c = Utils::parseJsonMultiline($cities, false);
            $s = Utils::parseJsonMultiline($shipping_options, false);

            $ret = new Shipping($c, $s, $this);
        }

        return $ret;
    }

    function getProductById($post_id, $calculateDiscount=true) {
        $ret=array();

        $product_code = get_post_meta($post_id, '_warung_product_code', true);
        $product_name = get_post_meta($post_id, '_warung_product_name', true);
        $product_price = get_post_meta($post_id, '_warung_product_price', true);
        $product_weight = get_post_meta($post_id, '_warung_product_weight', true);
        $product_options_name = get_post_meta($post_id, '_warung_product_options', true);
        $product_thumbnail = get_post_meta($post_id, 'thumbnail', true);
        $product_weight_discount = get_post_meta($post_id, '_warung_product_weight_discount', true);
        $product_stock = get_post_meta($post_id, '_warung_product_stock', true);
        $product_show_stock = get_post_meta($post_id, '_warung_product_show_stock', true);
        $product_price_discount = get_post_meta($post_id, '_warung_product_price_discount', true);

        $post = get_post($post_id);
        if (!empty($post) && empty($product_thumbnail)) {
            $dom = new DOMDocument();
            if (!empty($post->post_content) && $dom->loadHTML($post->post_content)) {
                $images = $dom->getElementsByTagName("img");
                foreach ($images as $img) {
                    $product_thumbnail = $img->getAttribute("src");
                    break;
                }
            }
        }

        if (!empty($product_code)) {
            $ret["id"] = $post_id;
            $ret["code"] = $product_code;
            $ret["name"] = $product_name;
            $ret["price"] = $product_price;
            $ret["weight"] = $product_weight;
            $ret["thumbnail"] = $product_thumbnail;
            $ret["stock"] = $product_stock;
            $ret["show_stock"] = $product_show_stock;
            
            // check for discount
         	if (!empty($product_weight_discount)) {
         		if ( $calculateDiscount ) {
	                $ret["weight_discount"] = $product_weight_discount;
	                $ret["weight"] = max(array(0, $product_weight-$product_weight_discount));
         		} else {
         			$ret["weight_discount"] = $product_weight_discount;
         		}
            }
            if (!empty($product_price_discount)) {
            	if ( $calculateDiscount ) {
	            	$ret["price_discount"] = $product_price;
	            	$ret["price"] = $product_price_discount;
            	} else {
            		$ret["price_discount"] = $product_price_discount;
            	}
            }

            if (!empty($product_options_name)) {
                $opts = $this->get_options();
                $prod_opts = $opts['prod_options'];

                if (!empty($prod_opts) && is_array($prod_opts)) {
                    foreach($prod_opts as $k=>$v) {
                        if ($v->name == $product_options_name) {
                            $ret["option_name"]=$product_options_name;
                            if (isset($v->value)) {
                                $ret["option_value"] = Utils::parseJsonMultiline($v->value);
                            }
                            if (isset($v->txt)) {
                                $ret["option_text"] = $v->txt;
                            }
                        }
                    }
                }
            }

           

        }

        return $ret;
    }

    function warung_get_selected_option($product, $id) {
        $ret;

        if (!empty($product["option_value"])) {
            foreach($product["option_value"] as $po) {
                if ($id == $po->id) {
                    $ret = $po;
                    break;
                }
            }
        }

        return $ret;
    }

    //-- util
    
}

class Utils {

    /**
     * Parse multiline json string into json object array
     * @param String $s_content json string
     * @param Boolean $b_generate_id if set to true 'id' properties will be added with autogenerated values for each json object
     * @return Array of json object 
     */
    public static function parseJsonMultiline($s_content, $b_generate_id=true) {
        $s_content = str_replace('\\"', '"', $s_content);

        $a_ret = explode("\n", $s_content);
        $i=0;
        foreach($a_ret as &$r) {
            if ($b_generate_id) {
                $r = '{"id":'.$i++.','.$r.'}';
            } else {
                $r = '{'.$r.'}';
            }
            $r = json_decode($r);
        }
        return $a_ret;
    }

    /**
     * Append url with get parameters in $param
     * @param String $url
     * @param Associative Arrays $param
     * @return String $url with appended parameter
     */
    public static function addParameter($url, $param) {
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

    /**
     * Parse all input element with name $name_form_name and value $value_form_name into assoc array
     * example product_options_name-1="sprei" product_option_value-1="123"
     * will be parsed into array ( sprei => 123 )
     *
     * @param <type> $posts
     * @param <type> $name_form_name
     * @param <type> $value_form_name
     * @return Assoc Array
     */
    public static function parseNamevalParameters($posts, $name_form_name, $value_form_name) {
        $ret = array();

        $prev_idx = 0;
        $prev_name = '';
        foreach ($posts as $key=>$val) {

            if (!empty ($val)) {
                if (strpos($key,$name_form_name) !== false) {
                    $tok = explode('-', $key);
                    $prev_idx = $tok[1];
                    $prev_name = $val;

                } else if (strpos($key, $value_form_name) !== false) {
                    if (strlen(trim($prev_name)) > 0) {
                        $ret[$prev_name] = $val;
                    }
                }
            }

        }

        return $ret;
    }

    /**
     * Return object from post request with this format
     * prefix-name-1=val1
     * prefix-text-1=val2
     * prefix-text-2=val3
     * prefix_name-2=val4
     *
     * will return:
     *     array (
     *         obj {name:val1;text=val2},
     *         obj {name:val4;text=val2}
     *     );
     * 
     * @param <type> $posts
     * @param <type> $name_prefix
     * @param <type> $value_form_name
     * @return <type> 
     */
    public static function parseParametersToObject($posts, $name_prefix) {
        $ret = array();

        foreach ($posts as $key => $val) {
            if (!empty($val)) {
                // starts with prefix
                $pat = '/' . $name_prefix . '[-_]*(\w+)([-_]*(\d+))*/i';
                if (preg_match($pat, $key, $matches)) {
                    if (isset ($matches[1]) && isset($matches[3])) {
                        if ( empty($ret[intval($matches[3])]) ) {
                            $newObj=null;
                            $newObj->$matches[1] = $val;
                            $ret[intval($matches[3])] = $newObj;
                        } else {
                        	$obj = $ret[intval($matches[3])];
                            $obj->$matches[1] = $val;
                        }
                    }
                }
            }
        }

        return $ret;
    }

    public static function ceilToThousand($n) {
        return Utils::ceilTo($n, 1000);
    }

    public static function ceilToHundred($n) {
        return Utils::ceilTo($n, 100);
    }

    public static function ceilTo($n,$rf) {
        
        if ($n/$rf > 1) {
            return ceil($n/$rf) * $rf;
        }
        return $n;
    }

}

class Shipping {

    private $ct;
    private $srv;
    private $warung;

    /**
     *
     * @param Array $cities [{id:1; name:Jakarta}, {id:2; name:Bogor}]
     * @param Array $services [
     *                          {name: pandusiwi; city_id:1; city_name:Jakarta; min_weight:0; price:7000; free_weight:1.5},
     *                          {name: pandusiwi; city_id:1; city_name:Bogor; min_weight:0; price:9000; free_weight:1.5},
     *                          {name: lorena; city_id:1; city_name:Jakarta; min_weight:2; price:5000; free_weight:1.5},
     *                          {name: lorena; city_id:1; city_name:Bogor; min_weight:2; price:6000; free_weight:1.5}
     *                        ]
     */
    public function __construct($cities, $services, $warung) {
        $this->ct = &$cities;
        $this->srv = &$services;
        $this->warung = $warung;
    }

    public function getCities() {
        return $this->ct;
    }

    public function getServices() {
        return $this->srv;
    }

    public function getDefaultCity() {
        foreach ($this->ct as $city) {
            if (isset($city->default)) {
                return $city;
            }
        }
        return null;
    }

    public function getCityById($cityId) {
        foreach ($this->ct as $city) {
            if ($city->id == $cityId) {
                return $city;
            }
        }
    }

    public function getServiceByCityAndWeight($cityId, $weight) {
        $ret = array();
       
        foreach ($this->srv as $service) {
            if ($service->city_id == $cityId && $weight >= $service->min_weight) {
                array_push($ret, $service);
            }
        }
        return $ret;
    }

    public function getCheapestServices($cityId, $weight) {
        $ret = null;

        //echo 'getCheapestServices('.$cityId.','.$weight.')';
        $serv = $this->getServiceByCityAndWeight($cityId, $weight);

        if (! empty ($serv)) {
            foreach ($serv as $service) {
                if ($ret == null) {
                    $ret = $service;
                } else {
                    if ($ret->price > $service->price) {
                        $ret = $service;
                    }
                }
            }
        }

        return $ret;

    }

    function getSavedShippingInfo() {

        $city = '';
        $shippings = $this->warung->get_shipping_options();

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

}

class WarungCartWidget extends WP_Widget {

    private $warung;

    function __construct() {
        global $warung;
        $this->warung = $warung;
        $widget_ops = array('classname' => 'wcart_widget', 'description' => 'Warung Cart Shopping Cart' );
        parent::__construct(false, $name='Warung Cart', $widget_ops );
    }

    public function widget($args, $instance) {
        $warung = $this->warung;
        $cartImage = $warung->pluginUrl."images/cart.png";
        $co_page = $warung->get_checkout_url();
        $clear_page = Utils::addParameter(get_option("home"), array("wc_clear"=>"1"));
        $cart_sumary = $warung->get_cart_summary();

        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title .'<a href="'.$co_page.'"><img src="'.$cartImage.'" alt="shopping cart"/> Keranjang Belanja</a>'. $after_title; ?>

                <? if (!empty($cart_sumary)) : ?>
                    <?extract($cart_sumary);?>
                    <div><a href="<?=$co_page?>">Ada <?=$total_items?> Item (<?=$warung->formatCurrency($total_price)?>)</a></div>
                    <div class="wcart_widget_nav"><a href="<?=$co_page?>">Lihat pesanan</a> | <a href="<?=$clear_page?>">Batal</a></div>
                <? else: ?>
                    <div>0 Item, beli dong!</div>
                <? endif; ?>
              <?php echo $after_widget; ?>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        return $instance;

    }

    public function form($instance) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => 'Shopping Cart') );
        $title = strip_tags($instance['title']);
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
        <?php
    }


}

class WarungFeaturedContentWidget extends WP_Widget {

    private $warung;

    function __construct() {
        global $warung;
        $this->warung = $warung;
        $widget_ops = array('classname' => 'wfeatured_widget', 'description' => 'Warung Featured Content');
        parent::__construct(false, $name = 'Warung Featured Content', $widget_ops);
    }

    /**
     * Displays category posts widget on blog.
     */
    function widget($args, $instance) {
        global $post;
        $post_old = $post; // Save the post object.

        extract($args);

        $sizes = get_option('jlao_cat_post_thumb_sizes');

        // If not title, use the name of the category.
        if (!$instance["title"]) {
            $category_info = get_category($instance["cat"]);
            $instance["title"] = $category_info->name;
        }

        // Get array of post info.
        $cat_posts = new WP_Query("showposts=" . $instance["num"] . "&cat=" . $instance["cat"]);

        // Excerpt length filter
        $new_excerpt_length = create_function('$length', "return " . $instance["excerpt_length"] . ";");
        if ($instance["excerpt_length"] > 0)
            add_filter('excerpt_length', $new_excerpt_length);

        echo $before_widget;

        // Widget title
        echo $before_title;
        if ($instance["title_link"])
            echo '<a href="' . get_category_link($instance["cat"]) . '">' . $instance["title"] . '</a>';
        else
            echo $instance["title"];
        echo $after_title;

        // Post list
        echo "<ul>\n";

        while ($cat_posts->have_posts()) {
            $cat_posts->the_post();
        ?>
                        <li class="cat-post-item">
                            <img class="thumbnail" src="<?php
            if (get_post_meta($post->ID, 'thumbnail', $single = true)) {
                echo get_post_meta($post->ID, 'thumbnail', $single = true);
            } else {
                bloginfo('url');
                echo "/wp-content/themes/gallery/images/thumbnail-default.jpg";
            }
        ?>" width="125" height="125" alt="<?php echo the_title() ?>" title="Click for more info"/>
                                <a class="post-title" href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>

        <?php
        if (
                function_exists('the_post_thumbnail') &&
                current_theme_supports("post-thumbnails") &&
                $instance["thumb"] &&
                has_post_thumbnail()
        ) :
        ?>
                                        <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
        <?php the_post_thumbnail('cat_post_thumb_size' . $this->id); ?>
                                        </a>
        <?php endif; ?>

        <?php if ($instance['date']) : ?>
                                <p class="post-date"><?php the_time("j M Y"); ?></p>
        <?php endif; ?>

        <?php if ($instance['excerpt']) : ?>
        <?php the_excerpt(); ?>
        <?php endif; ?>

        <?php if ($instance['comment_num']) : ?>
                                <p class="comment-num">(<?php comments_number(); ?>)</p>
        <?php endif; ?>
                        </li>
        <?php
        }

        echo "</ul>\n";

        echo $after_widget;

        remove_filter('excerpt_length', $new_excerpt_length);

        $post = $post_old; // Restore the post object.
    }

    /**
     * Form processing... Dead simple.
     */
    function update($new_instance, $old_instance) {
        /**
         * Save the thumbnail dimensions outside so we can
         * register the sizes easily. We have to do this
         * because the sizes must registered beforehand
         * in order for WP to hard crop images (this in
         * turn is because WP only hard crops on upload).
         * The code inside the widget is executed only when
         * the widget is shown so we register the sizes
         * outside of the widget class.
         */
        if (function_exists('the_post_thumbnail')) {
            $sizes = get_option('jlao_cat_post_thumb_sizes');
            if (!$sizes)
                $sizes = array();
            $sizes[$this->id] = array($new_instance['thumb_w'], $new_instance['thumb_h']);
            update_option('jlao_cat_post_thumb_sizes', $sizes);
        }

        return $new_instance;
    }

    /**
     * The configuration form.
     */
    function form($instance) {
    ?>
                    <p>
                            <label for="<?php echo $this->get_field_id("title"); ?>">
    <?php _e('Title'); ?>:
                                    <input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
                            </label>
                    </p>

                    <p>
                            <label>
    <?php _e('Category'); ?>:
    <?php wp_dropdown_categories(array('name' => $this->get_field_name("cat"), 'selected' => $instance["cat"])); ?>
                            </label>
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("num"); ?>">
    <?php _e('Number of posts to show'); ?>:
                                    <input style="text-align: center;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="text" value="<?php echo absint($instance["num"]); ?>" size='3' />
                            </label>
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("title_link"); ?>">
                                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("title_link"); ?>" name="<?php echo $this->get_field_name("title_link"); ?>"<?php checked((bool) $instance["title_link"], true); ?> />
    <?php _e('Make widget title link'); ?>
                            </label>
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("excerpt"); ?>">
                                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("excerpt"); ?>" name="<?php echo $this->get_field_name("excerpt"); ?>"<?php checked((bool) $instance["excerpt"], true); ?> />
    <?php _e('Show post excerpt'); ?>
                            </label>
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("excerpt_length"); ?>">
    <?php _e('Excerpt length (in words):'); ?>
                            </label>
                            <input style="text-align: center;" type="text" id="<?php echo $this->get_field_id("excerpt_length"); ?>" name="<?php echo $this->get_field_name("excerpt_length"); ?>" value="<?php echo $instance["excerpt_length"]; ?>" size="3" />
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("comment_num"); ?>">
                                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("comment_num"); ?>" name="<?php echo $this->get_field_name("comment_num"); ?>"<?php checked((bool) $instance["comment_num"], true); ?> />
    <?php _e('Show number of comments'); ?>
                            </label>
                    </p>

                    <p>
                            <label for="<?php echo $this->get_field_id("date"); ?>">
                                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("date"); ?>" name="<?php echo $this->get_field_name("date"); ?>"<?php checked((bool) $instance["date"], true); ?> />
    <?php _e('Show post date'); ?>
                            </label>
                    </p>

    <?php if (function_exists('the_post_thumbnail') && current_theme_supports("post-thumbnails")) : ?>
                    <p>
                            <label for="<?php echo $this->get_field_id("thumb"); ?>">
                                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("thumb"); ?>" name="<?php echo $this->get_field_name("thumb"); ?>"<?php checked((bool) $instance["thumb"], true); ?> />
    <?php _e('Show post thumbnail'); ?>
                            </label>
                    </p>
                    <p>
                            <label>
    <?php _e('Thumbnail dimensions'); ?>:<br />
                                    <label for="<?php echo $this->get_field_id("thumb_w"); ?>">
                                            W: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id("thumb_w"); ?>" name="<?php echo $this->get_field_name("thumb_w"); ?>" value="<?php echo $instance["thumb_w"]; ?>" />
                                    </label>

                                    <label for="<?php echo $this->get_field_id("thumb_h"); ?>">
                                            H: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id("thumb_h"); ?>" name="<?php echo $this->get_field_name("thumb_h"); ?>" value="<?php echo $instance["thumb_h"]; ?>" />
                                    </label>
                            </label>
                    </p>
    <?php endif; ?>

    <?php
    }

}

?>
