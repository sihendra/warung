<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungController
 *
 * @author hendra
 */
class WarungController {
    public static function process() {
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
            $added_product = WarungProduct::getProductById($_POST['product_id']);
            $item = WarungUtils::formatToKeranjangItem($added_product, $_POST["product_option"]);

            $keranjang->addItem($item, 1);
        } if (isset($_POST['wcart_ordernow'])) {
            $added_product = WarungProduct::getProductById($_POST['product_id']);
            $item = WarungUtils::formatToKeranjangItem($added_product, $_POST["product_option"]);

            $keranjang->addItem($item, 1);

            // redirect to checkoutpage
            header("Location: " . $warungOpt->getCheckoutURL());
            exit;
        }
    }
}
?>
