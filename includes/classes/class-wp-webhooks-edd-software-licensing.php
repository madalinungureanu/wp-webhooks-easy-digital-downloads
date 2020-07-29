<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_SL_Triggers' ) ){

	class WP_Webhooks_EDD_SL_Triggers{

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

		public function get_edd_license_statuses(){
			$statuses = array(
				'active' => WPWHPRO()->helpers->translate( 'Activated', 'trigger-edd-license-status-content' ),
				'inactive' => WPWHPRO()->helpers->translate( 'Deactivated', 'trigger-edd-license-status-content' ),
				'expired' => WPWHPRO()->helpers->translate( 'Expired', 'trigger-edd-license-status-content' ),
				'disabled' => WPWHPRO()->helpers->translate( 'Disabled', 'trigger-edd-license-status-content' ),
			);

			return apply_filters( 'wpwhpro/settings/edd_license_status', $statuses );
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

			$triggers[] = $this->trigger_edd_license_status_content();
			$triggers[] = $this->trigger_edd_license_activation_content();
			$triggers[] = $this->trigger_edd_license_deactivate_content();
			$triggers[] = $this->trigger_edd_license_creation_content();

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
			
			$active_webhooks   = WPWHPRO()->settings->get_active_webhooks();
			$availale_triggers = $active_webhooks['triggers'];

			if ( isset( $availale_triggers['edd_license_status'] ) ) {
				add_action( 'edd_sl_post_set_status', array( $this, 'wpwh_trigger_edd_license_status_init' ), 10, 2 );
				add_filter( 'ironikus_demo_test_edd_license_status', array( $this, 'wpwh_send_demo_edd_license_status' ), 10, 3 );
			}

			if ( isset( $availale_triggers['edd_license_activation'] ) ) {
				add_action( 'edd_sl_activate_license', array( $this, 'wpwh_trigger_edd_license_activation_init' ), 10, 2 );
				add_filter( 'ironikus_demo_test_edd_license_activation', array( $this, 'wpwh_send_demo_edd_license_activation' ), 10, 3 );
			}

			if ( isset( $availale_triggers['edd_license_deactivate'] ) ) {
				add_action( 'edd_sl_deactivate_license', array( $this, 'wpwh_trigger_edd_license_deactivate_init' ), 10, 2 );
				add_filter( 'ironikus_demo_test_edd_license_deactivate', array( $this, 'wpwh_send_demo_edd_license_deactivate' ), 10, 3 );
			}

			if ( isset( $availale_triggers['edd_license_creation'] ) ) {
				add_action( 'edd_sl_store_license', array( $this, 'wpwh_trigger_edd_license_creation_init' ), 10, 4 );
				add_filter( 'ironikus_demo_test_edd_license_creation', array( $this, 'wpwh_send_demo_edd_license_creation' ), 10, 3 );
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
		public function trigger_edd_license_status_content(){

			$choices = $this->get_edd_license_statuses();

			$parameter = array(
				'ID' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The license id.', 'trigger-edd_license_status-content' ) ),
				'key' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The license key.', 'trigger-edd_license_status-content' ) ),
				'customer_email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer.', 'trigger-edd_license_status-content' ) ),
				'customer_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full customer name.', 'trigger-edd_license_status-content' ) ),
				'product_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The id of the product.', 'trigger-edd_license_status-content' ) ),
				'product_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full product name.', 'trigger-edd_license_status-content' ) ),
				'activation_limit' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The activation limit.', 'trigger-edd_license_status-content' ) ),
				'activation_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of total activations.', 'trigger-edd_license_status-content' ) ),
				'activated_urls' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A list of activated URLs.', 'trigger-edd_license_status-content' ) ),
				'expiration' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The expiration date in SQL format.', 'trigger-edd_license_status-content' ) ),
				'is_lifetime' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number 1 or 0 if it is a lifetime.', 'trigger-edd_license_status-content' ) ),
				'status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The current license status.', 'trigger-edd_license_status-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_license_status.php' );
			$description = ob_get_clean();

			$settings = array(
				'data' => array(
					'wpwhpro_trigger_edd_license_status_whitelist_status' => array(
						'id'          => 'wpwhpro_trigger_edd_license_status_whitelist_status',
						'type'        => 'select',
						'multiple'    => true,
						'choices'      => $choices,
						'label'       => WPWHPRO()->helpers->translate('Trigger on selected license status changes', 'trigger-edd_license_status-content'),
						'placeholder' => '',
						'required'    => false,
						'description' => WPWHPRO()->helpers->translate('Select only the license statuses you want to fire the trigger on. You can choose multiple ones. If none is selected, all are triggered.', 'trigger-edd_license_status-content')
					),
				)
			);

			return array(
				'trigger' => 'edd_license_status',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD License Status Updates', 'trigger-edd_license_status-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_license_status( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on certain status changes of licenses within Easy Digital Downloads.', 'trigger-edd_license_status-content' ),
				'description' => $description,
				'callback' => 'test_edd_license_status'
			);

		}

		/*
		* Register the edd license status post delay trigger logic
		*/
		public function wpwh_trigger_edd_license_status_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_license_status' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_license_status( $license_id = 0, $new_status = '' ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_license_status' );
			$response_data_array = array();

			foreach( $webhooks as $webhook ){

				$is_valid = true;

				if( isset( $webhook['settings'] ) ){
					foreach( $webhook['settings'] as $settings_name => $settings_data ){

						if( $settings_name === 'wpwhpro_trigger_edd_license_status_whitelist_status' && ! empty( $settings_data ) ){
							if( ! in_array( $new_status, $settings_data ) ){
								$is_valid = false;
							}
						}

					}
				}

				if( $is_valid ) {

                    $license_data = $this->edd_get_license_data( $license_id );
					
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $license_data );

					do_action( 'wpwhpro/webhooks/trigger_edd_license_status', $license_id, $new_status, $license_data, $response_data_array );
				}
				
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_license_status( $data, $webhook, $webhook_group ){

			$data = array(
                'ID'               => 1234,
                'key'              => '736b31fec1ecb01c28b51a577bb9c2b3',
                'customer_name'    => 'Jane Doe',
                'customer_email'   => 'jane@test.com',
                'product_id'       => 4321,
                'product_name'     => 'Sample Product',
                'activation_limit' => 1,
                'activation_count' => 1,
                'activated_urls'   => 'sample.com',
                'expiration'       => date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ),
                'is_lifetime'      => 0,
                'status'           => 'active',
            );

			return $data;
        }
        
		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD License Activation
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_license_activation_content(){

			$parameter = array(
				'ID' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The license id.', 'trigger-edd_license_activation-content' ) ),
				'key' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The license key.', 'trigger-edd_license_activation-content' ) ),
				'customer_email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer.', 'trigger-edd_license_activation-content' ) ),
				'customer_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full customer name.', 'trigger-edd_license_activation-content' ) ),
				'product_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The id of the product.', 'trigger-edd_license_activation-content' ) ),
				'product_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full product name.', 'trigger-edd_license_activation-content' ) ),
				'activation_limit' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The activation limit.', 'trigger-edd_license_activation-content' ) ),
				'activation_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of total activations.', 'trigger-edd_license_activation-content' ) ),
				'activated_urls' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A list of activated URLs.', 'trigger-edd_license_activation-content' ) ),
				'expiration' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The expiration date in SQL format.', 'trigger-edd_license_activation-content' ) ),
				'is_lifetime' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number 1 or 0 if it is a lifetime.', 'trigger-edd_license_activation-content' ) ),
				'status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The current license status.', 'trigger-edd_license_activation-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_license_activation.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_license_activation',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD License Activation', 'trigger-edd_license_activation-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_license_activation( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on activation of a license within Easy Digital Downloads.', 'trigger-edd_license_activation-content' ),
				'description' => $description,
				'callback' => 'test_edd_license_activation'
			);

		}

		/*
		* Register the edd license status post delay trigger logic
		*/
		public function wpwh_trigger_edd_license_activation_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_license_activation' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_license_activation( $license_id = 0, $download_id = 0 ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_license_activation' );
			$response_data_array = array();

			foreach( $webhooks as $webhook ){
                $license_data = $this->edd_get_license_data( $license_id, $download_id );
                $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $license_data );
                do_action( 'wpwhpro/webhooks/trigger_edd_license_activation', $license_id, $download_id, $license_data, $response_data_array );
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_license_activation( $data, $webhook, $webhook_group ){

			$data = array(
                'ID'               => 1234,
                'key'              => '736b31fec1ecb01c28b51a577bb9c2b3',
                'customer_name'    => 'Jane Doe',
                'customer_email'   => 'jane@test.com',
                'product_id'       => 4321,
                'product_name'     => 'Sample Product',
                'activation_limit' => 1,
                'activation_count' => 1,
                'activated_urls'   => 'sample.com',
                'expiration'       => date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ),
                'is_lifetime'      => 0,
                'status'           => 'active',
            );

			return $data;
		}
        
		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD License Deactivation
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_license_deactivate_content(){

			$parameter = array(
				'ID' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The license id.', 'trigger-edd_license_deactivate-content' ) ),
				'key' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The license key.', 'trigger-edd_license_deactivate-content' ) ),
				'customer_email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer.', 'trigger-edd_license_deactivate-content' ) ),
				'customer_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full customer name.', 'trigger-edd_license_deactivate-content' ) ),
				'product_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The id of the product.', 'trigger-edd_license_deactivate-content' ) ),
				'product_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full product name.', 'trigger-edd_license_deactivate-content' ) ),
				'activation_limit' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The activation limit.', 'trigger-edd_license_deactivate-content' ) ),
				'activation_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of total activations.', 'trigger-edd_license_deactivate-content' ) ),
				'activated_urls' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A list of activated URLs.', 'trigger-edd_license_deactivate-content' ) ),
				'expiration' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The expiration date in SQL format.', 'trigger-edd_license_deactivate-content' ) ),
				'is_lifetime' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number 1 or 0 if it is a lifetime.', 'trigger-edd_license_deactivate-content' ) ),
				'status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The current license status.', 'trigger-edd_license_deactivate-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_license_deactivate.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_license_deactivate',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD License Deactivation', 'trigger-edd_license_deactivate-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_license_deactivate( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on deactivation of a license within Easy Digital Downloads.', 'trigger-edd_license_deactivate-content' ),
				'description' => $description,
				'callback' => 'test_edd_license_deactivate'
			);

		}

		/*
		* Register the edd license status post delay trigger logic
		*/
		public function wpwh_trigger_edd_license_deactivate_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_license_deactivate' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_license_deactivate( $license_id = 0, $download_id = 0 ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_license_deactivate' );
			$response_data_array = array();

			foreach( $webhooks as $webhook ){
                $license_data = $this->edd_get_license_data( $license_id, $download_id );
                $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $license_data );
                do_action( 'wpwhpro/webhooks/trigger_edd_license_deactivate', $license_id, $download_id, $license_data, $response_data_array );
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_license_deactivate( $data, $webhook, $webhook_group ){

			$data = array(
                'ID'               => 1234,
                'key'              => '736b31fec1ecb01c28b51a577bb9c2b3',
                'customer_name'    => 'Jane Doe',
                'customer_email'   => 'jane@test.com',
                'product_id'       => 4321,
                'product_name'     => 'Sample Product',
                'activation_limit' => 1,
                'activation_count' => 1,
                'activated_urls'   => 'sample.com',
                'expiration'       => date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ),
                'is_lifetime'      => 0,
                'status'           => 'inactive',
            );

			return $data;
		}
        
		/**
		 * ###########
		 * #### WEBHOOK - Send Data On EDD License Creation
		 * ###########
		 */

		/*
        * Register the trigger as an element
        */
		public function trigger_edd_license_creation_content(){

			$parameter = array(
				'ID' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The license id.', 'trigger-edd_license_creation-content' ) ),
				'key' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The license key.', 'trigger-edd_license_creation-content' ) ),
				'customer_email' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer.', 'trigger-edd_license_creation-content' ) ),
				'customer_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full customer name.', 'trigger-edd_license_creation-content' ) ),
				'product_id' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The id of the product.', 'trigger-edd_license_creation-content' ) ),
				'product_name' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The full product name.', 'trigger-edd_license_creation-content' ) ),
				'activation_limit' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The activation limit.', 'trigger-edd_license_creation-content' ) ),
				'activation_count' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of total activations.', 'trigger-edd_license_creation-content' ) ),
				'activated_urls' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A list of activated URLs.', 'trigger-edd_license_creation-content' ) ),
				'expiration' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The expiration date in SQL format.', 'trigger-edd_license_creation-content' ) ),
				'is_lifetime' => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number 1 or 0 if it is a lifetime.', 'trigger-edd_license_creation-content' ) ),
				'status' => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The current license status.', 'trigger-edd_license_creation-content' ) ),
			);

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/trigger_edd_license_creation.php' );
			$description = ob_get_clean();

			$settings = array();

			return array(
				'trigger' => 'edd_license_creation',
				'name'  => WPWHPRO()->helpers->translate( 'Send Data On EDD License Creation', 'trigger-edd_license_creation-content' ),
				'parameter' => $parameter,
				'settings'          => $settings,
				'returns_code'      => WPWHPRO()->helpers->display_var( $this->wpwh_send_demo_edd_license_creation( array(), '', '' ) ), //Display some response code within the frontend
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires on creation of a license within Easy Digital Downloads.', 'trigger-edd_license_creation-content' ),
				'description' => $description,
				'callback' => 'test_edd_license_creation'
			);

		}

		/*
		* Register the edd license status post delay trigger logic
		*/
		public function wpwh_trigger_edd_license_creation_init(){
			WPWHPRO()->delay->add_post_delayed_trigger( array( $this, 'wpwh_trigger_edd_license_creation' ), func_get_args() );
		}

		/**
		 * Triggers once a new EDD payment was changed
		 *
		 * @param  integer $customer_id   Customer ID.
		 * @param  array   $args          Customer data.
		 */
		public function wpwh_trigger_edd_license_creation( $license_id = 0, $download_id = 0, $payment_id = 0, $type = '' ){
			$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'edd_license_creation' );
			$response_data_array = array();

			foreach( $webhooks as $webhook ){
                $license_data = $this->edd_get_license_data( $license_id, $download_id, $payment_id );
                $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $license_data );
                do_action( 'wpwhpro/webhooks/trigger_edd_license_creation', $license_id, $download_id, $payment_id, $type, $license_data, $response_data_array );
			}
		}

		/*
        * Register the demo data response
        */
		public function wpwh_send_demo_edd_license_creation( $data, $webhook, $webhook_group ){

			$data = array(
                'ID'               => 1234,
                'key'              => '736b31fec1ecb01c28b51a577bb9c2b3',
                'customer_name'    => 'Jane Doe',
                'customer_email'   => 'jane@test.com',
                'product_id'       => 4321,
                'product_name'     => 'Sample Product',
                'activation_limit' => 1,
                'activation_count' => 1,
                'activated_urls'   => 'sample.com',
                'expiration'       => date( 'Y-n-d H:i:s', current_time( 'timestamp' ) ),
                'is_lifetime'      => 0,
                'status'           => 'inactive',
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
			 return ( class_exists( 'EDD_Software_Licensing' ) ) ? true : false;
         }
         
         /**
         * Get relevant data for a given license ID.
         *
         * @param  integer $license_id License post ID.
         * @return array               License data.
         */
        public function edd_get_license_data( $license_id = 0, $download_id = 0, $payment_id = 0 ) {

            $license = edd_software_licensing()->get_license( $license_id );

            // The license ID supplied didn't give us a valid license, no data to return.
            if ( false === $license ) {
                return array();
            }

            if ( empty( $download_id ) ) {

                $download_id = $license->download_id;

            }

            if ( empty( $payment_id ) ) {

                $payment_id = $license->payment_id;

            }

            $customer_id = edd_get_payment_customer_id( $payment_id );

            if( empty( $customer_id ) ) {

                $user_info       = edd_get_payment_meta_user_info( $payment_id );
                $customer        = new stdClass;
                $customer->email = edd_get_payment_user_email( $payment_id );
                $customer->name  = $user_info['first_name'];

            } else {

                $customer = new EDD_Customer( $customer_id );

            }

            if( $license->is_lifetime ) {
                $expiration = 'never';
            } else {
                $expiration = $license->expiration;
                $expiration = date( 'Y-n-d H:i:s', $expiration );
            }

            $download = method_exists( $license, 'get_download' ) ? $license->get_download() : new EDD_SL_Download( $download_id );


            $license_data = array(
                'ID'               => $license->ID,
                'key'              => $license->key,
                'customer_email'   => $customer->email,
                'customer_name'    => $customer->name,
                'product_id'       => $download_id,
                'product_name'     => $download->get_name(),
                'activation_limit' => $license->activation_limit,
                'activation_count' => $license->activation_count,
                'activated_urls'   => implode( ',', $license->sites ),
                'expiration'       => $expiration,
                'is_lifetime'      => $license->is_lifetime ? '1' : '0',
                'status'           => $license->status,
            );

            return $license_data;
        }

	} // End class

	new WP_Webhooks_EDD_SL_Triggers();

}