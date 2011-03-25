<?php

class KeranjangItem {

    // mandatory
    public $cartId;
    public $productId;
    public $name;
    public $price;
    public $weight;
    public $quantity;
    // can be empty
    public $attachment;
    public $category;
    public $weightDiscount;

    function __construct($cartId, $productId, $name, $price, $weight, $quantity, $attachment=null, $weightDiscount=0) {
        $this->cartId = $cartId;
        $this->productId = $productId;
        $this->name = $name;
        $this->price = $price;
        $this->weight = $weight;
        $this->attachment = $attachment;
        $this->quantity = $quantity;
        $this->weightDiscount = $weightDiscount;
    }

}

?>
