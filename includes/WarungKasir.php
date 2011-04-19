<?php

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

        if (!empty($items)) {
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
                            $mappedServiceItems[$sname] = array($item->name => $item);
                        }
                    } else {
                        // other item
                        $otherServiceItems[$item->name] = $item;
                    }
                } else {
                    // other item
                    $otherServiceItems[$item->name] = $item;
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

?>
