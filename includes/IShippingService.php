<?php

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

?>
