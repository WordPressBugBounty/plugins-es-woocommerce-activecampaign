<?php
/*
Plugin Name: ES Woocommerce ActiveCampaign
Plugin URI: https://www.equalserving.com/wordpress-plugins-equalserving/woocommerce-with-activecampaign/
Description: Integrates Woocommerce with ActiveCampaign by adding customers to ActiveCampaign at time of purchase.
Version: 2.1.4
Author: EqualServing.com
Author URI: http://www.equalserving.com/
Tested up to: 6.4.3
WC requires at least: 3.6
WC tested up to: 8.6.1
*/

add_action( 'plugins_loaded', 'es_woocommerce_activecampaign_init', 0 );
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

function es_woocommerce_activecampaign_init() {

	if (!class_exists('WC_Integration')) {
		return;
	} else {
		include_once( 'class-es-wc-integration-activecampaign.php' );
		// Register the integration.
		add_filter('woocommerce_integrations', 'add_es_activecampaign_integration' );

	}

	/**
 	* Add the Integration to WooCommerce
 	**/

	function add_es_activecampaign_integration($methods) {
    	$methods[] = 'ESWC\\ES_WC_Integration_ActiveCampaign';
		return $methods;
	}

	/**
 	* Add the Setting link to the Plugins listing page
 	**/

	function es_activecampaign_action_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration&section=activecampaign' ) . '">' . __( 'Settings', 'es_wc_activecampaign' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );

	}

	/**
	* Add the "Settings" links on the Plugins administration screen
 	**/

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'es_activecampaign_action_links' );

	function es_activecampaign_plugin_update_message( $data, $response ) {
		if( isset( $data['upgrade_notice'] ) ) {
			printf( __( "<span style='padding-top:5px;margin-top:15px;display:block;border-top:1px solid'><strong>Please note:</strong> %s</span>", 'woocommerce' ), $data['upgrade_notice'] );
		}
	}
	add_action( 'in_plugin_update_message-es-woocommerce-activecampaign/es-woocommerce-activecampaign.php', 'es_activecampaign_plugin_update_message', 10, 2 );
}
