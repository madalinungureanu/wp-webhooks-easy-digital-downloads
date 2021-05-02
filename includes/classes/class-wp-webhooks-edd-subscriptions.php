<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_Subscriptions_Triggers' ) ){

	class WP_Webhooks_EDD_Subscriptions_Triggers{

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

		public function get_edd_subscription_statuses(){
			$statuses = array(
				'create' => WPWHPRO()->helpers->translate( 'Created', 'trigger-edd-subscriptions-content' ),
				'renew' => WPWHPRO()->helpers->translate( 'Renewed', 'trigger-edd-subscriptions-content' ),
				'completed' => WPWHPRO()->helpers->translate( 'Completed', 'trigger-edd-subscriptions-content' ),
				'expired' => WPWHPRO()->helpers->translate( 'Expired', 'trigger-edd-subscriptions-content' ),
				'failing' => WPWHPRO()->helpers->translate( 'Failed', 'trigger-edd-subscriptions-content' ),
				'cancelled' => WPWHPRO()->helpers->translate( 'Cancelled', 'trigger-edd-subscriptions-content' ),
			);

			return apply_filters( 'wpwhpro/settings/edd_subscription_statuses', $statuses );
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

			if( ! $this->is_active() ){
				return $triggers;
			}

			$triggers[] = $this->trigger_edd_subscriptions_content();
			$triggers[] = $this->trigger_edd_subscription_payment_content();

			return $triggers;
		}

		/*
         * Add the specified webhook triggers logic.
         * We also add the demo functionality here
         */
		public function add_webhook_triggers() {
			
			if( ! $this->is_active() ){
				return;
			}

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_subscriptions' ) ) ){
				add_action( 'edd_subscription_post_create', array( $this, 'wpwh_trigger_edd_subscriptions_map_create' ), 10, 2 );
				add_action( 'edd_subscription_post_renew', array( $this, 'wpwh_trigger_edd_subscriptions_map_renew' ), 10, 3 );
				add_action( 'edd_subscription_completed', array( $this, 'wpwh_trigger_edd_subscriptions_map_completed' ), 10, 2 );
				add_action( 'edd_subscription_expired', array( $this, 'wpwh_trigger_edd_subscriptions_map_expired' ), 10, 2 );
				add_action( 'edd_subscription_failing', array( $this, 'wpwh_trigger_edd_subscriptions_map_failing' ), 10, 2 );
				add_action( 'edd_subscription_cancelled', array( $this, 'wpwh_trigger_edd_subscriptions_map_cancelled' ), 10, 2 );
				
				add_filter( 'ironikus_demo_test_edd_subscriptions', array( $this, 'wpwh_send_demo_edd_subscriptions' ), 10, 3 );
			}

			if( ! empty( WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_subscription_payment' ) ) ){
				add_action( 'edd_recurring_add_subscription_payment', array( $this, 'wpwh_trigger_edd_subscription_payment_init' ), 10, 2 );
				add_filter( 'ironikus_demo_test_edd_subscription_payment', array( $this, 'wpwh_send_demo_edd_subscription_payment' ), 10, 3 );
			}

		}

		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD Payment Status Update
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_subscriptions_content(){

			$choices = $this->get_edd_subscription_statuses();

			$parameter = array(
				'id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The subscription id.', 'trigger-edd_subscriptions-content' ) ),
				'customer_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The id of the related customer.', 'trigger-edd_subscriptions-content' ) ),
				'period' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The subcription period.', 'trigger-edd_subscriptions-content' ) ),
				'initial_amount' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The initial price amount.', 'trigger-edd_subscriptions-content' ) ),
				'initial_tax_rate' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The initial tax rate.', 'trigger-edd_subscriptions-content' ) ),
				'initial_tax' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The initial tax amount.', 'trigger-edd_subscriptions-content' ) ),
				'recurring_amount' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The recurring price amount.', 'trigger-edd_subscriptions-content' ) ),
				'recurring_tax_rate' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The recurring tax rate.', 'trigger-edd_subscriptions-content' ) ),
				'recurring_tax' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The recurring tax amount.', 'trigger-edd_subscriptions-content' ) ),
				'bill_times' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The times the customer gets billed.', 'trigger-edd_subscriptions-content' ) ),
				'transaction_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The transaction id.', 'trigger-edd_subscriptions-content' ) ),
				'parent_payment_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The parent payment id in case the payment is recurring.', 'trigger-edd_subscriptions-content' ) ),
				'product_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The related product id for this subscription.', 'trigger-edd_subscriptions-content' ) ),
				'price_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The price id in case it is a variation.', 'trigger-edd_subscriptions-content' ) ),
				'created' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date and time of creation (in SQL format).', 'trigger-edd_subscriptions-content' ) ),
				'expiration' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date and time of expiration (in SQL format).', 'trigger-edd_subscriptions-content' ) ),
				'trial_period' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The trial period.', 'trigger-edd_subscriptions-content' ) ),
				'status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The current subscription status.', 'trigger-edd_subscriptions-content' ) ),
				'profile_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The unique profile id.', 'trigger-edd_subscriptions-content' ) ),
				'gateway' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The chosen gateway for this subscription.', 'trigger-edd_subscriptions-content' ) ),
				'customer' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) An array with all of the customer information. Please see the example down below for further details.', 'trigger-edd_subscriptions-content' ) ),
				'notes' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) An array with all the subscription notes.', 'trigger-edd_subscriptions-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_subscriptions.php' );
			$description = ob_get_clean();

			$settings = array(
				'data' => array(
					'wpwhpro_trigger_edd_subscriptions_whitelist_status' => array(
						'id'          => 'wpwhpro_trigger_edd_subscriptions_whitelist_status',
						'type'        => 'select',
						'multiple'    => true,
						'choices'      => $choices,
						'label'       => WPWHPRO()->helpers->translate('Trigger on selected subscription status changes', 'trigger-edd_subscriptions-content'),
						'placeholder' => '',
						'required'    => false,
						'description' => WPWHPRO()->helpers->translate('Select only the subscription statuses you want to fire the trigger on. You can choose multiple ones. If none is selected, all are triggered.', 'trigger-edd_subscriptions-content')
					),
				)
			);

			return array(
				'trigger' => 'edd_subscriptions',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD Subscriptions', 'trigger-edd_subscriptions-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_subscriptions( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on certain status changes of subscriptions within Easy Digital Downloads.', 'trigger-edd_subscriptions-content' ),
				'description' => $description,
				'callback' => 'test_edd_subscriptions'
			);

		}

		public function wpwh_trigger_edd_subscriptions_map_create( $subscription_id = 0, $args = array() ) {

			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$subscription = new EDD_Subscription( $subscription_id );

			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'create' );
		}

		public function wpwh_trigger_edd_subscriptions_map_renew( $sub_id = 0, $expiration = '', EDD_Subscription $subscription ) {
			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'renew' );
		}

		public function wpwh_trigger_edd_subscriptions_map_completed( $sub_id = 0, EDD_Subscription $subscription ) {
			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'completed' );
		}

		public function wpwh_trigger_edd_subscriptions_map_expired( $sub_id = 0, EDD_Subscription $subscription ) {
			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'expired' );
		}

		public function wpwh_trigger_edd_subscriptions_map_failing( $sub_id = 0, EDD_Subscription $subscription ) {
			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'failing' );
		}

		public function wpwh_trigger_edd_subscriptions_map_cancelled( $sub_id = 0, EDD_Subscription $subscription ) {
			if( ! class_exists( 'EDD_Subscription' ) ) {
				return;
			}
			$this->wpwh_trigger_edd_subscriptions_init( $subscription, 'cancelled' );
		}

		/*
		* Register the edd payments post delay trigger logic
		*/
		public function wpwh_trigger_edd_subscriptions_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_subscriptions' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_subscriptions( $subscription, $status ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_subscriptions' );
			$response_data_array = array();

			

			foreach( $webhooks as $webhook ){

				$is_valid = true;

				if( isset( $webhook['settings'] ) ){
					foreach( $webhook['settings'] as $settings_name => $settings_data ){

						if( $settings_name === 'wpwhpro_trigger_edd_subscriptions_whitelist_status' && ! empty( $settings_data ) ){
							if( ! in_array( $status, $settings_data ) ){
								$is_valid = false;
							}
						}

					}
				}

				if( $is_valid ) {

					$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

                    if( $webhook_url_name !== null ){
                        $response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $subscription );
                    } else {
                        $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $subscription );
                    }

					do_action( 'wpwhpro/webhooks/trigger_edd_subscriptions', $subscription, $status, $response_data_array );
				}
				
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_subscriptions( $data, $webhook, $webhook_group ){

			$data = array (
				'id' => '1',
				'customer_id' => '1',
				'period' => 'year',
				'initial_amount' => '9.97',
				'initial_tax_rate' => '',
				'initial_tax' => '',
				'recurring_amount' => '9.97',
				'recurring_tax_rate' => '',
				'recurring_tax' => '',
				'bill_times' => '2',
				'transaction_id' => '',
				'parent_payment_id' => '706',
				'product_id' => '285',
				'price_id' => '0',
				'created' => '2020-04-23 16:29:36',
				'expiration' => '2020-04-22 23:59:59',
				'trial_period' => '',
				'status' => 'completed',
				'profile_id' => 'xxxxxxxx',
				'gateway' => 'manual',
				'customer' => 
				array (
				  'id' => '1',
				  'purchase_count' => 2,
				  'purchase_value' => 87.97,
				  'email' => 'johndoe123@test.com',
				  'emails' => 
				  array (
					0 => 'johndoe123more@test.com',
				  ),
				  'name' => 'John Doe',
				  'date_created' => '2019-02-26 07:32:56',
				  'payment_ids' => '695,706',
				  'user_id' => '1',
				),
				'notes' => 
				array (
				  'April 23, 2020 16:32:05 - Status changed from completed to failing by admin',
				  'April 23, 2020 16:30:59 - Status changed from active to completed by admin',
				  'April 23, 2020 16:30:45 - Status changed from expired to active by admin',
				),
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
		 * #### WEBHOOK - Send Data On EDD New Subscription Payment
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_subscription_payment_content(){

			$parameter = array(
				'payment' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) The payment data. For further details, please refer to the example down below.', 'trigger-edd_subscription_payment-content' ) ),
				'subscription' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) The subscription data. For further details, please refer to the example down below.', 'trigger-edd_subscription_payment-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_subscription_payment.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_subscription_payment',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD New Subscription Payment', 'trigger-edd_subscription_payment-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_subscription_payment( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires as soon as a new subscription payment is created within Easy Digital Downloads.', 'trigger-edd_subscription_payment-content' ),
				'description' => $description,
				'callback' => 'test_edd_subscription_payment'
			);

		}

		/*
		* Register the post delete trigger logic
		*/
		public function wpwh_trigger_edd_subscription_payment_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_subscription_payment' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD customer was created
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_subscription_payment( EDD_Payment $payment, EDD_Subscription $subscription ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_subscription_payment' );

			$response_data_array = array();
			$data = array( 
				'payment' => $payment,
				'subscription' => $subscription
			);

			foreach( $webhooks as $webhook ){
				$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

				if( $webhook_url_name !== null ){
					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $data );
				} else {
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $data );
				}
			}

			do_action( 'wpwhpro/webhooks/trigger_edd_subscription_payment', $data, $response_data_array );
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_subscription_payment( $data, $webhook, $webhook_group ){

			$data = array(
				'payment' => array(
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
				),
				'subscription' => array(
					'id'                => '183',
					'customer_id'       => '36',
					'period'            => 'month',
					'initial_amount'    => '16.47',
					'recurring_amount'  => '10.98',
					'bill_times'        => '0',
					'transaction_id'    => '',
					'parent_payment_id' => '845',
					'product_id'        => '8',
					'created'           => '2016-06-13 13:47:24',
					'expiration'        => '2016-07-13 23:59:59',
					'status'            => 'pending',
					'profile_id'        => 'ppe-4e3ca7d1c017e0ea8b24ff72d1d23022-8',
					'gateway'           => 'paypalexpress',
					'customer'          => array(
						'id'             => '36',
						'purchase_count' => '2',
						'purchase_value' => '32.93',
						'email'          => 'jane@test.com',
						'emails'         => array(
							'jane@test.com',
						),
						'name'           => 'Jane Doe',
						'date_created'   => '2016-06-13 13:19:50',
						'payment_ids'    => '842,845,846',
						'user_id'        => '1',
						'notes'          => array(
					  		'These are notes about the customer',
						),
					),
					'user_id' => '24',
				)
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

		 public function is_active(){
			 return ( defined( 'EDD_RECURRING_PRODUCT_NAME' ) ) ? true : false;
		 }

	} // End class

	new WP_Webhooks_EDD_Subscriptions_Triggers();

}