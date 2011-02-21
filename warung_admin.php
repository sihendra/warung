<?php

    class WarungAdmin {

        protected $warung;

        function __construct($warung) {
            $this->warung = $warung;
        }

        function admin_menu() {
            add_menu_page('Warung Options', 'Warung', 'administrator', __FILE__, array(&$this,'handle_options'), plugins_url('/images/icon.png', __FILE__));
            // add sub menu
            add_submenu_page(__FILE__, 'Warung General Options', 'General', 'administrator', __FILE__, array(&$this,'handle_options'));
            add_submenu_page(__FILE__, 'Warung Shipping', 'Shipping', 'administrator', __FILE__.'_shipping', array(&$this,'handle_shipping'));
            add_submenu_page(__FILE__, 'Warung Product Options', 'Product Options', 'administrator', __FILE__.'_product_option', array(&$this,'handle_product_opt'));

            // add metabox in edit post page
            add_meta_box('warung-product-id','Product Information', array(&$this,'display_product_options'),'post','normal','high');

            // save hook
            add_action('save_post', array(&$this,'save_product_details'));
            
            // add default action
        	add_action('warung_handle_shipping', array($this, 'handle_byweight_shipping'));

        }


        function handle_options() {

            ob_start();

            $options = $this->warung->get_options();

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

                    update_option(Warung::$db_option, $options);

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

        function handle_product_opt() {
            ob_start();

            $options = $this->warung->get_options();

            if (isset($_POST['product_opt_submit'])) {
                //check security
                if (check_admin_referer('warung-nonce')) {

                    if (empty($options) || !is_array($options)) {
                        $options = array();
                    }
                    $options['prod_options']=Utils::parseParametersToObject($_POST,'prod_option');

                    update_option(Warung::$db_option, $options);

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

        function handle_byweight_shipping() {
        	ob_start();

            $options = $this->warung->get_options();

            if (isset($_POST['shipping_byweight_submit'])) {
                //check security
                if (check_admin_referer('warung-nonce')) {

                    if (empty($options) || !is_array($options)) {
                        $options = array();
                    }
                    $options['shipping_byweight']=Utils::parseParametersToObject($_POST,'shipping');

                    update_option(Warung::$db_option, $options);

                    echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
                } else {
                    echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
                }
            }

            $shipping_options = $options['shipping_byweight'];

            ?>
            <div class="wrap" style="max-width:950px !important;">
                <h2>Shipping Services By Weight</h2>
                <div id="poststuff" style="margin-top:10px;">
                    <div id="mainblock" style="width:810px">
                        <div class="dbx-content">
                            <form action="" method="post">
                                <?=wp_nonce_field('warung-nonce')?>
                                <?
                                $i = 0;
                                if (is_array($shipping_options)) {
                                    foreach ($shipping_options as $key=>$val) {
                                        $name = '';
                                        $value = '';
                                        if (is_object($val)) {
                                            $name = $val->name;
                                            $value = $val->value;
                                        }
                                        ?>
                                <br/>
                                <label for="shipping_name-<?=$i?>">Name</label>
                                <input type="text" id="shipping_name-<?=$i?>" name="shipping_name-<?=$i?>" value="<?=stripslashes($name)?>" />
                                <br/>
                                <label for="shipping_value-<?=$i?>">Value</label>
                                <textarea id="shipping_value-<?=$i?>" name="shipping_value-<?=$i?>" rows="5" cols="50"><?=stripslashes($value)?></textarea>
                                <br/>
                                        <?
                                        $i++;
                                    }
                                }
                                ?>
                                <br/>
                                <label for="shipping_name-<?=$i?>">Name</label>
                                <input type="text" id="shipping_name-<?=$i?>" name="shipping_name-<?=$i?>" value="" />
                                <br/>
                                <label for="shipping_value-<?=$i?>">Value</label>
                                <textarea name="shipping_value-<?=$i?>" id="shipping_value-<?=$i?>" rows="5" cols="50"></textarea>
                                <br/>

                                <div class="submit"><input type="submit" name="shipping_byweight_submit" value="Update" /></div>
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
            do_action('warung_handle_shipping');
        }

        function display_product_options() {
            // Use nonce for verification
            global $post;


            // get prev meta
            $product = $this->warung->getProductById($post->ID, false /* dont calculate discount */);

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
            <label for="product_show_stock"><?=__("Show Available Product Stock?")?></label>
            <input type="checkbox" name="product_show_stock" value="show_stock" <?=!empty($product["show_stock"])?'checked="checked"':''?>/><br/>
            <p><?=__("Whether to show product stock number or not")?></p>
            </div>
            <?
            // get from option
            $prod_options = $this->warung->get_options();
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
                        ?><option value="<?=$value->name?>"><?=$value->name?></option><?
                    }
                }
                ?></select>
                <p><?=__("Choose option set")?></p>
            </div><?

            }

            ?>
            <h4><?=__("Discount")?></h4>
            <div class="form-field ">
            <label for="product_price_discount"><?=__("Discounted Price")?></label>
            <input type="text" name="product_price_discount" value="<?=isset($product["price_discount"])?$product["price_discount"]:'';?>"/><br/>
            <p><?=__("Enter discounted price if any. Example 10000")?></p>
            </div>
            
            <div class="form-field ">
            <label for="product_weight_discount"><?=__("Discounted Weight")?></label>
            <input type="text" name="product_weight_discount" value="<?=isset($product["weight_discount"])?$product["weight_discount"]:'';?>"/><br/>
            <p><?=__("Enter discounted weight if any. Example 1")?></p>
            </div>
            <?


        }

        function save_product_details($post_id) {
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
            $prod_price_discount = $_POST['product_price_discount'];

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
                update_post_meta($post_id, '_warung_product_price_discount', $prod_price_discount);
            }

        }

    }

?>
