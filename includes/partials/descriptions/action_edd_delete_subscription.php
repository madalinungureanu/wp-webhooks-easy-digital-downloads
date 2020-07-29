<?php

/**
 * Template for deleting an EDD subscription
 * 
 * Webhook type: action
 * Webhook name: edd_delete_subscription
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-delete-subscription-description";

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to delete an existing subscription for <strong>Easy Digital Downloads - Recurring</strong> within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_delete_subscription</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_delete_subscription</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_delete_subscription</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>subscription_id</strong> argument. Please set it to the id of the subscription you would like to delete. Further details are available down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the deletion of the EDD Subscription.", $translation_ident ); ?></>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "By default, we properly erase the subsription including the relations on the customer and payments.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "subscription_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The id of the subscription you would like to delete. Please note that the subscription needs to be existent, otherwise we will throw an error.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "keep_payment_meta", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this value to <strong>yes</strong> to keep the payment meta (meta key on the payment: _edd_subscription_payment). Usually, it makes sense to remove this relation as well. That's why this value is deleted by default. Please only set it to <strong>yes</strong> in case you need to keep the meta key.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "keep_list_of_trials", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this value to <strong>yes</strong> to keep the meta entry for the list of trials (meta key on the user: edd_recurring_trials). Usually, it makes sense to remove this relation as well. That's why this value is deleted by default. Please only set it to <strong>yes</strong> in case you need to keep the meta key.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>edd_delete_subscription</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 3 );
function my_custom_callback_function( $subscription_id, $subscription, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$subscription_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the id of the deleted subscription.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$subscription</strong> (object)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An object of the EDD_Subscription() class with the currently deleted subscription.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>