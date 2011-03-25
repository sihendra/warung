<?php

class KeranjangService implements IKeranjangService {

    /**
     * List of items
     * @var Array of KeranjangItem
     */
    protected $items;
    protected $summary;
    protected $needRecount;

    /**
     * Create KeranjangService from intial items.
     * If no items specified empty array will be assigned.
     * @param Array of KeranjangItem $items
     */
    function __construct() {
        if (!isset($_SESSION[WarungOptions::$CART_SESS_NAME])) {
            $_SESSION[WarungOptions::$CART_SESS_NAME] = array();
            $this->items = &$_SESSION[WarungOptions::$CART_SESS_NAME];
        } else {
            $this->items = &$_SESSION[WarungOptions::$CART_SESS_NAME];
            $this->generateSummary();
        }
    }

    function addItem($item, $count) {
        $oldItem;

        if (isset($this->items[strval($item->cartId)])) {
            $oldItem = $this->items[strval($item->cartId)];
        }
        if (isset($oldItem)) {
            // existing item
            if ($count > 0) {
                $oldItem->quantity += $count;
            }
        } else {
            // new item
            $item->quantity = $count;
            $this->items[strval($item->cartId)] = $item;
        }
        // recount summary
        $this->generateSummary();
    }

    function removeItem($item, $count) {
        $oldItem = $this->items[strval($item->cartId)];
        if (isset($oldItem)) {
            $oldQtt = $oldItem->quantity;
            if ($oldQtt > $count) {
                $oldItem->quantity = $oldQtt - $count;
            } else {
                // remove from cart
                unset($this->items[strval($item->cartId)]);
            }
            // recount summary
            $this->generateSummary();
        }
    }

    function updateQuantity($cartId, $count) {
        $oldItem = $this->items[strval($cartId)];
        if (isset($oldItem)) {
            if ($count <= 0) {
                unset($this->items[strval($cartId)]);
            } else {
                $oldItem->quantity = $count;
            }
            // recount summary
            $this->generateSummary();
        }
    }

    private function generateSummary() {
        $ret = new KeranjangSummary();

        $totalPrice = 0;
        $totalWeight = 0;
        $totalItems = 0;
        if (isset($this->items)) {
            $ret->items = $this->items;
            foreach ($this->items as $item) {
                $totalPrice += $item->price * $item->quantity;
                $totalWeight += $item->weight * $item->quantity;
                $totalItems += $item->quantity;
            }
        }

        $ret->totalPrice = $totalPrice;
        $ret->totalWeight = $totalWeight;
        $ret->totalItems = $totalItems;

        $this->summary = $ret;
    }

    function getSummary() {
        return $this->summary;
    }

    function emptyCart() {
        unset($this->items);
        unset($_SESSION[SWarungOptions::$CART_SESS_NAME]);
    }

    function getItems() {
        return $this->items;
    }

    function getTotalPrice() {
        $sum = $this->summary;
        return $sum->totalPrice;
    }

    function getTotalItems() {
        $sum = $this->summary;
        return $sum->totalItems;
    }

    function getTotalWeight() {
        $sum = $this->summary;
        return $sum->totalWeight;
    }

}

?>
