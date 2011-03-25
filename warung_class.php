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
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
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
        wp_enqueue_script('jquery_validaton', $this->pluginUrl . 'scripts/jquery.validate.js', array('jquery'));
        wp_enqueue_script('warung_js', $this->pluginUrl . 'scripts/warung.js', array('jquery'));
    }

    function init_styles() {
        wp_enqueue_style('warung_style', $this->pluginUrl . 'style/warung.css');
    }

    function process_params() {
        // update cart
        // parse action

        $warungOpt = new WarungOptions();
        $keranjang = $warungOpt->getCartService();

        if (isset($_REQUEST['action'])) {
            $a = $_REQUEST['action'];
            if ($a == 'updateCart') {
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
            } else if ($a == 'confirm') {
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
        $i = 0;
        foreach ($a_ret as &$r) {
            if ($b_generate_id) {
                $r = '{"id":' . $i++ . ',' . $r . '}';
            } else {
                $r = '{' . $r . '}';
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
        $ret = $url;
        $qstr = '';
        $i = 0;
        foreach ($param as $key => $value) {
            if ($i++ == 0) {
                $qstr .= $key . '=' . $value;
            } else {
                $qstr .= '&' . $key . '=' . $value;
            }
        }
        if (strpos($url, '?')) {
            $ret = $url . '&' . $qstr;
        } else {
            $ret = $url . '?' . $qstr;
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
        foreach ($posts as $key => $val) {

            if (!empty($val)) {
                if (strpos($key, $name_form_name) !== false) {
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
                    if (isset($matches[1]) && isset($matches[3])) {
                        if (empty($ret[intval($matches[3])])) {
                            $newObj = null;
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

    public static function ceilTo($n, $rf) {

        if ($n / $rf > 1) {
            return ceil($n / $rf) * $rf;
        }
        return $n;
    }

}

class WarungCartWidget extends WP_Widget {

    private $warung;

    function __construct() {
        global $warung;
        $this->warung = $warung;
        $widget_ops = array('classname' => 'wcart_widget', 'description' => 'Warung Cart Shopping Cart');
        parent::__construct(false, $name = 'Warung Cart', $widget_ops);
    }

    public function widget($args, $instance) {
        $warung = $this->warung;
        $cartImage = $warung->pluginUrl . "images/cart.png";
        $co_page = $warung->get_checkout_url();
        $clear_page = Utils::addParameter(get_option("home"), array("wc_clear" => "1"));

        $cart = new KeranjangService($_SESSION["warung_keranjang"]);
        $cart_sumary = $cart->getSummary();

        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
    ?>
<?php echo $before_widget; ?>
<?php if ($title)
            echo $before_title . '<a href="' . $co_page . '"><img src="' . $cartImage . '" alt="shopping cart"/> Keranjang Belanja</a>' . $after_title; ?>

<? if (!empty($cart_sumary->totalItems)) : ?>
            <div><a href="<?= $co_page ?>">Ada <?= $cart_sumary->totalItems ?> Item (<?= $warung->formatCurrency($cart_sumary->totalPrice) ?>)</a></div>
            <div class="wcart_widget_nav"><a href="<?= $co_page ?>">Lihat pesanan</a> | <a href="<?= $clear_page ?>">Batal</a></div>
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
                $instance = wp_parse_args((array) $instance, array('title' => 'Shopping Cart'));
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
