<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungCartWidget
 *
 * @author hendra
 */
class WarungCartWidget extends WP_Widget {

    private $warung;

    function __construct() {
        $this->warung = new Warung();
        $widget_ops = array('classname' => 'wcart_widget', 'description' => 'Warung Cart Shopping Cart');
        parent::__construct(false, $name = 'Warung Cart', $widget_ops);
    }

    public function widget($args, $instance) {

        $wo = new WarungOptions();

        $warung = $this->warung;
        $cartImage = $warung->pluginUrl . "images/cart.png";
        $co_page = $wo->getCheckoutURL();
        $clear_page = WarungUtils::addParameter(get_option("home"), array("action" => "clearCart"));

        $cart = KeranjangService::getInstance();
        $cart_sumary = $cart->getSummary();

        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
?>
<?php echo $before_widget; ?>
<?php if ($title)
            echo $before_title . '<a href="' . $co_page . '"><img src="' . $cartImage . '" alt="shopping cart"/> Keranjang Belanja</a>' . $after_title; ?>

<? if (!empty($cart_sumary->totalItems)) : ?>
            <div><a href="<?= $co_page ?>">Ada <?= $cart_sumary->totalItems ?> Item (<?= WarungUtils::formatCurrency($cart_sumary->totalPrice) ?>)</a></div>
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
?>
