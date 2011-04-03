<?php

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
                if (!empty($dest->country)) {
                    $this->countries[(string) $dest->country] = $dest->country;
                }
            }
            $ret = $this->countries;
        } else {
            $ret = $this->countries;
        }

        return $ret;
    }

    function getStates($country) {
        $ret = array();

        // validate parameter
        if (empty($country)){
            return $ret;
        }

        if (empty($this->statesByCountry) || empty($this->statesByCountry[$country])) {
            $this->statesByCountry[$country] = array();
            foreach ($this->destinations as $dest) {
                if ($dest->country == $country) {
                    // check state not empty
                    if (!empty($dest->state)) {
                        $this->statesByCountry[$country][$dest->state] = $dest->state;
                    }
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

        // validate parameter
        if (empty($country)){
            return $ret;
        }

        if (empty($this->citiesByCountry) || empty($this->citiesByCountry[$country])) {
            $this->citiesByCountry[$country] = array();
            foreach ($this->destinations as $dest) {
                if ($dest->country == $country) {
                    // check city not empty
                    if (!empty($dest->city)) {
                        $this->citiesByCountry[$country][$dest->city] = $dest->city;
                    }
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

        // validate parameter
        if (empty($country)){
            return $ret;
        }

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

?>
