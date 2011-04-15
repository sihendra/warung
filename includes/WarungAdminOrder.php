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
        ?>
<div class="wrap">
    <h2>Order</h2>
    <table class="wp-list-table widefat">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=title&amp;order=asc"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=comment_count&amp;order=asc"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Date</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</thead>

	<tfoot>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=title&amp;order=asc"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=comment_count&amp;order=asc"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable asc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=date&amp;order=desc"><span>Date</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</tfoot>

	<tbody id="the-list">
<?
        foreach($orders as $order) {
?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <th scope="row" class="check-column"><input name="post[]" value="1" type="checkbox"></th>
            <td class=""><strong><a class="" href="http://localhost/%7Ehendra/wp/wp-admin/post.php?post=1&amp;action=edit" title="Edit “Hello world!”"><?=$order->id?></a></strong></td>
            <td class="author column-author"><a href="edit.php?post_type=post&amp;author=1"><?=$order->getBuyerName()?></a></td>
            <td class="categories column-categories"><a href="edit.php?post_type=post&amp;category_name=uncategorized"><?=$order->getItemsSummary()?></a></td>
            <td class="tags column-tags"><?=$order->getShippingAddress()?></td>
            <td class="comments column-comments">
                <a href="http://localhost/%7Ehendra/wp/wp-admin/edit-comments.php?p=1" title="0 pending" class="post-com-count"><span class="comment-count"><?=$order->status?></span></a>
            </td>
            <td class="date column-date"><abbr title="<?=$order->dtcreated?>"><?=$order->dtcreated?></abbr></td>
        </tr>
<?
        }
?>
        </tbody>
    </table>
</div>
<?
    }
}
?>
