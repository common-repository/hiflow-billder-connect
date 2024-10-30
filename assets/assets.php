<?php


function enqueue_scripts()
{

    wp_enqueue_media();
    wp_enqueue_style( 'bmsde-styles',plugin_dir_url( __FILE__ ) . '/css/'.strtolower(WC_BILLDER_CONNECT_PRODUCT).'.css');
    wp_enqueue_script( 'bmsde-scripts', plugin_dir_url( __FILE__ ) . '/js/'.'main.js', array( 'jquery' ) );

}

add_action('admin_enqueue_scripts', 'enqueue_scripts');

