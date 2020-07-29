<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_Subscriptions_Actions' ) ){

	class WP_Webhooks_EDD_Subscriptions_Actions{

		public function __construct() {

			add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );

			add_filter( 'wpwh/descriptions/actions/edd_create_payment/default_cart_details', array( $this, 'customize_edd_create_payment_default_cart_details' ), 10 );

		}

		/**
		 * ######################
		 * ###
		 * #### EXTENSIONS
		 * ###
		 * ######################
		 */

		public function customize_edd_create_payment_default_cart_details( $default_data ){

			if( ! is_array( $default_data ) || ! $this->is_active() ){
				return $default_data;
			}

			foreach( $default_data as $key => $data ){
				if( isset( $data['item_number'] ) && isset( $data['item_number']['options'] ) ){
					$default_data[ $key ]['item_number']['options']['is_upgrade'] = true;
					$default_data[ $key ]['item_number']['options']['is_renewal'] = true;
					$default_data[ $key ]['item_number']['options']['license_id'] = 3423;
					$default_data[ $key ]['item_number']['options']['recurring'] = array(
						'trial_period' => array(
							'unit' => 33,
							'quantity' => 2,
						),
						'signup_fee' => 33.10,
						'period' => 'monthly',
						'times' => 4,
					);
				}
			}

			return $default_data;
		 }

		/**
		 * ######################
		 * ###
		 * #### WEBHOOK ACTIONS
		 * ###
		 * ######################
		 */

		/*
		 * Register all available action webhooks here
		 */
		public function add_webhook_actions_content( $actions ){

			if( ! $this->is_active() ){
				return $actions;
			}

			//subscriptions
			$actions[] = $this->action_edd_create_subscription_content();
			$actions[] = $this->action_edd_update_subscription_content();
			$actions[] = $this->action_edd_delete_subscription_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * We always send three different properties with the defined wehook.
		 * @param $action - the defined action defined within the action_edd_create_payment function
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $action, $webhook, $api_key ){

			if( ! $this->is_active() ){
				return;
			}

			$active_webhooks = WPWHPRO()->settings->get_active_webhooks();

			$available_actions = $active_webhooks['actions'];

			switch( $action ){
				case 'edd_create_subscription':
					if( isset( $available_actions['edd_create_subscription'] ) ){
						$this->action_edd_create_subscription();
					}
					break;
				case 'edd_update_subscription':
					if( isset( $available_actions['edd_update_subscription'] ) ){
						$this->action_edd_update_subscription();
					}
					break;
				case 'edd_delete_subscription':
					if( isset( $available_actions['edd_delete_subscription'] ) ){
						$this->action_edd_delete_subscription();
					}
					break;
			}
		}

		/**
		 * ###########
		 * #### edd_create_subscription
		 * ###########
		 */

		public function action_edd_create_subscription_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'expiration_date'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The date for the expiration of the subscription. Recommended format: 2021-05-25 11:11:11', 'action-edd_create_subscription-content' ) ),
				'profile_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) This is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe.', 'action-edd_create_subscription-content' ) ),
				'download_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the download you want to connect with the subscription.', 'action-edd_create_subscription-content' ) ),
				'customer_email'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'period'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The billing period of the subscription. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'initial_amount'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The amount for the initial payment. E.g. 39.97', 'action-edd_create_subscription-content' ) ),
				'recurring_amount'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The recurring amount for the subscription. E.g. 19.97', 'action-edd_create_subscription-content' ) ),
				'transaction_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) This is the unique ID of the initial transaction inside of the merchant processor, such as PayPal or Stripe.', 'action-edd_create_subscription-content' ) ),
				'status'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the given subscription. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'created_date'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date of creation of the subscription. Recommended format: 2021-05-25 11:11:11', 'action-edd_create_subscription-content' ) ),
				'bill_times'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) This refers to the number of times the subscription will be billed before being marked as Completed and payments stopped. Enter 0 if payments continue indefinitely.', 'action-edd_create_subscription-content' ) ),
				'parent_payment_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Use this argument to connect the subscription with an already existing payment. Otherwise, a new one is created. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'customer_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the customer you want to connect. If it is not given, we try to fetch the user from the customer_email argument. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'customer_first_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'customer_last_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'edd_price_option'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The variation id for a download price option. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'gateway'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The gateway you want to use for your subscription (and maybe payment). Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'initial_tax_rate'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The percentage for your initial tax rate. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'initial_tax'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The amount of tax for your initial tax amount. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'recurring_tax_rate'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The percentage for your recurring tax rate. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'recurring_tax'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The amount of tax for your recurring tax amount. Please see the description for further details.', 'action-edd_create_subscription-content' ) ),
				'notes'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple subscription notes. Please check the description for further details.', 'action-edd_create_subscription-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_create_subscription-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_subscription-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_create_subscription-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the new susbcription id, the payment id, customer id, as well as further details about the subscription.', 'action-edd_create_subscription-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The subscription was successfully created.",
    "data": {
        "subscription_id": "23",
        "payment_id": 843,
        "customer_id": 8
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_subscription.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_subscription',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a subscription within Easy Digital Downloads - Recurring.', 'action-edd_create_subscription-content' ),
				'description'       => $description
			);

		}

		public function action_edd_create_subscription() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$subscription_id = 0;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'subscription_id' => 0,
					'payment_id' => 0,
					'customer_id' => 0,
				),
			);

			$expiration_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$profile_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'profile_id' );
			$initial_amount   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_amount' );
			$recurring_amount   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_amount' );
			$download_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' );
			$transaction_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'transaction_id' );
			$status   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'status' );
			$created_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'created_date' );
			$bill_times   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bill_times' );
			$period   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'period' );
			$parent_payment_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'parent_payment_id' );
			$customer_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_id' );
			$customer_email   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_email' );
			$customer_first_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_first_name' );
			$customer_last_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_last_name' );
			$edd_price_option   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'edd_price_option' );
			$gateway   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'gateway' );
			$initial_tax_rate   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_tax_rate' );
			$initial_tax   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_tax' );
			$recurring_tax_rate   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_tax_rate' );
			$recurring_tax   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_tax' );
			$notes   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'notes' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Subscription' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Subscription() does not exist. The subscription was not created.', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $expiration_date ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The expiration_date argument cannot be empty. ', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $profile_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The profile_id argument cannot be empty. ', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $customer_email ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The customer_email argument cannot be empty. ', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $download_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The download_id argument cannot be empty. ', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $period ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The period argument cannot be empty. ', 'action-edd_create_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $initial_amount ) ){
				$initial_amount = 0; //set it default to 0
			}

			if( empty( $recurring_amount ) ){
				$recurring_amount = 0; //set it default to 0
			}

			if( empty( $customer_first_name ) && empty( $customer_last_name ) ) {
				$customer_name = $customer_email;
			} else {
				$customer_name = trim( $customer_first_name . ' ' . $customer_last_name );
			}

			if( ! empty( $created_date ) ) {
				$created_date = date( 'Y-m-d ' . date( 'H:i:s', current_time( 'timestamp' ) ), strtotime( $created_date, current_time( 'timestamp' ) ) );
			} else {
				$created_date = date( 'Y-m-d H:i:s',current_time( 'timestamp' ) );
			}

			//try to fetch the customer
			if( empty( $customer_id ) ){
				$tmpcustomer = EDD()->customers->get_customer_by( 'email', $customer_email );
				if( isset( $tmpcustomer->id ) && ! empty( $tmpcustomer->id ) ) {
					$customer_id = $tmpcustomer->id;
				}
			}

			if( ! empty( $customer_id ) ) {

				$customer    = new EDD_Recurring_Subscriber( absint( $customer_id ) );
				$customer_id = $customer->id;
				$email       = $customer->email;
		
			} else {
		
				$email       = sanitize_email( $customer_email );
				$user        = get_user_by( 'email', $email );
				$user_id     = $user ? $user->ID : 0;
				$customer    = new EDD_Recurring_Subscriber;
				$customer_id = $customer->create( array( 'email' => $email, 'user_id' => $user_id, 'name' => $customer_name ) );
		
			}
		
			$customer_id = absint( $customer_id );
		
			if( ! empty( $parent_payment_id ) ) {
		
				$payment_id = absint( $parent_payment_id );
				$payment    = new EDD_Payment( $payment_id );
		
			} else {
		
				$options = array();
				if ( ! empty( $edd_price_option ) ) {
					$options['price_id'] = absint( $edd_price_option );
				}
		
				$payment = new EDD_Payment;
				$payment->add_download( absint( $download_id ), $options );
				$payment->customer_id = $customer_id;
				$payment->email       = $email;
				$payment->user_id     = $customer->user_id;
				$payment->gateway     = sanitize_text_field( $gateway );
				$payment->total       = edd_sanitize_amount( sanitize_text_field( $initial_amount ) );
				$payment->date        = $created_date;
				$payment->status      = 'pending';
				$payment->save();
				$payment->status = 'complete';
				$payment->save();

				$payment_id = absint( $payment->ID );
			}

			$sub_args = array(
				'expiration'        => date( 'Y-m-d 23:59:59', strtotime( $expiration_date, current_time( 'timestamp' ) ) ),
				'created'           => date( 'Y-m-d H:i:s', strtotime( $created_date, current_time( 'timestamp' ) ) ),
				'status'            => sanitize_text_field( $status ),
				'profile_id'        => sanitize_text_field( $profile_id ),
				'transaction_id'    => sanitize_text_field( $transaction_id ),
				'initial_amount'    => edd_sanitize_amount( sanitize_text_field( $initial_amount ) ),
				'recurring_amount'  => edd_sanitize_amount( sanitize_text_field( $recurring_amount ) ),
				'bill_times'        => absint( $bill_times ),
				'period'            => sanitize_text_field( $period ),
				'parent_payment_id' => $payment_id,
				'product_id'        => absint( $download_id ),
				'price_id'          => absint( $edd_price_option ),
				'customer_id'       => $customer_id,
			);

			//these arguments are added extra on top of the default "Add subscription function just to keep it compliant with the default EDD logic
			if( $initial_tax_rate ){
				$sub_args['initial_tax_rate'] = edd_sanitize_amount( (float) $initial_tax_rate / 100 );
			}
			if( $initial_tax ){
				$sub_args['initial_tax'] = edd_sanitize_amount( $initial_tax );
			}
			if( $recurring_tax_rate ){
				$sub_args['recurring_tax_rate'] = edd_sanitize_amount( (float) $recurring_tax_rate / 100 );
			}
			if( $recurring_tax ){
				$sub_args['recurring_tax'] = edd_sanitize_amount( $recurring_tax );
			}

			//Add trial period
			if( sanitize_text_field( $status ) === 'trialling' ){
				if( ! empty( $edd_price_option ) ){
					$trial_period = edd_recurring()->get_trial_period( $download_id, $edd_price_option );
				} else {
					$trial_period = edd_recurring()->get_trial_period( $download_id );
				}
				if( ! empty( $trial_period ) ){
					$sub_args['trial_period'] = '+' . $trial_period['quantity'] . ' ' . $trial_period['unit'];

					if( ! empty( $created_date ) ){
						$sub_args['expiration'] = date( 'Y-m-d 23:59:59', strtotime( $sub_args['trial_period'], strtotime( $created_date, current_time( 'timestamp' ) ) ) );
					} else {
						$sub_args['expiration'] = date( 'Y-m-d 23:59:59', strtotime( $sub_args['trial_period'], current_time( 'timestamp' ) ) );
					}
					
				}
			}

			$subscription = new EDD_Subscription;
			$check = $subscription->create( $sub_args );

			if( $check ){
				if( 'trialling' === $subscription->status ) {
					$customer->add_meta( 'edd_recurring_trials', $subscription->product_id );
				}

				if( ! empty( $notes ) ){
					if( WPWHPRO()->helpers->is_json( $notes ) ){
						$notes_arr = json_decode( $notes, true );
						foreach( $notes_arr as $snote ){
							$subscription->add_note( $snote );
						}
					}
				}
			
				$payment->update_meta( '_edd_subscription_payment', true );
	
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The subscription was successfully created.", 'action-edd_create_subscription-success' );
				$return_args['success'] = true;
				$return_args['data']['subscription_id'] = $subscription->id;
				$return_args['data']['payment_id'] = $payment_id;
				$return_args['data']['customer_id'] = $customer_id;
				$subscription_id = $subscription->id;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error creating the subscription.", 'action-edd_create_subscription-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $subscription_id, $subscription, $payment, $customer, $return_args );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );
			die();
		}
		
		/**
		 * ###########
		 * #### edd_update_subscription
		 * ###########
		 */

		public function action_edd_update_subscription_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'subscription_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the subscription you would like to update.', 'action-edd_update_subscription-content' ) ),
				'expiration_date'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date for the expiration of the subscription. Recommended format: 2021-05-25 11:11:11', 'action-edd_update_subscription-content' ) ),
				'profile_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) This is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe.', 'action-edd_update_subscription-content' ) ),
				'download_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the download you want to connect with the subscription.', 'action-edd_update_subscription-content' ) ),
				'customer_email'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer in case you do not have the customer id. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'period'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The billing period of the subscription. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'initial_amount'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The amount for the initial payment. E.g. 39.97', 'action-edd_update_subscription-content' ) ),
				'recurring_amount'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The recurring amount for the subscription. E.g. 19.97', 'action-edd_update_subscription-content' ) ),
				'transaction_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) This is the unique ID of the initial transaction inside of the merchant processor, such as PayPal or Stripe.', 'action-edd_update_subscription-content' ) ),
				'status'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the given subscription. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'created_date'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The date of creation of the subscription. Recommended format: 2021-05-25 11:11:11', 'action-edd_update_subscription-content' ) ),
				'bill_times'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) This refers to the number of times the subscription will be billed before being marked as Completed and payments stopped. Enter 0 if payments continue indefinitely.', 'action-edd_update_subscription-content' ) ),
				'parent_payment_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Use this argument to connect the subscription with an already existing payment. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'customer_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the customer you want to connect. If it is not given, we try to fetch the user from the customer_email argument. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'edd_price_option'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The variation id for a download price option. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'initial_tax_rate'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The percentage for your initial tax rate. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'initial_tax'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The amount of tax for your initial tax amount. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'recurring_tax_rate'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The percentage for your recurring tax rate. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'recurring_tax'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The amount of tax for your recurring tax amount. Please see the description for further details.', 'action-edd_update_subscription-content' ) ),
				'notes'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple subscription notes. Please check the description for further details.', 'action-edd_update_subscription-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_update_subscription-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_subscription-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_update_subscription-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the new susbcription id, the payment id, customer id, as well as further details about the subscription.', 'action-edd_update_subscription-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The discount code was successfully deleted.",
    "data": {
        "discount_id": 803
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_subscription.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_subscription',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update a subscription within Easy Digital Downloads - Recurring.', 'action-edd_update_subscription-content' ),
				'description'       => $description
			);

		}

		public function action_edd_update_subscription() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'subscription_id' => 0,
					'payment_id' => 0,
					'customer_id' => 0,
				),
			);

			$subscription_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'subscription_id' ) );
			$expiration_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$profile_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'profile_id' );
			$initial_amount   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_amount' );
			$recurring_amount   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_amount' );
			$download_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' );
			$transaction_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'transaction_id' );
			$status   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'status' );
			$created_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'created_date' );
			$bill_times   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bill_times' );
			$period   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'period' );
			$parent_payment_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'parent_payment_id' ) );
			$customer_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_id' ) );
			$customer_email   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_email' );
			$edd_price_option   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'edd_price_option' );
			$initial_tax_rate   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_tax_rate' );
			$initial_tax   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'initial_tax' );
			$recurring_tax_rate   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_tax_rate' );
			$recurring_tax   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'recurring_tax' );
			$notes   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'notes' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Subscription' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Subscription() does not exist. The subscription was not created.', 'action-edd_update_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( empty( $subscription_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The subscription_id argument cannot be empty. ', 'action-edd_update_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			$subscription = new EDD_Subscription( $subscription_id );
			if( empty( $subscription ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'Error: Invalid subscription id provided.', 'action-edd_update_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			//try to fetch the customer
			if( empty( $customer_id ) ){
				if( ! empty( $customer_email ) ){
					$tmpcustomer = EDD()->customers->get_customer_by( 'email', $customer_email );
					if( isset( $tmpcustomer->id ) && ! empty( $tmpcustomer->id ) ) {
						$customer_id = $tmpcustomer->id;
					}
				}
			} else {
				$tmpcustomer = EDD()->customers->get_customer_by( 'id', $customer_id );
				if( isset( $tmpcustomer->id ) && ! empty( $tmpcustomer->id ) ) {
					$customer_id = $tmpcustomer->id;
				}
			}

			$sub_args = array();
			
			if( $expiration_date ){
				$sub_args['expiration'] = date( 'Y-m-d H:i:s', strtotime( $expiration_date, current_time( 'timestamp' ) ) );
			}
			
			if( $created_date ){
				$sub_args['created'] = date( 'Y-m-d H:i:s', strtotime( $created_date, current_time( 'timestamp' ) ) );
			}
			
			if( $status ){
				$sub_args['status'] = sanitize_text_field( $status );
			}
			
			if( $profile_id ){
				$sub_args['profile_id'] = sanitize_text_field( $profile_id );
			}
			
			if( $transaction_id ){
				$sub_args['transaction_id'] = sanitize_text_field( $transaction_id );
			}
			
			if( $initial_amount ){
				$sub_args['initial_amount'] = edd_sanitize_amount( sanitize_text_field( $initial_amount ) );
			}
			
			if( $recurring_amount ){
				$sub_args['recurring_amount'] = edd_sanitize_amount( sanitize_text_field( $recurring_amount ) );
			}
			
			if( $bill_times ){
				$sub_args['bill_times'] = absint( $bill_times );
			}
			
			if( $period ){
				$sub_args['period'] = sanitize_text_field( $period );
			}
			
			if( $parent_payment_id ){
				$sub_args['parent_payment_id'] = $parent_payment_id;
			}
			
			if( $download_id ){
				$sub_args['product_id'] = absint( $download_id );
			}
			
			if( $edd_price_option ){
				$sub_args['price_id'] = absint( $edd_price_option );
			}
			
			if( $customer_id ){
				$sub_args['customer_id'] = $customer_id;
			}
			
			if( $initial_tax_rate ){
				$sub_args['initial_tax_rate'] = edd_sanitize_amount( (float) $initial_tax_rate / 100 );
			}

			if( $initial_tax ){
				$sub_args['initial_tax'] = edd_sanitize_amount( $initial_tax );
			}

			if( $recurring_tax_rate ){
				$sub_args['recurring_tax_rate'] = edd_sanitize_amount( (float) $recurring_tax_rate / 100 );
			}

			if( $recurring_tax ){
				$sub_args['recurring_tax'] = edd_sanitize_amount( $recurring_tax );
			}

			$check = $subscription->update( $sub_args );

			if( $check ){

				if( ! empty( $notes ) ){
					if( WPWHPRO()->helpers->is_json( $notes ) ){
						$notes_arr = json_decode( $notes, true );
						foreach( $notes_arr as $snote ){
							$subscription->add_note( $snote );
						}
					}
				}
	
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The subscription was successfully updated.", 'action-edd_update_subscription-success' );
				$return_args['success'] = true;
				$return_args['data']['subscription_id'] = $subscription->id;
				$return_args['data']['subscription_arguments'] = $sub_args;
				$subscription_id = $subscription->id;
			} else {
				if( empty( $sub_args ) ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( "Error updating the subscription. No arguments/values for an update given.", 'action-edd_update_subscription-success' );
				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate( "Error updating the subscription.", 'action-edd_update_subscription-success' );
				}
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $subscription_id, $subscription, $sub_args, $return_args );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );
			die();
		}

		/**
		 * ###########
		 * #### edd_delete_subscription
		 * ###########
		 */

		public function action_edd_delete_subscription_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'subscription_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the subscription you would like to delete.', 'action-edd_delete_subscription-content' ) ),
				'keep_payment_meta'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" if you do not want to delet the relation of the subscription on the related payment. Default: no', 'action-edd_delete_subscription-content' ) ),
				'keep_list_of_trials'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" to delete the list of trials of the user that are related to the given subscription id. Default: no', 'action-edd_delete_subscription-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_delete_subscription-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_subscription-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_delete_subscription-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the new susbcription id and other arguments set during the deletion of the subscription.', 'action-edd_delete_subscription-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The subscription was successfully deleted.",
    "data": {
        "subscription_id": 21,
        "keep_payment_meta": false,
        "keep_list_of_trials": false
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_subscription.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_subscription',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete a subscription within Easy Digital Downloads - Recurring.', 'action-edd_delete_subscription-content' ),
				'description'       => $description
			);

		}

		public function action_edd_delete_subscription() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'subscription_id' => 0,
					'keep_payment_meta' => false,
					'keep_list_of_trials' => false,
				),
			);

			$subscription_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'subscription_id' ) );
			$keep_payment_meta   = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'keep_payment_meta' ) === 'yes' ) ? true : false;
			$keep_list_of_trials   = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'keep_list_of_trials' ) === 'yes' ) ? true : false;
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Subscription' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Subscription() does not exist. The subscription was not deleted.', 'action-edd_delete_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			$subscription = new EDD_Subscription( $subscription_id );
			if( empty( $subscription ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'Error: Invalid subscription id provided.', 'action-edd_update_subscription-failure' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( ! $keep_payment_meta && isset( $subscription->parent_payment_id ) ){
				delete_post_meta( $subscription->parent_payment_id, '_edd_subscription_payment' );
			}

			// Delete subscription from list of trials customer has used
			if( ! $keep_list_of_trials && isset( $subscription->product_id ) ){
				$subscription->customer->delete_meta( 'edd_recurring_trials', $subscription->product_id );
			}

			$check = $subscription->delete();

			if( $check ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The subscription was successfully deleted.", 'action-edd_delete_subscription-success' );
				$return_args['success'] = true;
				$return_args['data']['subscription_id'] = $subscription_id;
				$return_args['data']['keep_payment_meta'] = $keep_payment_meta;
				$return_args['data']['keep_list_of_trials'] = $keep_list_of_trials;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error deleting the subscription.", 'action-edd_delete_subscription-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $subscription_id, $subscription, $return_args );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );
			die();
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

	}

	new WP_Webhooks_EDD_Subscriptions_Actions();

}