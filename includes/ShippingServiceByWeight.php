<?php

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

?>
