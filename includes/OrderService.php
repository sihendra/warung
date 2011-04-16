<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OrderService
 *
 * @author hendra
 */
class OrderService implements IOrderService {

    private $orderTable;
    private $orderShippingTable;
    private $orderItemsTable;

    // update statuses
    public static $STATUS_ORDERED = "ordered";
    public static $STATUS_PAYMENT_VERIFIED = "payment_verified";
    public static $STATUS_PAYMENT_NOT_VERIFIED= "payment_not_verified";
    public static $STATUS_DELIVERED = "delivered";
    public static $STATUS_RECEIVED = "received";
    public static $STATUS_CANCELED = "canceled";

    public function __construct() {
        global $wpdb;
        
        $this->orderTable = $wpdb->prefix . "wrg_order";
        $this->orderShippingTable = $wpdb->prefix . "wrg_order_shipping";
        $this->orderItemsTable = $wpdb->prefix . "wrg_order_items";
    }

    //put your code here
    public function getAllOrders() {
        global $wpdb;

        $ret = array();

        $sql = "SELECT id, dtcreated, status, dtlastupdated, items_price, shipping_price, dtpayment, dtdelivery, delivery_number
                  FROM $this->orderTable
                 ORDER BY id DESC";

        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through order table
            foreach($result as $row) {

                $order = new Order();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->status = $row->status;
                $order->dtlastupdated = $row->dtlastupdated;
                $order->itemsPrice = $row->items_price;
                $order->shippingPrice = $row->shipping_price;
                $order->dtpayment = $row->dtpayment;
                $order->dtdelivery = $row->dtdelivery;
                $order->deliveryNumber = $row->delivery_number;

                // get shipping/buyer info
                $sql = $wpdb->prepare(
                        "SELECT name, email, mobile_phone, phone, address, city, state, country, additional_info
                          FROM $this->orderShippingTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);

                if($r2) {
                    $s = $r2[0];

                    $i = new UserInfo($s->name, $s->email, $s->mobile_phone, $s->phone, $s->address, $s->city, $s->country, $s->additional_info);
                    $i->state = $s->state;
                    
                    $order->shippingInfo=$i;

                }

                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = new KeranjangItem(0, $row2->item_id, $row2->name, $row2->price, $row2->weight, $row2->quantity, null, null);
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                array_push($ret, $order);
            }
        }

        return $ret;
    }

    public function putOrder($order) {
        $ret = false;
        
        global $wpdb;

        if ($order instanceof Order) {

            $sql = $wpdb->prepare("
                    INSERT INTO $this->orderTable (status, dtlastupdated, items_price, shipping_price, dtpayment, dtdelivery, delivery_number, shipping_weight)
                    VALUES (%s, %s, %d, %d, %s, %s, %s, %f)",
                    $order->status,
                    date('Y-m-d H:i:s'),
                    $order->itemsPrice,
                    $order->shippingPrice,
                    $order->dtpayment,
                    $order->dtdelivery,
                    $order->deliveryNumber,
                    $order->shippingWeight
                    );

            if (($ret = $wpdb->query($sql)) > 0) {

                $orderId = $wpdb->insert_id;
                $ret = $orderId;

                // insert into shipping info
                if (isset($orderId) && isset($order->shippingInfo)) {
                    $ts = $order->shippingInfo;

                    $sql = $wpdb->prepare("
                        INSERT INTO $this->orderShippingTable (order_id, name, email, mobile_phone, phone, address, city, state, country, additional_info)
                        VALUES (%d, %s, %s, %d, %d, %s, %s, %s, %s, %s)",
                        $orderId,
                        $ts->name,
                        $ts->email,
                        $ts->mobilePhone,
                        $ts->phone,
                        $ts->address,
                        $ts->city,
                        $ts->state,
                        $ts->country,
                        $ts->additionalInfo
                    );

                    $wpdb->query($sql);
                }

                // insert into items
                if (isset($orderId) && isset($order->items)) {
                    $ti = $order->items;

                    if (is_array($ti)) {
                        foreach ($ti as $i) {
                            $sql = $wpdb->prepare("
                                INSERT INTO $this->orderItemsTable (order_id, item_id, name, quantity, weight)
                                VALUES (%d, %s, %s, %d, %f)",
                                $orderId,
                                $i->productId,
                                $i->name,
                                $i->quantity,
                                $i->weight
                            );

                            $wpdb->query($sql);
                        }
                    }
                }
            }

        }

        return $ret;
        
    }

    public function updateStatus($orderId, $status) {
        global $wpdb;


        if ($this->isValidNewStatus($oldStatus, $status)) {
                
            $params = array($status);
            $others = "";
            if ($status == self::$STATUS_DELIVERED) {
                $others .= ", dtdelivery=%s ";
                array_push($params, date('Y-m-d H:i:s'));
            } else if ($status == self::$STATUS_PAYMENT_VERIFIED) {
                $others .= ", dtpayment=%s ";
                array_push($params, date('Y-m-d H:i:s'));
            }
            array_push($params, $orderId);

            $sql = $wpdb->prepare("
                    UPDATE $this->orderTable
                       SET status=%s, dtlastupdated = NOW() $others
                     WHERE id=%d",
                    $params
                    );

            return $wpdb->query($sql);
            
        }

        return false;

    }

    private function isValidNewStatus($oldStatus, $newStatus) {

        return true;
        /*
        if ($oldStatus == self::$STATUS_ORDERED) {
            if ($newStatus == self::$STATUS_PAYMENT_VERIFIED || $newStatus == self::$STATUS_PAYMENT_NOT_VERIFIED || $newStatus == self::$STATUS_CANCELED) {
                return true;
            }
        } else if ($oldStatus == self::$STATUS_CANCELED) {
            return false;
        }
        return false;
        */
    }

    /**
     *
     * @global wpdb $wpdb
     * @param int $orderId
     * @return false|Order
     */
    public function getOrderById($orderId) {
        global $wpdb;

        $ret = false;

        $sql = $wpdb->prepare("SELECT id, dtcreated, status, dtlastupdated, items_price, shipping_price, dtpayment, dtdelivery, delivery_number
                  FROM $this->orderTable
                 WHERE id = %d", $orderId);

        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through result
            foreach($result as $row) {

                $order = new Order();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->status = $row->status;
                $order->dtlastupdated = $row->dtlastupdated;
                $order->itemsPrice = $row->items_price;
                $order->shippingPrice = $row->shipping_price;
                $order->dtpayment = $row->dtpayment;
                $order->dtdelivery = $row->dtdelivery;
                $order->deliveryNumber = $row->delivery_number;

                // get shipping/buyer info
                $sql = $wpdb->prepare(
                        "SELECT name, email, mobile_phone, phone, address, city, state, country, additional_info
                          FROM $this->orderShippingTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);

                if($r2) {
                    $s = $r2[0];

                    $i = new UserInfo($s->name, $s->email, $s->mobile_phone, $s->phone, $s->address, $s->city, $s->country, $s->additional_info);
                    $i->state = $s->state;
                    
                    $order->shippingInfo=$i;

                }

                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = new KeranjangItem(0, $row2->item_id, $row2->name, $row2->price, $row2->weight, $row2->quantity, null, null);
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                $ret = $order;

            }
        }

        return $ret;
    }

    public function updateDeliveryNumber($orderId, $delivNum) {
        global $wpdb;

        $sql = $wpdb->prepare("
                UPDATE $this->orderTable
                   SET deliveryNumber=%s, dtlastupdated = NOW()
                 WHERE id=%d",
                $delivNum,
                $orderId
                );

        return $wpdb->query($sql);

    }

    public function getAllStatus() {
        $ret = array();
        $ret[self::$STATUS_ORDERED]="Ordered";
        $ret[self::$STATUS_PAYMENT_VERIFIED]="Payment Verified";
        $ret[self::$STATUS_PAYMENT_NOT_VERIFIED]="Payment Not Verified";
        $ret[self::$STATUS_DELIVERED]="Delivered";
        $ret[self::$STATUS_RECEIVED]="Received";
        $ret[self::$STATUS_CANCELED]="Canceled";
        return $ret;
    }
}
?>
