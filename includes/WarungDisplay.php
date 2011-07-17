<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungDisplay
 *
 * @author hendra
 */
class WarungDisplay {
    
    public static function shippingSimPage() {
        ob_start();

        $wo = new WarungOptions();
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
                    array_push($resp, '<strong>' . $s_cheap->getName() . ': ' . WarungUtils::formatCurrency(WarungUtils::ceilToHundred($s_price)) . ' (' . WarungUtils::formatCurrency(WarungUtils::ceilToHundred($s_dest->price)) . '/Kg) (paling murah)</strong>');
                }
                $s_serv = $kasir->getShippingServicesByDestination($dest);
                foreach ($s_serv as $sss) {
                    if ($sss != $s_cheap) {
                        $s_price = $sss->getPrice($dest, array(new KeranjangItem(0, 0, '', 0, $s_weight, 1, null, 0)));
                        $s_dest = $sss->getDestination($dest);
                        if ($s_price > 0) {
                            array_push($resp, $sss->getName() . ': ' . WarungUtils::formatCurrency(WarungUtils::ceilToHundred($s_price)) . ' (' . WarungUtils::formatCurrency(WarungUtils::ceilToHundred($s_dest->price)) . '/Kg)');
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
                        <td><?= WarungUtils::htmlSelect('wc_sim_city', 'wc_sim_city', $cities, $city) ?></td>
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

        $ret = ob_get_contents();
        ob_end_clean();

        return $ret;
    }

    public static function productOrderForm() {

        global $post;

        ob_start();

        // check is this post contains product informations

        $product = WarungProduct::getProductById($post->ID);
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
                
                ?>
                    <h3><?=  _e('Pilih Produk')?></h3>
                    <div class="wcart_product_opt">
                <?
                
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
                        ?>
                            <input type="radio" name="product_option" id="a2c-r-<?=$po->id?>" value="<?= $po->id ?>" <?= $checked ?>/>
                            <label for="a2c-r-<?=$po->id?>">
        <?= $po->name . '<span>' . WarungUtils::formatCurrency($po->price) .'</span>' ?>
                        </label><br/><?
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
                        <option value="<?= $po->id ?>" <?= $selected ?>><?= $po->name . '<span>' . WarungUtils::formatCurrency($po->price) .'</span>' ?></option>
        <?
                    }
        ?>
                </select>
        <?
                }
                
                ?></div><?
            } else {
                if (isset($disc_price) && !empty($disc_price)) {
        ?>
                    <span><s><?= WarungUtils::formatCurrency($disc_price) ?></s></span>
                    <span><?= WarungUtils::formatCurrency($product["price"]) ?></span>
<?
                } else {
?>
                    <span class="ws_price"><?= WarungUtils::formatCurrency($product["price"]) ?></span>
    <?
                }
            }
            $wo = new WarungOptions();
            $options = $wo->getOptions();
    ?>
            <input type="submit" name="wcart_ordernow" value="<?= $options["add_to_cart"] ?>"/>
        </form>
    </div>

<?
           
        }



        $ret = ob_get_contents();

        ob_clean();

        return $ret;
    }
}
?>
