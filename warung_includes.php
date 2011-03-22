<?php

// ===================
//  G E N E R A L 
// ===================
interface IComparable {
    function equals($obj);
}

// ===================
//  S H I P P I N G 
// ===================
class ShippingDestination implements IComparable {

    public $country;
    public $state;
    public $city;
    public $price;

    public function __construct($country, $state, $city, $price) {
        $this->country = $country;
        $this->state = $state;
        $this->city = $city;
        $this->price = $price;
    }

    public function equals($obj) {
        return $this->equalsCountry($obj) && $this->equalsState($obj) && $this->equalsCity($obj);
    }

    public function equalsCountry($obj) {
        if (is_object($obj)) {
            return strtolower($this->country) == strtolower($obj->country);
        } else {
            return strtolower($obj) == strtolower($this->country);
        }
    }

    public function equalsState($obj) {
        if (is_object($obj)) {
            return strtolower($this->state) == strtolower($obj->state);
        } else {
            return strtolower($obj) == strtolower($this->state);
        }
    }

    public function equalsCity($obj) {
        if (is_object($obj)) {
            return strtolower($this->city) == strtolower($obj->city);
        } else {
            return strtolower($obj) == strtolower($this->city);
        }
    }

    public function key() {
        return strtolower($this->country . '-' . $this->state . '-' . $this->city);
    }

}

// shipping service
interface IShippingService {

    /**
     * Get the price/destination info from given destination and items
     * @param IShippingDestination $destination
     * @param Array of KeranjangItem $items
     * @return the price or -1 if destination was not found
     */
    function getPrice($destination, $items);

    /**
     * Get all destinations available in this shipping service
     */
    function getDestinations();

    /**
     * Get destination of given param
     * @param ShippingDestination
     * @return the ShippingDestination object or null if destination was not found
     */
    function getDestination($destination);

    /**
     * Get all countries available in this shipping service
     */
    function getCountries();

    /**
     * Get all states available in this shipping services
     */
    function getStates($country);

    /**
     * Get all cities available in this shipping services;
     */
    function getCities($country);

    /**
     * Get all cities in given country and state
     * @param unknown_type $country
     * @param unknown_type $states
     */
    function getCitiesByState($country, $state);
}

interface IShippingServiceByWeight extends IShippingService {

    function getPriceByWeight($destination, $weight);

    /**
     * Round per item weight based on $perItemWeightRoundingPolicy
     *
     * @param numeric $weight
     */
    function roundWeight($weight);

    /**
     * Round total weight based on $totalWeightRoundingPolicy
     * 
     * @param numeric $weight
     */
    function roundTotalWeight($weight);
}

class ShippingDestinationByWeight extends ShippingDestination {

    public $minWeight;
    public $discountedWeight;

    function __construct($country, $state, $city, $price, $minWeight, $discountedWeight=0) {
        parent::__construct($country, $state, $city, $price);
        $this->minWeight = $minWeight;
        $this->discountedWeight = $discountedWeight;
    }

}

abstract class ShippingService implements IShippingService {

    /**
     * Shipping Service Name
     * Enter description here ...
     * @var string
     */
    protected $name;
    /**
     * Destinations reachable by this shipping service
     * Enter description here ...
     * @var array of IShippingDestination
     */
    protected $destinations;
    protected $priority = 10;
    protected $countries = array();
    protected $statesByCountry = array();
    protected $citiesByCountry = array();
    protected $citiesByState = array();

    function __construct($destinations, $name, $priority=10) {
        $this->destinations = $destinations;
        $this->name = $name;
        $this->priority = $priority;
    }

    function getName() {
        return $this->name;
    }

    function getPriority() {
        return $this->priority;
    }

    function getDestinations() {
        return $this->destinations;
    }

    function getDestination($destination) {
        if (isset($this->destinations[$destination->key()])) {
            return $this->destinations[$destination->key()];
        } else {
            // loop through
            foreach ($this->destinations as $dest) {
                if ($dest->equals($destination)) {
                    return $dest;
                }
            }
        }

        return null;
    }

    function getCountries() {
        $ret = array();

        if (empty($this->countries)) {
            foreach ($this->destinations as $dest) {
                $this->countries[(string) $dest->country] = $dest->country;
            }
            $ret = $this->countries;
        } else {
            $ret = $this->countries;
        }

        return $ret;
    }

    function getStates($country) {
        $ret = array();

        if (empty($this->statesByCountry) || empty($this->statesByCountry[$country])) {
            $this->statesByCountry[$country] = array();
            foreach ($this->destinations as $dest) {
                if ($dest->country == $country) {
                    $this->statesByCountry[$country][$dest->state] = $dest->state;
                }
            }
            $ret = $this->statesByCountry[$country];
        } else {
            $ret = $this->statesByCountry[$country];
        }

        return $ret;
    }

    function getCities($country) {
        $ret = array();

        if (empty($this->citiesByCountry) || empty($this->citiesByCountry[$country])) {
            $this->citiesByCountry[$country] = array();
            foreach ($this->destinations as $dest) {
                if ($dest->country == $country) {
                    $this->citiesByCountry[$country][$dest->city] = $dest->city;
                }
            }
            $ret = $this->citiesByCountry[$country];
        } else {
            $ret = $this->citiesByCountry[$country];
        }

        return $ret;
    }

    function getCitiesByState($country, $state) {
        $ret = array();

        if (empty($this->citiesByState) || empty($this->citiesByState[$country . '-' . $state])) {
            $this->citiesByState[$country . '-' . $state] = array();
            foreach ($this->destinations as $dest) {
                if ($dest->country == $country && $dest->state == $state) {
                    $this->citiesByState[$country . '-' . $state][$dest->city] = $dest->city;
                }
            }
            $ret = $this->citiesByState[$country . '-' . $state];
        } else {
            $ret = $this->citiesByState[$country . '-' . $state];
        }

        return $ret;
    }

}

class ShippingServiceByWeight extends ShippingService implements IShippingServiceByWeight {

    /**
     * total weight rounding policy
     *
     * @var int, -1=floor, 0=no rounding, 1=ceil, 2=round >= 0.5 up
     */
    protected $totalWeightRoundingPolicy = 0; // -1 floor, 0 no rounding, 1 ceil
    /**
     * per-Item weight rounding policy
     * 
     * @var int, -1=floor, 0=no rounding, 1=ceil, 2=round >= 0.5 up
     */
    protected $perItemWeightRoundingPolicy = 0; // -1 floor, 0 no rounding, 1 ceil

    function __construct($destinations, $name, $totalWeightRoundingPolicy=0, $perItemWeightRoundingPolicy=0, $priority=10) {
        parent::__construct($destinations, $name, $priority);
        $this->totalWeightRoundingPolicy = $totalWeightRoundingPolicy;
        $this->perItemWeightRoundingPolicy = $perItemWeightRoundingPolicy;
    }

    function setTotalWeightRoundingPolicy($p) {
        $this->totalWeightRoundingPolicy = $p;
    }

    function getTotalWeightRoundingPolicy() {
        return $this->totalWeightRoundingPolicy;
    }

    function setPerItemWeightRoundingPolicy($p) {
        $this->perItemWeightRoundingPolicy;
    }

    function getPerItemWeightRoundingPolicy() {
        return $this->perItemWeightRoundingPolicy;
    }

    function getPrice($destination, $items) {

        // count total weight first
        $total_weight = 0;
        foreach ($items as $item) {
            $weight = 0;
            $qtt = 0;
            if (isset($item->weight)) {
                // apply per item weight rounding
                $weight = $this->roundWeight($item->weight);
            }
            if (isset($item->quantity)) {
                $qtt = $item->quantity;
            }

            $total_weight += $weight * $qtt;
        }
        // apply total weight rounding
        $total_weight = $this->roundTotalWeight($total_weight);

        // check if destination exists with dest total weight
        return $this->getPriceByWeight($destination, $total_weight);
    }

    function getPriceByWeight($destination, $weight) {

        $ret = -1;

        $tmp;
        if (isset($this->destinations[$destination->key()])) {
            $tmp = $this->destinations[$destination->key()];
        }

        if (isset($tmp) && $tmp->minWeight <= $weight) {
            $ret = $tmp->price * $weight;
        } else {
            foreach ($this->destinations as $dest) {
                if ($dest->equals($destination) && $dest->minWeight <= $weight) {
                    $ret = $dest->price * $weight;
                    break;
                }
            }
        }

        return $ret;
    }

    function roundWeight($weight) {
        if ($this->perItemWeightRoundingPolicy == 0) {
            return $weight;
        } else if ($this->perItemWeightRoundingPolicy == 1) {
            return ceil($weight);
        } else if ($this->perItemWeightRoundingPolicy == 2) {
            return round($weight);
        } else if ($this->perItemWeightRoundingPolicy == -1) {
            return floor($weight);
        }
        return $weight;
    }

    function roundTotalWeight($totalWeight) {
        if ($this->totalWeightRoundingPolicy == 0) {
            return $totalWeight;
        } else if ($this->totalWeightRoundingPolicy == 1) {
            return ceil($totalWeight);
        } else if ($this->totalWeightRoundingPolicy == 2) {
            return round($totalWeight);
        } else if ($this->totalWeightRoundingPolicy == -1) {
            return floor($totalWeight);
        }
        return $weight;
    }

}

class ShippingServiceByWeightWithDiscount extends ShippingServiceByWeight {

    function __construct($destinations, $name, $totalWeightRoundingPolicy=0, $perItemWeightRoundingPolicy=0, $priority=10) {
        parent::__construct($destinations, $name, $totalWeightRoundingPolicy, $perItemWeightRoundingPolicy, $priority);
    }

    function getPrice($destination, $items) {

        // count total weight first
        $total_weight = 0;
        $total_weight_discount = 0;
        foreach ($items as $item) {
            $weight = 0;
            $qtt = 0;
            if (isset($item->weight)) {
                // apply per item weight rounding
                $weight = $this->roundWeight($item->weight);
            }
            if (isset($item->quantity)) {
                $qtt = $item->quantity;
            }

            $total_weight += $weight * $qtt;

            // count total weight discount
            if (isset($destination->discountedWeight) && $destination->discountedWeight > 0) {

                // apply weight discount
                if (isset($item->weightDiscount) && $item->weightDiscount < $weight && $item->weightDiscount > 0) {
                    $weight = $item->weightDiscount;
                }

                $dw = $destination->discountedWeight;
                if ($dw >= $weight) {
                    $dw = $weight;
                }

                $total_weight_discount += $dw * $qtt;
            }
        }
        // apply total weight rounding
        $total_weight = $this->roundTotalWeight($total_weight);
        $total_weight_discount = $this->roundTotalWeight($total_weight_discount);

        // apply weight discount
        if ($total_weight_discount >= 0) {
            $total_weight = $total_weight - $total_weight_discount;
        }

        // check if destination exists with dest total weight
        return $this->getPriceByWeight($destination, $total_weight);
    }

}

// ===================
//  K E R A N J A N G
// ===================
class KeranjangItem {

    // mandatory
    public $cartId;
    public $productId;
    public $name;
    public $price;
    public $weight;
    public $quantity;
    // can be empty
    public $attachment;
    public $category;
    public $weightDiscount;

    function __construct($cartId, $productId, $name, $price, $weight, $quantity, $attachment=null, $weightDiscount=0) {
        $this->cartId = $cartId;
        $this->productId = $productId;
        $this->name = $name;
        $this->price = $price;
        $this->weight = $weight;
        $this->attachment = $attachment;
        $this->quantity = $quantity;
        $this->weightDiscount = $weightDiscount;
    }

}

class KeranjangSummary {

    /**
     * Variable untuk menyimpan summary item
     * @var Array Of KeranjangItem
     */
    public $items;
    public $totalPrice;
    public $totalWeight;
    public $totalItems;
    // shipping
    public $shippingName;
    public $totalShippingPrice;
}

// cart service
interface IKeranjangService {

    /**
     * Add item to cart
     * @param KeranjangItem $item
     * @param int $count
     */
    function addItem($item, $count);

    /**
     * Remove specified item from cart
     * @param KeranjangItem $item
     * @param int $count
     */
    function removeItem($item, $count);

    /**
     * Update the quantity of an Item
     * @param KeranjangItem $item
     * @param int $count new quantity
     */
    function updateQuantity($item, $count);

    /**
     * Remove all item from the shopping cart
     */
    function emptyCart();

    /**
     * Return all items in the shopping cart
     */
    function getItems();

    /**
     * Get Total Pice of all items
     */
    function getTotalPrice();

    /**
     * Get total item available in the shopping cart
     */
    function getTotalItems();

    /**
     * 
     * Get total weight of available items
     */
    function getTotalWeight();

    function getSummary();
}

// KeranjangService class
class KeranjangService implements IKeranjangService {

    /**
     * List of items
     * @var Array of KeranjangItem
     */
    protected $items;
    protected $summary;
    protected $needRecount;

    /**
     * Create KeranjangService from intial items. 
     * If no items specified empty array will be assigned.
     * @param Array of KeranjangItem $items
     */
    function __construct() {
        if (!isset($_SESSION['warung_cart'])) {
            $_SESSION['warung_keranjang'] = array();
            $this->items = &$_SESSION['warung_cart'];
        } else {
            $this->items = &$_SESSION['warung_cart'];
            $this->generateSummary();
        }
    }

    function addItem($item, $count) {
        $oldItem;

        if (isset($this->items[strval($item->cartId)])) {
            $oldItem = $this->items[strval($item->cartId)];
        }
        if (isset($oldItem)) {
            // existing item
            if ($count > 0) {
                $oldItem->quantity += $count;
            }
        } else {
            // new item
            $item->quantity = $count;
            $this->items[strval($item->cartId)] = $item;
        }
        // recount summary
        $this->generateSummary();
    }

    function removeItem($item, $count) {
        $oldItem = $this->items[strval($item->cartId)];
        if (isset($oldItem)) {
            $oldQtt = $oldItem->quantity;
            if ($oldQtt > $count) {
                $oldItem->quantity = $oldQtt - $count;
            } else {
                // remove from cart
                unset($this->items[strval($item->cartId)]);
            }
            // recount summary
            $this->generateSummary();
        }
    }

    function updateQuantity($cartId, $count) {
        $oldItem = $this->items[strval($cartId)];
        if (isset($oldItem)) {
            if ($count <= 0) {
                unset($this->items[strval($cartId)]);
            } else {
                $oldItem->quantity = $count;
            }
            // recount summary
            $this->generateSummary();
        }
    }

    private function generateSummary() {
        $ret = new KeranjangSummary();

        $totalPrice = 0;
        $totalWeight = 0;
        $totalItems = 0;
        if (isset($this->items)) {
            $ret->items = $this->items;
            foreach ($this->items as $item) {
                $totalPrice += $item->price * $item->quantity;
                $totalWeight += $item->weight * $item->quantity;
                $totalItems += $item->quantity;
            }
        }

        $ret->totalPrice = $totalPrice;
        $ret->totalWeight = $totalWeight;
        $ret->totalItems = $totalItems;

        $this->summary = $ret;
    }

    function getSummary() {
        return $this->summary;
    }

    function emptyCart() {
        unset($this->items);
    }

    function getItems() {
        return $this->items;
    }

    function getTotalPrice() {
        $sum = $this->summary;
        return $sum->totalPrice;
    }

    function getTotalItems() {
        $sum = $this->summary;
        return $sum->totalItems;
    }

    function getTotalWeight() {
        $sum = $this->summary;
        return $sum->totalWeight;
    }

}

// ===================
//  U S E R
// ===================
class UserInfo {

    public $name;
    public $email;
    public $mobilePhone;
    public $phone;
    public $address;
    public $city;
    public $country;
    public $additionalInfo;

    public function __construct($name, $email, $mobilePhone, $phone, $address, $city, $country, $additionalInfo) {
        $this->name = $name;
        $this->email = $email;
        $this->mobilePhone = $mobilePhone;
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->additionalInfo = $additionalInfo;
    }

}

// ===================
//  K A S I R 
// ===================
interface IKasirService {

    // user info
    /**
     * save user info
     */
    function saveUserInfo($userInfo);

    /**
     * get saved user info
     */
    function getSavedUserInfo();

    // shipping
    /**
     * Return instance of WarungSummary class. with calculated totalShippingPrice.
     * @param ShippingDestination $destination
     * @param KeranjangService $keranjangService
     * @param ShippingService $shippingService
     */
    function getSummaryWithShippping($destination, $keranjangService, $shippingService);

    /**
     * Get all available shipping serivces
     */
    function getShippingServices();

    /**
     * Get array of shipping service that can ship to given destination
     * @param ShippingDestination $destination
     */
    function getShippingServicesByDestination($destination);

    /**
     * Get cheapest shipping service for given destination and items
     * @param ShippingDestination $destination
     * @param array Of KeranjangItem $items
     */
    function getCheapestShippingService($destination, $items);

    /**
     * Get shippingService instance of given name
     * @param String $sname
     * @return ShippingService instance or null if not found.
     */
    function getShippingServiceByName($sname);

    /**
     * Return array of Countries (string) that can be reach by at least one shipping service
     */
    function getCountries();

    /**
     * Return array of states within given country that can be reach by at least one shipping service
     * @param string $country
     */
    function getStatesByCountry($country);

    /**
     * Return array of cities within given country that can be reach by at least one shipping service
     * @param string $country
     */
    function getCitiesByCountry($country);

    /**
     * Return array of cities within given country and state that can be reach by at least one shipping service
     * @param string $country
     * @param string $state
     */
    function getCitiesByState($country, $state);

}

class Kasir implements IKasirService {

    /**
     * variable to store all shipping services
     * @var array of IShippingService
     */
    protected $shippingServices;
    protected $countries;
    protected $states;
    protected $cities;

    function __construct($shippingServices) {
        $this->shippingServices = $shippingServices;
    }

    // ================= user info ===================
    function saveUserInfo($userInfo) {
        $tmp = array(
            'email' => $userInfo->email,
            'phone' => $userInfo->phone,
            'name' => $userInfo->name,
            'address' => $userInfo->address,
            'city' => $userInfo->city,
            'additional_info' => $userInfo->additionalInfo
        );

        $_SESSION['wCartShipping'] = serialize($tmp);
        setcookie("wCartShipping", serialize($tmp), time() + 60 * 60 * 24 * 30); // save 1 month
    }

    function getSavedUserInfo() {

        $tmp_info = array(
            'email' => '',
            'phone' => '',
            'name' => '',
            'address' => '',
            'city' => '',
            'additional_info' => ''
        );


        if (isset($_SESSION['wCartShipping'])) {
            $tmp_info = unserialize(stripslashes($_SESSION['wCartShipping']));
        } else if (isset($_COOKIE['wCartShipping'])) {
            $tmp_info = unserialize(stripslashes($_COOKIE['wCartShipping']));
        }

        // alter old city info,
        // prev release city is id
        // now we use city name

        extract($tmp_info);

        if (!isset($country)) {
            $country = 'indonesia';
        }

        return new UserInfo($name, $email, $phone, $phone, $address, $city, $country, $additional_info);
    }

    // ================= shipping ====================

    function getSummaryWithShippping($destination, $keranjangService, $shippingService) {
        $sum = $keranjangService->getSummary();

        if (isset($sum)) {
            $sum->totalShippingPrice = $shippingService->getPrice($destination, $sum->items);
        }

        return $sum;
    }

    function getShippingServices() {
        return $this->shippingServices;
    }

    function getShippingServicesByDestination($destination) {
        $ret = array();
        foreach ($this->shippingServices as $ss) {
            if ($ss->getDestination($destination)) {
                array_push($ret, $ss);
            }
        }

        return $ret;
    }

    function getCheapestShippingService($destination, $items) {

        $map = array();

        foreach ($this->shippingServices as $ss) {
            $price = $ss->getPrice($destination, $items);
            $priority = $ss->getPriority();

            if ($price >=0) {
                $map[$price][$priority] = $ss;
            }

        }

        // sort to lowest price
        ksort($map);

        // get first array element
        $cheapest = array();
        foreach ($map as $m) {
            $cheapest = $m;
            break;
        }

        // return 1st priority if exist ss with same price
        ksort($cheapest);
        foreach ($cheapest as $c) {
            return $c;
        }
    }

    function getShippingServiceByName($sname) {
        if (isset($sname)) {
            foreach ($this->shippingServices as $ss) {
                if (strtolower($ss->getName()) == strtolower($sname)) {
                    return $ss;
                }
            }
        }

        return null;
    }

    function getCountries() {
        $ret = array();
        foreach ($this->shippingServices as $ss) {
            $ret = array_merge($ret, $ss->getCountries());
        }

        return $ret;
    }

    function getStatesByCountry($country) {
        $ret = array();
        foreach ($this->shippingServices as $ss) {
            $ret = array_merge($ret, $ss->getStates($country));
        }

        return $ret;
    }

    function getCitiesByCountry($country) {
        $ret = array();
        foreach ($this->shippingServices as $ss) {
            $ret = array_merge($ret, $ss->getCities($country));
        }

        return $ret;
    }

    function getCitiesByState($country, $state) {
        $ret = array();
        foreach ($this->shippingServices as $ss) {
            $ret = array_merge($ret, $ss->getCitiesByState($country, $state));
        }

        return $ret;
    }

}

class WarungKasir extends Kasir {

    protected $shippingProductMap;

    function __construct($shippingServices, $shippingProductMap) {
        parent::__construct($shippingServices);
        $this->shippingProductMap = $shippingProductMap;
    }

    function getSummaryWithShippping($destination, $keranjangService, $shippingService) {

        $sum = $keranjangService->getSummary();

        // split mapped shipping products in itemsdiscounted
        $items = $keranjangService->getItems();

        $mappedServiceItems = array();
        $otherServiceItems = array();

        if (! empty($items)) {
            foreach ($items as $item) {
                if (isset($item->category) && isset($this->shippingProductMap[strtolower($item->category)])) {
                    $ss = $this->getShippingServiceByName($this->shippingProductMap[strtolower($item->category)]);

                    // pisahkan item yang ada di map jika shipping servicenya ada
                    if (isset($ss)) {
                        $sname = strtolower($ss->getName());
                        // mapped item
                        if (isset($mappedServiceItems[$sname])) {
                            // update
                            $arr = &$mappedServiceItems[$sname];
                            $arr[$item->productId] = $item;
                        } else {
                            // new
                            $mappedServiceItems[$sname] = array($item->productId => $item);
                        }
                    } else {
                        // other item
                        $otherServiceItems[$item->productId] = $item;
                    }
                } else {
                    // other item
                    $otherServiceItems[$item->productId] = $item;
                }
            }
        }

        $totalOngkir = 0;

        // process mapped item
        if (!empty($mappedServiceItems)) {
            foreach ($mappedServiceItems as $k => $v) {
                $ss = $this->getShippingServiceByName($k);
                if (isset($ss)) {
                    $sum->shippingName = $ss->getName();
                    $totalOngkir += $ss->getPrice($destination, $v);
                }
            }
        }


        // process others item
        if (!empty($otherServiceItems)) {
            $ss = $this->getCheapestShippingService($destination, $otherServiceItems);
            if (isset($ss)) {
                $sum->shippingName = $ss->getName(); // default shipping name is from others item
                $totalOngkir += $ss->getPrice($destination, $otherServiceItems);
            }
        }

        $sum->totalShippingPrice = $totalOngkir;

        return $sum;
    }

}

interface ICheckoutWizard {

    function showPage();
}

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
            if ($a == 'confirm' && $cart->getTotalItems() > 0) {
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
                $admin_message = $this->showPayOk($emailView=true, array("order_id" => $order_id));
                $customer_message = $this->showPayOk($emailView=true, array("order_id" => $order_id));

                $headers = "Content-type: text/html;\r\n";
                $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";

                // send email to admin
                $ret = mail($admin_email, "[Admin] " . $subject, $admin_message, $headers);
                
                // send to pemesan bcc admin
                $headers .= "Bcc: " . $admin_email . "\r\n";
                mail($email_pemesan, $subject, $customer_message, $headers);

                $ret= true;

                if ($ret) {
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
            $destination = new ShippingDestination($userInfo->country, '', $userInfo->city, 0) ;
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
                    <? if ($showUpdateForm) {
                    ?>
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
                        <tr><td colspan="4" class="wcart-td-footer">Ongkos Kirim (<?= $this->formatWeight($cartSum->totalWeight) ?>) - <?=$cartSum->shippingName?></td><td class="wcart-td-footer"><span class="wcart_total"><?= $this->formatCurrency($cartSum->totalShippingPrice) ?></span></td></tr>
                        <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= $this->formatCurrency($cartSum->totalPrice + $cartSum->totalShippingPrice) ?></span></td></tr>
                <? } ?>
                </table>
            <? if ($showUpdateForm) {
            ?>
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

                foreach($cities as $city) {
                    break;
                }

                if (isset ($userInfo->city)) {
                    $city = $userInfo->city;
                }

                $co_page = $warungOpt->getCheckoutURL();
?>
                <div class="wcart_shipping_container">
                    <div><a name="w_shipping"/><h2>Informasi Pengiriman</h2></div>
                    <div id="wCart_shipping_form">
                        <?if($showUpdateForm) :?>
                        <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?= $this->getActionURL('confirm') ?>">
                        <? endif; ?>
                            <div class="wCart_form_row">
                                <label for="semail">Email *</label>
                <? if ($showUpdateForm) :?>
                    <input type="text" name="semail" value="<?= $userInfo->email ?>"/>
                <? else: ?>
                    <span><?= $userInfo->email ?></span>
                <? endif; ?>
            </div>

            <div class="wCart_form_row">
                <label for="sphone">HP (handphone) *</label>
                <? if ($showUpdateForm) :?>
                <input type="text" name="sphone" value="<?= $userInfo->phone ?>"/>
                <? else: ?>
                    <span><?= $userInfo->phone ?></span>
                <? endif; ?>
            </div>
            <div class="wCart_form_row">
                <label for="sname">Nama Penerima *</label>
                <? if ($showUpdateForm) :?>
                <input type="text" name="sname" value="<?= $userInfo->name ?>"/></div>
                <? else: ?>
                    <span><?= $userInfo->name ?></span>
                <? endif; ?>
            <div class="wCart_form_row">
                <label for="saddress">Alamat *</label>
                <? if ($showUpdateForm) :?>
                <textarea name="saddress"><?= $userInfo->address ?></textarea>
                <? else: ?>
                    <span><?= $userInfo->address ?></span>
                <? endif; ?>
            </div>
            <div class="wCart_form_row">
                <label for="scity">Kota</label>
                <? if ($showUpdateForm) :?>
<?= $this->form_select('scity', $cities, $city, array(&$this, 'city_callback'), false) ?>
                <? else: ?>
                    <span><?= $userInfo->city ?></span>
                <? endif; ?>
            </div>
            <input type="hidden" name="scountry" value="<?=$userInfo->country?>"/>
            <div class="wCart_form_row">
                <label for="sadditional_info">Info Tambahan</label>
                <? if ($showUpdateForm) :?>
                <textarea name="sadditional_info"><?= $userInfo->additionalInfo ?></textarea>
                <? else: ?>
                    <span><?= $userInfo->additionalInfo ?></span>
                <? endif; ?>
            </div>


                <?if($showUpdateForm) :?>

                    <div class="wCart_form_row">
                        <input type="hidden" name="step" value="2"/>
                        <input type="submit" name="scheckout" class="submit" value="Lanjut"/>
                    </div>

            </form>
            <?endif;?>
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
                $editURL= $wo->getCheckoutURL();

                echo $this->showDetailedCart(false);
                // show edit url
                ?><p><a class="wcart_button_url" href="<?=$editURL."#w_cart"?>">Edit</a></p><?
                echo  $this->showShippingForm(false);
                // show edit url
                ?><p><a class="wcart_button_url" href="<?=$editURL."#w_shipping"?>">Edit</a></p><?
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
                    $destination = new ShippingDestination($userInfo->country, '', $userInfo->city, 0) ;
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
	            <p><?=$userInfo->name?>, kami sudah menerima pesanan anda. Untuk pembayaran silahkan transfer ke salah satu nomor rekening berikut sebesar <b><?=$this->formatCurrency($cartSum->totalPrice + $cartSum->totalShippingPrice)?></b>:
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
	            <?

	            ?>
	                <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->email?></td></tr>
	                <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->phone?></td></tr>
	                <tr><td>Jasa Pengiriman</td><td>&nbsp;:&nbsp;</td><td><?=$cartSum->shippingName?></td></tr>
	                <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->name?></td></tr>
	                <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->address?></td></tr>
	                <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->city?></td></tr>
	                <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td><?=$userInfo->additionalInfo?></td></tr>
	            
	            </table>
	        </div>
	        <?

                $ret = ob_get_contents();
                ob_end_clean();

                return $ret;
            }

            public function showPayError($isEmailView = false, $params=null) {
                return '<p>'.__('Maaf kami blm dapat memproses pesanan anda silahkan coba beberapa saat lagi').'</p>';
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

        /**
         * Registry class for getting all required class instance
         */
        class WarungRegistry {

            private static $kasir;
            private static $option;

            public static function getKasir() {
                if (isset(WarungRegistry::$kasir)) {
                    return WarungRegistry::$kasir;
                } else {
                    $option = WarungRegistry::getOption();
                    return WarungRegistry::$kasir = new WarungKasir($option->getShippingServices(), $option->getShippingProductMap());
                }
            }

            public static function getOption() {
                if (isset(WarungRegistry::$option)) {
                    return WarungRegistry::$option;
                } else {
                    return WarungRegistry::$option = new WarungOptions();
                }
            }

        }

        class WarungOptions {

            public static $OPT_NAME = 'Warung_Options';
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
                return $this->options['currency'];
            }

            public function getWeightSign() {
                return $this->options['weight_sign'];
            }

            public function getAddToCartText() {
                return $this->options['add_to_cart'];
            }

            public function getCheckoutPageId() {
                return $this->options['checkout_page'];
            }

            public function getCheckoutURL() {
                return get_permalink($this->getCheckoutPageId());
            }

            public function getShippingSimPageId() {
                return $this->options['shipping_sim_page'];
            }

            public function getShippingSimURL() {
                return get_permalink($this->getShippingSimPageId());
            }

            /**
             * Get predefined global options set from admin page
             * @return Array of product options. format array { "name" => object{name, value, txt} }
             */
            public function getGlobalProductOptions() {
                return $this->options['prod_options'];
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
                                $r = '{' . stripslashes($r) . '}';
                                $r = json_decode($r);
                                $freeWeight = 0;
                                if (isset($r->free_weight)) {
                                    $freeWeight = $r->free_weight;
                                }
                                $minWeight = 0;
                                if (isset($r->min_weight)) {
                                    $minWeight = $r->min_weight;
                                }
                                $t = new ShippingDestinationByWeight($r->country, $r->state, $r->city, $r->price, $minWeight, $freeWeight);
                                array_push($dest, $t);
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

        /*
         * ==========================================================
         *  T E S T  C A S E
         * ==========================================================
         */
        /*

          // ==================
          //  Shopping Cart
          // ==================
          echo '<h1>Test Keranjang</h1>';
          echo '<h3>Test Add</h3>';
          $items=array();
          $cart = new KeranjangService($items);

          // add item
          $item = new KeranjangItem(1, 1, 'Sprei A - 180x200', 1000, 1, 1, null);
          $cart->addItem($item, 1);

          $item = new KeranjangItem(1, 1, 'Sprei A - 180x200', 1000, 1, 1);
          $cart->addItem($item, 5);

          $item = new KeranjangItem(2, 2, 'Sprei B - 120x200', 1000, 1, 1);
          $cart->addItem($item, 1);

          $item = new KeranjangItem(2, 2, 'Sprei B - 120x200', 1000, 1, 2);
          $cart->addItem($item, 1);

          $item = new KeranjangItem(3, 3, 'Bantal C - 100x30', 1000, 1, 1);
          $cart->addItem($item, 1);

          $item = new KeranjangItem(3, 3, 'Bantal C - 100x30', 1000, 1, 2);
          $cart->addItem($item, 1);

          var_dump($cart->getSummary());

          // remove item
          echo '<h3>Test Remove 1</h3>';

          $item = new KeranjangItem(3, 'Bantal C - 100x30', 1000, 1, 2);
          $cart->removeItem($item, 1);
          $cart->removeItem($item, 1);

          var_dump($cart->getSummary());

          echo '<h3>Test Remove 2</h3>';

          $item = new KeranjangItem(2, 2, 'Sprei B - 120x200', 1000, 1, 2);
          $cart->removeItem($item, 5);

          var_dump($cart->getSummary());


          echo '<h3>Test Remove 3</h3>';

          $item = new KeranjangItem(1, 1, 'Sprei B - 120x200', 1000, 1, 2);
          $cart->removeItem($item, 2);

          var_dump($cart->getSummary());


          // ==================
          //  Shipping
          // ==================
          echo '<h1>Test shipping service</h1>';

          $des = array(
          new ShippingDestinationByWeight('indonesia', 'dki', 'jakarta', 1000, 1, 1/*weight disc* /),
          new ShippingDestinationByWeight('indonesia', 'jawa timur', 'surabaya', 2000, 1),
          new ShippingDestinationByWeight('indonesia', 'jawa timur', 'kediri', 2000, 1),
          new ShippingDestinationByWeight('indonesia', 'bali', 'denpasar', 3000, 1),
          new ShippingDestinationByWeight('indonesia', 'bali', 'singaraja', 3000, 1),
          new ShippingDestinationByWeight('indonesia', 'papua', 'papua', 5000, 1),

          new ShippingDestinationByWeight('usa', 'alaska', 'anchorage', 11000, 1),
          new ShippingDestinationByWeight('usa', 'alaska', 'fairbanks', 11000, 1),
          new ShippingDestinationByWeight('usa', 'hawai', 'honolulu', 12000, 1)
          );

          $ss = new ShippingServiceByWeightWithDiscount($des, 'jne');

          echo '<h3>countries</h3>';
          $ss_countries = $ss->getCountries();
          var_dump($ss_countries);

          echo '<h3>states</h3>';
          foreach ($ss_countries as $c) {
          echo '<h4>get states in '.$c.'</h4>';
          $ss_states = $ss->getStates($c);
          var_dump($ss_states);
          foreach ($ss_states as $s) {
          echo '<h4>get cities in '.$s.'</h4>';
          var_dump($ss->getCitiesByState($c, $s));
          }
          }

          echo '<h3>cities</h3>';
          foreach ($ss_countries as $c) {
          echo '<h4>get cities in '.$c.'</h4>';
          var_dump($ss->getCities($c));
          }


          $testDest = $des[6];
          echo '<h3>get price by items to '.$testDest->city.'</h3>';
          $r = $ss->getPrice($testDest, $cart->getItems());
          var_dump($r);


          $testDest = $des[0];
          echo '<h3>get price by items to '.$testDest->city.' disc weight: '.$testDest->discountedWeight.'</h3>';

          // add item
          $item = new KeranjangItem(5, 5, 'Sprei E - 180x200', 1000, 1, 1);
          $cart->addItem($item, 4);

          var_dump($cart->getItems());
          $r = $ss->getPrice($testDest, $cart->getItems());
          var_dump($r);


          // ====================
          // K A S I R  T E S T
          // ====================

          echo '<h1>Kasir Test</h1>';

          $des2 = array(
          new ShippingDestinationByWeight('indonesia', 'dki', 'jakarta', 900, 1, 1/*weight disc* /),
          new ShippingDestinationByWeight('indonesia', 'jawa timur', 'surabaya', 1500, 1),
          new ShippingDestinationByWeight('indonesia', 'jawa timur', 'kediri', 2100, 1),
          new ShippingDestinationByWeight('indonesia', 'bali', 'denpasar', 3100, 1),
          new ShippingDestinationByWeight('indonesia', 'bali', 'singaraja', 3100, 1),
          new ShippingDestinationByWeight('indonesia', 'papua', 'papua', 5100, 1),
          new ShippingDestinationByWeight('indonesia', 'jawa barat', 'bandung', 3100, 1),
          new ShippingDestinationByWeight('indonesia', 'jawa barat', 'subang', 5100, 1),

          new ShippingDestinationByWeight('usa', 'alaska', 'anchorage', 11001, 1),
          new ShippingDestinationByWeight('usa', 'alaska', 'fairbanks', 11001, 1),
          new ShippingDestinationByWeight('usa', 'hawai', 'honolulu', 12001, 1),

          new ShippingDestinationByWeight('canada', 'manitoba', 'montreal', 12001, 1),
          );

          $ss_pandusiwi = new ShippingServiceByWeightWithDiscount($des2, 'pandusiwi', 0, 0, 1);

          $kasir = new WarungKasir(array ($ss, $ss_pandusiwi), array('belladona'=>'jne'));

          echo '<h3>Test get countries</h3>';

          $k_c = $kasir->getCountries();
          var_dump($k_c);

          foreach ($k_c as $co) {
          echo '<h3>Test get cities in '.$co.'</h3>';
          $c_c = $kasir->getCitiesByCountry($co);
          var_dump($c_c);
          }

          $k_d = array($des2[7], $des2[0]);
          foreach ($k_d as $k_d_1) {
          echo '<h3>Test get shipping services by destination '.$k_d_1->city.'</h3>';
          $ssbd = $kasir->getShippingServicesByDestination($k_d_1);
          foreach ($ssbd as $kss) {
          var_dump( $kss->getName() );
          }
          }

          echo '<h3>Test get cheapest services to '.$des[0]->city.'</h3>';
          $k_item = new KeranjangItem(1, 1, 'Sprei A - 180x200', 1000, 1, 1);
          $k_items = array(1=>$k_item);

          $k_cs = $kasir->getCheapestShippingService($des[0], $k_items);
          var_dump($k_cs->getName());


          $des = $des[1];
          echo '<h3>Test get summary  to '.$des->city.'</h3>';
          $k_item = new KeranjangItem(1, 1, 'Sprei Belladona - 180x200', 1000, 1, 1);
          $k_item->category = 'belladona';
          $k_item2 = new KeranjangItem(2, 2, 'Sprei Inu - 180x200', 1300, 1, 1);
          $k_items = array(1=>$k_item, 2=>$k_item2);

          $k_ks = new KeranjangService($k_items);

          $k_cs = $kasir->getSummaryWithShippping($des, $k_ks, null);
          var_dump($k_cs);

         */
?>