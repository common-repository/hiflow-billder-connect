<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Billder_Connect_Settings' ) ) {

    class WC_Billder_Connect_Settings
    {

        public static function getProductName() {
            return ucfirst(strtolower(WC_BILLDER_CONNECT_PRODUCT));
        }

        public static function registerPage(){
            add_option('billder-connect',array());
            add_option('billder-settings',array());


            add_action('admin_menu', function() {
                add_submenu_page( 'woocommerce', self::getProductName().' Connect', self::getProductName(), 'manage_options', 'billder-connect-settings', function(){

                    include_once(plugin_dir_path(__DIR__).'views/billder-connect-settings.php');
                });
            },99);
        }

        public function checkCredentials($apikey){
            $connector = new WC_Billder_Connect_Gateway();
            return $connector->checkToken($apikey);
        }


        public function setCredentials($apikey){
            $payload = WC_Billder_Connect_Gateway::parseJWT($apikey);
            update_option('billder-connect',array('apikey' => $apikey,'account' => $payload['account']));
        }

        public function setSettings($settings = array()){
            update_option('billder-settings',$settings);
        }

        public function unsetCredentials(){
            delete_option('billder-connect');
            add_option('billder-connect',array());
        }

        public function getCredentials(){
            return get_option('billder-connect');
        }

        public function getSettings(){
            return get_option('billder-settings');
        }

        public function getConfig(){
            $connector = new WC_Billder_Connect_Gateway();
            return $connector->getConfig();
        }
    }
}