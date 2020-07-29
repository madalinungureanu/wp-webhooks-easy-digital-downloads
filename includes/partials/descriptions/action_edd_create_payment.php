<?php

/**
 * Template for creating an EDD payment
 * 
 * Webhook type: action
 * Webhook name: edd_create_payment
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-create-payment-description";

//load default edd statuses
$payment_statuses = array(
    'pending'   => __( 'Pending', 'easy-digital-downloads' ),
    'publish'   => __( 'Complete', 'easy-digital-downloads' ),
    'refunded'  => __( 'Refunded', 'easy-digital-downloads' ),
    'failed'    => __( 'Failed', 'easy-digital-downloads' ),
    'abandoned' => __( 'Abandoned', 'easy-digital-downloads' ),
    'revoked'   => __( 'Revoked', 'easy-digital-downloads' ),
    'processing' => __( 'Processing', 'easy-digital-downloads' )
);

if( function_exists( 'edd_get_payment_statuses' ) ){
    $payment_statuses = array_merge( $payment_statuses, edd_get_payment_statuses() );
}
$payment_statuses = apply_filters( 'wpwh/descriptions/actions/edd_create_payment/payment_statuses', $payment_statuses );

$default_cart_details = array (
    array (
      'id' => 176,
      'quantity' => 1,
      'item_price' => 49,
      'tax' => 5,
      'discount' => 4,
      'fees' => 
      array (
        array (
          'label' => 'Custom Fee',
          'amount' => 10,
          'type' => 'fee',
          'id' => '',
          'no_tax' => false,
          'download_id' => 435,
        ),
      ),
      'item_number' => 
      array (
        'options' => 
        array (
          'price_id' => NULL,
        ),
      ),
    ),
);
$default_cart_details = apply_filters( 'wpwh/descriptions/actions/edd_create_payment/default_cart_details', $default_cart_details );

$beautified_cart_details = json_encode( $default_cart_details, JSON_PRETTY_PRINT );

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to create a payment for Easy Digital Downloads within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_create_payment</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_create_payment</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_create_payment</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the customer_email argument. Please set it to the email of the person you want to assign to the payment.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the creation of the EDD payment.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "This webhook action is very versatile. Depending on your active extensions of the plugin, you will see different arguments and descriptions. This way, we can always provide you personalized features based on your active plugins.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "To run the logic, we use the EDD default function for inserting payments: edd_insert_payment() - you can therefore use all the features available for the function.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_email", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The customer email is the email address of the customer you want to associate with the payment. In case there is no existing EDD customer with this email available, EDD will create one. (An EDD customer is not the same as a WordPress user. There is no WordPRess user created by simply defining the email.) To associate a WordPress user with the EDD customer, please check out the <strong>user_id</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "discounts", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a single discount code or a comma-separated list of multiple discount codes. Down below, you will find an example on how to use multiple discount codes. <strong>Please note</strong>: This only adds the discount code to the payment, but it does not affect the pricing. If you want to apply the discounts to the payment pricing, you need to use the discount key within the <strong>product_data</strong> line item argument.", $translation_ident ); ?>
<pre>10PERCENTOFF,EASTERDISCOUNT10</pre>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "gateway", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The slug of the gateway you want to use. Down below, you will find further details on the available default gateways:", $translation_ident ); ?>
<ol>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "PayPal Standard", $translation_ident ); ?></strong>: paypal
    </li>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "Test Payment", $translation_ident ); ?></strong>: manual
    </li>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "Amazon", $translation_ident ); ?></strong>: amazon
    </li>

    <?php do_action( 'wpwh/descriptions/actions/edd_create_payment/after_gateway_items' ) ?>

</ol>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "currency", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The currency code of the currency you want to use for this payment. You can set it to e.g. <strong>EUR</strong> or <strong>USD</strong>. If you leave it empty, we use your default currency. ( edd_get_currency() )", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "payment_status", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to set a custom payment status. Down below, you will find a list of all available, default payment names and its slugs. To make this argument work, please define the slug of the status you want. If you don't define any, <strong>pending</strong> is used.", $translation_ident ); ?>
<ol>
    <?php foreach( $payment_statuses as $ps_slug => $ps_name ) : ?>
        <li>
            <strong><?php echo WPWHPRO()->helpers->translate( $ps_name, $translation_ident ); ?></strong>: <?php echo $ps_slug; ?>
        </li>
    <?php endforeach; ?>
</ol>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "product_data", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a JSON formatted String, which contains all the downloads you want to add, including further details about the pricing. Due to the complexity of the string, we explained each section of the following JSON down below. The JSON below contains a list with one product which is added to your payment details. They also determine the pricing of the payment and other information.", $translation_ident ); ?>
<pre><?php echo $beautified_cart_details; ?></pre>
<?php echo WPWHPRO()->helpers->translate( "The above JSON adds a single download to the payment. If you want to add multiple products, simply add another entry within the [] brackets. HEre are all the values explained:", $translation_ident ); ?>
<ol>

  <li>
    <strong>id</strong> (<?php echo WPWHPRO()->helpers->translate( "Required", $translation_ident ); ?>)<br>
    <?php echo WPWHPRO()->helpers->translate( "This is the download id within WordPress.", $translation_ident ); ?>
  </li>
  
  <li>
    <strong>quantity</strong> (<?php echo WPWHPRO()->helpers->translate( "Required", $translation_ident ); ?>)<br>
    <?php echo WPWHPRO()->helpers->translate( "The number of how many times this product should be added.", $translation_ident ); ?>
  </li>

  <li>
    <strong>item_price</strong> (<?php echo WPWHPRO()->helpers->translate( "Required", $translation_ident ); ?>)<br>
    <?php echo WPWHPRO()->helpers->translate( "The price of the product you want to add", $translation_ident ); ?>
  </li>

  <li>
    <strong>tax</strong> (<?php echo WPWHPRO()->helpers->translate( "Required", $translation_ident ); ?>)<br>
    <?php echo WPWHPRO()->helpers->translate( "The amount of tax that should be added to the item_price", $translation_ident ); ?>
  </li>

  <li>
    <strong>discount</strong><br>
    <?php echo WPWHPRO()->helpers->translate( "The amount of discount that should be removed from the item_price", $translation_ident ); ?>
  </li>

  <li>
    <strong>fees</strong><br>
    <?php echo WPWHPRO()->helpers->translate( "Fees are extra prices that are added on top of the product price. Usually this is set for signup fees or other prices that are not directly related with the download. The values set within the fees are all optional, but recommended to be available within the JSON.", $translation_ident ); ?>
  </li>

  <li>
    <strong>item_number</strong><br>
    <?php echo WPWHPRO()->helpers->translate( "The item number contains variation related data about the product. In case you want to add a variation, you can define the price id there.", $translation_ident ); ?>
  </li>

  <?php do_action( 'wpwh/descriptions/actions/edd_create_payment/after_cart_details_items', $default_cart_details ); ?>

</ol>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "send_receipt", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The send_receipt argument allows you to send out the receipt for the payment you just made. Please note that this logic uses the EDD default functionality. The receipt is only send based on the given payment status.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_first_name", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Please not that defining the customer first name (or last name) are only affecting the custoemr in case it doesn't exist at that point. For existing customers, the first and last name is not updated.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_last_name", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Please not that defining the customer last name (or first name) are only affecting the custoemr in case it doesn't exist at that point. For existing customers, the first and last name is not updated.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The do_action argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the edd_create_payment action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 4 );
function my_custom_callback_function( $payment_id, $purchase_data, $send_receipt, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$payment_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the id of the newly created payment.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$purchase_data</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array that contins the validated payment data we sent over to the edd_insert_payment() function", $translation_ident ); ?>
    </li>
    <li>
        <strong>$send_receipt</strong> (string)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "A boolean value of wether the receipt should be sent (if applicable) or not.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>