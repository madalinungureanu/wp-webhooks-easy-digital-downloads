<?php

/**
 * Template for updating an EDD subscription
 * 
 * Webhook type: action
 * Webhook name: edd_update_subscription
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-update-subscription-description";

$default_subscription_statuses = array (
    'pending' => __( 'Pending', 'edd-recurring' ),
    'active' => __( 'Active', 'edd-recurring' ),
    'cancelled' => __( 'Cancelled', 'edd-recurring' ),
    'expired' => __( 'Expired', 'edd-recurring' ),
    'trialling' => __( 'Trialling', 'edd-recurring' ),
    'failing' => __( 'Failing', 'edd-recurring' ),
    'completed' => __( 'Completed', 'edd-recurring' ),
);
$default_subscription_statuses = apply_filters( 'wpwh/descriptions/actions/edd_update_subscription/default_subscription_statuses', $default_subscription_statuses );
$beautified_subscription_statuses = json_encode( $default_subscription_statuses, JSON_PRETTY_PRINT );

$default_subscription_periods = array (
    'day' => __( 'Daily', 'edd-recurring' ),
    'week' => __( 'Weekly', 'edd-recurring' ),
    'month' => __( 'Monthly', 'edd-recurring' ),
    'quarter' => __( 'Quarterly', 'edd-recurring' ),
    'semi-year' => __( 'Semi-Yearly', 'edd-recurring' ),
    'year' => __( 'Yearly', 'edd-recurring' ),
);
$default_subscription_periods = apply_filters( 'wpwh/descriptions/actions/edd_update_subscription/default_subscription_periods', $default_subscription_periods );
$beautified_subscription_periods = json_encode( $default_subscription_periods, JSON_PRETTY_PRINT );

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update an existing subscription for <strong>Easy Digital Downloads - Recurring</strong> within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_update_subscription</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_update_subscription</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_update_subscription</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>subscription_id</strong> argument. Please set it to the id of the subscription you would like to update. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the update process of the EDD Subscription code.", $translation_ident ); ?></>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "In case you would like to update the customer but you do not have the customer id, simply provide the customer email within the <strong>customer_email</strong> argument and we will fetch the customer automatically.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "subscription_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The id of the subscription you would like to update. Please note that the subscription needs to be existent, otherwise we will throw an error.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "expiration_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a date string what contains the date of expiration of the subscription. As a format, we recommend the SQL format (2021-05-25 11:11:11), but it also accepts other formats.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "profile_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe. It accepts any kind of string.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_email", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts the email of the customer you would like to set for the susbcription. You can set this argument in case you do not have the customer id of the customer available.", $translation_ident ); ?>
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
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "created_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a date string what contains the date of expiration of the subscription. As a format, we recommend the SQL format (2021-05-25 11:11:11), but it also accepts other formats.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "parent_payment_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to connect your subscription with an already existing payment.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to connect an already existing customer with your subscription. Please use the customer id and not the user id since these are different things. Please note, that in case you leave this argument empty, we will first try to find an existing customer based on your given email within the <strong>customer_email</strong> argument, and if we found a customer with it, we will map the customer id automatically.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "edd_price_option", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you work with multiple price options, please define the chosen price option for your download here. Please note, that the price option needs to be available within the download you chose for the <strong>download_id</strong> argument.", $translation_ident ); ?>
<br>
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
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>edd_update_subscription</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 4 );
function my_custom_callback_function( $subscription_id, $subscription, $sub_args, $return_args ){
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
        <strong>$sub_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing all the susbcription arguments that we are sending over to the EDD_Subscription()->update() function.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>