<?php

/**
 * Template for updating an EDD discount
 * 
 * Webhook type: action
 * Webhook name: edd_update_discount
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-update-discount-description";

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update (or create) a discount code for Easy Digital Downloads within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_update_discount</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_update_discount</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_update_discount</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>discount_id</strong> argument. Please set it to either the discount id ot the discount code you want to update.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the update process of the EDD discount code.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "You can also use this webhook action to create a discount code in case it does not exist yet. Simply set the argument <strong>create_if_none</strong> to <strong>yes</strong>", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "By changing the <strong>type</strong> argument, you can switch between flat or percentage based discounts. More details are down below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "create_if_none", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this argument to <strong>yes</strong> in case you want to create the discount code if it does not exist.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "status", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Defines the status in which you want to create the discount code with. Possible values are <strong>active</strong> and <strong>inactive</strong>. By default, this value is set to <strong>active</strong>.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "current_uses", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a number that defines how often this discount code has been already used. Usually, you do not need to define this argument for creating a discount code.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "max_uses", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument defines the maximal number on how often this discount code can be applied. Set it to <strong>0</strong> for unlimited uses.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "amount", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The amount argument accepts different values, based on the type you set. By default, you can set this value to the number of percents you want to discount the order. E.g.: <strong>10</strong> will be represented as ten percent. If the <strong>type</strong> argument is set to <strong>flat</strong>, it would discount 10$ (or the currency you choose for your shop).", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "start_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set the date you want this discount code do become active. We recommend using the SQL format: <strong>2020-03-10 17:16:18</strong>. This arguments also accepts other formats - if you have no chance of changing the date format, its the best if you simply give it a try.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "expiration_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set the date you want this discount code do become inactive. We recommend using the SQL format: <strong>2020-03-10 17:16:18</strong>. This arguments also accepts other formats - if you have no chance of changing the date format, its the best if you simply give it a try.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "type", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument defines the type of the discount code. If you want to use a percentage, set this argument to <strong>percent</strong>. If you would like to use a flat amount, please set it to <strong>flat</strong>. Based on the given value, you might also want to adjust the <strong>amount</strong> argument.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "min_price", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set a minimum price that needs to be reached for a purchase to actually apply this discount code. Please write the price in the following format: 19.99", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "product_requirement", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you want to limit the discount code to only certain downloads, this argument is made for you. Simply separate the download IDs that are required by a comma. Here is an example:", $translation_ident ); ?>
<pre>123,443</pre>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "product_condition", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you set this argument to <strong>all</strong>, it is required to have all downloads from the <strong>product_requirement</strong> argument within the cart before the coupon will be applied. If you set the argument to <strong>any</strong>, only one of the products mentioned within the <strong>product_requirement</strong> argument have to be within the cart.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "excluded_products", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you want to limit certain downloads from applying this coupon code to, this argument is made for you. Simply comma-separate the download IDs that the coupon code should ignore. Here is an example:", $translation_ident ); ?>
<pre>32,786</pre>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "is_not_global", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this argument to <strong>yes</strong> in case you do not want to apply the discount code globally on the whole order. If you set this argument to <strong>yes</strong>, it will only be applied to the downloads you defined within the <strong>product_requirement</strong> argument. Default: <strong>no</strong>", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "is_single_use", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this argument to <strong>yes</strong> in case you want to limit the use of this discount code to only one time per customer. Default: <strong>no</strong>", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>edd_update_discount</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 4 );
function my_custom_callback_function( $discount_id, $discount, $needs_creation, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$discount_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the id of the newly created discount code.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$discount</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array that contins the validated discount data we sent over to the EDD_Discounts() class", $translation_ident ); ?>
    </li>
    <li>
        <strong>$needs_creation</strong> (boolean)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This value is true if the <strong>create_if_none</strong> argument is set to <strong>yes</strong> and the discount code you tried to update, was not found.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>