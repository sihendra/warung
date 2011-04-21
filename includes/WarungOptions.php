<?php

class WarungOptions {

    public static $OPT_NAME = 'Warung_Options';
    public static $CART_SESS_NAME = 'wCart';
    public static $SHIPPING_SESS_NAME = 'wShipping';


    private $options;
    private $shippingServices;
    private $cartService;
    private $kasirService;
    private $checkoutWizard;

    public function __construct() {
        $this->options = get_option(WarungOptions::$OPT_NAME);
    }

    // general options

    public function getOptions() {
        return $this->options;
    }

    public function getCurrency() {
        if (!empty($this->options)) {
            return $this->options['currency'];
        }
    }

    public function getWeightSign() {
        if (!empty($this->options)) {
            return $this->options['weight_sign'];
        }
    }

    public function getAddToCartText() {
        if (!empty($this->options)) {
            return $this->options['add_to_cart'];
        }
    }

    public function getCheckoutPageId() {
        if (!empty($this->options)) {
            return $this->options['checkout_page'];
        }
    }

    public function getCheckoutURL() {
        if (!empty($this->options)) {
            return get_permalink($this->getCheckoutPageId());
        }
    }

    public function getShippingSimPageId() {
        if (!empty($this->options)) {
            return $this->options['shipping_sim_page'];
        }
    }

    public function getShippingSimURL() {
        if (!empty($this->options)) {
            return get_permalink($this->getShippingSimPageId());
        }
    }

    public function getHomeURL() {
        return get_option("home");
    }

    /**
     * Get predefined global options set from admin page
     * @return Array of product options. format array { "name" => object{name, value, txt} }
     */
    public function getGlobalProductOptions() {
        if (!empty($this->options)) {
            return $this->options['prod_options'];
        }
    }

    /**
     * Get configured keranjang service
     * @return IKeranjangService
     */
    public function getCartService() {
        if (isset($this->cartService)) {
            return $this->cartService;
        } else {
            return $this->cartService = new KeranjangService();
        }
    }

    /**
     * Get configured kasir service
     * @return IKasirService
     */
    public function getKasirService() {
        if (isset($this->kasirService)) {
            return $this->kasirService;
        } else {
            return $this->kasirService = new WarungKasir($this->getShippingServices(), $this->getShippingProductMap());
        }
    }

    /**
     * Get configured CheckoutWizard
     * @return ICheckoutWizard
     */
    public function getCheckoutWizard() {
        if (isset($this->checkoutWizard)) {
            return $this->checkoutWizard;
        } else {
            return $this->checkoutWizard = new GeneralCheckoutWizard();
        }
    }

    /**
     * Get configured shipping services
     * @return Array of IShippingService
     */
    public function getShippingServices() {
        if (empty($this->options)) {
            return array();
        }

        if (isset($this->shippingServices)) {
            return $this->shippingServices;
        } else {
            $ret = array();
            $services = $this->options['shipping_byweight'];
            if (isset($services)) {
                foreach ($services as $k => $v) {
                    $tdest = $v->value; // convert to destination first
                    $tdest = explode("\n", $v->value);
                    $dest = array();
                    foreach ($tdest as $r) {
                        // skip empty line
                        if (!empty($r)) {
                            $r = '{' . stripslashes($r) . '}';
                            // to lowercase all
                            $r = json_decode(strtolower($r));
                            $freeWeight = 0;
                            if (isset($r->free_weight)) {
                                $freeWeight = $r->free_weight;
                            }
                            $minWeight = 0;
                            if (isset($r->min_weight)) {
                                $minWeight = $r->min_weight;
                            }

                            // Upper First Character
                            if (isset ($r->country)) {
                                $r->country = ucwords($r->country);
                            }
                            if (isset ($r->state)) {
                                $r->state = ucwords($r->state);
                            }
                            if (isset ($r->city)) {
                                $r->city = ucwords($r->city);
                            }


                            $t = new ShippingDestinationByWeight($r->country, $r->state, $r->city, $r->price, $minWeight, $freeWeight);
                            array_push($dest, $t);
                        }
                    }
                    $name = $v->name;
                    $priority = $v->priority;
                    $s = new ShippingServiceByWeightWithDiscount($dest, $name, $v->total_weight_rounding, $perItemWeightRoundingPolicy = 0, $priority);
                    array_push($ret, $s);
                }
            }



            $this->shippingServices = $ret;
        }

        return $this->shippingServices;
    }

    /**
     * Get configured shipping to product mapping
     * @return Associative array of product map
     */
    public function getShippingProductMap() {
        return array("belladona" => "jne", "mylove" => "jne");
    }

}

?>
