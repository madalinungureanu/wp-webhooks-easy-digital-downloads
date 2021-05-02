<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_Software_Licensing_Actions' ) ){

	class WP_Webhooks_EDD_Software_Licensing_Actions{
		private $wpwh_use_new_filter = null;

		public function __construct() {

			if( $this->wpwh_use_new_action_filter() ){
				add_filter( 'wpwhpro/webhooks/add_webhook_actions', array( $this, 'add_webhook_actions' ), 20, 4 );
			} else {
				add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			}
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );

			add_filter( 'wpwh/descriptions/actions/edd_create_payment/default_cart_details', array( $this, 'customize_edd_create_payment_default_cart_details' ), 10 );

		}

		/**
		 * ######################
		 * ###
		 * #### HELPERS
		 * ###
		 * ######################
		 */

		public function wpwh_use_new_action_filter(){

			if( $this->wpwh_use_new_filter !== null ){
				return $this->wpwh_use_new_filter;
			}

			$return = false;
			$version_current = '0';
			$version_needed = '0';
	
			if( defined( 'WPWHPRO_VERSION' ) ){
				$version_current = WPWHPRO_VERSION;
				$version_needed = '4.1.0';
			}
	
			if( defined( 'WPWH_VERSION' ) ){
				$version_current = WPWH_VERSION;
				$version_needed = '3.1.0';
			}
	
			if( version_compare( (string) $version_current, (string) $version_needed, '>=') ){
				$return = true;
			}

			$this->wpwh_use_new_filter = $return;

			return $return;
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
					$default_data[ $key ]['item_number']['options']['license_key'] = 'iuzgsdfibasfsdfsafk';
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

			//licenses
			$actions[] = $this->action_edd_create_license_content();
			$actions[] = $this->action_edd_update_license_content();
			$actions[] = $this->action_edd_renew_license_content();
			$actions[] = $this->action_edd_delete_license_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * We always send three different properties with the defined wehook.
		 * @param $action - the defined action defined within the edd_create_license function
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $response, $action, $webhook, $api_key = '' ){

			if( ! $this->is_active() ){
				return $response;
			}

			//Backwards compatibility prior 4.1.0 (wpwhpro) or 3.1.0 (wpwh)
			if( ! $this->wpwh_use_new_action_filter() ){
				$api_key = $webhook;
				$webhook = $action;
				$action = $response;

				$active_webhooks = WPWHPRO()->settings->get_active_webhooks();
				$available_actions = $active_webhooks['actions'];

				if( ! isset( $available_actions[ $action ] ) ){
					return $response;
				}
			}

			$return_data = null;

			switch( $action ){
				case 'edd_create_license':
					$return_data = $this->action_edd_create_license();
					break;
				case 'edd_update_license':
					$return_data = $this->action_edd_update_license();
					break;
				case 'edd_renew_license':
					$return_data = $this->action_edd_renew_license();
					break;
				case 'edd_delete_license':
					$return_data = $this->action_edd_delete_license();
					break;
			}

			//Make sure we only fire the response in case the old logic is used
			if( $return_data !== null && ! $this->wpwh_use_new_action_filter() ){
				WPWHPRO()->webhook->echo_action_data( $return_data );
				die();
			}

			if( $return_data !== null ){
				$response = $return_data;
			}
			
			return $response;
		}

		/**
		 * ###########
		 * #### edd_create_license
		 * ###########
		 */

		public function action_edd_create_license_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'download_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the download you want to associate with the license. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'payment_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the payment you want to associate with the license. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'price_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) In case you work with multiple pricing options (variations) within the same product, please set the pricing id here. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'cart_index'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The numerical index in the cart items array of the product the license key is associated with. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'existing_license_ids'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string of existing license ids. Please see the description for further information.', 'action-edd_create_license-content' ) ),
				'parent_license_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Set the parent id of this license in case you want to use this license as a child license. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'activation_limit'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) A number representing the amount of possible activations at the same time. set it to 0 for unlimited activations. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'license_length'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The length of the license key.', 'action-edd_create_license-content' ) ),
				'expiration_date'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) In case you want to customize the expiration date, you can define the date here. Otherwise it will be calculated based on the added product. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'is_lifetime'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" to mark the license as a lifetime license. Default: no', 'action-edd_create_license-content' ) ),
				'manage_sites'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple site urls. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'logs'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple logs. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'license_meta'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple meta values. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'license_action'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Do additional, native actions using the license. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_create_license-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_license-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_create_license-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the new license id and other arguments set during the creation of the license.', 'action-edd_create_license-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The license was successfully created.",
    "data": {
        "license_id": 17,
        "download_id": 285,
        "payment_id": 843,
        "price_id": "2",
        "cart_index": 0,
        "license_options": {
            "activation_limit": 0,
            "license_length": "32",
            "expiration_date": 1621654140,
            "is_lifetime": true
        },
        "license_meta": "{\n  \"meta_1\": \"test1\",\n  \"meta_2\": \"ironikus-serialize{\\\"test_key\\\":\\\"wow\\\",\\\"testval\\\":\\\"new\\\"}\"\n}",
        "logs": "[\n  {\n    \"title\": \"Log 1\",\n    \"message\": \"This is my description for log 1\"\n  },\n  {\n    \"title\": \"Log 2\",\n    \"message\": \"This is my description for log 2\",\n    \"type\": null\n  }\n]"
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_license.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_license',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a license within Easy Digital Downloads - Software Licensing.', 'action-edd_create_license-content' ),
				'description'       => $description
			);

		}

		public function action_edd_create_license() {

            $response_body = WPWHPRO()->helpers->get_response_body();
            $license_id = 0;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'license_id' => 0,
					'download_id' => 0,
					'payment_id' => 0,
					'price_id' => false,
					'cart_index' => 0,
					'license_options' => array(),
					'license_meta' => array(),
				),
			);

			$download_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' ) );
			$payment_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_id' ) );
			$price_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'price_id' );
			$cart_index   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'cart_index' ) );
			$existing_license_ids   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'existing_license_ids' );
			$parent_license_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'parent_license_id' );
			$activation_limit   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'activation_limit' ) );
			$license_length   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_length' );
			$expiration_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$is_lifetime   = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_lifetime' ) === 'yes' ) ? true : false;
			$manage_sites   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'manage_sites' );
			$logs   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'logs' );
			$license_meta   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_meta' );
			$license_action   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_action' ) );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_SL_License' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_SL_License() does not exist. The license was not created.', 'action-edd_create_license-failure' );
				return $return_args;
			}

			if( ! class_exists( 'EDD_SL_Download' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_SL_Download() does not exist. The license was not created.', 'action-edd_create_license-failure' );
				return $return_args;
			}

			if( empty( $download_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The download_id argument cannot be empty. The license was not created.', 'action-edd_create_license-failure' );
				return $return_args;
			}

			if( empty( $payment_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The payment_id argument cannot be empty. The license was not created.', 'action-edd_create_license-failure' );
				return $return_args;
            }
            
            $purchased_download   = new EDD_SL_Download( $download_id );
            if( ! $purchased_download->licensing_enabled() ){
                $return_args['msg'] = WPWHPRO()->helpers->translate( 'The download given within the download_id argument has no licensing activated within the product. The license was not created.', 'action-edd_create_license-failure' );
				return $return_args;
            }

            $license = new EDD_SL_License();
            
            if( empty( $price_id ) ){
                $price_id = false;
            }

            $license_options = array();

            if( ! empty( $existing_license_ids ) ){
                if( WPWHPRO()->helpers->is_json( $existing_license_ids ) ){
                    $existing_license_ids_arr = json_decode( $existing_license_ids, true );
                    if( is_array( $existing_license_ids_arr ) && ! empty( $existing_license_ids_arr ) ){
                        $license_options['existing_license_ids'] = $existing_license_ids_arr;
                    }
                }
            }

            if( ! empty( $parent_license_id ) ){
                $license_options['parent_license_id'] = $parent_license_id;
            }

            if( ! empty( $activation_limit ) || $activation_limit === 0 ){
                $license_options['activation_limit'] = $activation_limit;
            }

            if( ! empty( $license_length ) ){
                $license_options['license_length'] = $license_length;
            }

            if( ! empty( $expiration_date ) ){
                $license_options['expiration_date'] = strtotime( $expiration_date );
            }

            if( ! empty( $is_lifetime ) ){
                $license_options['is_lifetime'] = $is_lifetime;
            }

			$check = $license->create( $download_id, $payment_id, $price_id, $cart_index, $license_options );

			if( $check ){

                //Make sure we set again the activation limit since by default it was not set properly
                if( ! empty( $activation_limit ) || $activation_limit === 0 ){
                    $license->update_meta( '_edd_sl_limit', $activation_limit );
                }

                if( ! empty( $logs ) ){
					if( WPWHPRO()->helpers->is_json( $logs ) ){
						$logs_arr = json_decode( $logs, true );
						foreach( $logs_arr as $slog ){

                            $title = WPWH_EDD_NAME;
                            if( isset( $slog['title'] ) && ! empty( $slog['title'] ) ){
                                $title = $slog['title'];
                            }

                            $message = '';
                            if( isset( $slog['message'] ) && ! empty( $slog['message'] ) ){
                                $message = $slog['message'];
                            }

                            $type = null;
                            if( isset( $slog['type'] ) && ! empty( $slog['type'] ) ){
                                $type = $slog['type'];
                            }

							$license->add_log( $title, $message, $type );
						}
					}
				}

                if( ! empty( $manage_sites ) ){
                    if( WPWHPRO()->helpers->is_json( $manage_sites ) ){
                        $manage_sites_arr = json_decode( $manage_sites, true );
                        foreach( $manage_sites_arr as $site ){

                            $ident = 'remove:';
                            if( is_string( $site ) && substr( $site , 0, strlen( $ident ) ) === $ident ){
                                $saction = 'remove';
                                $site = str_replace( $ident, '', $site );
                            } else {
                                $saction = 'add';
                            }

                            switch( $saction ){
                                case 'remove':
                                    $license->remove_site( $site );
                                break;
                                case 'add':
                                default: 
                                    $license->add_site( $site );
                                break;
                            }
                        }
                    }
                }

                if( ! empty( $license_meta ) ){
                    if( WPWHPRO()->helpers->is_json( $license_meta ) ){
                        $license_meta_arr = json_decode( $license_meta, true );
                        foreach( $license_meta_arr as $skey => $sval ){

                            if( ! empty( $skey ) ){
                                if( $sval == 'ironikus-delete' ){
                                    $license->delete_meta( $skey );
                                } else {
                                    $ident = 'ironikus-serialize';
                                    if( is_string( $sval ) && substr( $sval , 0, strlen( $ident ) ) === $ident ){
                                        $serialized_value = trim( str_replace( $ident, '', $sval ),' ' );

                                        if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
                                            $serialized_value = json_decode( $serialized_value );
                                        }

                                        $license->update_meta( $skey, $serialized_value );

                                    } else {
                                        $license->update_meta( $skey, maybe_unserialize( $sval ) );
                                    }
                                }
                            }
                        }
                    }
				}
				
				if( ! empty( $license_action ) ){
					switch( $license_action ){
						case 'enable':
							$license->enable();
						break;
						case 'disable':
							$license->disable();
						break;
					}
				}

                $license_id = $license->ID;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The license was successfully created.", 'action-edd_create_license-success' );
				$return_args['success'] = true;
				$return_args['data']['license_id'] = $license->ID;
				$return_args['data']['license_key'] = $license->get_license_key();
				$return_args['data']['download_id'] = $download_id;
				$return_args['data']['payment_id'] = $payment_id;
				$return_args['data']['price_id'] = $price_id;
				$return_args['data']['cart_index'] = $cart_index;
				$return_args['data']['license_options'] = $license_options;
				$return_args['data']['license_meta'] = $license_meta;
				$return_args['data']['logs'] = $logs;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error creating the license.", 'action-edd_create_license-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $license_id, $license, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_update_license
		 * ###########
		 */

		public function action_edd_update_license_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'license_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The license id or the license key of the license you would like to update. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'download_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the download you want to associate with the license. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'payment_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the payment you want to associate with the license. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'license_key'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A new license key for the susbcription. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'price_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) In case you work with multiple pricing options (variations) within the same product, please set the pricing id here. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'cart_index'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The numerical index in the cart items array of the product the license key is associated with. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'status'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the given license. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'parent_license_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Set the parent id of this license in case you want to use this license as a child license. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'activation_limit'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) A number representing the amount of possible activations at the same time. set it to 0 for unlimited activations. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'date_created'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) In case you want to customize the creation date, you can define the date here. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'expiration_date'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) In case you want to customize the expiration date, you can define the date here. Otherwise it will be calculated based on the added product. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'manage_sites'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple site urls. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'logs'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple logs. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'license_meta'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple meta values. Please see the description for further details.', 'action-edd_update_license-content' ) ),
				'license_action'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Do additional, native actions using the license. Please see the description for further details.', 'action-edd_create_license-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_update_license-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_license-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_update_license-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the license id, as well as the license key and other arguments set during the update of the license.', 'action-edd_update_license-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The license was successfully updated.",
    "data": {
        "license_id": 17,
        "download_id": 176,
        "payment_id": 711,
        "price_id": "2",
        "cart_index": 0,
        "license_options": {
            "download_id": 176,
            "payment_id": 711,
            "price_id": "2",
            "expiration": 1621690140,
            "customer_id": "1",
            "user_id": "1"
        },
        "license_meta": "{\n  \"meta_5\": \"test5\",\n  \"meta_6\": \"ironikus-serialize{\\\"test_key\\\":\\\"wow\\\",\\\"testval\\\":\\\"new\\\"}\"\n}",
        "license_key": "e5e52aa45bb0e7c82a471e8234f6e427",
        "logs": "[\n  {\n    \"title\": \"Log 5\",\n    \"message\": \"This is my description for log 1\"\n  },\n  {\n    \"title\": \"Log 6\",\n    \"message\": \"This is my description for log 2\",\n    \"type\": null\n  }\n]"
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_license.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_license',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update a license within Easy Digital Downloads - Software Licensing.', 'action-edd_update_license-content' ),
				'description'       => $description
			);

		}

		public function action_edd_update_license() {

            $response_body = WPWHPRO()->helpers->get_response_body();
            $license_id = 0;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'license_id' => 0,
					'license_key' => 0,
					'download_id' => 0,
					'payment_id' => 0,
					'price_id' => false,
					'cart_index' => 0,
					'license_options' => array(),
					'license_meta' => array(),
				),
			);

			$license_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_id' );
			$license_key   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_key' );
			$download_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' ) );
			$payment_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_id' ) );
			$price_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'price_id' );
			$status   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'status' ) );
			$cart_index   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'cart_index' ) );
			$date_created   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'date_created' );
			$parent_license_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'parent_license_id' );
			$activation_limit   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'activation_limit' ) );
			$expiration_date   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$manage_sites   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'manage_sites' );
			$logs   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'logs' );
			$license_meta   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_meta' );
			$license_action   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_action' ) );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_SL_License' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_SL_License() does not exist. The license was not created.', 'action-edd_update_license-failure' );
				return $return_args;
			}

			if( ! class_exists( 'EDD_Payment' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Payment() does not exist. The license was not created.', 'action-edd_update_license-failure' );
				return $return_args;
			}

			if( empty( $license_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The license_id argument cannot be empty. The license was not updated.', 'action-edd_update_license-failure' );
				return $return_args;
			}
            
            $payment = new EDD_Payment( $payment_id );
            $license = new EDD_SL_License( $license_id );
            
            if( empty( $price_id ) ){
                $price_id = null;
            }

            $license_options = array();

            if( ! empty( $license_key ) && $license_key !== 'regenerate' ){
                $license_options['license_key'] = $license_key;
            }

            if( ! empty( $download_id ) ){
                $license_options['download_id'] = $download_id;
            }

            if( ! empty( $payment_id ) ){
                $license_options['payment_id'] = $payment_id;
            }

            if( ! empty( $price_id ) ){
                $license_options['price_id'] = $price_id;
            }

            if( ! empty( $status ) ){
                $license_options['status'] = $status;
            }

            if( ! empty( $cart_index ) ){
                $license_options['cart_index'] = $cart_index;
            }

            if( ! empty( $date_created ) ){
                $license_options['date_created'] = date("Y-m-d H:i:s", strtotime( $date_created ) );
            }

            if( ! empty( $expiration_date ) ){
                $license_options['expiration'] = strtotime( $expiration_date );
            } else {
                if( intval( $expiration_date ) === 0 ){
                    $license_options['expiration'] = 0; //make it lifetime
                }
            }

            if( ! empty( $parent_license_id ) ){
                $license_options['parent'] = date("Y-m-d H:i:s", strtotime( $parent_license_id ) );
            }

            if( ! empty( $payment ) ){
                $license_options['customer_id'] = $payment->customer_id;
                $license_options['user_id'] = $payment->user_id;
            }

			$check = $license->update( $license_options );

			if( $check ){

				if( $license_key === 'regenerate' ){
					$license->regenerate_key();
				}

                //Make sure we set again the activation limit since by default it was not set properly
                if( ! empty( $activation_limit ) || $activation_limit === 0 ){
                    $license->update_meta( '_edd_sl_limit', $activation_limit );
                }

                if( ! empty( $logs ) ){
					if( WPWHPRO()->helpers->is_json( $logs ) ){
						$logs_arr = json_decode( $logs, true );
						foreach( $logs_arr as $slog ){

                            $title = WPWH_EDD_NAME;
                            if( isset( $slog['title'] ) && ! empty( $slog['title'] ) ){
                                $title = $slog['title'];
                            }

                            $message = '';
                            if( isset( $slog['message'] ) && ! empty( $slog['message'] ) ){
                                $message = $slog['message'];
                            }

                            $type = null;
                            if( isset( $slog['type'] ) && ! empty( $slog['type'] ) ){
                                $type = $slog['type'];
                            }

							$license->add_log( $title, $message, $type );
						}
					}
				}

                if( ! empty( $manage_sites ) ){
                    if( WPWHPRO()->helpers->is_json( $manage_sites ) ){
                        $manage_sites_arr = json_decode( $manage_sites, true );
                        foreach( $manage_sites_arr as $site ){

                            $ident = 'remove:';
                            if( is_string( $site ) && substr( $site , 0, strlen( $ident ) ) === $ident ){
                                $saction = 'remove';
                                $site = str_replace( $ident, '', $site );
                            } else {
                                $saction = 'add';
                            }

                            switch( $saction ){
                                case 'remove':
                                    $license->remove_site( $site );
                                break;
                                case 'add':
                                default: 
                                    $license->add_site( $site );
                                break;
                            }
                        }
                    }
                }

                if( ! empty( $license_meta ) ){
                    if( WPWHPRO()->helpers->is_json( $license_meta ) ){
                        $license_meta_arr = json_decode( $license_meta, true );
                        foreach( $license_meta_arr as $skey => $sval ){

                            if( ! empty( $skey ) ){
                                if( $sval == 'ironikus-delete' ){
                                    $license->delete_meta( $skey );
                                } else {
                                    $ident = 'ironikus-serialize';
                                    if( is_string( $sval ) && substr( $sval , 0, strlen( $ident ) ) === $ident ){
                                        $serialized_value = trim( str_replace( $ident, '', $sval ),' ' );

                                        if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
                                            $serialized_value = json_decode( $serialized_value );
                                        }

                                        $license->update_meta( $skey, $serialized_value );

                                    } else {
                                        $license->update_meta( $skey, maybe_unserialize( $sval ) );
                                    }
                                }
                            }
                        }
                    }
				}

				if( ! empty( $license_action ) ){
					switch( $license_action ){
						case 'enable':
							$license->enable();
						break;
						case 'disable':
							$license->disable();
						break;
					}
				}
				
				$new_fetched_license = new EDD_SL_License( $license->ID );

                $license_id = $license->ID;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The license was successfully updated.", 'action-edd_update_license-success' );
				$return_args['success'] = true;
				$return_args['data']['license_id'] = $license->ID;
				$return_args['data']['license_key'] = $new_fetched_license->license_key;
				$return_args['data']['download_id'] = $download_id;
				$return_args['data']['payment_id'] = $payment_id;
				$return_args['data']['price_id'] = $price_id;
				$return_args['data']['cart_index'] = $cart_index;
				$return_args['data']['license_options'] = $license_options;
				$return_args['data']['license_meta'] = $license_meta;
				$return_args['data']['logs'] = $logs;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error updating the license.", 'action-edd_update_license-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $license_id, $license, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_renew_license
		 * ###########
		 */

		public function action_edd_renew_license_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'license_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The license id or the license key of the license you would like to renew. Please see the description for further details.', 'action-edd_renew_license-content' ) ),
				'payment_id'     => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The payment id of the payment you want to use to process the renewal.', 'action-edd_renew_license-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_renew_license-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_renew_license-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_renew_license-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the license id, as well as the associated payment id of the license.', 'action-edd_renew_license-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The license was successfully renewed.",
    "data": {
        "license_id": 17,
        "payment_id": 843
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_renew_license.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_renew_license',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to renew a license within Easy Digital Downloads - Software Licensing.', 'action-edd_renew_license-content' ),
				'description'       => $description
			);

		}

		public function action_edd_renew_license() {

            $response_body = WPWHPRO()->helpers->get_response_body();
            $license_id = 0;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'license_id' => 0,
					'payment_id' => 0,
				),
			);

			$license_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_id' );
			$payment_id   = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_id' ) );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_SL_License' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_SL_License() does not exist. The license was not renewed.', 'action-edd_renew_license-failure' );
				return $return_args;
			}

			if( empty( $license_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The license_id argument cannot be empty. The license was not renewed.', 'action-edd_renew_license-failure' );
				return $return_args;
			}

			if( empty( $payment_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The payment_id argument cannot be empty. The license was not renewed.', 'action-edd_renew_license-failure' );
				return $return_args;
			}
            
            $license = new EDD_SL_License( $license_id );

			$check = $license->renew( $payment_id );

			if( $check ){
                $license_id = $license->ID;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The license was successfully renewed.", 'action-edd_renew_license-success' );
				$return_args['success'] = true;
				$return_args['data']['license_id'] = $license_id;
				$return_args['data']['payment_id'] = $payment_id;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error renewing the license.", 'action-edd_renew_license-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $license_id, $license, $return_args );
			}

			return $return_args;
		}
		/**
		 * ###########
		 * #### edd_delete_license
		 * ###########
		 */

		public function action_edd_delete_license_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'license_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The license id or the license key of the license you would like to delete. Please see the description for further details.', 'action-edd_delete_license-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_delete_license-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_license-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_delete_license-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the license id, as well as the license object.', 'action-edd_delete_license-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The license was successfully deleted.",
    "data": {
        "license_id": "4fc336680bf576cc0298777278ceb15a",
        "license": {
            "ID": 16
        }
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_license.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_license',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete a license within Easy Digital Downloads - Software Licensing.', 'action-edd_delete_license-content' ),
				'description'       => $description
			);

		}

		public function action_edd_delete_license() {

            $response_body = WPWHPRO()->helpers->get_response_body();
            $license_id = 0;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'license_id' => 0,
					'license' => 0,
				),
			);

			$license_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'license_id' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_SL_License' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_SL_License() does not exist. The license was not renewed.', 'action-edd_delete_license-failure' );
				return $return_args;
			}

			if( empty( $license_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The license_id argument cannot be empty. The license was not renewed.', 'action-edd_delete_license-failure' );
				return $return_args;
			}
            
            $license = new EDD_SL_License( $license_id );

			$check = $license->delete();

			if( $check ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The license was successfully deleted.", 'action-edd_delete_license-success' );
				$return_args['success'] = true;
				$return_args['data']['license_id'] = $license_id;
				$return_args['data']['license'] = $license;
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error deleting the license.", 'action-edd_delete_license-success' );
			}
		
			

			if( ! empty( $do_action ) ){
				do_action( $do_action, $license_id, $license, $return_args );
			}

			return $return_args;
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

	}

	new WP_Webhooks_EDD_Software_Licensing_Actions();

}