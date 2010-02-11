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

    function Warung() {
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL+'/'+dirname(plugin_basename(__FILE__)));
        add_shortcode('warung', array(&$this, 'shortCode'));        
    }

    function init() {
        
    }

    function install() {
        
    }

    // [warung name="Avocado" price="10000|20000" type="120x200|160x200"]
    function shortCode($params, $content=null) {

        extract(shortcode_atts(array(
            'name' => null,
            'price' => null,
            'type' => null
            ), $params));
       
        $ret = '';

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
                        '<input type="submit" value="Add to cart"/>'.
                    '</form>'.
                '</div>';
        } else {
            // just show name - price + add to cart
            $ret =
                '<div id="w_product">'.
                    '<form action="#" method="POST">'.
                        '<input type="hidden" name="product" value="'.$vals[key($vals)].'"/>'.
                        '<input type="submit" value="Add to cart"/>'.
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
                    $ret[$name.'-'.$t.'@'.$prices[$i]] = $name.'|'.$prices[$i].'|'.$t;
                    $i++;
                }
            } else {
                $i=0;
                foreach ($types as $t) {
                    $p = $prices[min($i, count($prices)-1)];
                    $ret[$name.'-'.$t.'@'.$p] = $name.'|'.$p.'|'.$t;
                    $i++;
                }
            }
        } else {
            $ret[$name.'@'.$price] = $name.'|'.$price;
        }

        return $ret;
    }

    

}
?>