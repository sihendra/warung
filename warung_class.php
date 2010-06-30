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

        return number_format($weight,0,',','.').' '.trim($weight_sign);
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
        add_parameter($this->get_checkout_url(), array("step"=>2));
    }

    function get_shipping_options() {
        $options = $this->get_options();
        $shipping_options = $options["shipping_options"];
        return $shipping_options;
    }

    function get_shipping_cities($shipping_name) {
        $shipping_options = $this->get_shipping_options();

        if (!empty ($shipping_name)) {
            $cities = $shipping_options[$shipping_name];
            if (!empty($cities)) {
                return $this->warung_parse_nameval_options($cities);
            }
        }
    }

    function get_shipping_services() {
        $shipping_options = $this->get_shipping_options();
        $ret = array();
        foreach ($shipping_options as $k=>$v) {
            array_push($ret, $k);
        }
        return $ret;
    }

    function get_shipping_info($shipping_name, $city_name) {
        $ret;
        if (!empty($shipping_name) && !empty($city_name)) {
            $cities = $this->get_shipping_cities($shipping_name);
            if (!empty($cities)) {
                foreach ($cities as $c) {
                    if ($c->kota == $city_name) {
                        $ret = $c;
                    }
                }
            }
        }
        return $ret;
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
                'prod_options' => array(),
                'shipping_options' => array(),
                'weight_sign' => 'Kg'
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

    function parse_product_options($posts) {
        $ret = array();

        $prev_idx = 0;
        $prev_name = '';
        foreach ($posts as $key=>$val) {

            if (!empty ($val)) {
                if (strpos($key,'prod_option_name') !== false) {
                    $tok = explode('-', $key);
                    $prev_idx = $tok[1];
                    $prev_name = $val;

                } else if (strpos($key, 'prod_option_value') !== false) {

                    if (strlen(trim($prev_name)) > 0) {
                        $ret[$prev_name] = $val;
                    }
                }
            }

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
     * @return <type>
     */
    function parse_nameval_options($posts, $name_form_name, $value_form_name) {
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

    function handle_options() {
        $options = $this->get_options();

        if (isset($_POST['submitted'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                $options = array();
                $options['currency'] = $_POST['currency'];
                $options['add_to_cart'] = $_POST['add_to_cart'];
                $options['checkout_page'] = $_POST['checkout_page'];
                $options['prod_options']=$this->parse_nameval_options($_POST,'prod_option_name','prod_option_value');
                $options['shipping_options']=$this->parse_nameval_options($_POST, 'shipping_option_name', 'shipping_option_value');
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
        $prod_options = $options['prod_options'];
        $shipping_options = $options['shipping_options'];
        $weight_sign = $options['weight_sign'];

        echo
        '<div class="wrap" style="max-width:950px !important;">
                <h2>Warung</h2>
                <div id="poststuff" style="margin-top:10px;">
                    <div id="mainblock" style="width:710px">
                        <div class="dbx-content">
                            <form action="" method="post">
                            '.wp_nonce_field('warung-nonce').'
                                <label for="currency">Currency</label>
                                <input id="currency" type="text" size="5" name="currency" value="'.stripslashes($currency).'"/><br/>
                                <label for="weight_sign">Weight Sign</label>
                                <input id="weight_sign" type="text" size="5" name="weight_sign" value="'.stripslashes($weight_sign).'"/><br/>
                                <label for="add_to_cart">Add to cart text</label>
                                <input id="add_to_cart" type="text" size="10" name="add_to_cart" value="'.stripslashes($add2cart).'"/><br/>
                                <label for="checkout_page">Checkout Page</label>
                                <select id="checkout_page" name="checkout_page"/>';
        foreach (get_pages() as $page) {
            echo '<option value="'.$page->ID.'"'.($checkout_page == $page->ID ? '"selected=selected"':'').'>'.$page->post_title.'</option>';
        }
        echo '</select><br/>
                                <h2>Product Options Set</h2>';
        $i = 0;
        if (is_array($prod_options)) {
            foreach ($prod_options as $name=>$pos) {
                echo '<label for="prod_option_name-'.$i.'">Name</label>';
                echo '<input type="text" id="prod_option_name-'.$i.'" name="prod_option_name-'.$i.'" value="'.stripslashes($name).'" />';
                echo '<label for="prod_option_value-'.$i.'">Value</label>';
                echo '<textarea id="prod_option_value-'.$i.'" name="prod_option_value-'.$i.'" rows="5" cols="50">'.stripslashes($pos).'</textarea>';
                echo '<br/>';
                $i++;
            }
        }

        echo '<label for="prod_option_name-'.$i.'">Name</label>';
        echo '<input type="text" id="prod_option_name-'.$i.'" name="prod_option_name-'.$i.'" value="" />';
        echo '<label for="prod_option_value-'.$i.'">Value</label>';
        echo '<textarea name="prod_option_value-'.$i.'" id="prod_option_value-'.$i.'" rows="5" cols="50"></textarea>';

        // tiki, dll
        echo '<h2>Shipping Options</h2>';
        $i = 0;
        if (is_array($shipping_options)) {
            foreach ($shipping_options as $name=>$pos) {
                echo '<label for="shipping_option_name-'.$i.'">Name</label>';
                echo '<input type="text" id="shipping_option_name-'.$i.'" name="shipping_option_name-'.$i.'" value="'.stripslashes($name).'" />';
                echo '<label for="shipping_option_value-'.$i.'">Value</label>';
                echo '<textarea id="shipping_option_value-'.$i.'" name="shipping_option_value-'.$i.'" rows="5" cols="50">'.stripslashes($pos).'</textarea>';
                echo '<br/>';
                $i++;
            }
        }

        echo '<label for="shipping_option_name-'.$i.'">Name</label>';
        echo '<input type="text" id="shipping_option_name-'.$i.'" name="shipping_option_name-'.$i.'" value="" />';
        echo '<label for="shipping_option_value-'.$i.'">Value</label>';
        echo '<textarea name="shipping_option_value-'.$i.'" id="shipping_option_value-'.$i.'" rows="5" cols="50"></textarea>';


        echo '<div class="submit"><input type="submit" name="submitted" value="Update" /></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>';
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
                            $ret["option_value"] = $this->warung_parse_nameval_options($v);
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
        <input type="text" name="product_weight" value="'.$product["weight"].'"/><br/>';

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

    function warung_parse_product_options($content) {
        $content = str_replace('\\"', '"', $content);

        $ret = explode("\n", $content);
        foreach($ret as &$r) {
            $r = '{'.$r.'}';
            $r = json_decode($r);
        }
        return $ret;
    }

    function warung_parse_nameval_options($content) {
        $content = str_replace('\\"', '"', $content);

        $ret = explode("\n", $content);
        $i=0;
        foreach($ret as &$r) {
            $r = '{"id":'.$i++.','.$r.'}';
            $r = json_decode($r);
        }
        return $ret;
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
}
?>
