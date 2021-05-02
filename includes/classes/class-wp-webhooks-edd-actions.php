<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_EDD_Actions' ) ){

	class WP_Webhooks_EDD_Actions{
		private $wpedd_use_new_filter = null;

		public function __construct() {

			if( $this->wpwh_use_new_action_filter() ){
				add_filter( 'wpwhpro/webhooks/add_webhook_actions', array( $this, 'add_webhook_actions' ), 20, 4 );
			} else {
				add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			}
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );

		}

		/**
		 * ######################
		 * ###
		 * #### HELPERS
		 * ###
		 * ######################
		 */

		public function wpwh_use_new_action_filter(){

			if( $this->wpedd_use_new_filter !== null ){
				return $this->wpedd_use_new_filter;
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

			$this->wpedd_use_new_filter = $return;

			return $return;
		}

		/**
		 * ######################
		 * ###
		 * #### WEBHOOK ACTIONS
		 * ###
		 * ######################
		 */

		public function add_webhook_actions_content( $actions ){

			//downloads
			$actions[] = $this->action_edd_create_download_content();
			$actions[] = $this->action_edd_update_download_content();
			$actions[] = $this->action_edd_delete_download_content();

			//payment
			$actions[] = $this->action_edd_create_payment_content();
			$actions[] = $this->action_edd_update_payment_content();
			$actions[] = $this->action_edd_delete_payment_content();

			//customer
			$actions[] = $this->action_edd_create_customer_content();
			$actions[] = $this->action_edd_update_customer_content();
			$actions[] = $this->action_edd_delete_customer_content();

			//discount
			$actions[] = $this->action_edd_create_discount_content();
			$actions[] = $this->action_edd_update_discount_content();
			$actions[] = $this->action_edd_delete_discount_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * @param $action - the defined action that is currently called
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $response, $action, $webhook, $api_key = '' ){

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
				case 'edd_create_download':
					$return_data = $this->action_edd_create_download();
					break;
				case 'edd_update_download':
					$return_data = $this->action_edd_create_download( true );
					break;
				case 'edd_delete_download':
					$return_data = $this->action_edd_delete_download( true );
					break;
				case 'edd_create_payment':
					$return_data = $this->action_edd_create_payment();
					break;
				case 'edd_update_payment':
					$return_data = $this->action_edd_update_payment();
					break;
				case 'edd_delete_payment':
					$return_data = $this->action_edd_delete_payment();
					break;
				case 'edd_create_customer':
					$return_data = $this->action_edd_create_customer();
					break;
				case 'edd_update_customer':
					$return_data = $this->action_edd_update_customer();
					break;
				case 'edd_delete_customer':
					$return_data = $this->action_edd_delete_customer();
					break;
				case 'edd_create_discount':
					$return_data = $this->action_edd_create_discount();
					break;
				case 'edd_update_discount':
					$return_data = $this->action_edd_update_discount();
					break;
				case 'edd_delete_discount':
					$return_data = $this->action_edd_delete_discount();
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
		 * #### edd_create_download && edd_update_download
		 * ###########
		 */

		public function action_edd_create_download_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'price'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(float) The price of the download you want to use. Format: 19.99', 'action-create-download-content' ) ),
				'is_variable_pricing'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Set this value to "yes" if you want to activate variable pricing for this product. Default: no', 'action-create-download-content' ) ),
				'variable_prices'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A JSON formatted string, containing all of the variable product prices. Please see the description for further details.', 'action-create-download-content' ) ),
				'default_price_id'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(integer) The ID of the price variation you want to use as the default price.', 'action-create-download-content' ) ),
				'download_files'          		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing all of the downloable file. Please see the description for further details.', 'action-create-download-content' ) ),
				'bundled_products'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string, containing all of the bundled products. Please see the description for further details.', 'action-create-download-content' ) ),
				'bundled_products_conditions'   => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string that contains the price dependencies. Please see the description for further details.', 'action-create-download-content' ) ),
				'increase_earnings'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you would like to increase the lifetime earnings of this product. Please see the description for further details.', 'action-create-download-content' ) ),
				'decrease_earnings'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you would like to decrease the lifetime earnings of this product. Please see the description for further details.', 'action-create-download-content' ) ),
				'increase_sales'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Increase the number of sales from a statistical point of view. Please see the description for further details.', 'action-create-download-content' ) ),
				'decrease_sales'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Decrease the number of sales from a statistical point of view. Please see the description for further details.', 'action-create-download-content' ) ),
				'hide_purchase_link'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this string to "yes" to hide the purchase button under the download. Please see the description for more details.', 'action-create-download-content' ) ),
				'download_limit'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Limits how often a customer can globally download the purchase. Please see the description for further details.', 'action-create-download-content' ) ),
				'download_author'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The ID or the email of the user who added the post. Default is the current user ID.', 'action-create-download-content' ) ),
				'download_date'             	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The date of the post. Default is the current time. Format: 2018-12-31 11:11:11', 'action-create-download-content' ) ),
				'download_date_gmt'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The date of the post in the GMT timezone. Default is the value of $post_date.', 'action-create-download-content' ) ),
				'download_content'          	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post content. Default empty.', 'action-create-download-content' ) ),
				'download_content_filtered' 	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The filtered post content. Default empty.', 'action-create-download-content' ) ),
				'download_title'            	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post title. Default empty.', 'action-create-download-content' ) ),
				'download_excerpt'          	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post excerpt. Default empty.', 'action-create-download-content' ) ),
				'download_status'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post status. Default \'draft\'.', 'action-create-download-content' ) ),
				'comment_status'        		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Whether the post can accept comments. Accepts \'open\' or \'closed\'. Default is the value of \'default_comment_status\' option.', 'action-create-download-content' ) ),
				'ping_status'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Whether the post can accept pings. Accepts \'open\' or \'closed\'. Default is the value of \'default_ping_status\' option.', 'action-create-download-content' ) ),
				'download_password'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The password to access the post. Default empty.', 'action-create-download-content' ) ),
				'download_name'             	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post name. Default is the sanitized post title when creating a new post.', 'action-create-download-content' ) ),
				'to_ping'               		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Space or carriage return-separated list of URLs to ping. Default empty.', 'action-create-download-content' ) ),
				'pinged'                		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Space or carriage return-separated list of URLs that have been pinged. Default empty.', 'action-create-download-content' ) ),
				'download_parent'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(int) Set this for the post it belongs to, if any. Default 0.', 'action-create-download-content' ) ),
				'menu_order'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(int) The order the post should be displayed in. Default 0.', 'action-create-download-content' ) ),
				'download_mime_type'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The mime type of the post. Default empty.', 'action-create-download-content' ) ),
				'guid'                  		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Global Unique ID for referencing the post. Default empty.', 'action-create-download-content' ) ),
				'download_category'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A comma separated list of category names, slugs, or IDs. Defaults to value of the \'default_category\' option. Example: cat_1,cat_2,cat_3', 'action-create-download-content' ) ),
				'tags_input'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A comma separated list of tag names, slugs, or IDs. Default empty.', 'action-create-download-content' ) ),
				'tax_input'             		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A simple or JSON formatted string containing existing taxonomy terms. Default empty. More details within the description.', 'action-update-post-content' ) ),
				'meta_input'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A json or a comma and semicolon separated list of post meta values keyed by their post meta key. Default empty. More info in the description.', 'action-create-download-content' ) ),
				'wp_error'              		=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Whether to return a WP_Error on failure. Posible values: "yes" or "no". Default value: "no".', 'action-create-download-content' ) ),
				'do_action'             		=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-create-download-content' ) )
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_download-content' ) ),
				'msg'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-create-download-content' ) ),
				'data'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the payment id and further information.', 'action-create-download-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "Download successfully created",
    "data": {
        "download_id": 797,
        "download_data": {
            "post_type": "download",
            "meta_data": "{\n  \"meta_key_1\": \"This is my meta value 1\",\n  \"another_meta_key\": \"This is my second meta key!\",\n  \"third_meta_key\": \"ironikus-serialize{\\\"price\\\": \\\"100\\\"}\"\n}",
            "tax_input": false,
            "edd": {
                "increase_earnings": "25.49",
                "decrease_earnings": false,
                "increase_sales": "15",
                "decrease_sales": false,
                "edd_price": "11.11",
                "is_variable_pricing": 1,
                "edd_variable_prices": "{\n    \"1\": {\n        \"index\": \"1\",\n        \"name\": \"Variation 1\",\n        \"amount\": \"39.90\",\n        \"license_limit\": \"0\",\n        \"is_lifetime\": \"1\"\n    },\n    \"2\": {\n        \"index\": \"2\",\n        \"name\": \"Variation 2\",\n        \"amount\": \"49.90\",\n        \"license_limit\": \"4\"\n    }\n}",
                "default_price_id": "2",
                "edd_download_files": "{\n    \"1\": {\n        \"index\": \"0\",\n        \"attachment_id\": \"\",\n        \"thumbnail_size\": \"\",\n        \"name\": \"wp-webhooks-pro-\",\n        \"file\": \"https:\\/\\/domain.demo\\/wp-content\\/uploads\\/edd\\/2020\\/02\\/wp-webhooks-pro.zip\",\n        \"condition\": \"all\"\n    }\n}",
                "edd_bundled_products": false,
                "bundled_products_conditions": false,
                "hide_purchase_link": "on",
                "download_limit": 45
            }
        },
        "edd": []
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_download.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_download',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a download within Easy Digital Downloads.', 'action-edd_create_download-content' ),
				'description'       => $description
			);

		}
		public function action_edd_update_download_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'download_id'           		=> array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the existing download', 'action-update-download-content' ) ),
				'create_if_none'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" to create the download in case the given download id does not exist. Default: no', 'action-update-download-content' ) ),
				'price'           				=> array( 'short_description' => WPWHPRO()->helpers->translate( '(float) The price of the download you want to use. Format: 19.99', 'action-update-download-content' ) ),
				'is_variable_pricing'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Set this value to "yes" if you want to activate variable pricing for this product. Default: no', 'action-update-download-content' ) ),
				'variable_prices'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A JSON formatted string, containing all of the variable product prices. Please see the description for further details.', 'action-update-download-content' ) ),
				'default_price_id'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(integer) The ID of the price variation you want to use as the default price.', 'action-update-download-content' ) ),
				'download_files'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing all of the downloable file. Please see the description for further details.', 'action-update-download-content' ) ),
				'bundled_products'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string, containing all of the bundled products. Please see the description for further details.', 'action-update-download-content' ) ),
				'bundled_products_conditions'   => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string that contains the price dependencies. Please see the description for further details.', 'action-update-download-content' ) ),
				'increase_earnings'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you would like to increase the lifetime earnings of this product. Please see the description for further details.', 'action-update-download-content' ) ),
				'decrease_earnings'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you would like to decrease the lifetime earnings of this product. Please see the description for further details.', 'action-update-download-content' ) ),
				'increase_sales'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Increase the number of sales from a statistical point of view. Please see the description for further details.', 'action-update-download-content' ) ),
				'decrease_sales'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Decrease the number of sales from a statistical point of view. Please see the description for further details.', 'action-update-download-content' ) ),
				'hide_purchase_link'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this string to "yes" to hide the purchase button under the download. Please see the description for more details.', 'action-update-download-content' ) ),
				'download_limit'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) Limits how often a customer can globally download the purchase. Please see the description for further details.', 'action-update-download-content' ) ),
				'download_author'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The ID or the email of the user who added the post. Default is the current user ID.', 'action-update-download-content' ) ),
				'download_date'             	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The date of the post. Default is the current time. Format: 2018-12-31 11:11:11', 'action-update-download-content' ) ),
				'download_date_gmt'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The date of the post in the GMT timezone. Default is the value of $post_date.', 'action-update-download-content' ) ),
				'download_content'          	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post content. Default empty.', 'action-update-download-content' ) ),
				'download_content_filtered' 	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The filtered post content. Default empty.', 'action-update-download-content' ) ),
				'download_title'            	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post title. Default empty.', 'action-update-download-content' ) ),
				'download_excerpt'          	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post excerpt. Default empty.', 'action-update-download-content' ) ),
				'download_status'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post status. Default \'draft\'.', 'action-update-download-content' ) ),
				'comment_status'        		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Whether the post can accept comments. Accepts \'open\' or \'closed\'. Default is the value of \'default_comment_status\' option.', 'action-update-download-content' ) ),
				'ping_status'           		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Whether the post can accept pings. Accepts \'open\' or \'closed\'. Default is the value of \'default_ping_status\' option.', 'action-update-download-content' ) ),
				'download_password'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The password to access the post. Default empty.', 'action-update-download-content' ) ),
				'download_name'             	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The post name. Default is the sanitized post title when creating a new post.', 'action-update-download-content' ) ),
				'to_ping'               		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Space or carriage return-separated list of URLs to ping. Default empty.', 'action-update-download-content' ) ),
				'pinged'                		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Space or carriage return-separated list of URLs that have been pinged. Default empty.', 'action-update-download-content' ) ),
				'download_parent'           	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(int) Set this for the post it belongs to, if any. Default 0.', 'action-update-download-content' ) ),
				'menu_order'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(int) The order the post should be displayed in. Default 0.', 'action-update-download-content' ) ),
				'download_mime_type'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) The mime type of the post. Default empty.', 'action-update-download-content' ) ),
				'guid'                  		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) Global Unique ID for referencing the post. Default empty.', 'action-update-download-content' ) ),
				'download_category'         	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A comma separated list of category names, slugs, or IDs. Defaults to value of the \'default_category\' option. Example: cat_1,cat_2,cat_3', 'action-update-download-content' ) ),
				'tags_input'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A comma separated list of tag names, slugs, or IDs. Default empty.', 'action-update-download-content' ) ),
				'tax_input'             		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A simple or JSON formatted string containing existing taxonomy terms. Default empty. More details within the description.', 'action-update-post-content' ) ),
				'meta_input'            		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A json or a comma and semicolon separated list of post meta values keyed by their post meta key. Default empty. More info in the description.', 'action-update-download-content' ) ),
				'wp_error'              		=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Whether to return a WP_Error on failure. Posible values: "yes" or "no". Default value: "no".', 'action-update-download-content' ) ),
				'do_action'             		=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-update-download-content' ) )
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_download-content' ) ),
				'msg'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-update-download-content' ) ),
				'data'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the payment id and further information.', 'action-update-download-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "Download successfully created",
    "data": {
        "download_id": 797,
        "download_data": {
            "post_type": "download",
            "meta_data": "{\n  \"meta_key_1\": \"This is my meta value 1\",\n  \"another_meta_key\": \"This is my second meta key!\",\n  \"third_meta_key\": \"ironikus-serialize{\\\"price\\\": \\\"100\\\"}\"\n}",
            "tax_input": false,
            "create_if_none": false,
            "edd": {
                "increase_earnings": "25.49",
                "decrease_earnings": false,
                "increase_sales": "15",
                "decrease_sales": false,
                "edd_price": "11.11",
                "is_variable_pricing": 1,
                "edd_variable_prices": "{\n    \"1\": {\n        \"index\": \"1\",\n        \"name\": \"Variation 1\",\n        \"amount\": \"39.90\",\n        \"license_limit\": \"0\",\n        \"is_lifetime\": \"1\"\n    },\n    \"2\": {\n        \"index\": \"2\",\n        \"name\": \"Variation 2\",\n        \"amount\": \"49.90\",\n        \"license_limit\": \"4\"\n    }\n}",
                "default_price_id": "2",
                "edd_download_files": "{\n    \"1\": {\n        \"index\": \"0\",\n        \"attachment_id\": \"\",\n        \"thumbnail_size\": \"\",\n        \"name\": \"wp-webhooks-pro-\",\n        \"file\": \"https:\\/\\/domain.demo\\/wp-content\\/uploads\\/edd\\/2020\\/02\\/wp-webhooks-pro.zip\",\n        \"condition\": \"all\"\n    }\n}",
                "edd_bundled_products": false,
                "bundled_products_conditions": false,
                "hide_purchase_link": "on",
                "download_limit": 45
            }
        },
        "edd": []
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_download.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_download',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update (or create) a download within Easy Digital Downloads.', 'action-edd_update_download-content' ),
				'description'       => $description
			);

		}

		/**
		 * Create a download via an action call
		 * 
		 * this logic uses the same functionality as the create_post action for compliance
		 *
		 * @param $update - Wether to create or to update the post
		 */
		public function action_edd_create_download( $update = false ){

			$response_body = WPWHPRO()->helpers->get_response_body();
			$post_type = 'download';
			$download = null;
			$return_args = array(
				'success'   => false,
				'msg'       => '',
				'data'      => array(
					'download_id' => null,
					'download_data' => null,
					'edd' => array()
				)
			);

			$post_id                		= intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' ) );

			//edd related
			$increase_earnings      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_earnings' );//float
			$decrease_earnings      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'decrease_earnings' );//float
			$increase_sales      			= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_sales' ); //int
			$decrease_sales      			= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'decrease_sales' );//int
			$edd_price      				= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'price' );//float
			$is_variable_pricing      		= ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_variable_pricing' ) === 'yes' ) ? 1 : 0;//integer
			$edd_variable_prices      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'variable_prices' );//json string
			$default_price_id      			= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'default_price_id' );//integer
			$edd_download_files      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_files' );//json string
			$edd_bundled_products      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bundled_products' );//json string
			$bundled_products_conditions    = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bundled_products_conditions' );//json string
			$hide_purchase_link      		= ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'hide_purchase_link' ) === 'yes' ) ? 'on': 'off';
			$download_limit      			= intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_limit' ) );

			//default wp
			$post_author            = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_author' );
			$post_date              = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_date' ) );
			$post_date_gmt          = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_date_gmt' ) );
			$post_content           = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_content' );
			$post_content_filtered  = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_content_filtered' );
			$post_title             = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_title' );
			$post_excerpt           = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_excerpt' );
			$post_status            = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_status' ) );
			$comment_status         = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'comment_status' ) );
			$ping_status            = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'ping_status' ) );
			$post_password          = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_password' ) );
			$post_name              = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_name' ) );
			$to_ping                = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'to_ping' ) );
			$pinged                 = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'pinged' ) );
			$post_modified          = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_modified' ) );
			$post_modified_gmt      = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_modified_gmt' ) );
			$post_parent            = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_parent' ) );
			$menu_order             = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'menu_order' ) );
			$post_mime_type         = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_mime_type' ) );
			$guid                   = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'guid' ) );
			$post_category          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_category' );
			$tags_input             = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'tags_input' );
			$tax_input              = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'tax_input' );
			$meta_input             = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'meta_input' );
			$wp_error               = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'wp_error' ) == 'yes' )     ? true : false;
			$create_if_none         = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'create_if_none' ) == 'yes' )     ? true : false;
			$do_action              = sanitize_text_field( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( ! class_exists( 'EDD_Download' ) ){
				if ( empty( $post_id ) ) {
					$return_args['msg'] = WPWHPRO()->helpers->translate("The class EDD_Download does not exist. Please check if the plugin is active.", 'action-create-download-not-found' );

					return $return_args;
				}
			}

			if( $update && ! $create_if_none ){
				if ( empty( $post_id ) ) {
					$return_args['msg'] = WPWHPRO()->helpers->translate("The download id is required to update a download.", 'action-create-download-not-found' );

					return $return_args;
				}
			}

			if( ! empty( $post_id ) && get_post_type( $post_id ) !== 'download' ){
				$return_args['msg'] = WPWHPRO()->helpers->translate("The given download id is not a download.", 'action-create-download-not-found' );

				return $return_args;
			}

			$create_post_on_update = false;
			$post_data = array();

			if( $update ){
				$post = '';

				if( ! empty( $post_id ) ){
					$post = get_post( $post_id );
				}

				if( ! empty( $post ) ){
					if( ! empty( $post->ID ) ){
						$post_data['ID'] = $post->ID;
					}
				}

				if( empty( $post_data['ID'] ) ){

					$create_post_on_update = apply_filters( 'wpwhpro/run/create_action_edd_download_on_update', $create_if_none );

					if( empty( $create_post_on_update ) ){
						$return_args['msg'] = WPWHPRO()->helpers->translate("Download not found.", 'action-create-download-not-found' );

						return $return_args;
					}

				}

			}

			if( ! empty( $post_author ) ){

				$post_author_id = 0;
				if( is_numeric( $post_author ) ){
					$post_author_id = intval( $post_author );
				} elseif ( is_email( $post_author ) ) {
					$get_user = get_user_by( 'email', $post_author );
					if( ! empty( $get_user ) && ! empty( $get_user->data ) && ! empty( $get_user->data->ID ) ){
						$post_author_id = $get_user->data->ID;
					}
				}

				$post_data['post_author'] = $post_author_id;
			}

			if( ! empty( $post_date ) ){
				$post_data['post_date'] = date( "Y-m-d H:i:s", strtotime( $post_date ) );
			}

			if( ! empty( $post_date_gmt ) ){
				$post_data['post_date_gmt'] = date( "Y-m-d H:i:s", strtotime( $post_date_gmt ) );
			}

			if( ! empty( $post_content ) ){
				$post_data['post_content'] = $post_content;
			}

			if( ! empty( $post_content_filtered ) ){
				$post_data['post_content_filtered'] = $post_content_filtered;
			}

			if( ! empty( $post_title ) ){
				$post_data['post_title'] = $post_title;
			}

			if( ! empty( $post_excerpt ) ){
				$post_data['post_excerpt'] = $post_excerpt;
			}

			if( ! empty( $post_status ) ){
				$post_data['post_status'] = $post_status;
			}

			if( ! empty( $post_type ) ){
				$post_data['post_type'] = $post_type;
			}

			if( ! empty( $comment_status ) ){
				$post_data['comment_status'] = $comment_status;
			}

			if( ! empty( $ping_status ) ){
				$post_data['ping_status'] = $ping_status;
			}

			if( ! empty( $post_password ) ){
				$post_data['post_password'] = $post_password;
			}

			if( ! empty( $post_name ) ){
				$post_data['post_name'] = $post_name;
			}

			if( ! empty( $to_ping ) ){
				$post_data['to_ping'] = $to_ping;
			}

			if( ! empty( $pinged ) ){
				$post_data['pinged'] = $pinged;
			}

			if( ! empty( $post_modified ) ){
				$post_data['post_modified'] = date( "Y-m-d H:i:s", strtotime( $post_modified ) );
			}

			if( ! empty( $post_modified_gmt ) ){
				$post_data['post_modified_gmt'] = date( "Y-m-d H:i:s", strtotime( $post_modified_gmt ) );
			}

			if( ! empty( $post_parent ) ){
				$post_data['post_parent'] = $post_parent;
			}

			if( ! empty( $menu_order ) ){
				$post_data['menu_order'] = $menu_order;
			}

			if( ! empty( $post_mime_type ) ){
				$post_data['post_mime_type'] = $post_mime_type;
			}

			if( ! empty( $guid ) ){
				$post_data['guid'] = $guid;
			}

			//Setup post categories
			if( ! empty( $post_category ) ){
				$post_category_data = explode( ',', trim( $post_category, ',' ) );

				if( ! empty( $post_category_data ) ){
					$post_data['post_category'] = $post_category_data;
				}
			}

			//Setup meta tags
			if( ! empty( $tags_input ) ){
				$post_tags_data = explode( ',', trim( $tags_input, ',' ) );

				if( ! empty( $post_tags_data ) ){
					$post_data['tags_input'] = $post_tags_data;
				}
			}

			add_action( 'wp_insert_post', array( $this, 'edd_create_update_download_add_meta' ), 8, 1 );

			if( $update && ! $create_post_on_update ){
				$post_id = wp_update_post( $post_data, $wp_error );
			} else {
				$download = new EDD_Download();
				if( ! empty( $download ) ){
					$new_dl = $download->create( $post_data ); //$wp_error is useless here
					if( ! empty( $new_dl ) && ! empty( $download->ID ) ){
						$post_id = $download->ID;
					} else {
						//fallback
						$post_id = wp_insert_post( $post_data, $wp_error );
					}
				} else {
					$post_id = wp_insert_post( $post_data, $wp_error );
				}
			}
			
			remove_action( 'wp_insert_post', array( $this, 'edd_create_update_download_add_meta' ) );
			
			if ( ! is_wp_error( $post_id ) && is_numeric( $post_id ) ) {

				//Setup meta tax
				if( ! empty( $tax_input ) ){
					$remove_all = false;
					$tax_append = false; //Default by WP wp_set_object_terms
					$tax_data = array(
						'delete' => array(),
						'create' => array(),
					);

					if( WPWHPRO()->helpers->is_json( $tax_input ) ){
						$post_tax_data = json_decode( $tax_input, true );
						foreach( $post_tax_data as $taxkey => $single_meta ){
		
							//Validate special values
							if( $taxkey == 'wpwhtype' && $single_meta == 'ironikus-append' ){
								$tax_append = true;
								continue;
							}
		
							if( $taxkey == 'wpwhtype' && $single_meta == 'ironikus-remove-all' ){
								$remove_all = true;
								continue;
							}
		
							$meta_key           = sanitize_text_field( $taxkey );
							$meta_values        = $single_meta;
		
							if( ! empty( $meta_key ) ){
		
								if( ! is_array( $meta_values ) ){
									$meta_values = array( $meta_values );
								}
		
								//separate for deletion and for creation
								foreach( $meta_values as $svalue ){
									if( strpos( $svalue, '-ironikus-delete' ) !== FALSE ){
		
										if( ! isset( $tax_data['delete'][ $meta_key ] ) ){
											$tax_data['delete'][ $meta_key ] = array();
										}
		
										//Replace deletion value to correct original value
										$tax_data['delete'][ $meta_key ][] = str_replace( '-ironikus-delete', '', $svalue );
									} else {
		
										if( ! isset( $tax_data['create'][ $meta_key ] ) ){
											$tax_data['create'][ $meta_key ] = array();
										}
		
										$tax_data['create'][ $meta_key ][] = $svalue;
									}
								}
		
							}
						}
					} else {
						$post_tax_data = explode( ';', trim( $tax_input, ';' ) );
						foreach( $post_tax_data as $single_meta ){
		
							//Validate special values
							if( $single_meta == 'ironikus-append' ){
								$tax_append = true;
								continue;
							}
		
							if( $single_meta == 'ironikus-remove-all' ){
								$remove_all = true;
								continue;
							}
		
							$single_meta_data   = explode( ',', $single_meta );
							$meta_key           = sanitize_text_field( $single_meta_data[0] );
							$meta_values        = explode( ':', $single_meta_data[1] );
		
							if( ! empty( $meta_key ) ){
		
								if( ! is_array( $meta_values ) ){
									$meta_values = array( $meta_values );
								}
		
								//separate for deletion and for creation
								foreach( $meta_values as $svalue ){
									if( strpos( $svalue, '-ironikus-delete' ) !== FALSE ){
		
										if( ! isset( $tax_data['delete'][ $meta_key ] ) ){
											$tax_data['delete'][ $meta_key ] = array();
										}
		
										//Replace deletion value to correct original value
										$tax_data['delete'][ $meta_key ][] = str_replace( '-ironikus-delete', '', $svalue );
									} else {
		
										if( ! isset( $tax_data['create'][ $meta_key ] ) ){
											$tax_data['create'][ $meta_key ] = array();
										}
		
										$tax_data['create'][ $meta_key ][] = $svalue;
									}
								}
		
							}
						}
					}

					if( $update && ! $create_post_on_update ){
						foreach( $tax_data['delete'] as $tax_key => $tax_values ){
							wp_remove_object_terms( $post_id, $tax_values, $tax_key );
						}
					}

					foreach( $tax_data['create'] as $tax_key => $tax_values ){

						if( $remove_all ){
							wp_set_object_terms( $post_id, array(), $tax_key, $tax_append );
						} else {
							wp_set_object_terms( $post_id, $tax_values, $tax_key, $tax_append );
						}

					}

					#$post_data['tax_input'] = $tax_data;
				}

				//Map response data
				$post_data['meta_data'] = $meta_input;
				$post_data['tax_input'] = $tax_input;
				$post_data['create_if_none'] = $create_if_none;
				$post_data['edd'] = array(
					'increase_earnings' => $increase_earnings,
					'decrease_earnings' => $decrease_earnings,
					'increase_sales' => $increase_sales,
					'decrease_sales' => $decrease_sales,
					'edd_price' => $edd_price,
					'is_variable_pricing' => $is_variable_pricing,
					'edd_variable_prices' => $edd_variable_prices,
					'default_price_id' => $default_price_id,
					'edd_download_files' => $edd_download_files,
					'edd_bundled_products' => $edd_bundled_products,
					'bundled_products_conditions' => $bundled_products_conditions,
					'hide_purchase_link' => $hide_purchase_link,
					'download_limit' => $download_limit,
				);

				//START EDD logic
				$download = new EDD_Download( $post_id );
				if( ! empty( $download ) ){

					if( ! empty( $increase_earnings ) && is_numeric( $increase_earnings ) ){
						$download->increase_earnings( $increase_earnings );
					}

					if( ! empty( $decrease_earnings ) && is_numeric( $decrease_earnings ) ){
						$download->decrease_earnings( $decrease_earnings );
					}

					if( ! empty( $increase_sales ) && is_numeric( $increase_sales ) ){
						$download->increase_sales( $increase_sales );
					}

					if( ! empty( $decrease_sales ) && is_numeric( $decrease_sales ) ){
						$download->decrease_sales( $decrease_sales );
					}

				}
				//END EDD logic

				if( $update && ! $create_post_on_update ){
					$return_args['msg'] = WPWHPRO()->helpers->translate("Download successfully updated", 'action-edd-create-download-success' );
				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate("Download successfully created", 'action-edd-create-download-success' );
				}

				$return_args['data']['download_data'] = $post_data;
				$return_args['data']['download_id'] = $post_id;
				$return_args['success'] = true;

			} else {

				if( is_wp_error( $post_id ) && $wp_error ){

					$return_args['data']['download_data'] = $post_data;
					$return_args['data']['download_id'] = $post_id;
					$return_args['msg'] = WPWHPRO()->helpers->translate("WP Error", 'action-edd-create-download-success' );
				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate("Error creating download.", 'action-edd-create-download-success' );
				}
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $post_data, $post_id, $meta_input, $return_args );
			}

			return $return_args;
		}

		/**
		 * Update the post (download) meta
		 *
		 * @param int $post_id - the post id
		 * @return void
		 */
		public function edd_create_update_download_add_meta( $post_id ){

			$response_body 				= WPWHPRO()->helpers->get_response_body();

			$meta_input 				= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'meta_input' );
			$edd_price      			= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'price' );//float
			$is_variable_pricing      	= ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_variable_pricing' ) === 'yes' ) ? 1 : 0;//integer
			$edd_variable_prices      	= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'variable_prices' );//json string
			$default_price_id      		= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'default_price_id' );//integer
			$edd_download_files      	= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_files' );//json string
			$edd_bundled_products      	= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bundled_products' );//json string
			$bundled_products_conditions= WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'bundled_products_conditions' );//json string
			$hide_purchase_link      	= ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'hide_purchase_link' ) === 'yes' ) ? 'on': 'off';
			$download_limit      		= intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_limit' ) );

			//START EDD
			if( ! empty( $edd_price ) && is_numeric( $edd_price ) ){
				update_post_meta( $post_id, 'edd_price', $edd_price );
			}

			if( ! empty( $edd_variable_prices ) && WPWHPRO()->helpers->is_json( $edd_variable_prices ) ){
				$edd_variable_prices = json_decode( $edd_variable_prices, true );
				update_post_meta( $post_id, 'edd_variable_prices', $edd_variable_prices );
			}

			if( ! empty( $edd_download_files ) && WPWHPRO()->helpers->is_json( $edd_download_files ) ){
				$edd_download_files = json_decode( $edd_download_files, true );
				update_post_meta( $post_id, 'edd_download_files', $edd_download_files );
			}

			if( ! empty( $edd_bundled_products ) && WPWHPRO()->helpers->is_json( $edd_bundled_products ) ){
				$edd_bundled_products = json_decode( $edd_bundled_products, true );
				update_post_meta( $post_id, '_edd_bundled_products', $edd_bundled_products );
			}

			if( ! empty( $bundled_products_conditions ) && WPWHPRO()->helpers->is_json( $bundled_products_conditions ) ){
				$bundled_products_conditions = json_decode( $bundled_products_conditions, true );
				update_post_meta( $post_id, '_edd_bundled_products_conditions', $bundled_products_conditions );
			}

			if( ! empty( $is_variable_pricing ) && is_numeric( $is_variable_pricing ) ){
				update_post_meta( $post_id, '_variable_pricing', intval( $is_variable_pricing ) );
			}

			if( ! empty( $default_price_id ) && is_numeric( $default_price_id ) ){
				update_post_meta( $post_id, '_edd_default_price_id', intval( $default_price_id ) );
			}

			if( ! empty( $hide_purchase_link ) && $hide_purchase_link === 'on' ){
				update_post_meta( $post_id, '_edd_hide_purchase_link', $hide_purchase_link );
			}

			if( ! empty( $download_limit ) && is_numeric( $download_limit ) ){
				update_post_meta( $post_id, '_edd_download_limit', $download_limit );
			}
			//END EDD

			if( ! empty( $meta_input ) ){
				
				if( WPWHPRO()->helpers->is_json( $meta_input ) ){

					$post_meta_data = json_decode( $meta_input, true );
					foreach( $post_meta_data as $skey => $svalue ){
						if( ! empty( $skey ) ){
							if( $svalue == 'ironikus-delete' ){
								delete_post_meta( $post_id, $skey );
							} else {

								$ident = 'ironikus-serialize';
								if( is_string( $svalue ) && substr( $svalue , 0, strlen( $ident ) ) === $ident ){
									$serialized_value = trim( str_replace( $ident, '', $svalue ),' ' );

									if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
										$serialized_value = json_decode( $serialized_value );
									}

									update_post_meta( $post_id, $skey, $serialized_value );

								} else {
									update_post_meta( $post_id, $skey, maybe_unserialize( $svalue ) );
								}

							}
						}
					}

				} else {

					$post_meta_data = explode( ';', trim( $meta_input, ';' ) );
					foreach( $post_meta_data as $single_meta ){
						$single_meta_data   = explode( ',', $single_meta );
						$meta_key           = sanitize_text_field( $single_meta_data[0] );
						$meta_value         = $single_meta_data[1];

						if( ! empty( $meta_key ) ){
							if( $meta_value == 'ironikus-delete' ){
								delete_post_meta( $post_id, $meta_key );
							} else {

								$ident = 'ironikus-serialize';
								if( substr( $meta_value , 0, strlen( $ident ) ) === $ident ){
									$serialized_value = trim( str_replace( $ident, '', $meta_value ),' ' );

									if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
										$serialized_value = json_decode( $serialized_value );
									}

									update_post_meta( $post_id, $meta_key, $serialized_value );

								} else {
									update_post_meta( $post_id, $meta_key, maybe_unserialize( $meta_value ) );
								}
							}
						}
					}

				}

			}

		}

		/**
		 * ###########
		 * #### edd_delete_download
		 * ###########
		 */

		public function action_edd_delete_download_content(){

			$parameter = array(
				'download_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'The download id of the download you want to delete.', 'action-delete-download-content' ) ),
				'force_delete'  	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(optional) Whether to bypass trash and force deletion. Possible values: "yes" and "no". Default: "no".', 'action-delete-download-content' ) ),
				'do_action'     	=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete-download-content' ) )
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_download-content' ) ),
				'msg'        		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-download-content' ) ),
				'data'        		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the download id and further information.', 'action-delete-download-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The download was successfully deleted.",
    "data": {
        "post_id": 747,
        "force_delete": false
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_download.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_download',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete (or trash) a download within Easy Digital Downloads.', 'action-edd_delete_download-content' ),
				'description'       => $description
			);

		}

		public function action_edd_delete_download() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'post_id' => 0,
					'force_delete' => false
				)
			);

			$post_id         = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'download_id' ) );
			$force_delete    = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'force_delete' ) == 'yes' ) ? true : false;
			$do_action       = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) ) ? WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) : '';
			$post = '';
			$check = '';

			if( ! empty( $post_id ) ){
				$post = get_post( $post_id );
			}

			if( ! empty( $post ) ){
				if( ! empty( $post->ID ) ){

					if( $force_delete ){
						$check = wp_delete_post( $post->ID, $force_delete );
					} else {
						$check = wp_trash_post( $post->ID );
					}

					if ( $check ) {

						if( $force_delete  ){
							$return_args['msg']     = WPWHPRO()->helpers->translate("Download successfully deleted.", 'action-delete-download-success' );
						} else {
							$return_args['msg']     = WPWHPRO()->helpers->translate("Download successfully trashed.", 'action-delete-download-success' );
						}
						
						$return_args['success'] = true;
						$return_args['data']['post_id'] = $post->ID;
						$return_args['data']['force_delete'] = $force_delete;
					} else {
						if( $force_delete  ){
							$return_args['msg']  = WPWHPRO()->helpers->translate("Error deleting download. Please check wp_delete_post() for more information.", 'action-delete-download-success' );
						} else {
							$return_args['msg']  = WPWHPRO()->helpers->translate("Error trashing download. Please check wp_trash_post() for more information.", 'action-delete-download-success' );
						}
						
						$return_args['data']['post_id'] = $post->ID;
						$return_args['data']['force_delete'] = $force_delete;
					}

				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate("Could not delete the download: No ID given.", 'action-delete-download-success' );
				}
			} else {
				$return_args['msg']  = WPWHPRO()->helpers->translate("No download found to your specified download id.", 'action-delete-download-success' );
				$return_args['data']['post_id'] = $post_id;
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $post, $post_id, $check, $force_delete );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_create_payment
		 * ###########
		 */

		public function action_edd_create_payment_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'customer_email'       			=> array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer you want to associate with the payment. Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'discounts'    					=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-separated list of discount codes. Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'gateway'    					=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The slug of the currently used gateway. Please see the description for further details. Default empty.', 'action-edd_create_payment-content' ) ),
				'currency'    					=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The currency code of the payment. Default is your default currency. Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'parent_payment_id'    			=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The payment id of a parent payment.', 'action-edd_create_payment-content' ) ),
				'payment_status'    			=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the payment. Default is "pending". Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'product_data'    				=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string, containing all the product data and options. Please refer to the description for examples and further details.', 'action-edd_create_payment-content' ) ),
				'edd_agree_to_terms'    		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Defines if a user agreed to the terms. Set it to "yes" to mark the user as agreed. Default: no', 'action-edd_create_payment-content' ) ),
				'edd_agree_to_privacy_policy'	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Defines if a user agreed to the privacy policy. Set it to "yes" to mark the user as agreed. Default: no', 'action-edd_create_payment-content' ) ),
				'payment_date'    				=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set a custom payment date. The format is flexible, but we recommend SQL format.', 'action-edd_create_payment-content' ) ),
				'user_id'    					=> array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The user id of the WordPress user. If not defined, we try to fetch the id using the customer_email.', 'action-edd_create_payment-content' ) ),
				'customer_first_name'    		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer. Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'customer_last_name'    		=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer. Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'customer_country'    			=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The country code of the customer.', 'action-edd_create_payment-content' ) ),
				'customer_state'    			=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The state of the customer.', 'action-edd_create_payment-content' ) ),
				'customer_zip'    				=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The zip of the customer.', 'action-edd_create_payment-content' ) ),
				'send_receipt'    				=> array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set it to "yes" for sending out a receipt to the customer. Default "no". Please see the description for further details.', 'action-edd_create_payment-content' ) ),
				'do_action'     				=> array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-edd_create_payment-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_payment-content' ) ),
				'msg'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-edd_create_payment-content' ) ),
				'data'        	=> array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the payment id and further information.', 'action-edd_create_payment-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The payment was successfully created.",
    "data": {
        "payment_id": 747,
        "payment_data": {
            "purchase_key": "aa10bc587fb544b10c01fe13905fba74",
            "user_email": "jondoe@test.test",
            "user_info": {
                "id": 0,
                "email": "jondoe@test.test",
                "first_name": "Jannis",
                "last_name": "Testing",
                "discount": false,
                "address": {
                    "country": "AE",
                    "state": false,
                    "zip": false
                }
            },
            "gateway": "paypal",
            "currency": "EUR",
            "cart_details": [
                {
                    "id": 176,
                    "quantity": 1,
                    "item_price": 49,
                    "tax": 5,
                    "discount": 4,
                    "fees": [
                        {
                            "label": "Custom Fee",
                            "amount": 10,
                            "type": "fee",
                            "id": "",
                            "no_tax": false,
                            "download_id": 435
                        }
                    ],
                    "item_number": {
                        "options": {
                            "price_id": null
                        }
                    }
                }
            ],
            "parent": false,
            "status": "publish",
            "post_date": "2020-04-23 00:00:00"
        }
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_payment.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_payment',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a payment within Easy Digital Downloads.', 'action-edd_create_payment-content' ),
				'description'       => $description
			);

		}

		function action_edd_create_payment() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array()
			);

			$purchase_key     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'purchase_key' );
			$discounts     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'discounts' );
			$gateway     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'gateway' );
			$parent_payment_id     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'parent_payment_id' );
			$currency     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'currency' );
			$payment_status     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_status' );
			$product_data     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'product_data' );
			$edd_agree_to_terms     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'edd_agree_to_terms' ) === 'yes' ) ? true : false;
			$edd_agree_to_privacy_policy     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'edd_agree_to_privacy_policy' ) === 'yes' ) ? true : false;
			$payment_date     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_date' );

			$user_id     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'user_id' ) );
			$customer_email     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_email' );
			$customer_first_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_first_name' );
			$customer_last_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_last_name' );
			$customer_country     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_country' );
			$customer_state     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_state' );
			$customer_zip     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_zip' );

			$send_receipt     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'send_receipt' ) === 'yes' ) ? true : false;
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( empty( $user_id ) && ! empty( $customer_email ) ){
				$wp_user = get_user_by( 'email', sanitize_email( $customer_email ) );
				if ( ! empty( $wp_user ) ) {
					$user_id = $wp_user->ID;
				}
			}

			$user_info = array(
				'id'            => $user_id,
				'email'         => $customer_email,
				'first_name'    => $customer_first_name,
				'last_name'     => $customer_last_name,
				'discount'      => $discounts,
				'address'		=> array(
					'country'	=> $customer_country,
					'state'	=> $customer_state,
					'zip'	=> $customer_zip,
				)
			);

			$product_details = array();
			if( ! empty( $product_data ) && WPWHPRO()->helpers->is_json( $product_data ) ){
				$product_details = json_decode( $product_data, true );
			}

			$purchase_data = array(
				'purchase_key'  => ( ! empty( $purchase_key ) ) ? $purchase_key : strtolower( md5( uniqid() ) ),
				'user_email'    => $customer_email,
				'user_info'     => $user_info,
				'gateway'     	=> ( ! empty( $gateway ) ) ? $gateway : '',
				'currency'      => ( ! empty( $currency ) ) ? $currency : edd_get_currency(),
				'cart_details'  => $product_details,
				'parent'        => $parent_payment_id,
				'status'        => 'pending',
			);

			if ( ! empty( $payment_date ) ) {
				$purchase_data['post_date'] = date( "Y-m-d H:i:s", strtotime( $payment_date ) );
			}

			if ( ! empty( $edd_agree_to_terms ) ) {
				$purchase_data['agree_to_terms_time'] = current_time( 'timestamp' );
			}

			if ( ! empty( $edd_agree_to_privacy_policy ) ) {
				$purchase_data['agree_to_privacy_time'] = current_time( 'timestamp' );
			}

			$purchase_data = apply_filters( 'wpwh/actions/edd_create_payment/purchase_data', $purchase_data, $payment_status, $send_receipt );

			//Validate required fields
			$valid_payment_data = $this->validate_payment_data( $purchase_data );
			if( ! $valid_payment_data['success'] ){

				$valid_payment_data['msg'] = WPWHPRO()->helpers->translate( "Your payment was not created. Please check the errors for further details.", 'action-edd_create_payment-failure' );

				return $valid_payment_data;
			}

			if( ! $send_receipt ){
				remove_action( 'edd_complete_purchase', 'edd_trigger_purchase_receipt', 1000 );

				// if we're using EDD Per Product Emails, prevent the custom email from being sent
				if ( class_exists( 'EDD_Per_Product_Emails' ) ) {
					remove_action( 'edd_complete_purchase', 'edd_ppe_trigger_purchase_receipt', 1000, 1 );
				}
			}

			$payment_id = edd_insert_payment( $purchase_data );

			//Make sure the status is updated after
			if( $payment_id && ! empty( $payment_status ) && $payment_status !== 'pending' ){
				edd_update_payment_status( $payment_id, $payment_status );
			}


			if( ! $send_receipt ){
				add_action( 'edd_complete_purchase', 'edd_trigger_purchase_receipt', 999, 3 );

				// if we're using EDD Per Product Emails, prevent the custom email from being sent
				if ( class_exists( 'EDD_Per_Product_Emails' ) ) {
					add_action( 'edd_complete_purchase', 'edd_ppe_trigger_purchase_receipt', 999, 1 );
				}
			}

			if( ! empty( $payment_id ) ){

				$return_args['data']['payment_id'] = $payment_id;
				$return_args['data']['payment_data'] = $purchase_data;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The payment was successfully created.", 'action-edd_create_payment-success' );
				$return_args['success'] = true;

			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "No payment was created.", 'action-edd_create_payment-success' );
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $payment_id, $purchase_data, $send_receipt, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_update_payment
		 * ###########
		 */

		public function action_edd_update_payment_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'payment_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the payment you want to update.', 'action-edd_update_payment-content' ) ),
				'payment_status'    => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the payment. Please see the description for further details.', 'action-edd_update_payment-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-edd_update_payment-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_payment-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-update-payment-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the payment id and further information.', 'action-update-payment-content' ) ),
				'errors'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(array) An array containing all errors that might happened during the update.', 'action-update-payment-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The payment was successfully updated or no changes have been made.",
    "data": {
        "payment_id": 749,
        "payment_status": "processing"
    },
    "errors": []
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_payment.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_payment',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update a payment within Easy Digital Downloads.', 'action-edd_update_payment-content' ),
				'description'       => $description
			);

		}

		function action_edd_update_payment() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => WPWHPRO()->helpers->translate( "The payment was successfully updated or no changes have been made.", 'action-edd_update_payment-success' ),
				'data' => array(),
				'errors' => array(),
			);

			$payment_id     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_id' ) );
			$payment_status     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_status' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( empty( $payment_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'Payment not updated. The argument payment_id cannot be empty.', 'action-edd_update_payment-failure' );
	
				return $return_args;
			}

			$payment_exists = edd_get_payment_by( 'id', $payment_id );

			if( empty( $payment_exists ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The payment id you tried to update, could not be fetched.', 'action-edd_update_payment-failure' );
	
				return $return_args;
			}

			$return_args['data']['payment_id'] = $payment_id;
			$return_args['data']['payment_status'] = $payment_status;

			if( ! empty( $payment_status ) ){
				$updates_status = edd_update_payment_status( $payment_id, $payment_status );
				if( ! empty( $updates_status ) ){
					$return_args['success'] = true;
				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate( "There have been partial issues with updates", 'action-edd_update_payment-success' );
					$return_args['errors'][] = WPWHPRO()->helpers->translate( "There was an issue updating the payment status.", 'action-edd_update_payment-success' );
				}
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $payment_id, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_delete_payment
		 * ###########
		 */

		public function action_edd_delete_payment_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'payment_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The id of the payment you want to update.', 'action-edd_delete_payment-content' ) ),
				'update_customer_stats'    => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" to update the statistics of the customer. Default: no', 'action-edd_delete_payment-content' ) ),
				'delete_download_logs'    => array( 'short_description' => WPWHPRO()->helpers->translate( '(String)  Set this value to "yes" to delete the payment including all its related download logs. Default: no', 'action-edd_delete_payment-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-edd_delete_payment-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_payment-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-payment-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(array) Within the data array, you will find further details about the response, as well as the payment id and further information.', 'action-delete-payment-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The payment was successfully created.",
    "data": {
        "payment_id": 747,
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_payment.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_payment',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete a payment within Easy Digital Downloads.', 'action-edd_delete_payment-content' ),
				'description'       => $description
			);

		}

		function action_edd_delete_payment() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(),
			);

			$payment_id     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'payment_id' ) );
			$update_customer_stats     	= ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'update_customer_stats' ) === 'yes' ) ? true : false;
			$delete_download_logs   = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'delete_download_logs' ) === 'yes' ) ? true : false;
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( empty( $payment_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'Payment not deleted. The argument payment_id cannot be empty.', 'action-edd_delete_payment-failure' );
	
				return $return_args;
			}

			$payment_exists = edd_get_payment_by( 'id', $payment_id );

			if( empty( $payment_exists ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The payment id you tried to delete, could not be fetched.', 'action-edd_delete_payment-failure' );
	
				return $return_args;
			}

			$return_args['data']['payment_id'] = $payment_id;
			$return_args['data']['update_customer'] = $update_customer_stats;
			$return_args['data']['delete_download_logs'] = $delete_download_logs;

			edd_delete_purchase( $payment_id, $update_customer_stats, $delete_download_logs ); //void function
			$return_args['success'] = true;
			$return_args['msg'] = WPWHPRO()->helpers->translate( "The payment was successfully deleted.", 'action-edd_delete_payment-success' );

			if( ! empty( $do_action ) ){
				do_action( $do_action, $payment_id, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_create_customer
		 * ###########
		 */

		public function action_edd_create_customer_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'customer_email'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The email of the customer you want to create. In case the user already exists, we do not update it.', 'action-edd_create_customer-content' ) ),
				'customer_first_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer.', 'action-edd_create_customer-content' ) ),
				'customer_last_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer.', 'action-edd_create_customer-content' ) ),
				'additional_emails'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-separated list of additional email addresses. Please check the description for further details.', 'action-edd_create_customer-content' ) ),
				'attach_payments'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-, and doublepoint-separated list of payment ids you want to assign to the user. Please check the description for further details.', 'action-edd_create_customer-content' ) ),
				'increase_purchase_count'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) increase the purchase count for the customer.', 'action-edd_create_customer-content' ) ),
				'increase_lifetime_value'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you want to add to the lifetime value of the customer. Please check the description for further details.', 'action-edd_create_customer-content' ) ),
				'set_primary_email'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email you want to set as the new primary email. Default: customer_email', 'action-edd_create_customer-content' ) ),
				'customer_notes'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple customer notes. Please check the description for further details.', 'action-edd_create_customer-content' ) ),
				'customer_meta'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple customer meta data. Please check the description for further details.', 'action-edd_create_customer-content' ) ),
				'user_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The user id of the WordPress user you want to assign to the customer. Please read the description for further details.', 'action-edd_create_customer-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-edd_create_customer-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_customer-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-create-customer-content' ) ),
				'customer_id'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the customer', 'action-create-customer-content' ) ),
				'customer_email'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email you set within the customer_email argument.', 'action-create-customer-content' ) ),
				'additional_emails'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The additional emails you set within the additional_emails argument.', 'action-create-customer-content' ) ),
				'customer_first_name'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name you set within the customer_first_name argument.', 'action-create-customer-content' ) ),
				'customer_last_name'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name you set within the customer_last_name argument.', 'action-create-customer-content' ) ),
				'attach_payments'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The payment ids you set within the attach_payments argument.', 'action-create-customer-content' ) ),
				'increase_purchase_count'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The purchase count you set within the increase_purchase_count argument.', 'action-create-customer-content' ) ),
				'increase_lifetime_value'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The lifetime value you set within the increase_lifetime_value argument.', 'action-create-customer-content' ) ),
				'customer_notes'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The customer notes you set within the customer_notes argument.', 'action-create-customer-content' ) ),
				'customer_meta'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The customer meta you set within the customer_meta argument.', 'action-create-customer-content' ) ),
				'user_id'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The user id you set within the user_id argument.', 'action-create-customer-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The user was successfully created.",
    "customer_id": "5",
    "customer_email": "test@domain.com",
    "additional_emails": "second@domain.com,thir@domain.com",
    "customer_first_name": "John",
    "customer_last_name": "Doe",
    "attach_payments": "747",
    "increase_purchase_count": 2,
    "increase_lifetime_value": "55.46",
    "customer_notes": "[\"First Note 1\",\"First Note 2\"]",
    "customer_meta": "{\"meta_1\": \"test1\",\"meta_2\": \"test2\"}"
    "user_id": 23
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_customer.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_customer',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a customer within Easy Digital Downloads.', 'action-edd_create_customer-content' ),
				'description'       => $description
			);

		}

		public function action_edd_create_customer() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$customer_id = 0;
			$customer = new stdClass;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'customer_id' => 0,
				'customer_email' => '',
				'additional_emails' => '',
				'customer_first_name' => '',
				'customer_last_name' => '',
				'attach_payments' => '',
				'increase_purchase_count' => '',
				'increase_lifetime_value' => '',
				'customer_notes' => '',
				'customer_meta' => '',
				'user_id' => '',
			);

			$customer_email     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_email' );
			$customer_first_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_first_name' );
			$customer_last_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_last_name' );
			$additional_emails     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'additional_emails' );
			$attach_payments     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'attach_payments' );
			$increase_purchase_count     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_purchase_count' ) );
			$user_id     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'user_id' ) );
			$increase_lifetime_value     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_lifetime_value' );
			$set_primary_email     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'set_primary_email' );
			$customer_notes     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_notes' );
			$customer_meta     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_meta' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Customer' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Customer() is undefined. The user could not be created.', 'action-edd_create_customer-failure' );
	
				return $return_args;
			}

			if ( ! empty( $customer_email ) ) {
				$customer = new EDD_Customer( $customer_email );
			}

			if( empty( $customer->id ) ){

				if( empty( $customer_first_name ) && empty( $customer_last_name ) ) {
					$name = $customer_email;
				} else {
					$name = trim( $customer_first_name . ' ' . $customer_last_name );
				}
	
				$customer_data = array(
					'name'        => $name,
					'email'       => $customer_email
				);

				//tro to match a WordPress user with an email
				if( empty( $user_id ) && ! empty( $customer_email ) && is_email( $customer_email ) ){
					$wp_user = get_user_by( 'email', sanitize_email( $customer_email ) );
					if ( ! empty( $wp_user ) ) {
						$user_id = $wp_user->ID;
					}
				}

				if( ! empty( $user_id ) ){
					$customer_data['user_id'] = $user_id;
				}
	
				$customer_id = $customer->create( $customer_data );
				
				if( ! empty( $customer_id ) ){

					if( ! empty( $additional_emails ) ){
						$email_arr = explode( ',', $additional_emails );
						if( is_array( $email_arr ) ){
							foreach( $email_arr as $semail ){
								if( is_email( $semail ) ){
									$customer->add_email( $semail );
								}
							}
						}
					}

					if( ! empty( $set_primary_email ) && is_email( $set_primary_email ) ){
						$customer->set_primary_email( $set_primary_email );
					}

					if( ! empty( $attach_payments ) ){
						$payments_arr = explode( ',', $attach_payments );
						if( is_array( $payments_arr ) ){
							foreach( $payments_arr as $spayment ){
								$spayment_settings = explode( ':', $spayment );
								if( in_array( 'no_update_stats', $spayment_settings ) ){
									$customer->attach_payment( intval( $spayment_settings[0] ), false );
								} else {
									$customer->attach_payment( intval( $spayment_settings[0] ) );
								}
							}
						}
					}

					if( ! empty( $increase_purchase_count ) && is_numeric( $increase_purchase_count ) ){
						$customer->increase_purchase_count( $increase_purchase_count );
					}

					if( ! empty( $increase_lifetime_value ) && is_numeric( $increase_lifetime_value ) ){
						$customer->increase_value( $increase_lifetime_value );
					}

					if( ! empty( $customer_notes ) ){
						if( WPWHPRO()->helpers->is_json( $customer_notes ) ){
							$customer_notes_arr = json_decode( $customer_notes, true );
							foreach( $customer_notes_arr as $snote ){
								$customer->add_note( $snote );
							}
						}
					}

					if( ! empty( $customer_meta ) ){
						if( WPWHPRO()->helpers->is_json( $customer_meta ) ){
							$customer_meta_arr = json_decode( $customer_meta, true );
							foreach( $customer_meta_arr as $skey => $sval ){

								if( ! empty( $skey ) ){
									if( $sval == 'ironikus-delete' ){
										$customer->delete_meta( $skey );
									} else {
										$ident = 'ironikus-serialize';
										if( is_string( $sval ) && substr( $sval , 0, strlen( $ident ) ) === $ident ){
											$serialized_value = trim( str_replace( $ident, '', $sval ),' ' );

											if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
												$serialized_value = json_decode( $serialized_value );
											}

											$customer->update_meta( $skey, $serialized_value );

										} else {
											$customer->update_meta( $skey, maybe_unserialize( $sval ) );
										}
									}
								}
							}
						}
					}

					$return_args['customer_id'] = $customer_id;
					$return_args['customer_email'] = $customer_email;
					$return_args['additional_emails'] = $additional_emails;
					$return_args['customer_first_name'] = $customer_first_name;
					$return_args['customer_last_name'] = $customer_last_name;
					$return_args['attach_payments'] = $attach_payments;
					$return_args['increase_purchase_count'] = $increase_purchase_count;
					$return_args['increase_lifetime_value'] = $increase_lifetime_value;
					$return_args['customer_notes'] = $customer_notes;
					$return_args['customer_meta'] = $customer_meta;
					$return_args['user_id'] = $user_id;
					$return_args['msg'] = WPWHPRO()->helpers->translate( "The user was successfully created.", 'action-edd_create_customer-success' );
					$return_args['success'] = true;
				} else {
					$return_args['customer_id'] = $customer_id;
					$return_args['msg'] = WPWHPRO()->helpers->translate( "An error occured creating the user.", 'action-edd_create_customer-success' );
				}

			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "We could not create the customer. Please set the user_id or the customer_email.", 'action-edd_create_customer-success' );
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $customer_id, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_update_customer
		 * ###########
		 */

		public function action_edd_update_customer_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'customer_value'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The actual value you want to use to determine the customer. In case you havent set the get_customer_by argument or you set it to email, place the customer email in here.', 'action-edd_update_customer-content' ) ),
				'get_customer_by'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The type of value you want to use to fetch the customer from the database. Possible values: email, customer_id, user_id. Default: email', 'action-edd_update_customer-content' ) ),
				'customer_email'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The primary email of the customer you want to update. In case the user does not exists, we use this email to create it (If you have set the argument create_if_none to yes).', 'action-edd_update_customer-content' ) ),
				'customer_first_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name of the customer.', 'action-edd_update_customer-content' ) ),
				'customer_last_name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name of the customer.', 'action-edd_update_customer-content' ) ),
				'additional_emails'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-separated list of additional email addresses. Please check the description for further details.', 'action-edd_update_customer-content' ) ),
				'attach_payments'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-, and doublepoint-separated list of payment ids you want to assign to the user. Please check the description for further details.', 'action-edd_update_customer-content' ) ),
				'increase_purchase_count'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) increase the purchase count for the customer.', 'action-edd_update_customer-content' ) ),
				'increase_lifetime_value'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The price you want to add to the lifetime value of the customer. Please check the description for further details.', 'action-edd_update_customer-content' ) ),
				'set_primary_email'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email you want to set as the new primary email. Default: customer_email', 'action-edd_create_customer-content' ) ),
				'customer_notes'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple customer notes. Please check the description for further details.', 'action-edd_update_customer-content' ) ),
				'customer_meta'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A JSON formatted string containing one or multiple customer meta data. Please check the description for further details.', 'action-edd_update_customer-content' ) ),
				'user_id'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The user id of the WordPress user you want to assign to the customer. Please read the description for further details.', 'action-edd_update_customer-content' ) ),
				'create_if_none'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Set this argument to "yes" if you want to create the customer in case it does not exist.', 'action-edd_update_customer-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-edd_update_customer-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_customer-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-update-customer-content' ) ),
				'customer_id'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the customer', 'action-update-customer-content' ) ),
				'customer_email'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The email you set within the customer_email argument.', 'action-update-customer-content' ) ),
				'additional_emails'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The additional emails you set within the additional_emails argument.', 'action-update-customer-content' ) ),
				'customer_first_name'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The first name you set within the customer_first_name argument.', 'action-update-customer-content' ) ),
				'customer_last_name'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The last name you set within the customer_last_name argument.', 'action-update-customer-content' ) ),
				'attach_payments'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The payment ids you set within the attach_payments argument.', 'action-update-customer-content' ) ),
				'increase_purchase_count'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The purchase count you set within the increase_purchase_count argument.', 'action-update-customer-content' ) ),
				'increase_lifetime_value'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Float) The lifetime value you set within the increase_lifetime_value argument.', 'action-update-customer-content' ) ),
				'customer_notes'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The customer notes you set within the customer_notes argument.', 'action-update-customer-content' ) ),
				'customer_meta'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The customer meta you set within the customer_meta argument.', 'action-update-customer-content' ) ),
				'user_id'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The user id you set within the user_id argument.', 'action-update-customer-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The user was successfully created.",
    "customer_id": "5",
    "customer_email": "test@domain.com",
    "additional_emails": "second@domain.com,thir@domain.com",
    "customer_first_name": "John",
    "customer_last_name": "Doe",
    "attach_payments": "747",
    "increase_purchase_count": 2,
    "increase_lifetime_value": "55.46",
    "customer_notes": "[\"First Note 1\",\"First Note 2\"]",
    "customer_meta": "{\"meta_1\": \"test1\",\"meta_2\": \"test2\"}"
    "user_id": 23
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_customer.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_customer',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update (and create) a customer within Easy Digital Downloads.', 'action-edd_update_customer-content' ),
				'description'       => $description
			);

		}

		public function action_edd_update_customer() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$customer_id = 0;
			$create_email = false;
			$customer = new stdClass;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'customer_id' => 0,
				'customer_email' => '',
				'additional_emails' => '',
				'customer_first_name' => '',
				'customer_last_name' => '',
				'attach_payments' => '',
				'increase_purchase_count' => '',
				'increase_lifetime_value' => '',
				'customer_notes' => '',
				'customer_meta' => '',
				'user_id' => '',
			);

			$get_customer_by   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'get_customer_by' );
			$customer_value     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_value' );
			$customer_email     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_email' );
			$set_primary_email     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'set_primary_email' );
			$customer_first_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_first_name' );
			$customer_last_name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_last_name' );
			$additional_emails     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'additional_emails' );
			$attach_payments     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'attach_payments' );
			$increase_purchase_count     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_purchase_count' ) );
			$user_id     = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'user_id' ) );
			$increase_lifetime_value     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'increase_lifetime_value' );
			$customer_notes     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_notes' );
			$customer_meta     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_meta' );
			
			$create_if_none          = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'create_if_none' ) === 'yes' ) ? true : false;
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Customer' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Customer() is undefined. The customer could not be created.', 'action-edd_update_customer-failure' );
	
				return $return_args;
			}

			if( empty( $customer_value ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'Customer not updated. The argument customer_value cannot be empty.', 'action-edd_update_customer-failure' );
	
				return $return_args;
			}

			switch( $get_customer_by ){
				case 'customer_id':
					$customer = new EDD_Customer( intval( $customer_value ) );
				break;
				case 'user_id':
					$customer = new EDD_Customer( intval( $customer_value ), true );
				break;
				case 'email':
				default:
					$customer = new EDD_Customer( $customer_value );
				break;
			}

			if ( empty( $customer ) || empty( $customer->id ) ) {
				if( ! $create_if_none ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( 'The customer you tried to update does not exist.', 'action-edd_update_customer-failure' );
	
					return $return_args;
				} else {

					if( is_email( $customer_value ) ){
						$create_email = $customer_value;
					} else {
						if( ! empty( $customer_email ) && is_email( $customer_email ) ){
							$create_email = $customer_email;
						}
					}

					if( empty( $create_email ) ){
						$return_args['msg'] = WPWHPRO()->helpers->translate( 'No email found. Please set an email first to create the customer.', 'action-edd_update_customer-failure' );
			
						return $return_args;
					}

					$customer = new EDD_Customer( $create_email );
					if( ! empty( $customer->id ) ){
						$return_args['msg'] = WPWHPRO()->helpers->translate( 'The email defined within the customer_value was not registered for a customer, but the email within customer_email was. Please use a different email to create the customer.', 'action-edd_update_customer-failure' );
			
						return $return_args;
					}

					if( empty( $customer_first_name ) && empty( $customer_last_name ) ) {
						$name = $create_email;
					} else {
						$name = trim( $customer_first_name . ' ' . $customer_last_name );
					}
		
					$customer_data = array(
						'name'        => $name,
						'email'       => $create_email
					);
	
					//tro to match a WordPress user with an email
					if( empty( $user_id ) && ! empty( $create_email ) && is_email( $create_email ) ){
						$wp_user = get_user_by( 'email', sanitize_email( $create_email ) );
						if ( ! empty( $wp_user ) ) {
							$user_id = $wp_user->ID;
						}
					}
	
					if( ! empty( $user_id ) ){
						$customer_data['user_id'] = $user_id;
					}
		
					$customer_id = $customer->create( $customer_data );

				}
			} else {
				$customer_id = $customer->id;
			}

			if( ! empty( $customer_id ) ){

				$new_customer_data = array();

				if( ! empty( $customer_first_name ) || ! empty( $customer_last_name ) ) {
					$new_customer_data['name'] = trim( $customer_first_name . ' ' . $customer_last_name );
				}

				if( ! empty( $customer_email ) ) {
					$new_customer_data['email'] = $customer_email;
				}

				if( ! empty( $user_id ) ) {
					$new_customer_data['user_id'] = $user_id;
				}
	
				if( ! empty( $new_customer_data ) ){
					$customer->update( $new_customer_data );
				}

				if( ! empty( $additional_emails ) ){
					$email_arr = explode( ',', $additional_emails );
					if( is_array( $email_arr ) ){
						foreach( $email_arr as $semail ){
							$semail_settings = explode( ':', $semail );
							if( in_array( 'remove', $semail_settings ) ){
								if( is_email( $semail_settings[0] ) ){
									$customer->remove_email( $semail_settings[0] );
								}
							} else {
								if( is_email( $semail_settings[0] ) ){
									$customer->add_email( $semail_settings[0] );
								}
							}
						}
					}
				}

				if( ! empty( $set_primary_email ) && is_email( $set_primary_email ) ){
					$customer->set_primary_email( $set_primary_email );
				}

				if( ! empty( $attach_payments ) ){
					$payments_arr = explode( ',', $attach_payments );
					if( is_array( $payments_arr ) ){
						foreach( $payments_arr as $spayment ){
							$spayment_settings = explode( ':', $spayment );
							if( in_array( 'remove', $spayment_settings ) ){
								if( in_array( 'no_update_stats', $spayment_settings ) ){
									$customer->remove_payment( intval( $spayment_settings[0] ), false );
								} else {
									$customer->remove_payment( intval( $spayment_settings[0] ) );
								}
							} else {
								if( in_array( 'no_update_stats', $spayment_settings ) ){
									$customer->attach_payment( intval( $spayment_settings[0] ), false );
								} else {
									$customer->attach_payment( intval( $spayment_settings[0] ) );
								}
							}
						}
					}
				}

				if( ! empty( $increase_purchase_count ) && is_numeric( $increase_purchase_count ) ){
					$customer->increase_purchase_count( $increase_purchase_count );
				}

				if( ! empty( $increase_lifetime_value ) && is_numeric( $increase_lifetime_value ) ){
					$customer->increase_value( $increase_lifetime_value );
				}

				if( ! empty( $customer_notes ) ){
					if( WPWHPRO()->helpers->is_json( $customer_notes ) ){
						$customer_notes_arr = json_decode( $customer_notes, true );
						foreach( $customer_notes_arr as $snote ){
							$customer->add_note( $snote );
						}
					}
				}

				if( ! empty( $customer_meta ) ){
					if( WPWHPRO()->helpers->is_json( $customer_meta ) ){
						$customer_meta_arr = json_decode( $customer_meta, true );
						foreach( $customer_meta_arr as $skey => $sval ){

							if( ! empty( $skey ) ){
								if( $sval == 'ironikus-delete' ){
									$customer->delete_meta( $skey );
								} else {
									$ident = 'ironikus-serialize';
									if( is_string( $sval ) && substr( $sval , 0, strlen( $ident ) ) === $ident ){
										$serialized_value = trim( str_replace( $ident, '', $sval ),' ' );

										if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
											$serialized_value = json_decode( $serialized_value );
										}

										$customer->update_meta( $skey, $serialized_value );

									} else {
										$customer->update_meta( $skey, maybe_unserialize( $sval ) );
									}
								}
							}
						}
					}
				}

				$return_args['customer_id'] = $customer_id;
				$return_args['get_customer_by'] = $get_customer_by;
				$return_args['customer_value'] = $customer_value;
				$return_args['customer_email'] = $customer_email;
				$return_args['additional_emails'] = $additional_emails;
				$return_args['customer_first_name'] = $customer_first_name;
				$return_args['customer_last_name'] = $customer_last_name;
				$return_args['attach_payments'] = $attach_payments;
				$return_args['increase_purchase_count'] = $increase_purchase_count;
				$return_args['increase_lifetime_value'] = $increase_lifetime_value;
				$return_args['customer_notes'] = $customer_notes;
				$return_args['customer_meta'] = $customer_meta;
				$return_args['user_id'] = $user_id;
				$return_args['create_if_none'] = $create_if_none;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The user was successfully updated.", 'action-edd_update_customer-success' );
				$return_args['success'] = true;

			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "We could not update the customer since we did not find any customer id.", 'action-edd_update_customer-success' );
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $customer_id, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_delete_customer
		 * ###########
		 */

		public function action_edd_delete_customer_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'customer_value'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The actual value you want to use to determine the customer. In case you havent set the get_customer_by argument or you set it to email, place the customer email in here.', 'action-edd_delete_customer-content' ) ),
				'get_customer_by'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The type of value you want to use to fetch the customer from the database. Possible values: email, customer_id, user_id. Default: email', 'action-edd_delete_customer-content' ) ),
				'delete_records'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Set this argument to "yes" if you want to delete all of the customer records (payments) from the database. More info is within the description.', 'action-edd_delete_customer-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_delete_customer-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_customer-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-customer-content' ) ),
				'customer_id'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The ID of the customer', 'action-delete-customer-content' ) ),
				'get_customer_by'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The type of value you want to use to fetch the customer from the database. Possible values: email, customer_id, user_id. Default: email', 'action-delete-customer-content' ) ),
				'customer_value'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The additional emails you set within the additional_emails argument.', 'action-delete-customer-content' ) ),
				'delete_records'        => array( 'short_description' => WPWHPRO()->helpers->translate( 'Set this argument to "yes" if you want to delete all of the customer records (payments) from the database.', 'action-delete-customer-content' ) ),
				'customer_data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(array) The Data from the EDD_Customer class.', 'action-delete-customer-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The user was successfully created.",
    "customer_id": "5",
    "get_customer_by": "email",
    "customer_value": "jondoe@domain.test",
    "delete_records": false,
    "customer_data": []
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_customer.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_customer',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete a customer within Easy Digital Downloads.', 'action-edd_delete_customer-content' ),
				'description'       => $description
			);

		}

		public function action_edd_delete_customer() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$customer_id = 0;
			$customer = new stdClass;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'customer_id' => 0,
				'get_customer_by' => '',
				'customer_value' => '',
				'delete_records' => '',
				'customer_data' => '',
			);

			$get_customer_by   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'get_customer_by' );
			$customer_value     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'customer_value' );
			$delete_records     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'delete_records' ) === 'yes' ) ? true : false;
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Customer' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Customer() is undefined. The user could not be deleted.', 'action-edd_delete_customer-failure' );
	
				return $return_args;
			}

			if( empty( $customer_value ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'User not deleted. The argument customer_value cannot be empty.', 'action-edd_delete_customer-failure' );
	
				return $return_args;
			}

			switch( $get_customer_by ){
				case 'customer_id':
					$customer = new EDD_Customer( intval( $customer_value ) );
				break;
				case 'user_id':
					$customer = new EDD_Customer( intval( $customer_value ), true );
				break;
				case 'email':
				default:
					$customer = new EDD_Customer( $customer_value );
				break;
			}

			if ( empty( $customer ) || empty( $customer->id ) ) {
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The user you tried to delete does not exist.', 'action-edd_delete_customer-failure' );
				return $return_args;
			}

			$customer_id = $customer->id;
			do_action( 'edd_pre_delete_customer', $customer_id, true, $delete_records ); //confirm is always true

			$payments_array = explode( ',', $customer->payment_ids );
			$success        = EDD()->customers->delete( $customer_id );

			if ( $success ) {

				if ( $delete_records ) {

					// Remove all payments, logs, etc
					foreach ( $payments_array as $payment_id ) {
						edd_delete_purchase( $payment_id, false, true );
					}

				} else {

					// Just set the payments to customer_id of 0
					foreach ( $payments_array as $payment_id ) {
						edd_update_payment_meta( $payment_id, '_edd_payment_customer_id', 0 );
					}

				}

				$return_args['customer_id'] = $customer_id;
				$return_args['get_customer_by'] = $get_customer_by;
				$return_args['customer_value'] = $customer_value;
				$return_args['delete_records'] = $delete_records;
				$return_args['customer_data'] = $customer;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The user was successfully deleted.", 'action-edd_delete_customer-success' );
				$return_args['success'] = true;

			} else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error deleting the customer. (EDD error)", 'action-edd_delete_customer-success' );

			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $customer_id, $customer, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_create_discount
		 * ###########
		 */

		public function action_edd_create_discount_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'code'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The dicsount code you would like to set for this dicsount. Only alphanumeric characters are allowed.', 'action-edd_create_discount-content' ) ),
				'name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The name to identify the discount code.', 'action-edd_create_discount-content' ) ),
				'status'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the discount code. Default: active', 'action-edd_create_discount-content' ) ),
				'current_uses'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) A number that tells how many times the coupon code has been already used.', 'action-edd_create_discount-content' ) ),
				'max_uses'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of how often the discount code can be used in total.', 'action-edd_create_discount-content' ) ),
				'amount'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The amount of the discount code. If chosen percent, use an interger, for an amount, use float. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'start_date'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The start date of the availability of the discount code. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'expiration_date'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The end date of the availability of the discount code. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'type'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The type of the discount code. Default: percent. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'min_price'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The minimum price that needs to be reached to use the discount code. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'product_requirement'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'A comma-separated list of download IDs that are required to apply the discount code. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'product_condition'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A string containing further conditions on when the discount code can be applied. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'excluded_products'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-separated list, containing all the products that are excluded from the discount code. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'is_not_global'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this argument to "yes" if you do not want to apply the discount code globally to all products. Default: no. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'is_single_use'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this argument to "yes" if you want to limit this discount code to only a single use per customer. Default: no. More info is within the description.', 'action-edd_create_discount-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_create_discount-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_create_discount-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-customer-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing all of the predefined data of the webhook, as well as the discount id in case it was successfully created.', 'action-delete-customer-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The discount code was successfully created.",
    "data": {
        "code": "erthsashtsw",
        "name": "Demo Discount Code",
        "status": "inactive",
        "uses": "5",
        "max": "10",
        "amount": "11.10",
        "start": "05/23/2020 00:00:00",
        "expiration": "06/27/2020 23:59:59",
        "type": "flat",
        "min_price": "22",
        "products": [
            "176",
            "772"
        ],
        "product_condition": "any",
        "excluded-products": [
            "774"
        ],
        "not_global": true,
        "use_once": true,
        "discount_id": 805
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_create_discount.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_create_discount',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to create a dicsount code within Easy Digital Downloads.', 'action-edd_create_discount-content' ),
				'description'       => $description
			);

		}

		public function action_edd_create_discount() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$discount_id = 0;
			$discount = new stdClass;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'code'              => '',
					'name'              => '',
					'status'            => 'active',
					'current_uses'		=> '',
					'max_uses'          => '',
					'amount'            => '',
					'start_date'             => '',
					'expiration_date'        => '',
					'type'              => '',
					'min_price'         => '',
					'product_requirement'      => array(),
					'product_condition' => '',
					'excluded_products' => array(),
					'is_not_global'     => false,
					'is_single_use'     => false,
				),
			);

			$code   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'code' );
			$name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'name' );
			$status     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'status' );
			$current_uses     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'current_uses' );
			$max_uses     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'max_uses' );
			$amount     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'amount' );
			$start_date     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'start_date' );
			$expiration_date     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$type     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'type' );
			$min_price     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'min_price' );
			$product_requirement     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'product_requirement' );
			$product_condition     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'product_condition' );
			$excluded_products     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'excluded_products' );
			$is_not_global     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_not_global' ) === 'yes' ) ? true : false;
			$is_single_use     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_single_use' ) === 'yes' ) ? true : false;
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Discount' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Discount() is undefined. The discount code could not be created.', 'action-edd_create_discount-failure' );
	
				return $return_args;
			}

			if( empty( $code ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'No code given. The argument code cannot be empty.', 'action-edd_create_discount-failure' );
	
				return $return_args;
			}

			$discount = new EDD_Discount();
			$discount_args = array(
				'code' => $code
			);

			if( ! empty( $name ) ){
				$discount_args['name'] = $name;
			}

			if( ! empty( $status ) ){
				$discount_args['status'] = $status;
			}

			if( ! empty( $current_uses ) ){
				$discount_args['uses'] = $current_uses;
			}

			if( ! empty( $max_uses ) ){
				$discount_args['max'] = $max_uses;
			}

			if( ! empty( $amount ) ){
				$discount_args['amount'] = $amount;
			}

			if( ! empty( $start_date ) ){
				$discount_args['start'] = $start_date;
			}

			if( ! empty( $expiration_date ) ){
				$discount_args['expiration'] = $expiration_date;
			}

			if( ! empty( $type ) ){
				$discount_args['type'] = $type;
			}

			if( ! empty( $min_price ) ){
				$discount_args['min_price'] = $min_price;
			}

			if( ! empty( $product_requirement ) ){
				$product_requirement = explode( ',', trim( $product_requirement, ',' ) );
				$discount_args['products'] = $product_requirement;
			}

			if( ! empty( $product_condition ) ){
				$discount_args['product_condition'] = $product_condition;
			}

			if( ! empty( $excluded_products ) ){
				$excluded_products = explode( ',', trim( $excluded_products, ',' ) );
				$discount_args['excluded-products'] = $excluded_products;
			}

			if( ! empty( $is_not_global ) ){
				$discount_args['not_global'] = $is_not_global;
			}

			if( ! empty( $is_single_use ) ){
				$discount_args['use_once'] = $is_single_use;
			}

			$discount_args = apply_filters( 'wpwh/actions/edd_create_discount/filter_discount_arguments', $discount_args );

			$discount_id = $discount->add( $discount_args );
			
			//fallback since the ID is not directly available within the class
			if( ! empty( $discount_id ) && is_numeric( $discount_id ) ){
				$discount = new EDD_Discount( $discount_id );
			}

			if ( empty( $discount ) || empty( $discount->ID ) ) {
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The discount code was not created.', 'action-edd_create_discount-failure' );
				return $return_args;
			}

			$return_args['data'] = $discount_args;
			$return_args['data']['discount_id'] = $discount_id;
			$return_args['msg'] = WPWHPRO()->helpers->translate( "The discount code was successfully created.", 'action-edd_create_discount-success' );
			$return_args['success'] = true;

			if( ! empty( $do_action ) ){
				do_action( $do_action, $discount_id, $discount, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_update_discount
		 * ###########
		 */

		public function action_edd_update_discount_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'discount_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The discount ID or the discount code of the discount you would like to update.', 'action-edd_update_discount-content' ) ),
				'create_if_none'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this value to "yes" in case the given discount does not exist and you want to create it. Default: no', 'action-edd_update_discount-content' ) ),
				'code'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The dicsount code you would like to set for this dicsount. Only alphanumeric characters are allowed.', 'action-edd_update_discount-content' ) ),
				'name'       => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The name to identify the discount code.', 'action-edd_update_discount-content' ) ),
				'status'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The status of the discount code.', 'action-edd_update_discount-content' ) ),
				'current_uses'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) A number that tells how many times the coupon code has been already used.', 'action-edd_update_discount-content' ) ),
				'max_uses'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Integer) The number of how often the discount code can be used in total.', 'action-edd_update_discount-content' ) ),
				'amount'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The amount of the discount code. If chosen percent, use an interger, for an amount, use float. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'start_date'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The start date of the availability of the discount code. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'expiration_date'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The end date of the availability of the discount code. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'type'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) The type of the discount code. Default: percent. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'min_price'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The minimum price that needs to be reached to use the discount code. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'product_requirement'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'A comma-separated list of download IDs that are required to apply the discount code. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'product_condition'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A string containing further conditions on when the discount code can be applied. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'excluded_products'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) A comma-separated list, containing all the products that are excluded from the discount code. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'is_not_global'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this argument to "yes" if you do not want to apply the discount code globally to all products. Default: no. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'is_single_use'     => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) Set this argument to "yes" if you want to limit this discount code to only a single use per customer. Default: no. More info is within the description.', 'action-edd_update_discount-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_update_discount-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_update_discount-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-customer-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing all of the predefined data of the webhook, as well as the discount id in case it was successfully updated (or created).', 'action-delete-customer-content' ) ),
			);

			//This area will be displayed within the "return" area of the webhook action
			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "The discount code was successfully created.",
    "data": {
        "code": "erthsashtsw",
        "name": "Demo Discount Code",
        "status": "inactive",
        "uses": "5",
        "max": "10",
        "amount": "11.10",
        "start": "05/23/2020 00:00:00",
        "expiration": "06/27/2020 23:59:59",
        "type": "flat",
        "min_price": "22",
        "products": [
            "176",
            "772"
        ],
        "product_condition": "any",
        "excluded-products": [
            "774"
        ],
        "not_global": true,
        "use_once": true,
        "discount_id": 805
    }
}</pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_update_discount.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_update_discount',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to update (or create) a dicsount code within Easy Digital Downloads.', 'action-edd_update_discount-content' ),
				'description'       => $description
			);

		}

		public function action_edd_update_discount() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$discount = new stdClass;
			$needs_creation = false;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'code'              => '',
					'name'              => '',
					'status'            => 'active',
					'current_uses'		=> '',
					'max_uses'          => '',
					'amount'            => '',
					'start_date'             => '',
					'expiration_date'        => '',
					'type'              => '',
					'min_price'         => '',
					'product_requirement'      => array(),
					'product_condition' => '',
					'excluded_products' => array(),
					'is_not_global'     => false,
					'is_single_use'     => false,
				),
			);

			$discount_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'discount_id' );
			$code   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'code' );
			$name     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'name' );
			$status     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'status' );
			$current_uses     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'current_uses' );
			$max_uses     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'max_uses' );
			$amount     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'amount' );
			$start_date     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'start_date' );
			$expiration_date     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'expiration_date' );
			$type     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'type' );
			$min_price     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'min_price' );
			$product_requirement     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'product_requirement' );
			$product_condition     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'product_condition' );
			$excluded_products     = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'excluded_products' );
			$is_not_global     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_not_global' ) === 'yes' ) ? true : false;
			$is_single_use     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'is_single_use' ) === 'yes' ) ? true : false;
			$create_if_none     = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'create_if_none' ) === 'yes' ) ? true : false;
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! class_exists( 'EDD_Discount' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The class EDD_Discount() is undefined. The discount code could not be updated.', 'action-edd_update_discount-failure' );
	
				return $return_args;
			}

			if( ! empty( $discount_id ) ){

				//Fetch the discount id from the code
				if( ! is_numeric( $discount_id ) ){
					$tmp_dsc_obj = edd_get_discount_by_code( $discount_id );
					if( ! empty( $tmp_dsc_obj->ID ) ){
						$discount_id = $tmp_dsc_obj->ID;
					}
				}

				$tmp_discount = new EDD_Discount( $discount_id );
				if ( empty( $tmp_discount ) || empty( $tmp_discount->ID ) ){
					$needs_creation = true;
				}
			} else {
				$needs_creation = true;
			}
			

			if( empty( $discount_id ) || $needs_creation ){
				if( ! $create_if_none ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( 'We could not match your given ID to a discount. No discount was updated.', 'action-edd_update_discount-failure' );
	
					return $return_args;
				} else {
					$discount = new EDD_Discount();
				}
			} else {
				$discount = new EDD_Discount( $discount_id );
			}

			$discount_args = array();

			if( ! empty( $code ) ){
				$discount_args['code'] = $code;
			}

			if( ! empty( $name ) ){
				$discount_args['name'] = $name;
			}

			if( ! empty( $status ) ){
				$discount_args['status'] = $status;
			}

			if( ! empty( $current_uses ) ){
				$discount_args['uses'] = $current_uses;
			}

			if( ! empty( $max_uses ) ){
				$discount_args['max'] = $max_uses;
			}

			if( ! empty( $amount ) ){
				$discount_args['amount'] = $amount;
			}

			if( ! empty( $start_date ) ){
				$discount_args['start'] = $start_date;
			}

			if( ! empty( $expiration_date ) ){
				$discount_args['expiration'] = $expiration_date;
			}

			if( ! empty( $type ) ){
				$discount_args['type'] = $type;
			}

			if( ! empty( $min_price ) ){
				$discount_args['min_price'] = $min_price;
			}

			if( ! empty( $product_requirement ) ){
				$product_requirement = explode( ',', trim( $product_requirement, ',' ) );
				$discount_args['products'] = $product_requirement;
			}

			if( ! empty( $product_condition ) ){
				$discount_args['product_condition'] = $product_condition;
			}

			if( ! empty( $excluded_products ) ){
				$excluded_products = explode( ',', trim( $excluded_products, ',' ) );
				$discount_args['excluded-products'] = $excluded_products;
			}

			if( ! empty( $is_not_global ) ){
				$discount_args['not_global'] = $is_not_global;
			}

			if( ! empty( $is_single_use ) ){
				$discount_args['use_once'] = $is_single_use;
			}

			$discount_args = apply_filters( 'wpwh/actions/edd_update_discount/filter_discount_arguments', $discount_args, $discount_id, $discount );

			if( $create_if_none ){
				$discount_id = $discount->add( $discount_args );
			} else {
				$discount_id = $discount->update( $discount_args );
			}
			
			//fallback since the ID might not be directly available within the class
			if( ! empty( $discount_id ) && is_numeric( $discount_id ) ){
				$discount = new EDD_Discount( $discount_id );
			}

			if ( empty( $discount ) || empty( $discount->ID ) ) {
				if( $needs_creation ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( 'The discount code was not created.', 'action-edd_update_discount-failure' );
				} else {
					$return_args['msg'] = WPWHPRO()->helpers->translate( 'The discount code was not updated.', 'action-edd_update_discount-failure' );
				}
				
				return $return_args;
			}

			if( $needs_creation ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The discount code was successfully created.", 'action-edd_update_discount-success' );
			} else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The discount code was successfully updated.", 'action-edd_update_discount-success' );
			}

			$return_args['data'] = $discount_args;
			$return_args['data']['discount_id'] = $discount_id;
			$return_args['success'] = true;

			if( ! empty( $do_action ) ){
				do_action( $do_action, $discount_id, $discount, $needs_creation, $return_args );
			}

			return $return_args;
		}

		/**
		 * ###########
		 * #### edd_delete_discount
		 * ###########
		 */

		public function action_edd_delete_discount_content(){

			//These are the main arguments the user can use to input. You should always grab them within your action function.
			$parameter = array(
				'discount_id'       => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The dicsount ID or discount code of the discount you want to delete.', 'action-edd_delete_discount-content' ) ),
				'do_action'     => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More info is within the description.', 'action-edd_delete_discount-content' ) ),
			);

			//This is a more detailled view of how the data you sent will be returned.
			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-edd_delete_discount-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete-customer-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Array) Containing the discount id of the deleted discount.', 'action-delete-customer-content' ) ),
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
				include( WPWH_EDD_PLUGIN_DIR . 'includes/partials/descriptions/action_edd_delete_discount.php' );
			$description = ob_get_clean();

			return array(
				'action'            => 'edd_delete_discount',
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'This webhook action allows you to delete a dicsount code within Easy Digital Downloads.', 'action-edd_delete_discount-content' ),
				'description'       => $description
			);

		}

		public function action_edd_delete_discount() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$discount = new stdClass;
			$return_args = array(
				'success' => false,
				'msg' => '',
				'data' => array(
					'discount_id' => 0,
				),
			);

			$discount_id   = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'discount_id' );
			
			$do_action          = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' );

			if( ! function_exists( 'edd_get_discount_by_code' ) && ! function_exists( 'edd_get_discount_by' ) && ! function_exists( 'edd_remove_discount' ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'The functions edd_remove_discount() and edd_get_discount_by() are undefined. The discount code could not be deleted.', 'action-edd_delete_discount-failure' );
	
				return $return_args;
			}

			if( ! empty( $discount_id ) ){
				//Fetch the discount id from the code
				if( ! is_numeric( $discount_id ) ){
					$tmp_dsc_obj = edd_get_discount_by_code( $discount_id );
					if( ! empty( $tmp_dsc_obj->ID ) ){
						$discount_id = $tmp_dsc_obj->ID;
					}
				}
			}

			if( empty( $discount_id ) || ! is_numeric( $discount_id ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( 'We could not find any discount for your given value.', 'action-edd_delete_discount-failure' );
	
				return $return_args;
			}

			edd_remove_discount( $discount_id );

			$return_args['msg'] = WPWHPRO()->helpers->translate( "The discount code was successfully deleted.", 'action-edd_delete_discount-success' );
			$return_args['data']['discount_id'] = $discount_id;
			$return_args['success'] = true;

			if( ! empty( $do_action ) ){
				do_action( $do_action, $discount_id, $discount, $return_args );
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

		 /**
		  * Validate the payment dat against crucial values
		  *
		  * @param array $payment_data
		  * @return array - the response with further details
		  */
		public function validate_payment_data( $payment_data ){

			$response = array(
				'success' => true,
				'errors' => array(),
			);

			if ( empty( $payment_data ) ) {
				$response['errors'][] = WPWHPRO()->helpers->translate("The payment data cannot be empty.", 'action-edd_helpers-validate-failure' );
				$response['success'] = false;
			}

			if ( empty( $payment_data['user_info']['email'] ) ) {
				$response['errors'][] = WPWHPRO()->helpers->translate("The argument user_email cannot be empty.", 'action-edd_helpers-validate-failure' );
				$response['success'] = false;
			}

			if( ! empty( $payment_data['cart_details'] ) ){
				foreach( $payment_data['cart_details'] as $item ){

					if ( ! isset( $item['id'] ) ) {
						$response['errors'][] = WPWHPRO()->helpers->translate("The item argument id cannot be empty. Please set it to the download id.", 'action-edd_helpers-validate-failure' );
						$response['success'] = false;
					}

					if ( ! isset( $item['quantity'] ) ) {
						$response['errors'][] = WPWHPRO()->helpers->translate("The item argument quantity cannot be empty.", 'action-edd_helpers-validate-failure' );
						$response['success'] = false;
					}

					if ( ! isset( $item['tax'] ) ) {
						$response['errors'][] = WPWHPRO()->helpers->translate("The item argument tax cannot be empty.", 'action-edd_helpers-validate-failure' );
						$response['success'] = false;
					}

				}
			}
			
			return $response;

		}

	}

	new WP_Webhooks_EDD_Actions();

}