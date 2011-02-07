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

        if (isset($_POST['general_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }
                
                $options['currency'] = $_POST['currency'];
                $options['add_to_cart'] = $_POST['add_to_cart'];
                $options['checkout_page'] = $_POST['checkout_page'];
                $options['shipping_sim_page'] = $_POST['shipping_sim_page'];
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
        $weight_sign = $options['weight_sign'];

        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>General Options</h2>
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



                            <div class="submit"><input type="submit" name="general_submit" value="Update" /></div>
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

    function handle_shipping() {
        ob_start();

        $options = $this->get_options();

        if (isset($_POST['shipping_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }
                $options['shipping_cities']=$_POST['shipping_cities'];
                $options['shipping_options']=$_POST['shipping_options'];

                update_option($this->db_option, $options);

                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $shipping_cities = $options['shipping_cities'];
        $shipping_options = $options['shipping_options'];

        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>Shipping Options</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?=wp_nonce_field('warung-nonce')?>
                            <h2>Cities</h2>
<!--                            <label for="shipping_cities">Cities</label>-->
                            <textarea id="shipping_cities" name="shipping_cities" rows="5" cols="50"><?=stripslashes($shipping_cities)?></textarea>
                            <br/>
                            <h2>Services</h2>
<!--                            <label for="shipping_options">Shipping Services</label>-->
                            <textarea id="shipping_options" name="shipping_options" rows="5" cols="50"><?=stripslashes($shipping_options)?></textarea>
                            <br/>
                            <div class="submit"><input type="submit" name="shipping_submit" value="Update" /></div>
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

    function handle_product_opt() {
        ob_start();

        $options = $this->get_options();

        if (isset($_POST['product_opt_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }
                $options['prod_options']=Utils::parseParametersToObject($_POST,'prod_option');

                update_option($this->db_option, $options);

                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $prod_options = $options['prod_options'];

        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>Product Option Set</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?=wp_nonce_field('warung-nonce')?>
                            <?
                            $i = 0;
                            if (is_array($prod_options)) {
                                foreach ($prod_options as $key=>$val) {
                                    $name = '';
                                    $prod = '';
                                    $txt = '';
                                    if (is_object($val)) {
                                        $name = $val->name;
                                        $prod = $val->value;
                                        if (isset ($val->txt)) {
                                            $txt = $val->txt;
                                        }
                                    } else {
                                        // backward compatibility
                                        $name = $key;
                                        $prod = $val;
                                    }
                                    ?>
                            <br/>
                            <label for="prod_option_name-<?=$i?>">Name</label>
                            <input type="text" id="prod_option_name-<?=$i?>" name="prod_option_name-<?=$i?>" value="<?=stripslashes($name)?>" />
                            <br/>
                            <label for="prod_option_value-<?=$i?>">Value</label>
                            <textarea id="prod_option_value-<?=$i?>" name="prod_option_value-<?=$i?>" rows="5" cols="50"><?=stripslashes($prod)?></textarea>
                            <br/>
                            <label for="prod_option_txt-<?=$i?>">Text</label>
                            <textarea id="prod_option_txt-<?=$i?>" name="prod_option_txt-<?=$i?>" rows="5" cols="50"><?=stripslashes($txt)?></textarea>
                            <br/>

                                    <?
                                    $i++;
                                }
                            }
                            ?>
                            <br/>
                            <label for="prod_option_name-<?=$i?>">Name</label>
                            <input type="text" id="prod_option_name-<?=$i?>" name="prod_option_name-<?=$i?>" value="" />
                            <br/>
                            <label for="prod_option_value-<?=$i?>">Value</label>
                            <textarea name="prod_option_value-<?=$i?>" id="prod_option_value-<?=$i?>" rows="5" cols="50"></textarea>
                            <br/>
                            <label for="prod_option_value-<?=$i?>">Text</label>
                            <textarea name="prod_option_txt-<?=$i?>" id="prod_option_txt-<?=$i?>" rows="5" cols="50"></textarea>


                            <div class="submit"><input type="submit" name="product_opt_submit" value="Update" /></div>
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
        add_menu_page('Warung Options', 'Warung', 'administrator', __FILE__, array(&$this, 'handle_options'), plugins_url('/images/icon.png', __FILE__));
        // add sub menu
        add_submenu_page(__FILE__, 'Warung General Options', 'General', 'administrator', __FILE__, array(&$this, 'handle_options'));
        add_submenu_page(__FILE__, 'Warung Shipping', 'Shipping', 'administrator', __FILE__.'_shipping', array(&$this, 'handle_shipping'));
        add_submenu_page(__FILE__, 'Warung Product Options', 'Product Options', 'administrator', __FILE__.'_product_option', array(&$this, 'handle_product_opt'));

        // add metabox in edit post page
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
        $product_stock = get_post_meta($post_id, '_warung_product_stock', true);
        $product_show_stock = get_post_meta($post_id, '_warung_product_show_stock', true);

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
        $prod_stock = $_POST['product_stock'];
        $prod_show_stock = $_POST['product_show_stock'];

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
            update_post_meta($post_id, '_warung_product_stock', $prod_stock);
            update_post_meta($post_id, '_warung_product_show_stock', $prod_show_stock);
        }


        // Do something with $mydata
        // probably using add_post_meta(), update_post_meta(), or
        // a custom table (see Further Reading section below)


    }

    function display_product_custom_field() {
        // Use nonce for verification
        global $post;

        
        // get prev meta
        $product = $this->warung_get_product_by_id($post->ID);

        //default values
        if (empty($product['stock'])) {
            $product['stock'] = '';
        }

        // product code
        // price
        // weight
        // option set
        ?>
        <input type="hidden" name="warung_noncename" id="warung_noncename"
               value="<?=wp_create_nonce( plugin_basename(__FILE__) )?>" />
        <style type="text/css">
            .form-field label {font-weight: bold; display: block; padding: 5px 0pt 2px 2px;}
        </style>
        <div class="form-field">
        <label for="product_code"><?=__("Code")?></label>
        <input type="text" name="product_code" value="<?=$product["code"]?>"/><br/>
        <p><?=__("Enter product code")?></p>
        </div>
        <div class="form-field">
        <label for="product_name"><?=__("Name")?></label>
        <input type="text" name="product_name" value="<?=$product["name"]?>"/><br/>
        <p><?=__("Enter product name")?></p>
        </div>
        <div class="form-field">
        <label for="product_price"><?=__("Price")?></label>
        <input type="text" name="product_price" value="<?=$product["price"]?>"/><br/>
        <p><?=__("Enter product price")?></p>
        </div>
        <div class="form-field ">
            <label for="product_weight"><?=__("Weight")?></label>
            <input type="text" name="product_weight" value="<?=$product["weight"]?>"/><br/>
            <p><?=__("Enter the product weight")?></p>
        </div>
        <div class="form-field ">
        <label for="product_stock"><?=__("Product Stock")?></label>
        <input type="text" name="product_stock" value="<?=$product["stock"]?>"/><br/>
        <p><?=__("Enter the product stock or leave blank if stock is unlimited.")?></p>
        </div>
        <div class="form-field ">
        <label for="product_show_stock"><?=__("Show Product Stock?")?></label>
        <input type="checkbox" name="product_show_stock" value="show_stock" <?=!empty($product["show_stock"])?'checked="checked"':''?>/><br/>
        <p><?=__("Whether to show product stock number or not")?></p>
        </div>
        <?
        // get from option
        $prod_options = $this->get_options();
        $prod_options = $prod_options["prod_options"];
        // get from product custom field

        if (is_array($prod_options) && !empty($prod_options)) {
            ?>
        <div class="form-field ">
            <label for="product_options"><?=__("Option Set")?></label>
            <select name="product_options">
                <option value="-- none --">-- none --</option><?
            foreach ($prod_options as $key => $value) {
                if ($product["option_name"] == $value->name) {
                    ?><option value="<?=$value->name?>" selected="selected"><?=$value->name?></option><?
                } else {
                    ?><option value="<?=$value->name?>"><?$value->name?></option><?
                }
            }
            ?></select>
            <p><?=__("Choose option set")?></p>
        </div><?

        }

        ?>
        <h4><?=__("Discount")?></h4>
        <div class="form-field ">
        <label for="product_weight_discount"><?=__("Weight Discount")?></label>
        <input type="text" name="product_weight_discount" value="<?=$product["weight_discount"]?>"/><br/>
        <p><?=__("Enter discounted weight if any ")?></p>
        </div>
        <?


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
                        $obj = $ret[intval($matches[3])];
                        if (empty($obj)) {
                            $newObj=null;
                            $newObj->$matches[1] = $val;
                            $ret[intval($matches[3])] = $newObj;
                        } else {
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
