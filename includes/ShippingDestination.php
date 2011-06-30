<?php

class ShippingDestination {

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

?>
