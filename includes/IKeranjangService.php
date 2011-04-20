<?php

interface IKeranjangService {

    /**
     * Add item to cart
     * @param KeranjangItem $item
     * @param int $count
     */
    function addItem($item, $count);

    /**
     * Remove specified item from cart
     * @param KeranjangItem $item
     * @param int $count
     */
    function removeItem($item, $count);

    /**
     * Update the quantity of an Item
     * @param KeranjangItem $item
     * @param int $count new quantity
     */
    function updateQuantity($item, $count);

    /**
     * Remove all item from the shopping cart
     */
    function emptyCart();

    /**
     * Return all items in the shopping cart
     * @return KeranjangItem[] items
     */
    function getItems();

    /**
     * Get Total Pice of all items
     */
    function getTotalPrice();

    /**
     * Get total item available in the shopping cart
     */
    function getTotalItems();

    /**
     *
     * Get total weight of available items
     */
    function getTotalWeight();

    /**
     * @return KeranjangSummary 
     */
    function getSummary();
}

?>
