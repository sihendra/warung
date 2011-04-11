<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Order
 *
 * @author hendra
 */
class Order {
    public $id;
    public $dtcreated;
    public $status='order';
    public $dtlastupdated;

    public $itemsPrice;
    public $shippingPrice;
    public $shippingWeight;

    public $dtpayment;
    public $dtdelivery;
    public $deliveryNumber;

    /**
     * Buery Info
     * @var UserInfo
     */
    public $buyerInfo;
    /**
     * Shipping Info
     * @var UserInfo
     */
    public $shippingInfo;
    public $items;


    public function getBuyerName() {
        if (isset($this->shippingInfo)) {
            return $this->shippingInfo->name;
        }
    }

    public function getItemsSummary() {
        $ret = "";
        if (isset($this->items)) {
            $i=0;
            foreach ($this->items as $item) {
                if ($i++ == 0) {
                    $ret .= $item->name." (".$item->quantity.")";
                } else {
                    $ret .= ", ".$item->name." (".$item->quantity.")";
                }
            }
        }
        return $ret;
    }

    public function getShippingAddress() {
        if (isset($this->shippingInfo)) {
            return $this->shippingInfo->address.', '.$this->shippingInfo->city;
        }
    }
}
?>
