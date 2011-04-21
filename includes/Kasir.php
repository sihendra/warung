<?php

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

        $_COOKIE[WarungOptions::$SHIPPING_SESS_NAME] = serialize($tmp);
        setcookie(WarungOptions::$SHIPPING_SESS_NAME, serialize($tmp), time() + 60 * 60 * 24 * 30); // save 1 month
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

        if (isset($_COOKIE[WarungOptions::$SHIPPING_SESS_NAME])) {
            $tmp_info = unserialize(stripslashes($_COOKIE[WarungOptions::$SHIPPING_SESS_NAME]));
        }

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

            if ($price >= 0) {
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

    function getCheapestShippingServiceByWeight($destination, $weight) {
        $item = new KeranjangItem(0, 0, 'sprei', 0, $weight, 1, null, 0);
        return $this->getCheapestShippingService($destination, array($item));
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

?>
