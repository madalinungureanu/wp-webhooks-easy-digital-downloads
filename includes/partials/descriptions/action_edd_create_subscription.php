<?php

/**
 * Template for creating an EDD subscription
 * 
 * Webhook type: action
 * Webhook name: edd_create_subscription
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-create-subscription-description";

$default_subscription_statuses = array (
    'pending' => __( 'Pending', 'edd-recurring' ),
    'active' => __( 'Active', 'edd-recurring' ),
    'cancelled' => __( 'Cancelled', 'edd-recurring' ),
    'expired' => __( 'Expired', 'edd-recurring' ),
    'trialling' => __( 'Trialling', 'edd-recurring' ),
    'failing' => __( 'Failing', 'edd-recurring' ),
    'completed' => __( 'Completed', 'edd-recurring' ),
);
$default_subscription_statuses = apply_filters( 'wpwh/descriptions/actions/edd_create_subscription/default_subscription_statuses', $default_subscription_statuses );
$beautified_subscription_statuses = json_encode( $default_subscription_statuses, JSON_PRETTY_PRINT );

$default_subscription_periods = array (
    'day' => __( 'Daily', 'edd-recurring' ),
    'week' => __( 'Weekly', 'edd-recurring' ),
    'month' => __( 'Monthly', 'edd-recurring' ),
    'quarter' => __( 'Quarterly', 'edd-recurring' ),
    'semi-year' => __( 'Semi-Yearly', 'edd-recurring' ),
    'year' => __( 'Yearly', 'edd-recurring' ),
);
$default_subscription_periods = apply_filters( 'wpwh/descriptions/actions/edd_create_subscription/default_subscription_periods', $default_subscription_periods );
$beautified_subscription_periods = json_encode( $default_subscription_periods, JSON_PRETTY_PRINT );

$default_subscription_gateways = array ();
foreach( edd_get_payment_gateways() as $gwslug => $gwdata ){
    $default_subscription_gateways[ $gwslug ] = ( isset( $gwdata['admin_label'] ) ) ? $gwdata['admin_label'] : $gwdata['checkout_label'];
}
$default_subscription_gateways = apply_filters( 'wpwh/descriptions/actions/edd_create_subscription/default_subscription_gateways', $default_subscription_gateways );
$beautified_subscription_gateways = json_encode( $default_subscription_gateways, JSON_PRETTY_PRINT );

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to create a subscription for <strong>Easy Digital Downloads - Recurring</strong> within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_create_subscription</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_create_subscription</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_create_subscription</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>expiration_date</strong> argument. Please set it to the date of expiration. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "The third argument you need to set is <strong>profile_id</strong>. The profile id is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also a requirement to define the <strong>product_id</strong> argument. Please set it to the id of the download you want to connect with the subscription. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "The last required argument is the <strong>customer_email</strong> argument. Please set it to the email of the customer this subscription is for, or leave it empty if you want to create a new payment. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Please also set the <strong>period</strong> argument to the frequency you want to run the subscription it. Please see the <strong>Special Arguments</strong> section down below for further details.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the creation of the EDD Subscription.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "Creating a subscription will also create a payment, except you define the <strong>parent_payment_id</strong> argument.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Creating the subscription will also create a customer from the given email address of the <strong>customer_email</strong> argument, except you set the <strong>customer_id</strong> argument.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "expiration_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a date string what contains the date of expiration of the subscription. As a format, we recommend the SQL format (2021-05-25 11:11:11), but it also accepts other formats. Please note that in case you set the <strong>status</strong> argument to <strong>trialling</strong>, this date field will be ignored since we will calculate the expiration date based on the in the product given trial period.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "profile_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe. It accepts any kind of string.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_email", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the email of the customer you create the subscription for. In case we could not find a customer with your given data, it will be created. Please note that creating a customer does not automatically create a user within your WordPress system.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "period", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This is the frequency of the renewals for the subscription. Down below, you will find a list with all of the default subscription periods. Please use the slug as a value (e.g. <strong>month</strong>).", $translation_ident ); ?>
<pre><?php echo $beautified_subscription_periods;  ?></pre>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "transaction_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This is the unique ID of the initial transaction inside of the merchant processor, such as PayPal or Stripe. The argument accepts any kind of string.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "status", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to customize the status of the subscription. Please use the slug of the status as a value (e.g. <strong>completed</strong>). Down below, you will find a list with all available default statuses:", $translation_ident ); ?>
<pre><?php echo $beautified_subscription_statuses; ?></pre>
<?php echo WPWHPRO()->helpers->translate( "Please note that in case you choose <strong>trialling</strong> as a subscription status, we will automatically apply the given trial period instead of the given expiration date from the <strong>expiration_date</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "created_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a date string what contains the date of expiration of the subscription. As a format, we recommend the SQL format (2021-05-25 11:11:11), but it also accepts other formats. Please note that in case you set the <strong>status</strong> argument to <strong>trialling</strong>, this argument will influence the expiration date of the trial perdod, which is defined within the download itself.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "parent_payment_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to connect your subscription with an already existing payment. Please note that if you set this argument, the <strong>gateway</strong> argument is ignored since the gateway will be based on the gateway of the payment you try to add. If you do not set this argument, we will create a payment automatically for you.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to connect an already existing customer with your newly created subscription. Please use the customer id and not the user id since these are different things. Please note, that in case you leave this argument empty, we will first try to find an existing customer based on your given email within the <strong>customer_email</strong> argument, and if we cannot find any customer, we will create one for you based on the given email within the <strong>customer_email</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_first_name", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to add a first name to the customer in case it does not exist at that point. If we could find a customer to your given email or the cucstomer id, this argument is ignored. It is only used once a new customer is created. If it is not set, we will use the email as the default name.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_last_name", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to add a last name to the customer in case it does not exist at that point. If we could find a customer to your given email or the cucstomer id, this argument is ignored. It is only used once a new customer is created. If it is not set, we will use the email as the default name.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "edd_price_option", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you work with multiple price options, please define the chosen price option for your download here. Please note, that the price option needs to be available within the download you chose for the <strong>download_id</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "gateway", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Define the gateway you want to use for this subscription. Please note that if you set the <strong>parent_payment_id</strong> argument, the gateway of the payment is used and this argument is ignored. Please use the slug of the gateway (e.g. <strong>paypal</strong>). Here is a list of all currently available gateways:", $translation_ident ); ?>
<pre><?php echo $beautified_subscription_gateways; ?></pre>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "initial_tax_rate", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the percentage of tax that is included within your initial price. E.g.: In case you add 20, it is interpreted as 20% tax.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "initial_tax", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the amount of tax for the initial payment. E.g.: In case your tax is 13.54$, simply add 13.54", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "recurring_tax_rate", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the percentage of tax that is included within your recurring price. E.g.: In case you add 20, it is interpreted as 20% tax.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "recurring_tax", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the amount of tax for the recurring payment. E.g.: In case your tax is 13.54$, simply add 13.54", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "notes", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to add one or multiple subscription notes to the subscription. This value accepts a JSON, containing one subscription note per line. Here is an example:", $translation_ident ); ?>
<pre>[
  "First Note 1",
  "First Note 2"
]</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above adds two notes.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>edd_create_subscription</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 5 );
function my_custom_callback_function( $subscription_id, $subscription, $payment, $customer, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$subscription_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the id of the newly created subscription.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$subscription</strong> (object)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An object of the EDD_Subscription() class with the current subscription.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$payment</strong> (object)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An object of the EDD_Payment() class with the current related payment.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$customer</strong> (object)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An object of the EDD_Recurring_Subscriber() class with the current related customer.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>