<?php

class GeneralCheckoutWizard implements ICheckoutWizard {

    private $parameters;
    private $prevContent;
    private $actionURL;
    private $warungOption;

    public function __construct() {
        $this->warungOption = new WarungOptions();
        $this->actionURL = $this->warungOption->getCheckoutURL();
        $this->setParameters($_REQUEST);
    }

    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    public function setPrevContent($prevContent) {
        $this->prevContent = $prevContent;
    }

    public function showPage() {
        $p = $this->parameters;

        if (isset($p['action'])) {
            $a = $p['action'];

            $cart = $this->warungOption->getCartService();
            if ($cart->getTotalItems() > 0) {
                if ($a == 'confirm') {
                    return $this->showConfirmation();
                } else if ($a == 'pay') {

                    $okMsg = $this->showPayOk();
                    $errMsg = $this->showPayError();

                    $warungOpt = new WarungOptions();
                    $kasir = $warungOpt->getKasirService();
                    $userInfo = $kasir->getSavedUserInfo();

                    $email_pemesan = $userInfo->email;

                    $admin_email = get_option("admin_email");
                    $order_id = date('ymdH') . mt_rand(10, 9999);
                    $subject = "[Warungsprei.com] Pemesanan #" . $order_id;
                    $admin_message = $this->showPayOk($emailView = true, array("order_id" => $order_id));
                    $customer_message = $this->showPayOk($emailView = true, array("order_id" => $order_id));

                    $headers = "Content-type: text/html;\r\n";
                    $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";

                    // send email to admin
                    $ret = mail($admin_email, "[Admin] " . $subject, $admin_message, $headers);

                    // send to pemesan bcc admin
                    $headers .= "Bcc: " . $admin_email . "\r\n";
                    mail($email_pemesan, $subject, $customer_message, $headers);

                    $ret = true;

                    if ($ret) {
                        $home_url = get_option('home');
                        ob_start();
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
                        $cart->emptyCart();
                        $ret = ob_get_contents();
                        ob_get_clean();
                        return $ret;
                    } else {
                        return $errMsg;
                    }
                }
            }
        }

        return $this->showDefault();
    }

    public function showDefault() {
        $ret = $this->showDetailedCart();

        // show only when cart not empty
        $cart = $this->warungOption->getCartService();
        if ($cart->getTotalItems() > 0) {
            $ret .= $this->showShippingForm();
        }

        return $ret;
    }

    public function showDetailedCart($showUpdateForm=true) {
        ob_start();

        // show cart
        $homePageURL = get_option("home");
        $warungOpt = new WarungOptions();
        $cart = $warungOpt->getCartService();
        $kasir = $warungOpt->getKasirService();

        $cartEntry;
        $cartSum;
        if (!empty($cart)) {
            $cartEntry = $cart->getItems();
            $cartSum = $cart->getSummary();
        }

        $userInfo = $kasir->getSavedUserInfo();
        $destination;
        // only allow new version, old version city is stored as array
        if (isset($userInfo->city)) {
            $destination = new ShippingDestination($userInfo->country, $userInfo->state, $userInfo->city, 0);
        }

        // jika sudah ada destination hitung plus ongkir
        if (isset($destination)) {
            $cartSum = $kasir->getSummaryWithShippping($destination, $cart, null);
        }
?>
        <div class="wcart_detailed_cart_container">
<?
        if (!empty($cartEntry)) {

            $clearPage = $this->getActionURL("clearCart");
?>
            <div><a name="w_cart"/><h2><? _e('Keranjang Belanja') ?></h2></div>
            <div id="wcart-detailed-div">
    <?
            if ($showUpdateForm) {
    ?>
                    <form method="POST" action="<?= $this->getActionURL("updateCart") ?>">
        <?
            }
        ?>
                <table id="wcart-detailed">
                    <tr><th><? _e('Item') ?></th><th><? _e('Berat') ?></th><th><? _e('Harga') ?></th><th><? _e('Jumlah') ?></th><th><? _e('Total') ?></th><th>-</th></tr>
            <?
            foreach ($cartEntry as $i) {
                //name|price[|type]
                $removePage = $this->getActionURL("removeCartItem", array("ci" => $i->cartId));
                $productInfo = $i->attachment["product"];
                $productURL = get_permalink($i->productId);
            ?>
                <tr>
                    <td>
                        <div>
                            <div id="wcart_item_thumbnail"><a href="<?= $productURL ?>"><img src="<?= $productInfo["thumbnail"] ?>" alt="<?= $i->name ?>"/></a></div>
                            <div id="wcart_pinfo"><?= $i->name ?></div>
                        </div>
                    </td>
                    <td><?= $this->formatWeight($i->weight) ?></td>
                    <td><?= $this->formatCurrency($i->price) ?></td>
                    <td><? if ($showUpdateForm) { ?>
                            <input type="text" name="qty_<?= $i->cartId ?>" value="<?= $i->quantity ?>" size="1"/>
<?
                } else {
                    echo $i->quantity;
                } ?>
                    </td>
                    <td><?= $this->formatCurrency($i->price * $i->quantity) ?> </td>
                        <? if ($showUpdateForm) { ?>
                        <td><a class="wcart_remove_item" href="<?= $removePage ?>"><div><span>(X)</span></div></a></td>
<? } ?>
                </tr>

<?
                    }

                    if ($showUpdateForm) {
?>
                        <tr><td colspan="3" class="wcart-td-footer">&nbsp</td><td class="wcart-td-footer"><input type="submit" name="wc_update" value="Update" title="Klik tombol ini jika ingin mengupdate jumlah barang"/></td><td class="wcart-td-footer">&nbsp;</td></tr>
                <? } ?>
                    <tr><td colspan="4" class="wcart-td-footer">Total Sebelum Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= $this->formatCurrency($cartSum->totalPrice) ?></span></td></tr>
                <?
                    if (isset($cartSum->totalShippingPrice)) {
                ?>
                        <tr><td colspan="4" class="wcart-td-footer">Ongkos Kirim (<?= $this->formatWeight($cartSum->totalWeight) ?>) - <?= $cartSum->shippingName ?></td><td class="wcart-td-footer"><span class="wcart_total"><?= $this->formatCurrency($cartSum->totalShippingPrice) ?></span></td></tr>
                        <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= $this->formatCurrency($cartSum->totalPrice + $cartSum->totalShippingPrice) ?></span></td></tr>
                <? } ?>
                </table>
<? if ($showUpdateForm) { ?>
                    <div id="wcart_detailed_nav">
                        <a href="<?= $homePageURL ?>" class="wcart_button_url">Kembali Berbelanja</a> atau isi form di bawah ini jika ingin lanjut ke pemesanan.
                    </div>

                </form>
<?
                    }
?>
            </div>
<?
                } else {
?>
                <p id="status"><?php _e('Keranjang belanja kosong') ?><a href="<?= $homePageURL ?>" class="wcart_button_url"> <?php _e('Lihat Produk') ?></a></p><?php
                }
?>
            </div>
    <?
                $ret = ob_get_contents();
                ob_end_clean();


                return $ret;
            }

            public function showShippingForm($showUpdateForm=true) {
                ob_start();

                $warungOpt = new WarungOptions();
                $kasir = $warungOpt->getKasirService();

                $userInfo = $kasir->getSavedUserInfo();

                $countries = $kasir->getCountries();
                $country = array_pop($countries);
                $cities = $kasir->getCitiesByCountry($country);

                foreach ($cities as $city) {
                    break;
                }

                if (isset($userInfo->city)) {
                    $city = $userInfo->city;
                }

                $co_page = $warungOpt->getCheckoutURL();
    ?>
                <div class="wcart_shipping_container">
                    <div><a name="w_shipping"/><h2>Informasi Pengiriman</h2></div>
                    <div id="wCart_shipping_form">
<? if ($showUpdateForm) : ?>
                            <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?= $this->getActionURL('confirm') ?>">
<? endif; ?>
                                <div class="wCart_form_row">
                                    <label for="semail">Email *</label>
        <? if ($showUpdateForm) : ?>
                                <input type="text" name="semail" value="<?= $userInfo->email ?>"/>
            <? else: ?>
                                <span><?= $userInfo->email ?></span>
<? endif; ?>
                            </div>

                            <div class="wCart_form_row">
                                <label for="sphone">HP (handphone) *</label>
                <? if ($showUpdateForm) : ?>
                                <input type="text" name="sphone" value="<?= $userInfo->phone ?>"/>
<? else: ?>
                                    <span><?= $userInfo->phone ?></span>
<? endif; ?>
                                </div>
                                <div class="wCart_form_row">
                                    <label for="sname">Nama Penerima *</label>
<? if ($showUpdateForm) : ?>
                                        <input type="text" name="sname" value="<?= $userInfo->name ?>"/></div>
<? else: ?>
                                        <span><?= $userInfo->name ?></span>
<? endif; ?>
                                        <div class="wCart_form_row">
                                            <label for="saddress">Alamat *</label>
            <? if ($showUpdateForm) : ?>
                                                    <textarea name="saddress"><?= $userInfo->address ?></textarea>
            <? else: ?>
                                                        <span><?= $userInfo->address ?></span>
<? endif; ?>
                                                    </div>
                                                    <div class="wCart_form_row">
                                                        <label for="scity">Kota</label>
<? if ($showUpdateForm) : ?>
                <?= $this->form_select('scity', $cities, $city, array(&$this, 'city_callback'), false) ?>
<? else: ?>
                                                            <span><?= $userInfo->city ?></span>
<? endif; ?>
                                                        </div>
                                                        <input type="hidden" name="scountry" value="<?= $userInfo->country ?>"/>
                                                        <div class="wCart_form_row">
                                                            <label for="sadditional_info">Info Tambahan</label>
                <? if ($showUpdateForm) : ?>
                                                                <textarea name="sadditional_info"><?= $userInfo->additionalInfo ?></textarea>
<? else: ?>
                                                                    <span><?= $userInfo->additionalInfo ?></span>
<? endif; ?>
                                                                </div>


<? if ($showUpdateForm) : ?>

                                                                    <div class="wCart_form_row">
                                                                        <input type="hidden" name="step" value="2"/>
                                                                        <input type="submit" name="scheckout" class="submit" value="Lanjut"/>
                                                                    </div>

                                                                </form>
<? endif; ?>
                                                                </div>
                                                            </div>
<?
                                                                        $ret = ob_get_contents();
                                                                        ob_end_clean();

                                                                        return $ret;
                                                                    }

                                                                    public function showConfirmation() {
                                                                        ob_start();

                                                                        $ret = "Jika data sudah benar, klik tombol 'Pesan' di bawah, atau jika masih ada yang salah klik tombol 'Edit' untuk membenarkan";

                                                                        // get edit url
                                                                        $wo = new WarungOptions();
                                                                        $editURL = $wo->getCheckoutURL();

                                                                        echo $this->showDetailedCart(false);
                                                                        // show edit url
?><p><a class="wcart_button_url" href="<?= $editURL . "#w_cart" ?>">Edit</a></p><?
                                                                        echo $this->showShippingForm(false);
                                                                        // show edit url
?><p><a class="wcart_button_url" href="<?= $editURL . "#w_shipping" ?>">Edit</a></p><?
?>
                                                                        <div style="padding: 10px;">
                                                                            <form method="POST" id="wCart_confirmation" action="<?= $this->getActionURL("pay") ?>">
                                                                                <input type="submit" name="send_order" value="Pesan"/>
                                                                            </form>
                                                                        </div>
<?
                                                                        $ret .= ob_get_contents();
                                                                        ob_end_clean();

                                                                        return $ret;
                                                                    }

                                                                    public function showPayOk($isEmailView = false, $params=null) {
                                                                        ob_start();

                                                                        $warungOpt = new WarungOptions();
                                                                        $cart = $warungOpt->getCartService();
                                                                        $kasir = $warungOpt->getKasirService();

                                                                        $cartEntry;
                                                                        $cartSum;
                                                                        if (!empty($cart)) {
                                                                            $cartEntry = $cart->getItems();
                                                                            $cartSum = $cart->getSummary();
                                                                        }

                                                                        $userInfo = $kasir->getSavedUserInfo();
                                                                        $destination;
                                                                        // only allow new version, old version city is stored as array
                                                                        if (isset($userInfo->city)) {
                                                                            $destination = new ShippingDestination($userInfo->country, '', $userInfo->city, 0);
                                                                        }

                                                                        // jika sudah ada destination hitung plus ongkir
                                                                        if (isset($destination)) {
                                                                            $cartSum = $kasir->getSummaryWithShippping($destination, $cart, null);
                                                                        }

                                                                        if (!empty($cartItems)) {
                                                                            ob_end_clean();
                                                                            return __('Keranjang belanja kosong');
                                                                        }

                                                                        if (is_array($params)) {
                                                                            extract($params);
                                                                        }
?>
                                                                        <div>
                                                                            <p><?= $userInfo->name ?>, kami sudah menerima pesanan anda. Untuk pembayaran silahkan transfer ke salah satu nomor rekening berikut sebesar <b><?= $this->formatCurrency($cartSum->totalPrice + $cartSum->totalShippingPrice) ?></b>:
                                                                            <ul>
                                                                                <li>BCA: 5800106950 a.n. Hendra Setiawan</li>
                                                                                <li>Mandiri: 1270005578586 a.n. Hendra Setiawan</li>
                                                                            </ul>
                                                                            <br/>
                                                                        	            Setelah pembayaran dilakukan harap lakukan konfirmasi pembayaran agar pesanan dapat segera kami proses.
                                                                        	            Konfirmasi dapat dilakukan dengan cara me-reply email pemesanan ini atau menghubungi kami di:
                                                                            <ul>
                                                                                <li>HP: 08889693342, 081808815325 </li>
                                                                                <li>Email: info@warungsprei.com</li>
                                                                                <li>YM: reni_susanto, warungsprei_hendra</li>
                                                                            </ul>
                                                                            <br/>
                                                                            <br/>
                                                                        	            Terima Kasih,<br/>
                                                                        	            Warungsprei.com<br/>
                                                                        	            -----------------------------------
                                                                            <br/>
<?
                                                                        // ####### show detailed cart
                                                                        echo $this->showDetailedCart(false);

                                                                        // ####### show shipping info
?>
                                                                        <br/>
                                                                        <br/>
                                                                        <!--shipping info-->
                                                                        <div><h2>Informasi Pengiriman</h2></div>
                                                                        <table>
    <? ?>
                                                                            <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->email ?></td></tr>
                                                                            <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->phone ?></td></tr>
                                                                            <tr><td>Jasa Pengiriman</td><td>&nbsp;:&nbsp;</td><td><?= $cartSum->shippingName ?></td></tr>
                                                                            <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->name ?></td></tr>
                                                                            <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->address ?></td></tr>
                                                                            <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->city ?></td></tr>
                                                                        <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td><?= $userInfo->additionalInfo ?></td></tr>

                                                                    </table>
                                                                </div>
<?
                                                                        $ret = ob_get_contents();
                                                                        ob_end_clean();

                                                                        return $ret;
                                                                    }

                                                                    public function showPayError($isEmailView = false, $params=null) {
                                                                        return '<p>' . __('Maaf kami blm dapat memproses pesanan anda silahkan coba beberapa saat lagi') . '</p>';
                                                                    }

                                                                    function getActionURL($action, $params=null) {
                                                                        $u = Utils::addParameter($this->actionURL, array("action" => $action));
                                                                        if (isset($params)) {
                                                                            $u = Utils::addParameter($u, $params);
                                                                        }
                                                                        return $u;
                                                                    }

                                                                    /**
                                                                     * Add currency sign and add period every thousand
                                                                     * @param number $price
                                                                     * @return string
                                                                     */
                                                                    protected function formatCurrency($price) {
                                                                        $currency = $this->warungOption->getCurrency();
                                                                        return trim($currency) . number_format($price, 0, ',', '.');
                                                                    }

                                                                    /**
                                                                     * Add weight sign and add period on thousand
                                                                     * @param number $weight
                                                                     * @return string
                                                                     */
                                                                    protected function formatWeight($weight) {
                                                                        $weight_sign = $this->warungOption->getWeightSign();
                                                                        return number_format($weight, 1, ',', '.') . ' ' . trim($weight_sign);
                                                                    }

                                                                    function form_selected($selname, $value) {
                                                                        if ($selname == $value) {
                                                                            return 'selected="selected"';
                                                                        }
                                                                        return '';
                                                                    }

                                                                    function form_select($name, $arr, $selected, $callback='', $isArrayOfObject=false, $style='') {
                                                                        $ret = '<select id="' . $name . '" name="' . $name . '" ' . $style . '><option value="--- Please Select ---">--- Please Select ---</option>';
                                                                        if (empty($callback)) {
                                                                            foreach ($arr as $k => $v) {
                                                                                $ret .= '<option value="' . $k . '" ' . $this->form_selected($selected, $k) . '>' . $v . '</option>';
                                                                            }
                                                                        } else {
                                                                            if ($isArrayOfObject) {
                                                                                foreach ($arr as $v) {
                                                                                    $r = call_user_func($callback, $v);
                                                                                    if (empty($selected) && isset($r['default'])) {
                                                                                        $selected = $r['value'];
                                                                                    }
                                                                                    $ret .= '<option value="' . $r['value'] . '" ' . $this->form_selected($selected, $r['value']) . '>' . $r['name'] . '</option>';
                                                                                }
                                                                            } else {
                                                                                foreach ($arr as $k => $v) {
                                                                                    $r = call_user_func($callback, $k, $v);
                                                                                    if (empty($selected) && isset($r['default'])) {
                                                                                        $selected = $r['value'];
                                                                                    }
                                                                                    $ret .= '<option value="' . $r['value'] . '" ' . $this->form_selected($selected, $r['value']) . '>' . $r['name'] . '</option>';
                                                                                }
                                                                            }
                                                                        }
                                                                        $ret .= '</select>';
                                                                        return $ret;
                                                                    }

                                                                    function kv_callback($k, $v) {
                                                                        return array('value' => $v, 'name' => $v);
                                                                    }

                                                                    function city_callback($c) {
                                                                        $arr = array('value' => $c, 'name' => $c);

                                                                        return $arr;
                                                                    }

                                                                }
?>
