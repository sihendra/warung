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

    function install() {

    }
    
    function shortCode() {
        return "add-to-cart";
    }
}
?>
