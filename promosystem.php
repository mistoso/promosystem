<?php
/*
Plugin Name: Promo-System
Description: API for Promo-System
Version: 1.0
Author: Misha
Author URI: http://promo-system.com/
Plugin URI: http://plugin.promo-system.com/plugin-part-one
*/

define('PS_DIR', plugin_dir_path(__FILE__));
define('PS_URL', plugin_dir_url(__FILE__));
define( 'PS_VERSION', '0.1' );
define( 'PS__MINIMUM_WP_VERSION', '3.7' );


function promosystem_load(){
    require_once(PS_DIR.'includes/class.promosystem.php');
    require_once(PS_DIR.'includes/class.promosystem-widget.php');
}

promosystem_load();

register_activation_hook( __FILE__, array( 'Promosystem', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Promosystem', 'plugin_deactivation' ) );

add_action( 'init', array( 'Promosystem', 'init' ) );

if (isset($_POST['code']) ){
    print_r($_POST);
    do_action( 'ps_code_check', $_POST );
    
}