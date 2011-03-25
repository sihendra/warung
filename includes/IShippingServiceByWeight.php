<?php

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

?>
