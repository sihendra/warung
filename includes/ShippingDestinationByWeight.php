<?php

class ShippingDestinationByWeight extends ShippingDestination {

    public $minWeight;
    public $discountedWeight;

    function __construct($country, $state, $city, $price, $minWeight, $discountedWeight=0) {
        parent::__construct($country, $state, $city, $price);
        $this->minWeight = $minWeight;
        $this->discountedWeight = $discountedWeight;
    }

}

?>
