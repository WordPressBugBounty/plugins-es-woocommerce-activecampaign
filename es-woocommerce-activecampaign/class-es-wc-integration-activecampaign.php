<?php
namespace ESWC;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * ActiveCampaign Integration
 *
 * Allows integration with ActiveCampaign
 *
 * @class 		ES_WC_Integration_ActiveCampaign
 * @extends		WC_Integration
 * @version		2.1.5
 * @package		WooCommerce ActiveCampaign
 * @author 		EqualServing
 */

class ES_WC_Integration_ActiveCampaign extends \WC_Integration {

	protected $activecampaign_lists, $product_lists;
	public $id;
	public $method_title;
	public $method_description;
	public $error_msg;
	public $dependencies_found;
	public $params;
	public $activecampaign_url;
	public $activecampaign_key;
	public $activecampaign_tags_list;
	public $enabled;
	public $logdata;
	public $occurs;
	public $list;
	public $double_optin;
	public $groups;
	public $display_opt_in;
	public $opt_in_label;
	public $opt_in_position;
	public $purchased_product_tag_add;
	public $tag_purchased_products;
	public $contact_tag;

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */

	public function __construct() {

		$this->id					= 'activecampaign';
		$this->method_title     	= __( 'ActiveCampaign', 'es_wc_activecampaign' );
		$this->method_description	= __( 'ActiveCampaign is a marketing automation service.', 'es_wc_activecampaign' );
		$this->error_msg            = '';
		$this->dependencies_found = 1;
		$this->params               = $this->get_params();

		if ( !class_exists( '\ESWC\AC\ActiveCampaign' ) ) {
			include_once( 'includes/ActiveCampaign.class.php' );
		}

		$this->activecampaign_url = "";
		$this->activecampaign_key  = "";
		$this->set_url();
		$this->set_key();

		$this->activecampaign_lists = array();
		$this->activecampaign_tags_list = array();

		// Get setting values
		$this->enabled        = $this->get_option( 'enabled' );
		$this->logdata        = $this->get_option( 'logdata' );

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->occurs         = $this->get_option( 'occurs' );

		$this->list           = $this->get_option( 'list' );
		$this->double_optin   = $this->get_option( 'double_optin' );
		$this->groups         = $this->get_option( 'groups' );
		$this->display_opt_in = $this->get_option( 'display_opt_in' );
		$this->opt_in_label   = $this->get_option( 'opt_in_label' );
		$this->opt_in_position   = $this->get_option( 'opt_in_position' );
		$this->purchased_product_tag_add = $this->get_option('purchased_product_tag_add');
		if (empty(trim($this->get_option('purchased_product_tag_add')))) {
			$this->tag_purchased_products = "no";
		} else {
			$this->tag_purchased_products = "yes";
		}

		$this->contact_tag = $this->get_option('contact_tag');

		// Hooks
		add_action( 'admin_notices', array( &$this, 'checks' ) );
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options') );

		// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available,
		// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires at the end of the checkout process after the order meta has been saved
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'order_status_changed' ), 10, 1 );

		// hook into woocommerce order status changed hook to handle the desired subscription event trigger
		add_action( 'woocommerce_order_status_changed', array( &$this, 'order_status_changed' ), 10, 3 );

		if ($this->enabled == 'yes') {
			if ( 'yes' == $this->display_opt_in || 'uncheck' == $this->display_opt_in  || 'must' == $this->display_opt_in ) {
				if ($this->opt_in_position == 'yes') {
					add_action( 'woocommerce_before_order_notes', array( &$this, 'subscribe_checkbox') );
				} elseif ($this->opt_in_position == 'before_place_order') {
					add_action( 'woocommerce_review_order_before_submit', array( &$this, 'subscribe_checkbox') );
				} else {
					add_action( 'woocommerce_after_order_notes', array( &$this, 'subscribe_checkbox') );
				}
			}
		}

		// Maybe save the "opt-in" field on the checkout
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'maybe_save_checkout_fields' ) );

		// Display field value on the order edit page
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'checkout_field_display_admin_order_meta') );

	}

	/**
	 * Check if the user has enabled the plugin functionality, but hasn't provided an api key.
	 *
	 * @access public
	 * @return void
	 */

	public function checks() {
		global $pagenow;

		if ( $error = get_transient( "es_wc_activecampaign_errors" ) ) { ?>
		    <div class="error notice">
		        <p><strong>ActiveCampaign error</strong>: <?php echo $error; ?></p>
		    </div><?php

		    delete_transient("es_wc_activecampaign_errors");
		}

		if ($pagenow == "admin.php" && isset($_REQUEST["page"]) && $_REQUEST["page"] == "wc-settings" && isset($_REQUEST["tab"]) && $_REQUEST["tab"] == "integration") {
			if ( $this->enabled == 'yes' ) {
				// Check required fields
				if(!$this->has_api_info()) {
					if (!$this->has_key() && !$this->has_url()) {
						echo '<div class="error notice"><p>' . sprintf( __('<strong>ActiveCampaign error</strong>: Please enter your API URL and Key <a href="%s">here</a>', 'es_wc_activecampaign'), admin_url('options-general.php?page=activecampaign' ) ) . '</p></div>';
					} elseif (!$this->has_key() ) {
						echo '<div class="error notice"><p>' . sprintf( __('<strong>ActiveCampaign error</strong>: Please enter your API Key <a href="%s">here</a>', 'es_wc_activecampaign'), admin_url('options-general.php?page=activecampaign' ) ) . '</p></div>';
					} elseif (!$this->has_url()) {
						echo '<div class="error notice"><p>' . sprintf( __('<strong>ActiveCampaign error</strong>: Please enter your API Key <a href="%s">here</a>', 'es_wc_activecampaign'), admin_url('options-general.php?page=activecampaign' ) ) . '</p></div>';
					}
					return;
				} else {
					$this->set_url();
					$this->set_key();

					$request = add_query_arg(
						array(
							'api_output' => 'json',
							'api_key'    => $this->activecampaign_key,
							'api_action' => 'user_me'
						),
						$this->activecampaign_url . '/admin/api.php'
					);
					$params                            = $this->params;
					$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

					$response = wp_remote_post( $request, $params );
					$accepted = $this->ac_accepted_request($response);

					if ( !$accepted ) {

						$error_msg_object = $this->http_error_response($response);
						// Email admin
						$error_msg = sprintf( __( 'ActiveCampaign credentials check failed: %s', 'es_wc_activecampaign' ), $error_msg_object->get_error_message() ) ;
						$this->log_this("error", $error_msg);
						$this->error_msg = $msg;
						set_transient("es_wc_activecampaign_errors", $error_msg, 45);

						wp_mail( get_option('admin_email'), __( 'ActiveCampaign credentials check failed (ActiveCampaign)', 'es_wc_activecampaign' ), ' ' . $error_msg );
					}
				}
			}
		}
	}

	/**
	 * Get common params for the HTTP API
	 *
	 * @access public
	 * @return array Params
	 */

	public function get_params( $api_key = false ) {

		$params = array(
			'user-agent' => 'ESWCAC; ' . home_url(),
			'timeout'    => 10,
			'headers'    => array(
				'Content-Type' => 'application/json; charset=utf-8',
				'Api-Token'    => $api_key ? $api_key : $this->set_key(),
			),
		);

		$this->params = $params;

		return $params;

	}

	/**
	 * Check HTTP Response for errors and return WP_Error if found
	 *
	 * @access public
	 * @return HTTP Response
	 */

	public function http_error_response( $response ) {

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		$code = intval(wp_remote_retrieve_response_code( $response ));

		if ( isset( $body->errors ) ) {

			$response = new \WP_Error( $body->errors[0]->code, $body->errors[0]->title );

		} elseif ( isset( $body->error ) ) {

			if ( ! isset( $body->code ) ) {
				$body->code = 'error';
			}

			$response = new \WP_Error( $body->code, $body->message . ': ' . $body->error );

		} elseif ( isset( $body->result_code ) && 0 === $body->result_code ) {

			if ( false !== strpos( $body->result_message, 'Invalid contact ID' ) || false !== strpos( $body->result_message, 'Contact does not exist' ) ) {
				$code = 'not_found';
			} else {
				$code = 'error';
			}

			$response = new \WP_Error( $code, $body->result_message );

		} elseif ( 400 === $code ) {

			$response = new \WP_Error( 'error', 'Bad request (400).' );

		} elseif ( 402 === $code ) {

			$response = new \WP_Error( 'error', 'Payment required (402).' );

		} elseif ( 403 === $code ) {

			$response = new \WP_Error( 'error', 'Access denied (403). This usually means your ActiveCampaign API key is invalid or ActiveCampaign URL is incorrect.' );

		} elseif ( 404 === $code ) {

			if ( ! empty( $body ) && ! empty( $body->message ) ) {
				$message = $body->message;
			} else {
				$message = 'Not found (404)';
			}

			$response = new \WP_Error( 'not_found', $message );

		} elseif ( 500 === $code || 429 === $code ) {

			$response = new \WP_Error( 'error', sprintf( __( 'An error has occurred in the API server. [error %d]', 'es_wc_activecampaign' ), $code ) );

		} else {
			$response = new \WP_Error( 'error',  __( 'An error has occurred in the ActiveCampaign API server. No data returned.', 'es_wc_activecampaign' ) );
		}


		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			set_transient("es_wc_activecampaign_errors", $error_message, 45);
		}


		return $response;

	}

	/**
	 * order_status_changed function.
	 *
	 * @access public
	 * @return void
	 */

	public function order_status_changed( $id, $status = 'new', $new_status = 'pending' ) {

		if ($this->logdata == 'yes') {
			$this->log_this("debug", "order_status_changed entering function - id: ". $id." status: ".$status." new_status: ".$new_status." is_valid: ". $this->is_valid()." occurs: ".wc_print_r($this->occurs, TRUE)." in_array: ".(in_array($new_status, $this->occurs, TRUE) ? "TRUE" : "FALSE"));
		}

		if ( $this->is_valid() && in_array($new_status, $this->occurs, TRUE) ) {
			$order = wc_get_order($id);
			$order_data = $order->get_data();
			$item_details = $order->get_items();
			$order_key = $order->get_order_key();
			$payment_method = $order->get_payment_method();
			$order_opt_in = "no";
			if ( ( isset($_POST['es_wc_activecampaign_opt_in']) && $_POST['es_wc_activecampaign_opt_in']) ) {
				$order_opt_in = "yes";
			} else if ($order->get_meta('es_wc_activecampaign_opt_in') !== "") {
				// If the subscribe event is on Order Processing or Order Completed the variable "es_wc_activecampaign_opt_in" will not be in the POST. It must be retrieved from the order meta data.
				$order_opt_in = $order->get_meta('es_wc_activecampaign_opt_in');
			}
			if ($this->logdata == 'yes') {
				$this->log_this("debug", "order_status_changed isset(opt_in): ". isset($_POST['es_wc_activecampaign_opt_in'])." es_wc_activecampaign_opt_in: ".wc_print_r($order_opt_in)." order_opt_in: ".$order_opt_in." _POST: ".wc_print_r($_POST, true)." items: ".wc_print_r($item_details, TRUE));
			}


			// If customer checks newsletter opt or plugin setting was set to Hidden and checked by default
			if ( $order_opt_in == "yes" || (isset($this->display_opt_in) && ($this->display_opt_in == 'no' || $this->display_opt_in == 'must'))) {

				if ($this->logdata == 'yes') {
					$this->log_this("debug", "order_status_changed isset(opt_in): ". isset($_POST['es_wc_activecampaign_opt_in'])." es_wc_activecampaign_opt_in: ".wc_print_r($order_opt_in). " display_opt_in: ".$this->display_opt_in );
				}

				$this->subscribe(
					$order_data['billing']['first_name'],
					$order_data['billing']['last_name'],
					$order_data['billing']['email'],
					$order_data['billing']['phone'],
					$order_data['billing']['address_1'],
					$order_data['billing']['address_2'],
					$order_data['billing']['city'],
					$order_data['billing']['state'],
					$order_data['billing']['postcode'],
					$this->list,
					$item_details,
					$new_status,
					$payment_method,
					($order_opt_in == 'yes'));
			}
		}
	}

	/**
	 * has_list function - have the ActiveCampaign lists been retrieved.
	 *
	 * @access public
	 * @return boolean
	 */

	public function has_list() {
		if ( $this->list )
			return true;
	}

	/**
	 * has_appid function - has the ActiveCampaign URL and Key been entered.
	 *
	 * @access public
	 * @return boolean
	 */

	public function has_api_info() {
		if ( $this->activecampaign_url && $this->activecampaign_key )
			return true;
	}

	/**
	 * has_appid function - has the ActiveCampaign URL been entered.
	 *
	 * @access public
	 * @return boolean
	 */

	public function has_url() {
		if ( $this->activecampaign_url )
			return true;
	}

	/**
	 * set_url function - set the ActiveCampaign URL property.
	 *
	 * @access public
	 * @return void
	 */

	public function set_url() {

		if (get_option("woocommerce_activecampaign_settings")) {
			$ac_settings = get_option("woocommerce_activecampaign_settings");
			$this->activecampaign_url = $ac_settings["activecampaign_url"];
		}
	}

	/**
	 * has_key function - has the ActiveCampaign Key been entered.
	 *
	 * @access public
	 * @return boolean
	 */

	public function has_key() {
		if ( $this->activecampaign_key )
			return true;
	}

	/**
	 * set_key function - set the ActiveCampaign Key property.
	 *
	 * @access public
	 * @return void
	 */

	public function set_key() {

		if (get_option("woocommerce_activecampaign_settings")) {
			$ac_settings = get_option("woocommerce_activecampaign_settings");
			$this->activecampaign_key = $ac_settings["activecampaign_key"];
		}
	}

	/**
	 * is_valid function - is ActiveCampaign ready to accept information from the site.
	 *
	 * @access public
	 * @return boolean
	 */

	public function is_valid() {
		if ( $this->enabled == 'yes' && $this->has_api_info() && $this->has_list() ) {
			return true;
		}
		return false;
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */

	public function init_form_fields() {
		if ( is_admin() ) {
			if ($this->has_api_info()) {
				array_merge( array( '' => __('Select a list...', 'es_wc_activecampaign' ) ), $this->activecampaign_lists );
			} else {
				array( '' => __( 'Enter your key and save to see your lists', 'es_wc_activecampaign' ) );
			}
			if (get_option("woocommerce_activecampaign_settings")) {
				$ac_settings = get_option("woocommerce_activecampaign_settings");
				if ($ac_settings) {
					$default_ac_url = $ac_settings["activecampaign_url"];
					$default_ac_key = $ac_settings["activecampaign_key"];
				}

			// If ActiveCampaign's plugin is installed and configured, collect the URL and Key from their their plugin for the inital default values.
			} else if (get_option("settings_activecampaign")) {
				$ac_settings = get_option("settings_activecampaign");
				if ($ac_settings) {
					$default_ac_url = $ac_settings["api_url"];
					$default_ac_key = $ac_settings["api_key"];
				}
			} else {
				$default_ac_url = "";
				$default_ac_key = "";
			}

			$this->get_ac_lists();
			$this->get_ac_tags_list();

			$list_help = 'All customers will be added to this list. <a href="admin.php?'.http_build_query(array_merge($_GET, array("reset"=>"yes"))).'">Click here to reset lists and tags</a>.';
			if (empty($this->activecampaign_lists)) {
				$list_help .= '<br /><strong>NOTE: If this dowpdown list is empty AND you have entered the API URL and Key correctly, please save your settings and reload the page. <a href="'.$_SERVER['REQUEST_URI'].'">[Click Here]</a></strong>';
			}

			$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'es_wc_activecampaign' ),
								'label' => __( 'Enable ActiveCampaign', 'es_wc_activecampaign' ),
								'type' => 'checkbox',
								'description' => '',
								'default' => 'no',
							),
				'logdata' => array(
								'title' => __( 'Log API Data', 'es_wc_activecampaign' ),
								'label' => __( 'Enable Logging of API calls to ActiveCampaign', 'es_wc_activecampaign' ),
								'type' => 'checkbox',
								'description' => '',
								'default' => 'no',
							),
				'activecampaign_url' => array(
								'title' => __( 'ActiveCampaign API URL', 'es_wc_activecampaign' ),
								'type' => 'text',
								'description' => __( '<a href="http://www.activecampaign.com/help/using-the-api/" target="_blank">Login to activecampaign</a> to look up your api url.', 'es_wc_activecampaign' ),
								'default' => $default_ac_url
							),
				'activecampaign_key' => array(
								'title' => __( 'ActiveCampaign API Key', 'es_wc_activecampaign' ),
								'type' => 'text',
								'description' => __( '<a href="http://www.activecampaign.com/help/using-the-api/" target="_blank">Login to activecampaign</a> to look up your api key.', 'es_wc_activecampaign' ),
								'default' => $default_ac_key
							),
				'occurs' => array(
								'title' => __( 'Subscribe Event', 'es_wc_activecampaign' ),
								'type' => 'multiselect',
								'description' => __( 'When should customers be subscribed to lists?', 'es_wc_activecampaign' ),
								'css' => 'height: 100%;',
								'default' => 'pending',
								'options' => array(
									'pending' => __( 'Order Created', 'es_wc_activecampaign' ),
									'processing' => __( 'Order Processing', 'es_wc_activecampaign' ),
									'completed'  => __( 'Order Completed', 'es_wc_activecampaign' ),
									'on-hold'  => __( 'Order On Hold', 'es_wc_activecampaign' ),
									'cancelled'  => __( 'Order Cancelled', 'es_wc_activecampaign' ),
									'refunded'  => __( 'Order Refunded', 'es_wc_activecampaign' ),
									'failed'  => __( 'Order Failed', 'es_wc_activecampaign' ),
								),
							),
				'list' => array(
								'title' => __( 'Main List', 'es_wc_activecampaign' ),
								'type' => 'select',
								'description' => __( $list_help, 'es_wc_activecampaign' ),
								'default' => '',
								'options' => $this->activecampaign_lists,
							),
				'contact_tag' => array(
								'title' => __( 'Contact Tag', 'es_wc_activecampaign' ),
								'type' => 'select',
								'description' => __( 'When a WooCommerce customer is added to your ActiveCampaign contacts, you can tag them. Select the tag for this purpose. If you want to tag the contact for each product they purchase, please see Purchased Product Tags field below. ', 'es_wc_activecampaign' ),
								'default' => '',
								'options' => $this->activecampaign_tags_list,
							),

				'display_opt_in' => array(
								'title'       => __( 'Display Opt-In Field', 'es_wc_activecampaign' ),
								'label'       => __( 'Display an Opt-In Field on Checkout', 'es_wc_activecampaign' ),
								'type'        => 'select',
								'default'     => 'no',
								'description' => __( '<ul><li>If <strong>Visible, checked by default</strong> or <strong>Visible, unchecked by default</strong> is chosen, customers will be presented with an "Opt-in" checkbox during checkout and will ONLY be added to the <strong>Main List</strong> above IF they opt-in.</li><li>If <strong>Hidden, checked by default</strong> is chosen, ALL customers will be added to the <strong>Main List</strong>.</li><li>If <strong>Visible. Must collect email address to send essential product information. Unchecked by default.</strong> is chosen, the customer is added to ActiveCampaign but tagged <strong>newsletter_opt_out</strong></li></ul>', 'es_wc_activecampaign' ),
								'options' => array(
									'yes' => __( 'Visible, checked by default', 'es_wc_activecampaign' ),
									'uncheck' => __( 'Visible, unchecked by default', 'es_wc_activecampaign' ),
									'no'  => __( 'Hidden, checked by default', 'es_wc_activecampaign' ),
									'must'  => __( 'Visible. Must collect email address to send essential product information. Unchecked by default.', 'es_wc_activecampaign' ),
								),
							),

				'opt_in_label' => array(
								'title'       => __( 'Opt-In Field Label', 'es_wc_activecampaign' ),
								'type'        => 'text',
								'description' => __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'es_wc_activecampaign' ),
								'default'     => __( 'Add me to the newsletter (we will never share your email).', 'es_wc_activecampaign' ),
							),
				'opt_in_position' => array(
								'title'       => __( 'Opt-In Field Position Above Order Notes', 'es_wc_activecampaign' ),
								'type'        => 'select',
								'description' => __( 'By default, the Opt-In Field will appear below the Order Notes. If you would like the Opt-In Field to appear above the Order Notes, please select the option.', 'es_wc_activecampaign' ),
								'default'     => 'no',
								'options' => array(
									'no'  => __( 'Below the Order Notes', 'es_wc_activecampaign' ),
									'yes' => __( 'Above the Order Notes', 'es_wc_activecampaign' ),
									'before_place_order' => __( 'Before Place Order button', 'es_wc_activecampaign' ),
								),
							),
				'purchased_product_tag_add' => array(
								'title'       => __( 'Purchased Product Tags', 'es_wc_activecampaign' ),
								'type'        => 'text',
								'description' => __( 'Customers added to ActiveCampaign via WooCommerce can be tagged with this additional product-based information. Supported placeholders: <b>#NAME#, #STATUS#, #SKU#, #ID#, #QTY#, #IDWVAR#, #PAYMETHOD#, #CAT#, #TAG#</b>. If you want to tag your contacts with the product SKU of all the products they buy, just enter #SKU# in the field above. To tag customers with the product category, enter #CAT#.  To tag customers with the SKU and Order Status, enter "#SKU# - #STATUS#." To tag customers with both the product SKU and product category, enter "#SKU#, #CAT#". PLEASE NOTE the comma between the two placeholders. This will generate two separate tags. If the comma is omitted, one tag will be applied with the SKU and category name in it. If this field is left blank, NO tag will be applied.', 'es_wc_activecampaign' ),

								'default'     => __( '', 'es_wc_activecampaign' ),
								'desc_tip'    => __( 'Customers added to ActiveCampaign via WooCommerce can be tagged with this additional product-based information. Supported placeholders: <b>#NAME#, #STATUS#, #SKU#, #ID#, #QTY#, #IDWVAR#, #PAYMETHOD#, #CAT#, #TAG#</b>. If you want to tag your contacts with the product SKU of all the products they buy, just enter #SKU# in the field above. To tag customers with the product category, enter #CAT#.  To tag customers with the SKU and Order Status, enter "#SKU# - #STATUS#." To tag customers with both the product SKU and product category, enter "#SKU#, #CAT#". PLEASE NOTE the comma between the two placeholders. This will generate two separate tags. If the comma is omitted, one tag will be applied with the SKU and category name in it. If this field is left blank, NO tag will be applied.', 'es_wc_activecampaign'),

							),
			);
		}
	} // End init_form_fields()

	/**
	 * get_ac_lists function - retrieve the active lists created in ActiveCampaign.
	 *
	 * @access public
	 * @return void
	 */

	public function get_ac_lists() {
		if ( is_admin() && $this->has_api_info() ) {
			if (isset($_REQUEST["reset"]) && $_REQUEST["reset"] == "yes") {
				delete_transient( 'es_wc_activecampaign_list_' . md5( $this->activecampaign_key ) );
			}
			if ( ! get_transient( 'es_wc_activecampaign_list_' . md5( $this->activecampaign_key ) ) ) {

				$api_action = 'list_list';
				$request = add_query_arg(
					array(
						'api_output' => 'json',
						'api_key'    => $this->activecampaign_key,
						'api_action' => $api_action,
						'ids'        => 'all'
					),
					$this->activecampaign_url . '/admin/api.php'
				);
				$params                            = $this->params;
				$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

				$response = wp_remote_post( $request, $params );
				$accepted = $this->ac_accepted_request($response);

				if ( $accepted ) {

					$retval = json_decode( wp_remote_retrieve_body( $response ) );

					if ($this->logdata == 'yes') {
						$total = count((array)$retval);
						$this->log_this("debug", "get_ac_lists (list_list) number of elements returned: ". $total);
					}

					$this->activecampaign_lists = array();

					if ($retval && is_object($retval)) {
						foreach ( $retval as $list ) {
							if (is_object($list)) {
								$this->activecampaign_lists["es|".$list->id ] = $list->name;
							}
						}
					}
					if ( sizeof( $this->activecampaign_lists ) > 0 )
						set_transient( 'es_wc_activecampaign_list_' . md5( $this->activecampaign_key ), $this->activecampaign_lists, 60*60*1 );

				} else {

					$error_msg_object = $this->http_error_response($response);
					// Email admin
					$error_msg = sprintf( __( 'Unable to retrieve lists from ActiveCampaign: %s', 'es_wc_activecampaign' ), $error_msg_object->get_error_message() ) ;
					$this->log_this("error", $error_msg);
					$this->error_msg = $error_msg;
					set_transient("es_wc_activecampaign_errors", $error_msg, 45);

					wp_mail( get_option('admin_email'), __( 'Retrieve lists failed (ActiveCampaign)', 'es_wc_activecampaign' ), ' ' . $error_msg );
					if ($this->logdata == 'yes') {
						$total = count((array)$retval);
						$this->log_this("debug", __FUNCTION__. " (". $api_action .") number of elements returned: ". $total);
					}


				}

			} else {
				$this->activecampaign_lists = get_transient( 'es_wc_activecampaign_list_' . md5( $this->activecampaign_key ));
			}
		}
	}


	/**
	 * get_ac_lists function - retrieve the active lists created in ActiveCampaign.
	 *
	 * @access public
	 * @return void
	 */

	public function get_ac_tags_list() {
		if ( is_admin() && $this->has_api_info() ) {
			if (isset($_REQUEST["reset"]) && $_REQUEST["reset"] == "yes") {
				delete_transient( 'es_wc_activecampaign_tags_list_' . md5( $this->activecampaign_key ) );
			}
			if ( ! get_transient( 'es_wc_activecampaign_tags_list_' . md5( $this->activecampaign_key ) ) ) {

				$this->activecampaign_tags_list = array();
				$this->activecampaign_tags_list[""] = "Do not apply a tag";
				$api_action = 'tags_list';

				$request = add_query_arg(
					array(
						'api_output' => 'json',
						'api_key'    => $this->activecampaign_key,
						'api_action' => $api_action
					),
					$this->activecampaign_url . '/admin/api.php'
				);
				$params                            = $this->params;
				$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

				$response = wp_remote_post( $request, $params );
				$accepted = $this->ac_accepted_request($response);

				if ( $accepted ) {

					$retval = json_decode( wp_remote_retrieve_body( $response ) );

					if ($this->logdata == 'yes') {
						$total = count((array)$retval);
						$this->log_this("debug", __FUNCTION__. " (". $api_action .") number of elements returned: ". $total);
					}

					if ($retval && is_array($retval)) {
						foreach ( $retval as $list ) {
							if (is_object($list)) {
								$this->activecampaign_tags_list[$list->name ] = $list->name;
							}
						}
					} else {

						if (isset($retval->result_message)) {
							$error_msg = sprintf( __( 'Unable to retrieve tags list from ActiveCampaign: %s', 'es_wc_activecampaign' ), $retval->result_message );
						} else {
							$error_msg = sprintf( __( 'Unable to retrieve tags list from ActiveCampaign: %s', 'es_wc_activecampaign' ), wc_print_r($retval, true) );
						}
						$this->log_this("error", $error_msg);

						set_transient("es_wc_activecampaign_errors", $error_msg, 45);

					}


					if ( sizeof( $this->activecampaign_tags_list ) > 0 )
						set_transient( 'es_wc_activecampaign_tags_list_' . md5( $this->activecampaign_key ), $this->activecampaign_tags_list, 60*60*1 );

				} else {

					$error_msg_object = $this->http_error_response($response);
					// Email admin
					$error_msg = sprintf( __( 'Unable to retrieve tags list from ActiveCampaign: %s', 'es_wc_activecampaign' ), $error_msg_object->get_error_message() ) ;
					$this->log_this("error", $error_msg);
					$this->error_msg = $error_msg;
					set_transient("es_wc_activecampaign_errors", $error_msg, 45);

					wp_mail( get_option('admin_email'), __( 'Retrieve tags list failed (ActiveCampaign)', 'es_wc_activecampaign' ), ' ' . $error_msg );
					if ($this->logdata == 'yes') {
						$total = count((array)$retval);
						$this->log_this("debug", __FUNCTION__. " (". $api_action .") number of elements returned: ". $total);
					}
				}

			} else {
				$this->activecampaign_tags_list = get_transient( 'es_wc_activecampaign_tags_list_' . md5( $this->activecampaign_key ));
			}
		}
	}

	/**
	 * subscribe function - customer will be subscribed to selected list, if the
	 *                      option to tag the customer with products purchased, that too will
	 *                      be done.
	 *
	 * @access public
	 * @param mixed $first_name
	 * @param mixed $last_name
	 * @param mixed $email
	 * @param mixed $address_1
	 * @param mixed $address_2
	 * @param mixed $city
	 * @param mixed $state
	 * @param mixed $zip
	 * @param string $listid (default: 'false')
	 * @param object $items
	 * @param mixed $status
	 * @param mixed $payment_method
	 * @param mixed $newsletter_opt_in
	 * @return void
	 */

	public function subscribe( $first_name, $last_name, $email, $phone = null, $address_1 = null, $address_2 = null, $city = null, $state = null, $zip = null, $listid = false, $items = array(), $status = null, $payment_method = null, $newsletter_opt_in = null) {

		if($this->has_api_info()) {

			if ( $listid == false )
				$listid = $this->list;

			if ( !$email || !$listid || !$this->enabled )
				return; // Email and listid is required

			$data = array(
		    	'email'			=> $email,
		    	'first_name'	=> $first_name,
		    	'last_name'		=> $last_name,
		    	'phone'			=> $phone
			);

			$tags = array();
			if ($newsletter_opt_in) {
				$tags[] = 'newsletter_opt_in';
			}

			$api_action = 'contact_sync';
			$request = add_query_arg(
				array(
					'api_output' 	=> 'json',
					'api_key'    	=> $this->activecampaign_key,
					'api_action' 	=> $api_action
				),
				$this->activecampaign_url . '/admin/api.php'
			);
			$params                            	= $this->params;
			$params['headers']['Content-Type'] 	= 'application/x-www-form-urlencoded';
			$params['body']						= $data;

			$response = wp_remote_post( $request, $params );
			$accepted = $this->ac_accepted_request($response);

			if ( $accepted ) {

				if ($this->logdata == 'yes') {
					$this->log_this("debug", __FUNCTION__. " line: " .__LINE__.  " (". $api_action .") post: ".wc_print_r($data, true) ." retval: ". wc_print_r($response, true));
				}

				if (isset($listid) && $listid != "") {
					$listid = ltrim($listid, "es|");

					$data = array(
					    'email'				=> $email,
						"p[{$listid}]" 		=> urlencode($listid),
						"status[{$listid}]" => 1, // "Active" status
					);
					$request = add_query_arg(
						array(
							'api_output' 		=> 'json',
							'api_key'    		=> $this->activecampaign_key,
							'api_action' 		=> $api_action,
						),
						$this->activecampaign_url . '/admin/api.php'
					);
					$params                            	= $this->params;
					$params['headers']['Content-Type'] 	= 'application/x-www-form-urlencoded';
					$params['body']						= $data;

					$response = wp_remote_post( $request, $params );
					$accepted = $this->ac_accepted_request($response);

					if ($this->logdata == 'yes') {
						$this->log_this("debug", __FUNCTION__. " line: " .__LINE__.  " (". $api_action .") post: ".wc_print_r($data, true) ." retval: ". wc_print_r($response, true));
					}
				}
				if ($accepted) {
					$this->log_this("debug", __FUNCTION__. " line: " .__LINE__);

					if ( !empty($this->contact_tag) ) {
						$tags[] = $this->contact_tag;
					}
					$this->log_this("debug", __FUNCTION__. " line: " .__LINE__. "tags[] ".wc_print_r($tags, true));

					if ( $this->tag_purchased_products == 'yes' ) {
						if ( !empty($items) ) {

							foreach ( $items as $item ) {
								$purchased_product_id = $item['product_id'];
								if ($item['variation_id']) {
									$product_details = wc_get_product( $item['variation_id'] );
									$idwvar = $item['product_id']." / ".$item['variation_id'];
								} else {
									$product_details = wc_get_product( $item['product_id'] );
									$idwvar = $item['product_id'];
								}

								$tag_formats = explode(",", $this->purchased_product_tag_add);
								if (!empty($tag_formats) ) {

									$search = array("#NAME#", "#STATUS#", "#SKU#", "#ID#", "#QTY#", '#IDWVAR#', '#PAYMETHOD#');
									$replace = array($product_details->get_name(), $status, $product_details->get_sku(), $item['product_id'], $item['quantity'], $idwvar, $payment_method );
									foreach ($tag_formats as $tag_format) {
										$tag_format = str_replace($search, $replace, $tag_format);

										// Replace product category
										$cpos = strpos($tag_format, "#CAT#");
										if ($cpos !== false) {

											$terms = wp_get_post_terms( $item['product_id'], 'product_cat', array( 'fields' => 'names' ) );
											foreach ($terms as $term) {
												$tags[] = str_replace("#CAT#", $term, $tag_format);
											}
										}
										// Replace product tags
										$tpos = strpos($tag_format, "#TAG#");
										if ($tpos !== false) {

											$terms = wp_get_post_terms( $item['product_id'], 'product_tag', array( 'fields' => 'names' ) );
											foreach ($terms as $term) {
												$tags[] = str_replace("#TAG#", $term, $tag_format);
											}
										}

										if ($cpos === FALSE && $tpos === FALSE) {
											$tags[] = $tag_format;
										}
									}
								}

								$email_text = 'Product id: '.$item['product_id'].' sku: '.$product_details->get_sku().' categories: ';
								$terms = wp_get_post_terms( $item['product_id'], 'product_cat', array( 'fields' => 'names' ) );
								foreach ($terms as $term) {
									$email_text .= $term.', ';
								}
								$email_text = ' tags: ';
								$terms = wp_get_post_terms( $item['product_id'], 'product_tag', array( 'fields' => 'names' ) );
								foreach ($terms as $term) {
									$email_text .= $term.', ';
								}

							}
						}
					}
					$this->log_this("debug", __FUNCTION__. " line: " .__LINE__. "tags[] ".wc_print_r($tags, true));

					if (!empty($tags)) {

						$data = array(
							"email" 	=> $email,
							"tags" 		=> $tags
						);
						$api_action = 'contact_tag_add';
						$request = add_query_arg(
							array(
								'api_output' 	=> 'json',
								'api_key'    	=> $this->activecampaign_key,
								'api_action' 	=> $api_action
							),
							$this->activecampaign_url . '/admin/api.php'
						);
						$params                            	= $this->params;
						$params['headers']['Content-Type'] 	= 'application/x-www-form-urlencoded';
						$params['body']						= $data;

						$response = wp_remote_post( $request, $params );
						$accepted = $this->ac_accepted_request($response);

						if ($this->logdata == 'yes') {
							$this->log_this("debug", __FUNCTION__. " line: " .__LINE__.  " (". $api_action .") post: ".wc_print_r($data, true) ." retval: ". wc_print_r($response, true));
						}

						if (!$accepted) {

							$error_msg_object = $this->http_error_response($response);
							// Email admin
							$error_msg = sprintf( __( 'Unable to tag contact in ActiveCampaign: %s', 'es_wc_activecampaign' ), $error_msg_object->get_error_message() ) ."\n".wc_print_r($request, true) ;
							$this->log_this("error", $error_msg);

							wp_mail( get_option('admin_email'), __( 'Tag contact failed (ActiveCampaign)', 'es_wc_activecampaign' ), ' ' . $error_msg );

						}
					}

				} else {
					$error_msg_object = $this->http_error_response($response);
					// Email admin
					$error_msg = sprintf( __( "Unable to add contact to list from ActiveCampaign: %s \n", 'es_wc_activecampaign' ), $retval->result_message ) .wc_print_r($request, true);
					$this->log_this("error", $error_msg." retval: ". wc_print_r($retval, true));

					wp_mail( get_option('admin_email'), __( 'Subscribe contact failed (ActiveCampaign)', 'es_wc_activecampaign' ), ' ' . $error_msg );

				}

			} else {
				$error_msg_object = $this->http_error_response($response);
				// Email admin
				$error_msg = sprintf( __( 'Unable to create/update contact in ActiveCampaign: %s', 'es_wc_activecampaign' ), $error_msg_object->get_error_message() ).wc_print_r($request, true) ;
				$this->log_this("error", $error_msg);
				$this->error_msg = $error_msg;
				set_transient("es_wc_activecampaign_errors", $error_msg, 45);
				wp_mail( get_option('admin_email'), __( $error_msg, 'es_wc_activecampaign' ), ' ' . $error_msg );
			}
		}
	}

	/**
	 * Admin Panel Options
	 */

	function admin_options() {
		echo '<table><tboby><tr><td>';
		echo '<div class="column-2">';
		echo '<h3>';
		_e( 'ActiveCampaign', 'es_wc_activecampaign' );
		echo '</h3>';
		if ($this->dependencies_found) {
			echo '<p>';
			_e( 'Enter your ActiveCampaign settings below to control how WooCommerce integrates with your ActiveCampaign lists.', 'es_wc_activecampaign' );
			echo '</p>';
    		echo '<table class="form-table">';
	    	$this->generate_settings_html();
			echo '</table><!--/.form-table-->';
		} else {
			echo "<p>".$this->error_msg."</p>";
		}
		echo '</div>';
		echo '</td><td width="130" style="vertical-align:top;text-align:center;border-left: 1px solid #cdd0d4;border-bottom: 1px solid #cdd0d4;">';
		echo '<span style="display:block;vertical-align:top;text-align:center;border-bottom:rgb(204, 204, 204) 1px solid;padding:10px 5px 20px 5px;margin:20px 0;background-color:#3576ba;"><h3 style="color:#fff;">Having problems?</h3><ul><li>
		<a target="_blank" href="https://www.equalserving.com/how-to-configure-our-woocommerce-activecampaign-plugin/" style="color:#fff;">Configuration instructions.</a></li><li>
		<a target="_blank" href="https://equalserving.com/support" style="color:#fff;">Check our Knowledge Base for solutions.</a></li></ul></span>';
		echo '<span style="vertical-align:top;text-align:center;border-bottom:rgb(204, 204, 204) 1px solid;padding:10px; margin:10px 0;"><a target="_blank" href="https://shareasale.com/r.cfm?b=2176762&amp;u=651899&amp;m=41388&amp;urllink=&amp;afftrack="><img src="https://static.shareasale.com/image/41388/WPE-Product-Ecomm-Banner-AffiliateAds_01.png" border="0" /></a></span>';
		echo '<span style="vertical-align:top;text-align:center;border-bottom:rgb(204, 204, 204) 1px solid;padding:10px; margin:10px 0;"><a target="_blank" href="https://shareasale.com/r.cfm?b=1081125&amp;u=651899&amp;m=74778&amp;urllink=&amp;afftrack="><img src="https://static.shareasale.com/image/74778/300x2501.jpg" border="0" alt="WP Rocket - WordPress Caching Plugin" /></a></span>';
		echo '<span style="display:block;vertical-align:top;text-align:center;border-bottom:rgb(204, 204, 204) 1px solid;padding:10px;;margin:10px 0"><a href="https://www.siteground.com/go/i1yy4q30p0" target="_blank"><img border="0" src="https://uapi.siteground.com/img/affiliate/en/USD/general_EN_USD_woocommerce-medium-rectangle-violet.jpg" alt="SiteGround Web Hosting"></a></span>';
//		echo '<span style="display:block;vertical-align:top;text-align:center;border-bottom:rgb(204, 204, 204) 1px solid;padding:10px;;margin:10px 0"><a href="https://be.elementor.com/visit/?bta=5797&nci=5383" target="_blank"><img src="https://equalserving.s3.amazonaws.com/wp-content/uploads/2022/04/05204901/elementor-Intuitive-pink-360x300-1.jpg" alt="Elementor Page Builder" width="300" border="0"></a></span>';
		echo '<p style="text-align:left;padding:10px;"><small><b>Disclaimer:</b> At no additional cost to you, EqualServing may earn a small commission if you click-through and make a purchase of the above product or service. We only feature products that we believe in and use. Your support means the world to us and allows us to continue supporting this plugin. Thank you!</small></p>';
		echo '</td></tr></tbody></table>';

	}

	/**
	 * opt-in function - Add the opt-in checkbox to the checkout fields (to be displayed on checkout).
	 */

	function subscribe_checkbox( $checkout ) {

		if (empty($checkout)) {
			woocommerce_form_field( 'es_wc_activecampaign_opt_in', array(
				'type'	=> 'checkbox',
				'class'	=> array('eswcac-field form-row-wide'),
				'label'	=> esc_attr( $this->opt_in_label ),
				'default' => ('yes' == $this->display_opt_in ? 1 : 0),
				)
			);

		} else {
			woocommerce_form_field( 'es_wc_activecampaign_opt_in', array(
				'type'	=> 'checkbox',
				'class'	=> array('eswcac-field form-row-wide'),
				'label'	=> esc_attr( $this->opt_in_label ),
				'default' => ('yes' == $this->display_opt_in ? 1 : 0),
				), $checkout->get_value( 'es_wc_activecampaign_opt_in' )
			);

		}

	}

	/**
	 * save opt-in function - When the checkout form is submitted, save opt-in value.
	 *
	 * @access public
	 * @param integer $order_id
	 * @return void
	 */

	function maybe_save_checkout_fields( $order_id ) {
		$opt_in = isset( $_POST['es_wc_activecampaign_opt_in'] ) ? 'yes' : 'no';
		update_post_meta( $order_id, 'es_wc_activecampaign_opt_in', $opt_in );
	}

	/**
	 * display opt-in function - Display the opt-in value on the order details.
	 *
	 * @access public
	 * @param mixed $order
	 * @return void
	 */

	function checkout_field_display_admin_order_meta($order){
    	echo '<p><strong>'.__('ActiveCampaign Subscribe Opt In').':</strong> <br/>' . get_post_meta( $order->get_id(), 'es_wc_activecampaign_opt_in', true ) . '</p>';
	}

	function log_this($type, $msg) {
		$logger = wc_get_logger();
 		$logger_context = array( 'source' => $this->id );
		$logger->log( $type, $msg, $logger_context );
	}

	function ac_accepted_request($response) {
		$retval = TRUE;

		$code = intval(wp_remote_retrieve_response_code( $response ));
		if ($code === 200) {
			$body_object = json_decode($response["body"]);
			if (isset($body_object->response_code) && $body_object->response_code === 0) {
				$this->log_this("error", "URL: ".$response["url"]." Result_code: ".$body_object->result_code." Result_message: ".$body_object->result_message);
				$retval = FALSE;
			}

		} else {
			$retval = FALSE;
		}

		return $retval;
	}

}
