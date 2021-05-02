<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_Triggers' ) ){

	class WP_Webhooks_EDD_Triggers{

		/**
		 * Preserver certain values
		 *
		 * @var array
		 */
		private $pre_trigger_values = array();

		public function __construct() {

			//Init triggers
			add_action( 'plugins_loaded', array( $this, 'add_webhook_triggers' ), 10 );
			add_filter( 'wpwhpro/webhooks/get_webhooks_triggers', array( $this, 'add_webhook_triggers_content' ), 10 );

		}

		/**
		 * ######################
		 * ###
		 * #### WEBHOOK TRIGGERS
		 * ###
		 * ######################
		 */

		/*
         * Regsiter all available webhook triggers
         */
		public function add_webhook_triggers_content( $triggers ){

			$triggers[] = $this->trigger_edd_new_customer_content();
			$triggers[] = $this->trigger_edd_update_customer_content();
			$triggers[] = $this->trigger_edd_payments_content();
			$triggers[] = $this->trigger_edd_file_downloaded_content();

			return $triggers;
		}

		/*
         * Add the specified webhook triggers logic.
         * We also add the demo functionality here
         */
		public function add_webhook_triggers() {

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_new_customer' ) ) ){
				add_action( 'edd_customer_post_create', array( $this, 'wpwh_trigger_edd_new_customer_init' ), 10, 2 );
				add_filter( 'ironikus_demo_test_edd_new_customer', array( $this, 'wpwh_send_demo_edd_new_customer' ), 10, 3 );
			}

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_update_customer' ) ) ){
				add_action( 'edd_customer_post_update', array( $this, 'wpwh_trigger_edd_update_customer_init' ), 10, 3 );
				add_filter( 'ironikus_demo_test_edd_update_customer', array( $this, 'wpwh_send_demo_edd_update_customer' ), 10, 3 );
			}

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_payments' ) ) ){
				add_action( 'edd_payment_delete', array( $this, 'wpwh_trigger_edd_payments_delete_prepare' ), 10, 1 );
				add_action( 'edd_update_payment_status', array( $this, 'wpwh_trigger_edd_payments_init' ), 10, 3 );
				add_filter( 'ironikus_demo_test_edd_payments', array( $this, 'wpwh_send_demo_edd_payments' ), 10, 3 );
			}

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_file_downloaded' ) ) ){
				add_action( 'edd_process_verified_download', array( $this, 'wpwh_trigger_edd_file_downloaded' ), 10, 4 );
				add_filter( 'ironikus_demo_test_edd_file_downloaded', array( $this, 'wpwh_send_demo_edd_file_downloaded' ), 10, 3 );
			}

		}

		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD New Customer
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_new_customer_content(){

			$parameter = array(
				'first_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer.', 'trigger-edd_new_customer-content' ) ),
				'last_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer.', 'trigger-edd_new_customer-content' ) ),
				'id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The unique id of the customer. (This is not the user id)', 'trigger-edd_new_customer-content' ) ),
				'purchase_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of purchases of the customer.', 'trigger-edd_new_customer-content' ) ),
				'purchase_value' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The value of all purchases of the customer.', 'trigger-edd_new_customer-content' ) ),
				'email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The main email of the customer.', 'trigger-edd_new_customer-content' ) ),
				'emails' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Additional emails of the customer.', 'trigger-edd_new_customer-content' ) ),
				'name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full name of the customer.', 'trigger-edd_new_customer-content' ) ),
				'date_created' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date and time of the user creation in SQL format.', 'trigger-edd_new_customer-content' ) ),
				'payment_ids' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comme-separated list of payment ids.', 'trigger-edd_new_customer-content' ) ),
				'user_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The user id of the customer.', 'trigger-edd_new_customer-content' ) ),
				'notes' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Additional ntes given by the customer.', 'trigger-edd_new_customer-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_new_customer.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_new_customer',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD New Customer', 'trigger-edd_new_customer-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_new_customer( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires as soon as a new customer is created within Easy Digital Downloads.', 'trigger-edd_new_customer-content' ),
				'description' => $description,
				'callback' => 'test_edd_new_customer'
			);

		}

		/*
		* Register the post delete trigger logic
		*/
		public function wpwh_trigger_edd_new_customer_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_new_customer' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD customer was created
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_new_customer( $customer_id = 0, $args = array() ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_new_customer' );

			if( ! function_exists( 'EDD_Customer' ) ){
				return;
			}
			
			$customer = new EDD_Customer( $customer_id );

			//Properly calculate names as given by the Zapier extension
			$first_name = '';
			$last_name = '';
			if( isset( $customer->name ) ){
				$separated_names = explode( ' ', $customer->name );

				$first_name = ( ! empty( $separated_names[0] ) ) ? $separated_names[0] : '';

				if( ! empty( $separated_names[1] ) ) {
					unset( $separated_names[0] );
					$last_name = implode( ' ', $separated_names );
				}
			}
			$customer->first_name = $first_name;
			$customer->last_name  = $last_name;

			$response_data_array = array();

			foreach( $webhooks as $webhook ){

				$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

				if( $webhook_url_name !== null ){
					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $customer );
				} else {
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $customer );
				}

			}

			do_action( 'wpwhpro/webhooks/trigger_edd_new_customer', $customer_id, $customer, $response_data_array );
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_new_customer( $data, $webhook, $webhook_group ){

			$data = array(
				'user_id'        => 1234,
				'name'           => 'John Doe',
				'first_name'     => 'John',
				'last_name'      => 'Doe',
				'email'          => 'johndoe123@test.com',
				'payment_ids'    => 2345,
				'purchase_value' => '23.5',
				'date_created'   => date( 'Y-m-d h:i:s' ),
				'purchase_count' => 1,
				'notes'          => null,
			);

			return $data;
		}

		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD New Customer
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_update_customer_content(){

			$parameter = array(
				'first_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer.', 'trigger-edd_update_customer-content' ) ),
				'last_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer.', 'trigger-edd_update_customer-content' ) ),
				'id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The unique id of the customer. (This is not the user id)', 'trigger-edd_update_customer-content' ) ),
				'purchase_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of purchases of the customer.', 'trigger-edd_update_customer-content' ) ),
				'purchase_value' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The value of all purchases of the customer.', 'trigger-edd_update_customer-content' ) ),
				'email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The main email of the customer.', 'trigger-edd_update_customer-content' ) ),
				'emails' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Additional emails of the customer.', 'trigger-edd_update_customer-content' ) ),
				'name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full name of the customer.', 'trigger-edd_update_customer-content' ) ),
				'date_created' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date and time of the user creation in SQL format.', 'trigger-edd_update_customer-content' ) ),
				'payment_ids' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comme-separated list of payment ids.', 'trigger-edd_update_customer-content' ) ),
				'user_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The user id of the customer.', 'trigger-edd_update_customer-content' ) ),
				'notes' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Additional ntes given by the customer.', 'trigger-edd_update_customer-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_update_customer.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_update_customer',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD Customer Update', 'trigger-edd_update_customer-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_update_customer( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires as soon as a customer is updated within Easy Digital Downloads.', 'trigger-edd_update_customer-content' ),
				'description' => $description,
				'callback' => 'test_edd_update_customer'
			);

		}

		/*
		* Register the post delete trigger logic
		*/
		public function wpwh_trigger_edd_update_customer_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_update_customer' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD customer was created
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_update_customer( $updated = false, $customer_id = 0, $args = array() ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_update_customer' );

			if( ! function_exists( 'EDD_Customer' ) || ! $updated ){
				return;
			}
			
			$customer = new EDD_Customer( $customer_id );

			//Properly calculate names as given by the Zapier extension
			$first_name = '';
			$last_name = '';
			if( isset( $customer->name ) ){
				$separated_names = explode( ' ', $customer->name );

				$first_name = ( ! empty( $separated_names[0] ) ) ? $separated_names[0] : '';

				if( ! empty( $separated_names[1] ) ) {
					unset( $separated_names[0] );
					$last_name = implode( ' ', $separated_names );
				}
			}
			$customer->first_name = $first_name;
			$customer->last_name  = $last_name;

			$response_data_array = array();

			foreach( $webhooks as $webhook ){

				$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

				if( $webhook_url_name !== null ){
					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $customer );
				} else {
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $customer );
				}

			}

			do_action( 'wpwhpro/webhooks/trigger_edd_update_customer', $customer_id, $customer, $response_data_array );
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_update_customer( $data, $webhook, $webhook_group ){

			$data = array(
				'user_id'        => 1234,
				'name'           => 'John Doe',
				'first_name'     => 'John',
				'last_name'      => 'Doe',
				'email'          => 'johndoe123@test.com',
				'payment_ids'    => 2345,
				'purchase_value' => '23.5',
				'date_created'   => date( 'Y-m-d h:i:s' ),
				'purchase_count' => 1,
				'notes'          => null,
			);

			return $data;
		}

		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD Payment Status Update
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_payments_content(){

			$choices = array();
			if( function_exists( 'edd_get_payment_statuses' ) ){
				$choices = edd_get_payment_statuses();

				//add our custom delete status
				$choices['wpwh_deleted'] = WPWHPRO()->helpers->translate( 'Deleted', 'trigger-edd_payments-content' );
			}

			$parameter = array(
				'ID' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The payment id.', 'trigger-edd_payments-content' ) ),
				'key' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The unique payment key.', 'trigger-edd_payments-content' ) ),
				'subtotal' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The subtotal of the payment.', 'trigger-edd_payments-content' ) ),
				'tax' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The tax amount of the payment.', 'trigger-edd_payments-content' ) ),
				'fees' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Additional payment fees of the payment.', 'trigger-edd_payments-content' ) ),
				'total' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The total amount of the payment.', 'trigger-edd_payments-content' ) ),
				'gateway' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The chosen payment gateway of the payment.', 'trigger-edd_payments-content' ) ),
				'email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The customer email that was used for the payment the payment.', 'trigger-edd_payments-content' ) ),
				'date' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date (in SQL format) of the payment creation.', 'trigger-edd_payments-content' ) ),
				'products' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) An array of al products that are included within the payment. Please check the example below for further details.', 'trigger-edd_payments-content' ) ),
				'discount_codes' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma separated list of applied coupon codes.', 'trigger-edd_payments-content' ) ),
				'first_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer.', 'trigger-edd_payments-content' ) ),
				'last_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer.', 'trigger-edd_payments-content' ) ),
				'transaction_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The transaction id of the payment.', 'trigger-edd_payments-content' ) ),
				'billing_address' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) The billing adress with all its values. Please check the example below for further details.', 'trigger-edd_payments-content' ) ),
				'shipping_address' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) The shipping adress with all its values. Please check the example below for further details.', 'trigger-edd_payments-content' ) ),
				'metadata' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) An array of all available meta fields.', 'trigger-edd_payments-content' ) ),
				'new_status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The new status of the payment.', 'trigger-edd_payments-content' ) ),
				'old_status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The prrevious status of the payment.', 'trigger-edd_payments-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_payments.php' );
			$description = ob_get_clean();

			$settings = array(
				'data' => array(
					'wpwhpro_trigger_edd_payments_whitelist_status' => array(
						'id'          => 'wpwhpro_trigger_edd_payments_whitelist_status',
						'type'        => 'select',
						'multiple'    => true,
						'choices'      => $choices,
						'label'       => WPWHPRO()->helpers->translate('Trigger on selected payment status changes', 'trigger-edd_payments-content'),
						'placeholder' => '',
						'required'    => false,
						'description' => WPWHPRO()->helpers->translate('Select only the payment statuses you want to fire the trigger on. You can choose multiple ones. If none is selected, all are triggered.', 'trigger-edd_payments-content')
					),
				)
			);

			return array(
				'trigger' => 'edd_payments',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD Payments', 'trigger-edd_payments-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_payments( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on certain status changes of payments within Easy Digital Downloads.', 'trigger-edd_payments-content' ),
				'description' => $description,
				'callback' => 'test_edd_payments'
			);

		}

		/*
		* Register the edd payments post delay trigger logic for deletion of posts
		*/
		public function wpwh_trigger_edd_payments_delete_prepare( $payment_id = 0 ){

			if( ! isset( $this->pre_trigger_values['edd_payments'] ) ){
				$this->pre_trigger_values['edd_payments'] = array();
			}

			if( ! isset( $this->pre_trigger_values['edd_payments'][ $payment_id ] ) ){
				$this->pre_trigger_values['edd_payments'][ $payment_id ] = array();
			}

			$this->pre_trigger_values['edd_payments'][ $payment_id ] = $this->wpwh_get_edd_order_data( $payment_id );
			
			//Init the post delay functions with further default parameters
			$this->wpwh_trigger_edd_payments_init( $payment_id, 'wpwh_deleted', 'wpwh_undeleted' );
			
		}

		/*
		* Register the edd payments post delay trigger logic
		*/
		public function wpwh_trigger_edd_payments_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_payments' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_payments( $payment_id, $new_status, $old_status ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_payments' );
			$order_data = array();

			//Only fire on change
			if( $new_status === $old_status ){
				return;
			}

			foreach( $webhooks as $webhook ){

				$is_valid = true;

				if( isset( $webhook['settings'] ) ){
					foreach( $webhook['settings'] as $settings_name => $settings_data ){

						if( $settings_name === 'wpwhpro_trigger_edd_payments_whitelist_status' && ! empty( $settings_data ) ){
							if( ! in_array( $new_status, $settings_data ) ){
								$is_valid = false;
							}
						}

					}
				}

				if( $is_valid ) {

					if( isset( $this->pre_trigger_values['edd_payments'][ $payment_id ] ) ){
						$order_data = $this->pre_trigger_values['edd_payments'][ $payment_id ];
					} else {
						$order_data = $this->wpwh_get_edd_order_data( $payment_id );
					}

					//append status changes
					$order_data['new_status'] = $new_status;
					$order_data['old_status'] = $old_status;

					$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

					if( $webhook_url_name !== null ){
						$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $order_data );
					} else {
						$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $order_data );
					}

					do_action( 'wpwhpro/webhooks/trigger_edd_payments', $payment_id, $new_status, $old_status, $response_data_array );
				}
				
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_payments( $data, $webhook, $webhook_group ){

			$data = array (
				'ID' => 123,
				'key' => 'c36bc5d3315cde89ce18a19bb6a1d559',
				'subtotal' => 39,
				'tax' => '0',
				'fees' => 
				array (
				),
				'total' => 39,
				'gateway' => 'manual',
				'email' => 'johndoe123@test.com',
				'date' => '2020-04-23 09:16:00',
				'products' => 
				array (
				  array (
					'Product' => 'Demo Download',
					'Subtotal' => 39,
					'Tax' => '0.00',
					'Discount' => 0,
					'Price' => 39,
					'PriceName' => 'Single Site',
					'Quantity' => 1,
				  ),
				),
				'discount_codes' => 'none',
				'first_name' => 'Jon',
				'last_name' => 'Doe',
				'transaction_id' => 123,
				'billing_address' => array( 'line1' => 'Street 1', 'line2' => 'Line 2', 'city' => 'My Fair City', 'country' => 'US', 'state' => 'MD', 'zip' => '55555' ),
				'shipping_address' => array( 'address' => 'Street 1', 'Address2' => 'Line 2', 'city' => 'My Fair City', 'country' => 'US', 'state' => 'MD', 'zip' => '55555' ),
				'metadata' => 
				array (
				  '_edd_payment_tax_rate' => 
				  array (
					0 => '0',
				  ),
				  '_edd_complete_actions_run' => 
				  array (
					0 => '8763342154',
				  ),
				),
				'new_status' => 'publish',
				'old_status' => 'pending',
			  );

			return $data;
		}
		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD File Download
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_file_downloaded_content(){

			$choices = array();
			if( function_exists( 'edd_get_payment_statuses' ) ){
				$choices = edd_get_payment_statuses();

				//add our custom delete status
				$choices['wpwh_deleted'] = WPWHPRO()->helpers->translate( 'Deleted', 'trigger-edd_file_downloaded-content' );
			}

			$parameter = array(
				'file_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The file name without the file extension.', 'trigger-edd_file_downloaded-content' ) ),
				'file' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full file URL.', 'trigger-edd_file_downloaded-content' ) ),
				'email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer who started the download.', 'trigger-edd_file_downloaded-content' ) ),
				'product' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The product name wich contains the download.', 'trigger-edd_file_downloaded-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_file_downloaded.php' );
			$description = ob_get_clean();

			$settings = array(
				'load_default_settings' => true,
			);

			return array(
				'trigger' => 'edd_file_downloaded',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD File Downloads', 'trigger-edd_file_downloaded-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_file_downloaded( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires once a file download is initiated within Easy Digital Downloads.', 'trigger-edd_file_downloaded-content' ),
				'description' => $description,
				'callback' => 'test_edd_file_downloaded'
			);

		}

		/**
		 * Triggers once a new EDD file was downloaded
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_file_downloaded( $download_id, $email, $payment_id, $args ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_file_downloaded' );

			$data  = array();
			$files = edd_get_download_files( $download_id );

			$data['file_name'] = $files[ $args['file_key'] ]['name'];
			$data['file']      = $files[ $args['file_key'] ]['file'];
			$data['email']     = $email;
			$data['product']   = get_the_title( $download_id );

			foreach( $webhooks as $webhook ){

				$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

				if( $webhook_url_name !== null ){
					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $data );
				} else {
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $data );
				}
				
			}

			do_action( 'wpwhpro/webhooks/trigger_edd_file_downloaded', $download_id, $email, $payment_id, $args, $response_data_array );
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_file_downloaded( $data, $webhook, $webhook_group ){

			$data = array(
				'file_name' => 'sample_file_name',
				'file'      => home_url( 'sample/file/url/file.zip' ),
				'email'     => 'jane@test.com',
				'product'   => 'Sample Product',
			);

			return $data;
		}

		/**
		 * ######################
		 * ###
		 * #### EDD HELPERS
		 * ###
		 * ######################
		 */

		 /**
		 * Get relevant data for a given complete order.
		 *
		 * @param  integer $payment_id Payment post ID.
		 * @return array               Order data.
		 */
		function wpwh_get_edd_order_data( $payment_id = 0 ) {

			if( ! function_exists( 'edd_get_payment_meta_user_info' ) ){
				return false;
			}

			$user_info                      = edd_get_payment_meta_user_info( $payment_id );
			$order_data                     = array();
			$order_data['ID']               = $payment_id;
			$order_data['key']              = edd_get_payment_key( $payment_id );
			$order_data['subtotal']         = edd_get_payment_subtotal( $payment_id );
			$order_data['tax']              = edd_get_payment_tax( $payment_id );
			$order_data['fees']             = edd_get_payment_fees( $payment_id );
			$order_data['total']            = edd_get_payment_amount( $payment_id );
			$order_data['gateway']          = edd_get_payment_gateway( $payment_id );
			$order_data['email']            = edd_get_payment_user_email( $payment_id );
			$order_data['date']             = get_the_time( 'Y-m-d H:i:s', $payment_id );
			$order_data['products']         = $this->wpwh_edd_get_order_products( $payment_id );
			$order_data['discount_codes']   = $user_info['discount'];
			$order_data['first_name']       = $user_info['first_name'];
			$order_data['last_name']        = $user_info['last_name'];
			$order_data['transaction_id']   = edd_get_payment_transaction_id( $payment_id );
			$order_data['billing_address']  = ! empty( $user_info['address'] ) ? $user_info['address'] : array( 'line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );
			$order_data['shipping_address'] = ! empty( $user_info['shipping_info'] ) ? $user_info['shipping_info'] : array( 'address' => '', 'address2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );
			$order_data['metadata']         = $this->wpwh_edd_get_order_metadata( $payment_id );

			return $order_data;
		}

		/**
		 * Get ordered products for a given order.
		 *
		 * @param  integer $payment_id Payment post ID.
		 * @return array               Ordered products.
		 */
		function wpwh_edd_get_order_products( $payment_id = 0 ) {

			$cart_items = edd_get_payment_meta_cart_details( $payment_id );
			$products   = array();

			foreach ( $cart_items as $key => $item ) {

				$price_name = '';
				if ( isset( $cart_items[ $key ]['item_number'] ) ) {
					$price_options  = $cart_items[ $key ]['item_number']['options'];
					if ( isset( $price_options['price_id'] ) ) {
						$price_name = edd_get_price_option_name( $item['id'], $price_options['price_id'], $payment_id );
					}
				}

				$products[ $key ]['Product']   = $item['name'];
				$products[ $key ]['Subtotal']  = $item['subtotal'];
				$products[ $key ]['Tax']       = $item['tax'];
				$products[ $key ]['Discount']  = $item['discount'];
				$products[ $key ]['Price']     = $item['price'];
				$products[ $key ]['PriceName'] = $price_name;
				$products[ $key ]['Quantity']  = $item['quantity'];
			}

			return $products;
		}

		/**
		 * Retrieve an array of all custom metadata on a payment
		 *
		 * @param  integer $payment_id Payment post ID.
		 * @return array               Metadata
		 */
		function wpwh_edd_get_order_metadata( $payment_id = 0 ) {

			$ignore = array(
				'_edd_payment_gateway',
				'_edd_payment_mode',
				'_edd_payment_transaction_id',
				'_edd_payment_user_ip',
				'_edd_payment_customer_id',
				'_edd_payment_user_id',
				'_edd_payment_user_email',
				'_edd_payment_purchase_key',
				'_edd_payment_number',
				'_edd_completed_date',
				'_edd_payment_unlimited_downloads',
				'_edd_payment_total',
				'_edd_payment_tax',
				'_edd_payment_meta',
				'user_info',
				'cart_details',
				'downloads',
				'fees',
				'currency',
				'address'
			);

			$metadata = get_post_custom( $payment_id );
			foreach( $metadata as $key => $value ) {

				if( in_array( $key, $ignore ) ) {

					if( '_edd_payment_meta' == $key ) {

						// Look for custom values added to _edd_payment_meta
						foreach( $value as $inner_key => $inner_value ) {

							if( ! in_array( $inner_key, $ignore ) ) {

								$metadata[ $inner_key ] = $inner_value;

							}

						}

					}

					unset( $metadata[ $key ] );
				}

			}

			return $metadata;

		}

	} // End class

	new WP_Webhooks_EDD_Triggers();

}