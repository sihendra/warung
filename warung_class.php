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


    function parse_image_url($post) {
        
    }

    function Warung() {
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
    }

    function init() {

    }

    function install() {
        // set default options
        $this->get_options();
    }


    function init_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form');//, $warung->pluginUrl.'scripts/jquery.form.js',array('jquery'));
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
        $saved = get_option($this->db_option);

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
            update_option($this->db_option, $options);
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

            $ret = new Shipping($c, $s);
        }

        return $ret;
    }

    function handle_options() {

        ob_start();

        $options = $this->get_options();

        if (isset($_POST['submitted'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                $options = array();
                $options['currency'] = $_POST['currency'];
                $options['add_to_cart'] = $_POST['add_to_cart'];
                $options['checkout_page'] = $_POST['checkout_page'];
                $options['shipping_sim_page'] = $_POST['shipping_sim_page'];
                $options['prod_options']=Utils::parseNamevalParameters($_POST,'prod_option_name','prod_option_value');
                $options['shipping_cities']=$_POST['shipping_cities'];
                $options['shipping_options']=$_POST['shipping_options'];
                $options['weight_sign'] = $_POST['weight_sign'];

                update_option($this->db_option, $options);

                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $currency = $options['currency'];
        $add2cart = $options['add_to_cart'];
        $checkout_page = $options['checkout_page'];
        $shipping_sim_page = $options['shipping_sim_page'];
        $prod_options = $options['prod_options'];
        $shipping_cities = $options['shipping_cities'];
        $shipping_options = $options['shipping_options'];
        $weight_sign = $options['weight_sign'];

        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>Warung</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?=wp_nonce_field('warung-nonce')?>
                            <label for="currency">Currency</label>
                            <input id="currency" type="text" size="5" name="currency" value="<?=stripslashes($currency)?>"/><br/>
                            <label for="weight_sign">Weight Sign</label>
                            <input id="weight_sign" type="text" size="5" name="weight_sign" value="<?=stripslashes($weight_sign)?>"/><br/>
                            <label for="add_to_cart">Add to cart text</label>
                            <input id="add_to_cart" type="text" size="10" name="add_to_cart" value="<?=stripslashes($add2cart)?>"/><br/>
                            <label for="checkout_page">Checkout Page</label>
                            <select id="checkout_page" name="checkout_page">
                            <?
                            foreach (get_pages() as $page) {
                                echo '<option value="'.$page->ID.'"'.($checkout_page == $page->ID ? '"selected=selected"':'').'>'.$page->post_title.'</option>';
                            }
                            ?>
                            </select><br/>
                            <label for="shipping_sim_page">Shipping Sim Page</label>
                            <select id="shipping_sim_page" name="shipping_sim_page">
                            <?
                            if (empty($shipping_sim_page)) {
                                echo '<option value="" selected="selected">-- Please Select --</option>';
                            }
                            foreach (get_pages() as $page) {
                                echo '<option value="'.$page->ID.'"'.($shipping_sim_page == $page->ID ? '"selected=selected"':'').'>'.$page->post_title.'</option>';
                            }
                            ?>
                            </select><br/>
                            <h2>Product Options Set</h2>
                            <?
                            $i = 0;
                            if (is_array($prod_options)) {
                                foreach ($prod_options as $name=>$pos) {
                                    ?>
                            <label for="prod_option_name-<?=$i?>">Name</label>
                            <input type="text" id="prod_option_name-<?=$i?>" name="prod_option_name-<?=$i?>" value="<?=stripslashes($name)?>" />
                            <label for="prod_option_value-<?=$i?>">Value</label>
                            <textarea id="prod_option_value-<?=$i?>" name="prod_option_value-<?=$i?>" rows="5" cols="50"><?=stripslashes($pos)?></textarea>
                            <br/>
                                    <?
                                    $i++;
                                }
                            }
                            ?>
                            <label for="prod_option_name-<?=$i?>">Name</label>
                            <input type="text" id="prod_option_name-<?=$i?>" name="prod_option_name-<?=$i?>" value="" />
                            <label for="prod_option_value-<?=$i?>">Value</label>
                            <textarea name="prod_option_value-<?=$i?>" id="prod_option_value-<?=$i?>" rows="5" cols="50"></textarea>


                            <h2>Shipping Cities</h2>
                            <label for="shipping_cities">Cities</label>
                            <textarea id="shipping_cities" name="shipping_cities" rows="5" cols="50"><?=stripslashes($shipping_cities)?></textarea>
                            <br/>


                            <h2>Shipping Services</h2>
                            <label for="shipping_options">Shipping Services</label>
                            <textarea id="shipping_options" name="shipping_options" rows="5" cols="50"><?=stripslashes($shipping_options)?></textarea>
                            <br/>


                            <div class="submit"><input type="submit" name="submitted" value="Update" /></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?

        $out = ob_get_contents();
        ob_end_clean();

        echo $out;
    }

    function admin_menu() {
        add_menu_page('Warung Options', 'Warung', 8, basename(__FILE__), array(&$this, 'handle_options'));
        add_meta_box('warung-product-id','Product Information',array(&$this, 'display_product_custom_field'),'post','normal','high');

        //add_action('do_meta_boxes', array(&$this, 'display_meta'));
        add_action('save_post', array($this,'warung_save_product_details'));

        //add_options_page('Warung Options', 'Warung', 8, basename(__FILE__), array(&$this, 'handle_options'));
    }

    function warung_get_product_by_id($post_id) {
        $ret=array();

        $product_code = get_post_meta($post_id, '_warung_product_code', true);
        $product_name = get_post_meta($post_id, '_warung_product_name', true);
        $product_price = get_post_meta($post_id, '_warung_product_price', true);
        $product_weight = get_post_meta($post_id, '_warung_product_weight', true);
        $product_options_name = get_post_meta($post_id, '_warung_product_options', true);
        $product_thumbnail = get_post_meta($post_id, 'thumbnail', true);
        $product_weight_discount = get_post_meta($post_id, '_warung_product_weight_discount', true);

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

            if (!empty($product_options_name)) {
                $opts = $this->get_options();
                $prod_opts = $opts['prod_options'];

                if (!empty($prod_opts) && is_array($prod_opts)) {
                    foreach($prod_opts as $k=>$v) {
                        if ($k == $product_options_name) {
                            $ret["option_name"]=$product_options_name;
                            $ret["option_value"] = Utils::parseJsonMultiline($v);
                        }
                    }
                }
            }

            if (!empty($product_weight_discount)) {
                $ret["weight_discount"] = $product_weight_discount;
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

    function warung_save_product_details($post_id) {
        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

        if ( !wp_verify_nonce( $_POST['warung_noncename'], plugin_basename(__FILE__) )) {
            return $post_id;
        }

        // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
        // to do anything
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            return $post_id;


        // Check permissions
        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) )
                return $post_id;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data

        $prod_code = $_POST['product_code'];
        $prod_name = $_POST['product_name'];
        $prod_price = $_POST['product_price'];
        $prod_weight = $_POST['product_weight'];
        $prod_options = $_POST['product_options'];
        $prod_weight_discount = $_POST['product_weight_discount'];

        if (!empty($prod_code) && !empty($prod_name)) {
            update_post_meta($post_id, '_warung_product_code', $prod_code);
            update_post_meta($post_id, '_warung_product_name', $prod_name);
            if (empty($prod_price)) {
                $prod_price = 0;
            }
            if (empty($prod_weight)) {
                $prod_weight = 1;
            }
            update_post_meta($post_id, '_warung_product_price', $prod_price);
            update_post_meta($post_id, '_warung_product_weight', $prod_weight);
            update_post_meta($post_id, '_warung_product_weight_discount', $prod_weight_discount);
            if ($prod_options != '-- none --') {
                update_post_meta($post_id, '_warung_product_options', $prod_options);
            } else {
                delete_post_meta($post_id, '_warung_product_options');
            }
        }


        // Do something with $mydata
        // probably using add_post_meta(), update_post_meta(), or
        // a custom table (see Further Reading section below)


    }

    function display_product_custom_field() {
        // Use nonce for verification
        global $post;

        echo '<input type="hidden" name="warung_noncename" id="warung_noncename" value="' .
                wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

        // get prev meta
        $product = $this->warung_get_product_by_id($post->ID);

        // product code
        // price
        // weight
        // option set
        echo '<label for="product_code">Code</label>
        <input type="text" name="product_code" value="'.$product["code"].'"/><br/>

        <label for="product_name">Name</label>
        <input type="text" name="product_name" value="'.$product["name"].'"/><br/>

        <label for="product_price">Price</label>
        <input type="text" name="product_price" value="'.$product["price"].'"/><br/>


        <label for="product_weight">Weight</label>
        <input type="text" name="product_weight" value="'.$product["weight"].'"/><br/>

        <label for="product_weight_discount">Weight Discount</label>
        <input type="text" name="product_weight_discount" value="'.$product["weight_discount"].'"/><br/>';

        // get from option
        $prod_options = $this->get_options();
        $prod_options = $prod_options["prod_options"];
        // get from product custom field

        if (is_array($prod_options) && !empty($prod_options)) {
            echo '<label for="product_options">Option Set</label>
            <select name="product_options">';
            echo '<option value="-- none --">-- none --</option>';
            foreach ($prod_options as $key => $value) {
                if ($product["option_name"] == $key) {
                    echo '<option value="'.$key.'" selected="selected">'.$key.'</option>';
                } else {
                    echo '<option value="'.$key.'">'.$key.'</option>';
                }
            }
            echo'</select><br/>';

        }

    }

    function display_meta() {
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            remove_meta_box( 'postcustom', 'post', $context );
            remove_meta_box( 'postcustom', 'page', $context );
            //Use the line below instead of the line above for WP versions older than 2.9.1
            //remove_meta_box( 'pagecustomdiv', 'page', $context );
        }
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
    public function __construct($cities, $services) {
        $this->ct = &$cities;
        $this->srv = &$services;
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
        $cart_sumary = get_cart_summary();

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
?>
