<?php

class ShippingServiceByWeightWithDiscount extends ShippingServiceByWeight {

    function __construct($destinations, $name, $totalWeightRoundingPolicy=0, $perItemWeightRoundingPolicy=0, $priority=10) {
        parent::__construct($destinations, $name, $totalWeightRoundingPolicy, $perItemWeightRoundingPolicy, $priority);
    }

    function getPrice($destination, $items) {
        
        // get destination info from db;
        if (isset($this->destinations[$destination->key()])) {
            $destination = $this->destinations[$destination->key()];
        } else {
            foreach ($this->destinations as $dest) {
                if ($dest->equals($destination)) {
                    $destination = $dest;
                    break;
                }
            }
        }
        
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

?>
