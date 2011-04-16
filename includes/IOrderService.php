<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IOrder
 *
 * @author hendra
 */
interface IOrderService {
    /**
     * Insert new Order
     * @param Order $order
     * @return int|false if error will return false
     */
    function putOrder($order);

    /**
     * Update order
     * @param Order $order
     * @return int|false if error will return false
     */
    function updateStatus($orderId, $status);

    function updateDeliveryNumber($orderId, $delivNum);


    function getOrderById($orderId);
    function getAllOrders();
    function getAllStatus();
}
?>
