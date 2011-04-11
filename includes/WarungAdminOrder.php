<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungAdminOrder
 *
 * @author hendra
 */
class WarungAdminOrder {
    function handle_orders() {
        $orderService = new OrderService();
        $orders = $orderService->getAllOrders();
        foreach($orders as $order) {
            ?><div><?=$order->id?>
                <br/><?=$order->status?>
                <br/><?=$order->getBuyerName()?>
                <br/><?=$order->getItemsSummary()?>
                <br/><?=$order->getShippingAddress()?></div><?
        }
    }
}
?>
