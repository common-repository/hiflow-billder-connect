<?php
/*
    Plugin Name: Hiflow & Billder Connect
    Plugin URI: https://doc.brainmade.io/doc/connectez-woocommerce-a-billder-ou-hiflow/
    Description: Allow you to connect to your Hiflow or Billder account and manage your orders invoices.
    Author: Brainmade Solutions
    Domain Path: /languages/
    Version: 0.1.2
    Author URI: https://www.brainmade.io/solutions
 */

/**
 * @package hiflow-billder-connect
 * @version 0.1.2
 */


define('WC_BILLDER_CONNECT_PRODUCT','BILLDER');
define('WC_BILLDER_CONNECT_DOMAIN','hiflow-billder-connect');


if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require_once( plugin_basename( 'classes/class-billder-connect-order.php' ) );
require_once( plugin_basename( 'classes/class-billder-connect-gateway.php' ) );

require_once( plugin_basename( 'classes/class-billder-connect-vattypes.php' ) );
require_once( plugin_basename( 'classes/class-billder-connect-settings.php' ) );
require_once( plugin_basename( 'classes/class-billder-connect-logs.php' ) );

require_once( plugin_basename( 'assets/assets.php' ) );




function WC_billder_connect_missing_wc_notice() {
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . __( 'Hiflow & Billder Connect requires WooCommerce to be installed and active. You can download %s here.', WC_BILLDER_CONNECT_DOMAIN ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'WC_billder_connect_init' );

function WC_billder_connect_init() {
    load_plugin_textdomain( WC_BILLDER_CONNECT_DOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'WC_billder_connect_missing_wc_notice' );
        return;
    }


    WC_Billder_Connect_Settings::registerPage();
    WC_Billder_Connect_Logs::registerPage();
    WC_Billder_Connect_Logs::creatingLogsTable();

    WC_Billder_Connect_Order::addWidgetInAdminOrder();
    WC_Billder_Connect_Order::handleValidPayment();
    WC_Billder_Connect_Order::creatingBillderAccountAssociationTable();

    // update vat types from Billder
    WC_Billder_Connect_Vattypes::registerVatTable();
    WC_Billder_Connect_Vattypes::updateVatTypes();

}

