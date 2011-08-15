<?php
/**
 * Description of WarungAdminOrder
 *
 * @author hendra
 */
class WarungAdminOrder {
    function handle_orders() {
        $orderService = new OrderService();

        //check_admin_referer('warung-nonce')
        if ( !empty($_REQUEST['wrg_order_status_submit'])
                && !empty($_REQUEST['wrg_order_status_id'])
                && !empty($_REQUEST['wrg_order_status_status'])) {
            // update status
            $orderService->updateStatus($_REQUEST['wrg_order_status_id'], $_REQUEST['wrg_order_status_status']);
        }

        // current url
        $pageURL = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];

        // nav
        $page = 1;
        if (isset($_REQUEST['wrg_order_page'])) {
            $page = $_REQUEST['wrg_order_page'];
        }

        // order by
        $orderBy = 'id asc';
        if (isset($_REQUEST['wrg_order_sortby'])) {
            $orderBy = $_REQUEST['wrg_order_sortby'];
        }

        // status order
        $orderStatusClass = "asc";
        $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status asc"));
        if ($orderBy == 'status desc') {
            $orderStatusClass = "asc";
            $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status asc"));
        } else {
            $orderStatusClass = "desc";
            $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status desc"));
        }

        // id order
        $orderIdClass = "asc";
        $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id asc"));
        if ($orderBy == 'id desc') {
            $orderIdClass = "asc";
            $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id asc"));
        } else {
            $orderIdClass = "desc";
            $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id desc"));
        }

        // date order
        $orderDateClass = "asc";
        $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated asc"));
        if ($orderBy == 'dtcreated desc') {
            $orderDateClass = "asc";
            $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated asc"));
        } else {
            $orderDateClass = "desc";
            $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated desc"));
        }

        // lastupdate order
        $orderLastUpdateClass = "asc";
        $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated asc"));
        if ($orderBy == 'dtlastupdated desc') {
            $orderLastUpdateClass = "asc";
            $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated asc"));
        } else {
            $orderLastUpdateClass = "desc";
            $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated desc"));
        }

        // get all order
        $orders = $orderService->getAllOrders(5, $page, $orderBy);
        $orderData = array();
        if (isset($orders['data'])) {
            $orderData = $orders['data'];
        }
        $orderStatuses = $orderService->getAllStatus();

        // page nav
        $pageNav = new PageNav('wrg_order_page', $orders);

        $chartURL = WarungChart::getOrderChartURL(400,150);
        ?>
<div class="wrap">
    <h2>Order</h2>
    
    <div class="wschart">
        <img alt="order chart" src="<?=$chartURL?>"/>
    </div>
    <div class="tablenav"><?=$pageNav->show(' ', '«', '»')?></div>
    <div class="clear"></div>
    <table class="wp-list-table widefat">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable <?=$orderIdClass?>" style=""><a href="<?=$orderByIdURL?>"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable <?=$orderDateClass?>" style=""><a href="<?=$orderByIdURL?>"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable <?=$orderStatusClass?>" style=""><a href="<?=$orderByStatusURL?>"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable <?=$orderLastUpdateClass?>" style=""><a href="<?=$orderByLastUpdateURL?>"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</thead>

	<tfoot>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable <?=$orderIdClass?>" style=""><a href="<?=$orderByIdURL?>"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable <?=$orderDateClass?>" style=""><a href="<?=$orderByIdURL?>"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable <?=$orderStatusClass?>" style=""><a href="<?=$orderByStatusURL?>"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable <?=$orderLastUpdateClass?>" style=""><a href="<?=$orderByLastUpdateURL?>"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</tfoot>

	<tbody id="the-list">
<?
        if (sizeof($orderData) > 0) {

        foreach($orderData as $order) {
?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <th scope="row" class="check-column"><input name="post[]" value="1" type="checkbox"></th>
            <td class=""><strong><a class="" href="http://localhost/%7Ehendra/wp/wp-admin/post.php?post=1&amp;action=edit" title="Edit “Hello world!”"><?=$order->id?></a></strong></td>
            <td class="date column-date"><abbr title="<?=$order->dtcreated?>"><?=$order->dtcreated?></abbr></td>
            <td class="author column-author"><a href="edit.php?post_type=post&amp;author=1"><?=$order->getBuyerName()?></a></td>
            <td class="categories column-categories"><a href="edit.php?post_type=post&amp;category_name=uncategorized"><?=str_replace(",","<br/>",$order->getItemsSummary())?></a></td>
            <td class="tags column-tags"><?=$order->getShippingAddress()?></td>
            <td class="comments column-comments">
                <form id="wrg_order_status_form_<?=$order->id?>" name="wrg_order_status_form_<?=$order->id?>" method="POST">
                    <?wp_nonce_field('wrg_order_status_nonce')?>
                    <input type="hidden" name="wrg_order_status_id" value="<?=$order->id?>"/>
                    <?=WarungUtils::htmlSelect("wrg_order_status_status_".$order->id, "wrg_order_status_status", $orderStatuses, $order->status)?>
                    <input type="submit"value="Update" name="wrg_order_status_submit"/>
                </form>
            </td>
            <td class="date column-date"><abbr title="<?=$order->dtlastupdated?>"><?=$order->dtlastupdated?></abbr></td>
        </tr>
<?
        }

        } else {
            ?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <td colspan="8" style="text-align: center">Empty Order</td>
        </tr>
            <?
        }
?>
        </tbody>
    </table>
    <div class="tablenav"><?=$pageNav->show(' ', '«', '»')?></div>
</div>
<?
    }
}
?>
