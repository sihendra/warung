<?php
/**
 * Base class for settingup plugins filter/actions
 *
 * @author hendra
 */
class Warung {
    public $pluginUrl;
    // name for our options in the DB
    public static $db_option = 'Warung_Options';


    function __construct() {
        $this->pluginUrl = trailingslashit(WP_PLUGIN_URL . '/warung');
    }

    function init() {
        session_start();

        // register content filter
        add_filter('the_content', array(&$this, 'filter_content'));

        // call the controller
        WarungController::process();
    }

    function init_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form'); //, $this->pluginUrl.'scripts/jquery.form.js',array('jquery'));
        wp_enqueue_script('jquery_validaton', $this->pluginUrl . '/scripts/jquery.validate.js', array('jquery'));
        wp_enqueue_script('warung_js', $this->pluginUrl . '/scripts/warung.js', array('jquery'));
    }

    function init_styles() {
        wp_enqueue_style('warung_style', $this->pluginUrl . '/style/warung.css');
    }

    function filter_content($content) {
        global $post;

        $wo = new WarungOptions();

        $co_page = $wo->getCheckoutPageId();
        $shipping_sim_page = $wo->getShippingSimPageId();

        if ($post->ID == $co_page) {
            $wiz = $wo->getCheckoutWizard();
            $content = $wiz->showPage();
        } else if ($post->ID == $shipping_sim_page) {
            $content .= WarungDisplay::shippingSimPage();
        } else {
            $content .= WarungDisplay::productOrderForm();
        }

        return $content;
    }

    function install() {
        $installed_ver = get_option( "warung_db_version" );

        // DB
        global $wpdb;
        //define the custom table name
        $table_name = $wpdb->prefix . "wrg_order";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `dtcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `status` varchar(30) NOT NULL DEFAULT 'order',
              `dtlastupdated` datetime NOT NULL,
              `items_price` int(11) NOT NULL,
              `shipping_price` int(11) NOT NULL,
              `dtpayment` datetime DEFAULT NULL,
              `dtdelivery` datetime DEFAULT NULL,
              `delivery_number` varchar(100) DEFAULT NULL,
              `shipping_weight` float NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }

        $table_name = $wpdb->prefix . "wrg_order_items";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `order_id` int(11) NOT NULL,
              `item_id` int(11) NOT NULL,
              `name` varchar(512) NOT NULL,
              `quantity` int(11) NOT NULL,
              `weight` float NOT NULL DEFAULT '0',
              `price` float NOT NULL DEFAULT '0',
              KEY `idx_wrg_order_items` (`order_id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }

        $table_name = $wpdb->prefix . "wrg_order_shipping";
        //set the table structure version
        $warung_db_version = "1.0";
        //verify the table doesn’t already exist
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            //build our query to create our new table
            $sql =
            "CREATE TABLE `$table_name` (
              `order_id` int(11) NOT NULL,
              `name` varchar(100) NOT NULL,
              `email` varchar(100) NOT NULL,
              `mobile_phone` int(11) DEFAULT NULL,
              `phone` int(11) DEFAULT NULL,
              `address` varchar(200) DEFAULT NULL,
              `city` varchar(100) DEFAULT NULL,
              `state` varchar(100) DEFAULT NULL,
              `country` varchar(100) DEFAULT NULL,
              `additional_info` varchar(200) DEFAULT NULL,
              PRIMARY KEY (`order_id`)
            ) ENGINE=InnoDB;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //execute the query creating our table
            dbDelta($sql);
            //save the table structure version number
            add_option("warung_db_version", $warung_db_version);
        }


    }

}
?>
