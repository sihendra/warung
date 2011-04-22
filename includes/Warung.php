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

        $wo = new WarungOptions();

        $co_page = $wo->getCheckoutPageId();
        $shipping_sim_page = $wo->getShippingSimPageId();
        $home_url = $wo->getHomeURL();

        ob_start();

        if ($post->ID == $co_page) {
            $wiz = $wo->getCheckoutWizard();
            $content = $wiz->showPage();
        } else if ($post->ID == $shipping_sim_page) {
            $s = $wo->getShippingServices();
            if (!empty($s)) {
                $kasir = new WarungKasir($s, null);
                $countries = $kasir->getCountries();
                $country = '';
                if (sizeof($countries >= 1)) {
                    foreach($countries as $k=>$v) {
                        $country = $v;
                        break;
                    }
                }

                $cities = $kasir->getCitiesByCountry($country);
                $city = $_REQUEST["wc_sim_city"];
                $wc_weight = $_REQUEST["wc_sim_weight"];
                if (empty($wc_weight)) {
                    $wc_weight = 1;
                }

                $resp = array();
                if (isset($city)) {
                    $s_cid = $_REQUEST["wc_sim_city"];
                    $s_weight = $_REQUEST["wc_sim_weight"];
                    $dest = new ShippingDestination($country, null, $s_cid, 0);
                    $s_cheap = $kasir->getCheapestShippingServiceByWeight($dest, $s_weight);
                    if (!empty($s_cheap)) {
                        $s_price = $s_cheap->getPrice($dest, array(new KeranjangItem(0, 0, '', 0, $s_weight, 1, null, 0)));
                        $s_dest = $s_cheap->getDestination($dest);
                        array_push($resp, '<strong>' . $s_cheap->getName() . ': ' . Utils::formatCurrency(Utils::ceilToHundred($s_price)) . ' (' . Utils::formatCurrency(Utils::ceilToHundred($s_dest->price)) . '/Kg) (paling murah)</strong>');
                    }
                    $s_serv = $kasir->getShippingServicesByDestination($dest);
                    foreach ($s_serv as $sss) {
                        if ($sss != $s_cheap) {
                            $s_price = $sss->getPrice($dest, array(new KeranjangItem(0, 0, '', 0, $s_weight, 1, null, 0)));
                            $s_dest = $sss->getDestination($dest);
                            if ($s_price > 0) {
                                array_push($resp, $sss->getName() . ': ' . Utils::formatCurrency(Utils::ceilToHundred($s_price)) . ' (' . Utils::formatCurrency(Utils::ceilToHundred($s_dest->price)) . '/Kg)');
                            }
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
                            <td><?= HTMLUtil::select('wc_sim_city', 'wc_sim_city', $cities, $city) ?></td>
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
        <?= $po->name . '@' . Utils::formatCurrency($po->price) ?>
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
                            <option value="<?= $po->id ?>" <?= $selected ?>><?= $po->name . '@' . Utils::formatCurrency($po->price) ?></option>
            <?
                        }
            ?>
                    </select>
            <?
                    }
                } else {
                    if (isset($disc_price) && !empty($disc_price)) {
            ?>
                        <h2><s><?= Utils::formatCurrency($disc_price) ?></s></h2>
                        <h2><?= Utils::formatCurrency($product["price"]) ?></h2>
<?
                    } else {
?>
                        <h2><?= Utils::formatCurrency($product["price"]) ?></h2>
        <?
                    }
                }
                $options = $wo->getOptions();
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

    function install() {
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

    // -------------------------- OPTIONS ------------------------------


    function get_shipping_options() {
        $wo = new WarungOptions();
        $options = $wo->getOptions();
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
                $wo = new WarungOptions();

                $opts = $wo->getOptions();
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
