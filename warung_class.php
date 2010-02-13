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
    var $pluginUrl;
    // name for our options in the DB
    var $db_option = 'Warung_Options';

    function Warung() {
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL+'/'+dirname(plugin_basename(__FILE__)));
        add_shortcode('warung', array(&$this, 'shortCode'));        
    }

    function init() {
        
    }

    function install() {
        // set default options
        $this->get_options();
    }

    // [warung name="Avocado" price="10000|20000" type="120x200|160x200"]
    function shortCode($params, $content=null) {

        extract(shortcode_atts(array(
            'name' => null,
            'price' => null,
            'type' => null
            ), $params));
       
        $ret = '';

        $options = $this->get_options();
        $add2cart = $options['add_to_cart'];

        $vals = $this->formatForPost($name, $price, $type);

        if (count($vals) > 1) {
            // show select
            // - name1|type 1|price1, name1 - type1 - price
            // - name2|type 2|price2, name2 - type2 = price
            // - name3|type 3|price3, name3 - type3 - price
            // +
            // add to cart

            $ret =
                '<div id="wr_product">'.
                    '<form action="#" method="POST">'.
                        '<select name="product">';
            foreach ($vals as $n => $v) {
                $ret .= '<option value="'.$v.'">'.$n.'</option>';
            }
            $ret .= '</select>'.
                        '<input type="submit" value="'.$add2cart.'"/>'.
                    '</form>'.
                '</div>';
        } else {
            // just show name - price + add to cart
            $ret =
                '<div id="w_product">'.
                    '<form action="#" method="POST">'.
                        '<input type="hidden" name="product" value="'.$vals[key($vals)].'"/>'.
                        '<input type="submit" value="'.$add2cart.'"/>'.
                    '</form>'.
                '</div>';
        }



        return $ret;
    }

    /**
     *
     * @param <string> $str
     * @return <array> array [ 'name'=> name, 'price'=>price, 'type'=>type, 'quantyty'=>quantity, .., ]
     */
    function formatForSession($str) {
        $ret = array();

        $tmp = explode('|',$str);
        if (count($tmp) == 3) {
            $ret['name'] = $tmp[0];
            $ret['price'] = $tmp[1];
            $ret['type'] = $tmp[2];
            $ret['quantity'] = 0;
        } else if (count($tmp) == 2) {
            $ret['name'] = $tmp[0];
            $ret['price'] = $tmp[1];
            $ret['quantity'] = 0;
        }

        return $ret;
    }

    /**
     * Format given string to arrays of product to be used for form post (add to cart)
     * @param <type> $name
     * @param <type> $price
     * @param <type> $type
     * @return <array> array [ name-type@price => name|price[|type], .., ]
     */
    function formatForPost($name, $price, $type) {
        $ret = array();

        $types = null;
        $prices = null;

        // checking
        if (is_null($name) || is_null($price)) {
            return ret;
        }

        // get type
        if (! is_null($type) ) {
            $types = explode('|', $type);
        }
        // get price
        if (! is_null($price) ) {
            $prices= explode('|', $price);
        }


        if (count($types) > 1) {
            // key(name) => value
            // name - type @ price => name|price|type
            if (count($types) == count($price)) {
                $i=0;
                foreach ($types as $t) {
                    $ret[$name.' - '.$t.'@'.$this->formatCurrency($prices[$i])] = $name.'|'.$prices[$i].'|'.$t;
                    $i++;
                }
            } else {
                $i=0;
                foreach ($types as $t) {
                    $p = $prices[min($i, count($prices)-1)];
                    $ret[$name.' - '.$t.'@'.$this->formatCurrency($p)] = $name.'|'.$p.'|'.$t;
                    $i++;
                }
            }
        } else {
            $ret[$name.'@'.$this->formatCurrency($price)] = $name.'|'.$price;
        }

        return $ret;
    }

    function formatCurrency($price) {

        $options = $this->get_options();
        $currency = $options['currency'];

        return $currency.$price;
    }

    // -------------------------- OPTIONS ------------------------------

    function get_options() {

        // default
        $options = array(
          'currency' => 'Rp. ',
          'add_to_cart' => 'Beli',
          'checkout_page' => 2
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

    function handle_options() {
        $options = $this->get_options();

        if (isset($_POST['submitted'])) {
            //check security
            check_admin_referer('warung-nonce');

            $options = array();
            $options['currency'] = $_POST['currency'];
            $options['add_to_cart'] = $_POST['add_to_cart'];
            $options['checkout_page'] = $_POST['checkout_page'];

            update_option($this->db_option, $options);

            echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
        }

        $currency = $options['currency'];
        $add2cart = $options['add_to_cart'];
        $checkout_page = $options['checkout_page'];

        echo
            '<div class="wrap" style="max-width:950px !important;">
                <h2>Warung</h2>
                <div id="poststuff" style="margin-top:10px;">
                    <div id="mainblock" style="width:710px">
                        <div class="dbx-content">
                            <form action="" method="post">
                            '.wp_nonce_field('warung-nonce').'
                                <label for="currency">Currency</label>
                                <input id="currency" type="text" size="5" name="currency" value="'.$currency.'"/><br/>
                                <label for="add_to_cart">Add to cart text</label>
                                <input id="add_to_cart" type="text" size="10" name="add_to_cart" value="'.$add2cart.'"/><br/>
                                <label for="checkout_page">Checkout Page ID</label>
                                <input id="checkout_page" type="text" size="10" name="checkout_page" value="'.$checkout_page.'"/><br/>
                                <div class="submit"><input type="submit" name="submitted" value="Update" /></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>';
    }

    function admin_menu() {
        add_menu_page('Warung Options', 'Warung', 8, basename(__FILE__), array(&$this, 'handle_options'));
        //add_options_page('Warung Options', 'Warung', 8, basename(__FILE__), array(&$this, 'handle_options'));
    }

}
?>
