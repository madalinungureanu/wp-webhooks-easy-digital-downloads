<?php

/**
 * Template for updating an EDD customer
 * 
 * Webhook type: action
 * Webhook name: edd_update_customer
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-update-customer-description";

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update a customer for Easy Digital Downloads within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_update_customer</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_update_customer</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_update_customer</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "As a second argument, you need to set the actual data you want to use for fetching the user. If you have chosen nothing or <strong>email</strong> for the <strong>get_customer_by</strong> argument, you need to include the customers email address here.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the update process of the EDD customer.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "Updating a customer is not the same as updating a user. Easy Digital Downloads uses its own logic and tables for customers. Still, you can assign or update a user to a customer using the <strong>user_id</strong> argument.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "You can also create a user if your given values did not find any. To do that, simply set the <strong>create_if_none</strong> argument to <strong>yes</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Since this webhook action is very versatile, it is highly recommended to check out the <strong>Special Arguments list down below</strong>.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_value", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The value we use to determine the customer. In case you haven't set the <strong>get_user_by</strong> argument or you have set it to email, please include the customer email in here. If you have chosen the <strong>customer_id</strong>, please include the customer id and in case you set user_id, please include the user id.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "get_customer_by", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Customize the default way we use to fetch the customer from the backend. Possible values are <strong>email</strong> (Default), <strong>customer_id</strong> or <strong>user_id</strong>.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_email", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The customer email is the primary email address of the customer you want to update. In case we didn't find any customer and you set the <strong>create_if_none</strong> argument to <strong>yes</strong>, we will create the customer with this email address in case you did not use an email for the <strong>customer_value</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "additional_emails", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "You can add and/or remove additional emails on the customer. To do that, simply comma-separate the emails within the field. The primary email address is always the <strong>customer_email</strong> argument. Here is an example:", $translation_ident ); ?>
<pre>jondoe@mydomain.com,anotheremail@domain.com</pre>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can also remove already assigned email addresses. To do that, simply add <strong>:remove</strong> after the email. This will cause the email to be removed from the user. Here is an example:", $translation_ident ); ?>
<pre>fourth@domain.com:remove,more@domain.demo</pre>
<?php echo WPWHPRO()->helpers->translate( "The above example removes the email fourth@domain.com and adds the email more@domain.demo", $translation_ident ); ?>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "attach_payments", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to connect and/or remove certain payment ids at the user. To set multiple payments, please separate them with a comma. By default, it recalculates the total amount. If you do not want that, add <strong>:no_update_stats</strong> after the payment id. Here is an example:", $translation_ident ); ?>
<pre>125,365,444:no_update_stats,777</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above asigns the payment ids 125, 365, 444, 777 to the customer. It also assigns the payment id 444, but it does not update the statistics.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "To remove certain payments, please add <strong>:remove</strong> after the payment id. You can also combine it with the recalculation setting of the totals. Here is an example:", $translation_ident ); ?>
<pre>125remove,444:no_update_stats,777</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above removes the payment with the id 125, adds the payment 777 and adds the payment 444 without refreshing the stats.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "increase_purchase_count", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This field accepts a number, which is added on top of the existing purchase count. If you are going to add three payments for a customer that has currently none, and you set this value to 1, your total purchase count will show 4.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "increase_lifetime_value", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This field accepts a decimalnumber, which is added on top of the existing lifetime value. If you are going to add one payment with a price of 20$ for a customer that has 0 lifetime value, and you set this value to 5$, the total lifetime value will show 25$.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_notes", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to add one or multiple customer notes to the customer. This value accepts a JSON, containing one customer note per line. Here is an example:", $translation_ident ); ?>
<pre>[
  "First Note 1",
  "First Note 2"
]</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above adds two notes.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "customer_meta", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to add one or multiple customer meta values to your newly created customer, using a JSON string. Easy Digital Downloads uses a custom table for these meta values. Here are some examples on how you can use it:", $translation_ident ); ?>
<ul class="list-group list-group-flush">
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Add/update/remove meta values", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This JSON shows you how to add simple meta values for your customer.", $translation_ident ); ?>
        <pre>{
  "meta_1": "test1",
  "meta_2": "test2"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "The key is always the customer meta key. On the right, you always have the value for the customer meta value. In this example, we add two meta values to the customer meta. In case a meta key already exists, it will be updated.", $translation_ident ); ?>
    </li>
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Delete meta values", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "You can also delete existing meta key by setting the value to <strong>ironikus-delete</strong>. This way, the meta will be removed. Here is an example:", $translation_ident ); ?>
        <pre>{
  "meta_1": "test1",
  "meta_2": "ironikus-delete"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "The example above will add the meta key <strong>meta_1</strong> with the value <strong>test1</strong> and it deletes the meta key <strong>meta_2</strong> including its value.", $translation_ident ); ?>
    </li>
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Add/update serialized meta values", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Sometimes, it is necessary to add serialized arrays to your data. Using the json below, you can do exactly that. You can use a simple JSON string as the meta value and we automatically convert it to a serialized array once you place the identifier <strong>ironikus-serialize</strong> in front of it. Here is an example:", $translation_ident ); ?>
        <pre>{
  "meta_1": "test1",
  "meta_2": "ironikus-serialize{\"test_key\":\"wow\",\"testval\":\"new\"}"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "This example adds a simple meta with <strong>meta_1</strong> as the key and <strong>test1</strong> as the value. The second meta value contains a json value with the identifier <strong>ironikus-serialize</strong> in the front. Once this value is saved to the database, it gets turned into a serialized array. In this example, it would look as followed: ", $translation_ident ); ?>
        <pre>a:2:{s:8:"test_key";s:3:"wow";s:7:"testval";s:3:"new";}</pre>
    </li>
</ul>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "user_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to assign a user to the Easy Digital Downloads customer. In case the user id is not defined, we will automatically try to match the primary email with a WordPress user.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "create_if_none", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to create a customer in case it does not exist yet. Please set this argument to <strong>yes</strong> to create the customer. Default: <strong>no</strong>. In case the user does not exist, we use the email from the <strong>customer_value</strong> argument or in case you used a different value, we use the emfial from the <strong>customer_email</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The do_action argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>edd_update_customer</strong> action was fired (It also fires if the user was not successfully updated, but you can check if the user id is set or not to determine if it worked).", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 2 );
function my_custom_callback_function( $customer_id, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$customer_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "The customer id of the newly created customer. 0 or false if something went wrong.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller. This includes all of in the request set data.", $translation_ident ); ?>
    </li>
</ol>