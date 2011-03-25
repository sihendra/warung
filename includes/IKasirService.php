<?php

interface IKasirService {

    /**
     * save user info
     * @param UserInfo instance of userinfo
     */
    function saveUserInfo($userInfo);

    /**
     * get saved user info
     */
    function getSavedUserInfo();

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

?>
