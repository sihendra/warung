<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungAdmnOrder
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

        // nav
        $page = 1;
        if (isset($_REQUEST['wrg_order_page'])) {
            $page = $_REQUEST['wrg_order_page'];
        }

        $orders = $orderService->getAllOrders(5, $page);
        $orderData = array();
        if (isset($orders['data'])) {
            $orderData = $orders['data'];
        }

        $pageNav = new PageNav('wrg_order_page', $orders);

        $orderStatuses = $orderService->getAllStatus();
        
        ?>
<div class="wrap">
    <h2>Order</h2>
    <div class="tablenav"><?=$pageNav->show(' ', '«', '»')?></div>
    <div class="clear"></div>
    <table class="wp-list-table widefat">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=title&amp;order=asc"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=comment_count&amp;order=asc"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</thead>

	<tfoot>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=title&amp;order=asc"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=comment_count&amp;order=asc"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</tfoot>

	<tbody id="the-list">
<?
        foreach($orderData as $order) {
?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <th scope="row" class="check-column"><input name="post[]" value="1" type="checkbox"></th>
            <td class=""><strong><a class="" href="http://localhost/%7Ehendra/wp/wp-admin/post.php?post=1&amp;action=edit" title="Edit “Hello world!”"><?=$order->id?></a></strong></td>
            <td class="date column-date"><abbr title="<?=$order->dtcreated?>"><?=$order->dtcreated?></abbr></td>
            <td class="author column-author"><a href="edit.php?post_type=post&amp;author=1"><?=$order->getBuyerName()?></a></td>
            <td class="categories column-categories"><a href="edit.php?post_type=post&amp;category_name=uncategorized"><?=$order->getItemsSummary()?></a></td>
            <td class="tags column-tags"><?=$order->getShippingAddress()?></td>
            <td class="comments column-comments">
                <form id="wrg_order_status_form_<?=$order->id?>" name="wrg_order_status_form_<?=$order->id?>" method="POST">
                    <?wp_nonce_field('wrg_order_status_nonce')?>
                    <input type="hidden" name="wrg_order_status_id" value="<?=$order->id?>"/>
                    <?=HTMLUtil::select("wrg_order_status_status_".$order->id, "wrg_order_status_status", $orderStatuses, $order->status)?>
                    <input type="submit"value="Update" name="wrg_order_status_submit"/>
                </form>
            </td>
            <td class="date column-date"><abbr title="<?=$order->dtlastupdated?>"><?=$order->dtlastupdated?></abbr></td>
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
