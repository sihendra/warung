<?php
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
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL . '/warung');
    }

    function init() {
        session_start();
        //wp_register_sidebar_widget('Warung Cart', 'Warung Cart', 'warung_cart');
        add_filter('the_content', array(&$this, 'filter_content'));
        // save cookie
        //updateShippingInfo();
        $this->process_params();
    }

    function init_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form'); //, $this->pluginUrl.'scripts/jquery.form.js',array('jquery'));
        wp_enqueue_script('jquery_validaton', $this->pluginUrl . '/scripts/jquery.validate.js', array('jquery'));
        wp_enqueue_script('warung_js', $this->pluginUrl . '/scripts/warung.js', array('jquery'));
    }

    function init_styles() {
        wp_enqueue_style('warung_style', $this->pluginUrl . '/style/warung.css');
    }

    function process_params() {
        // update cart
        // parse action

        $warungOpt = new WarungOptions();
        $keranjang = $warungOpt->getCartService();

        if (isset($_REQUEST['action'])) {
            $a = $_REQUEST['action'];
            if ($a == 'updateCart' && wp_verify_nonce($_REQUEST['warung_detailed_cart_nonce'],'warung_detailed_cart')) {
                foreach ($_REQUEST as $key => $val) {
                    if (strpos($key, 'qty_') !== false) {
                        //echo $key.'->'.$val;
                        $tok = explode('_', $key);
                        if (count($tok) == 2) {
                            $keranjang->updateQuantity($tok[1], $val);
                        }
                    }
                }
            } else if ($a == 'clearCart') {
                $keranjang->emptyCart();
            } else if ($a == 'removeCartItem' && isset($_REQUEST['ci'])) {
                $keranjang->updateQuantity($_REQUEST['ci'], 0);
            } else if ($a == 'confirm' && wp_verify_nonce($_REQUEST['warung_shipping_form_nonce'],'warung_shipping_form')) {
                // update shipping
                $kasir = $warungOpt->getKasirService();
                extract($_REQUEST);

                if (isset($sname) && !empty($sname)) {
                    $userInfo = new UserInfo($sname, $semail, $sphone, $sphone, $saddress, $scity, $scountry, $sadditional_info);
                    $kasir->saveUserInfo($userInfo);
                }


            } else if ($a == 'pay') {
                // send email
                // redirect to payOK
                // redirect to payError
            }
        }

        if (isset($_POST['add_to_cart'])) {
            $added_product = $this->getProductById($_POST['product_id']);
            $item = $this->formatToKeranjangItem($added_product, $_POST["product_option"]);

            $keranjang->addItem($item, 1);
        } if (isset($_POST['wcart_ordernow'])) {
            $added_product = $this->getProductById($_POST['product_id']);
            $item = $this->formatToKeranjangItem($added_product, $_POST["product_option"]);

            $keranjang->addItem($item, 1);

            // redirect to checkoutpage
            header("Location: " . $warungOpt->getCheckoutURL());
            exit;
        }
    }

    function formatToKeranjangItem($product, $optId = -1) {
        $ret = null;
        if (!empty($product)) {
            if (isset($optId) && $optId != -1) {
                $opt = $this->warung_get_selected_option($product, $optId);
                if (!empty($opt)) {

                    $iCartId = $product["id"] . '-' . $opt->id;
                    $iProductId = $product["id"];
                    $iName = $product["name"] . ' - ' . $opt->name;
                    $iPrice = $opt->price;
                    $iWeight = $opt->weight;
                    $iQuantity = 1;
                    $iWeightDiscount = 0;
                    if (isset($opt->weight_discount)) {
                        $iWeightDiscount = $opt->weight_discount;
                    }

                    $iAttachment = array("product" => $product, "opt_name" => $opt->name, "opt_id" => $opt->id);

                    $ret = new KeranjangItem($iCartId, $iProductId, $iName, $iPrice, $iWeight, $iQuantity);
                    $ret->weightDiscount = $iWeightDiscount;
                    $ret->attachment = $iAttachment;
                }
            } else {


                $iCartId = $product["id"];
                $iProductId = $product["id"];
                $iName = $product["name"];
                $iPrice = $product["price"];
                $iWeight = $product["weight"];
                $iQuantity = 1;
                $iWeightDiscount = 0;
                if (isset($opt->weight_discount)) {
                    $iWeightDiscount = $opt->weight_discount;
                }

                $iAttachment = array("product" => $product);
                if (isset($product["price_discount"])) {
                    $iAttachment["price_discount"] = $product["price_discount"];
                }

                $ret = new KeranjangItem($iCartId, $iProductId, $iName, $iPrice, $iWeight, $iQuantity);
                $ret->weightDiscount = $iWeightDiscount;
                $ret->attachment = $iAttachment;
            }
        }
        return $ret;
    }

    function filter_content($content) {
        global $post;

        $co_page = $this->get_checkout_page();
        $shipping_sim_page = $this->get_shipping_simulation_page();
        $home_url = get_option("home");

        ob_start();

        if ($post->ID == $co_page) {
            $warungOpt = new WarungOptions();
            $wiz = $warungOpt->getCheckoutWizard();
            $content = $wiz->showPage();
        } else if ($post->ID == $shipping_sim_page) {
            $s = $this->get_shipping_options();
            if (!empty($s)) {
                $cities = array();
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
                    if (!empty($s_cheap)) {
                        array_push($resp, '<strong>' . $s_cheap->name . ': ' . $this->formatCurrency(Utils::ceilToHundred($s_cheap->price) * $s_weight) . ' (' . $this->formatCurrency(Utils::ceilToHundred($s_cheap->price)) . '/Kg) (paling murah)</strong>');
                    }
                    $s_serv = $s->getServiceByCityAndWeight($s_cid, $s_weight);
                    foreach ($s_serv as $sss) {
                        if ($sss != $s_cheap) {
                            array_push($resp, $sss->name . ': ' . $this->formatCurrency(Utils::ceilToHundred($sss->price) * $s_weight) . ' (' . $this->formatCurrency(Utils::ceilToHundred($sss->price)) . '/Kg)');
                        }
                    }
                }
?>
<?
                if (!empty($resp)) {
?>
                    <div class="wcart_shipping_sim_result">
<?
                    foreach ($resp as $r) {
?><?= $r ?><br/><?
                    }
?>
                </div>
    <? } ?>
            <div class="wcart_shipping_sim" >

                <form method="POST">
                    <table>
                        <tr>
                            <td><label for="wc_sim_city">Kota Tujuan</label></td>
                            <td><?= $this->form_select('wc_sim_city', $cities, $city, 'city_callback', true) ?></td>
                            </tr>
                            <tr>
                                <td><label for="wc_sim_weight">Berat (Kg)</label></td>
                                <td><input id="wc_sim_weight" type="text" name="wc_sim_weight" value="<?= $wc_weight ?>"/></td>
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

                $disc_price = null;
                if (isset($product ['price_discount'])) {
                    $disc_price = $product['price_discount'];
                }
?>
                <div id="wCart_add_2_cart">
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?= $product["id"] ?>">
<?
                if (!empty($product["option_value"])) {
                    $isRadioOption = true;
                    if ($isRadioOption) {

                        $hasDefault = false;
                        foreach ($product["option_value"] as $po) {
                            if (isset($po->default)) {
                                $hasDefault = true;
                            }
                        }

                        foreach ($product["option_value"] as $po) {
                            $checked = "";

                            // set default to first entry if no default given
                            if (!$hasDefault && empty($checked)) {
                                $checked = "checked=checked";
                            }

                            if (isset($po->default)) {
                                $checked = "checked=checked";
                            }
?><p>
                                <input type="radio" name="product_option" value="<?= $po->id ?>" <?= $checked ?>/>
        <?= $po->name . '@' . $this->formatCurrency($po->price) ?>
                            </p><?
                        }
                    } else {
        ?>

                        <select name="product_option" class="wcart_price" size="<?= max(1, sizeof($product["option_value"]) / 3) ?>">
<?
                        foreach ($product["option_value"] as $po) {
                            $selected = "";
                            if (isset($po->default)) {
                                $selected = 'selected="selected"';
                            }
?>
                            <option value="<?= $po->id ?>" <?= $selected ?>><?= $po->name . '@' . $this->formatCurrency($po->price) ?></option>
            <?
                        }
            ?>
                    </select>
            <?
                    }
                } else {
                    if (isset($disc_price) && !empty($disc_price)) {
            ?>
                        <h2><s><?= $this->formatCurrency($disc_price) ?></s></h2>
                        <h2><?= $this->formatCurrency($product["price"]) ?></h2>
<?
                    } else {
?>
                        <h2><?= $this->formatCurrency($product["price"]) ?></h2>
        <?
                    }
                }
                $options = $this->get_options();
        ?>
        <!--<input type="submit" name="add_to_cart" value="<?= $options["add_to_cart"] ?>"/>-->
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

    function send_order($bccAdmin=false) {

        if (!empty($_SESSION['wCart']) && isset($_COOKIE['wCartShipping'])) {
            $so = $this->get_shipping_options();
            $sh = $so->getSavedShippingInfo();
            $email_pemesan = $sh['email'];

            $admin_email = get_option("admin_email");
            $order_id = date('ymdH') . mt_rand(10, 9999);
            $subject = "[Warungsprei.com] Pemesanan #" . $order_id;
            $admin_message = $this->get_order_summary(true, true, array("order_id" => $order_id));
            $customer_message = $this->get_order_summary(false, true, array("order_id" => $order_id));
            //echo get_order_summary();
            $headers = "Content-type: text/html;\r\n";
            $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";

            // send email to admin
            $ret = mail($admin_email, "[Admin] " . $subject, $admin_message, $headers);
            // send to pemesan bcc admin
            if ($bccAdmin) {
                $headers .= "Bcc: " . $admin_email . "\r\n";
            }
            mail($email_pemesan, $subject, $customer_message, $headers);

            $home_url = get_option("home");

            if ($ret) {
?>
                <div class="wcart_info">
                    <p>Informasi pemesanan juga sudah kami kirim ke <b>'<?= $email_pemesan ?>'.</b> Mohon periksa juga folder <b>'Junk'</b> jika tidak ada di inbox.</p>
                </div>
                <div class="wcart_general_container">
<?
                echo $customer_message;
?>
                </div>
                <div><br/><a href="<?= $home_url ?>" class="wcart_button_url">Kembali berbelanja &gt;&gt;</a></div>
    <?
                $this->warung_empty_cart();
            } else {
    ?>
            <div class="wcart_general_container">
                            	                Maaf kami belum dapat memproses pesanan anda. Silahkan coba beberapa saat lagi.<br/>
                            	                Untuk pemesanan dapat langsung dikirim via SMS ke: 08888142879 atau 081808815325.<br/>
                            	                Klik <a href="<?= $this->get_checkout_url() ?>" class="wcart_button_url">di sini</a> untuk melihat daftar pesanan anda.
                </div>
    <?
            }


            return $ret;
        }

        return false;
    }

    function parse_image_url($post) {

    }

    function install() {
        // set default options
        $this->get_options();

        $installed_ver = get_option( "warung_db_version" );

        // DB
        global $wpdb;
        //define the custom table name
        $table_name = $wpdb->prefix . "wrg_order";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `dtcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `status` varchar(30) NOT NULL DEFAULT 'order',
              `dtlastupdated` datetime NOT NULL,
              `items_price` int(11) NOT NULL,
              `shipping_price` int(11) NOT NULL,
              `dtpayment` datetime DEFAULT NULL,
              `dtdelivery` datetime DEFAULT NULL,
              `delivery_number` varchar(100) DEFAULT NULL,
              `shipping_weight` float NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }

        $table_name = $wpdb->prefix . "wrg_order_items";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `order_id` int(11) NOT NULL,
              `item_id` int(11) NOT NULL,
              `name` varchar(512) NOT NULL,
              `quantity` int(11) NOT NULL,
              `weight` float NOT NULL DEFAULT '0',
              `price` float NOT NULL DEFAULT '0',
              KEY `idx_wrg_order_items` (`order_id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }

        $table_name = $wpdb->prefix . "wrg_order_shipping";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `order_id` int(11) NOT NULL,
              `name` varchar(100) NOT NULL,
              `email` varchar(100) NOT NULL,
              `mobile_phone` int(11) DEFAULT NULL,
              `phone` int(11) DEFAULT NULL,
              `address` varchar(200) DEFAULT NULL,
              `city` varchar(100) DEFAULT NULL,
              `state` varchar(100) DEFAULT NULL,
              `country` varchar(100) DEFAULT NULL,
              `additional_info` varchar(200) DEFAULT NULL,
              PRIMARY KEY (`order_id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }


    }

    function formatCurrency($price) {

        $options = $this->get_options();

        $wo = new WarungOptions();
        $currency = $wo->getCurrency();

        return trim($currency) . number_format($price, 0, ',', '.');
    }

    function formatWeight($weight) {
        $options = $this->get_options();

        $wo = new WarungOptions();
        $weight_sign = $wo->getWeightSign();

        return number_format($weight, 1, ',', '.') . ' ' . trim($weight_sign);
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
        return Utils::addParameter($this->get_checkout_url(), array("step" => 2));
    }

    function get_order_url() {
        return Utils::addParameter($this->get_checkout_url(), array("step" => 3));
    }

    function get_shipping_simulation_page() {
        $options = $this->get_options();
        return $options['shipping_sim_page'];
    }

    function get_options() {

        // default
        $def_page;
        foreach (get_pages () as $page) {
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
        $shipping_options = $options["shipping_byweight"];
        $cities = $options["shipping_cities"];
        $ret = null;

        if (!empty($shipping_options) && !empty($cities)) {

            $c = Utils::parseJsonMultiline($cities, false);
            $s = Utils::parseJsonMultiline($shipping_options, false);

            $ret = new Shipping($c, $s, $this);
        }

        return $ret;
    }

    function getProductById($post_id, $calculateDiscount=true) {
        $ret = array();

        $product_code = get_post_meta($post_id, '_warung_product_code', true);
        $product_name = get_post_field('post_title', $post_id);
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
                if ($calculateDiscount) {
                    $ret["weight_discount"] = $product_weight_discount;
                    $ret["weight"] = max(array(0, $product_weight - $product_weight_discount));
                } else {
                    $ret["weight_discount"] = $product_weight_discount;
                }
            }
            if (!empty($product_price_discount)) {
                if ($calculateDiscount) {
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
                    foreach ($prod_opts as $k => $v) {
                        if ($v->name == $product_options_name) {
                            $ret["option_name"] = $product_options_name;
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
            foreach ($product["option_value"] as $po) {
                if ($id == $po->id) {
                    $ret = $po;
                    break;
                }
            }
        }

        return $ret;
    }
}
?>
