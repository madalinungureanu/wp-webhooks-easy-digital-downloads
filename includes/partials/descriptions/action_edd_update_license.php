<?php

/**
 * Template for updating an EDD license
 * 
 * Webhook type: action
 * Webhook name: edd_update_license
 * Template version: 1.0.0
 */

$translation_ident = "action-edd-update-license-description";

?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update a license for Easy Digital Downloads within your WordPress system via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "The description is uniquely made for the <strong>edd_update_license</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>edd_update_license</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>edd_update_license</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>license_id</strong> argument. You can set it to either the license id or the license key.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the creation of the EDD license.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Tipps", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "In case you would like to set the license to a lifetime validity, simply set the <strong>expiration_date</strong> argument to <strong>0</strong>.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>

<h5><?php echo WPWHPRO()->helpers->translate( "license_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts either the numeric license id or the license key that was set for the license. E.g. 4fc336680bf576cc0298777278ceb15a", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "download_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The download id of the download (product) you want to relate with the license. Please note that the product needs to have licensing activated.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "payment_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The payment id of the payment you want to relate with the license. It will be used to assign the user to the license, as well as the customer.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "license_key", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to update the license key for the given license. Alternatively, you can also set the argument value to <strong>regenerate</strong> to automatically regenerte the license key.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "price_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you work with pricing options (variations) for your downloads, use this argument to set the pricing id of the variation price. The pricing id is called <strong>Download file ID</strong> on the edit-download page.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "cart_index", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The identifier of the given download within the cart array. You can use this argument to associate the license with a specifc product wiithin the payment.", $translation_ident ); ?>
<br>
<hr>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "parent_license_id", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to set a parent license for the updated license.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "activation_limit", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "In case you would like to customize the licensing slots for your license (the amount of wesbites that can be added), you can use this argument. Please set it to e.g. 20 to allow 20 licensing slots. If you set this argument to 0, the license will contain unlimited license slots.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "date_created", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "You can use this argument to customize the creation date. It allows you to set most kind of date formats, but we suggest you using the SQL format: 2021-05-25 11:11:11", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "expiration_date", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "You can use this argument to customize the expiration date. It allows you to set most kind of date formats, but we suggest you using the SQL format: 2021-05-25 11:11:11. If you would like to never expire the license, set this argument to <strong>0</strong>.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "manage_sites", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to add and/or remove sites on a license. It accepts a JSON formatted string containg the site URLs. Here is an example:", $translation_ident ); ?>
<pre>[
  "https://demo.com",
  "https://demo.demo",
  "remove:https://demo3.demo"
]</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above adds two new site URLs. It also removes one site URL. To remove a site URL, please place <strong>remove:</strong> in front of the site URL.", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "logs", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Use this argument to add one or multiple log entries to the license. This value accepts a JSON formated string. Here is an example:", $translation_ident ); ?>
<pre>[
  {
    "title": "Log 1",
    "message": "This is my description for log 1"
  },
  {
    "title": "Log 2",
    "message": "This is my description for log 2",
    "type": null
  }
]</pre>
<?php echo WPWHPRO()->helpers->translate( "The example above adds two logs. The <strong>type</strong> key can contain a single term slug, single term id, or array of either term slugs or ids. For further details on the type key, please check out the \$terms variable within the wp_set_object_terms() function:", $translation_ident ); ?>
<a href="https://developer.wordpress.org/reference/functions/wp_set_object_terms/" target="_blank">https://developer.wordpress.org/reference/functions/wp_set_object_terms/</a>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "license_meta_arr", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to add/update or remove one or multiple license meta values to your newly created license, using a JSON string. Easy Digital Downloads uses a custom table for these meta values. Here are some examples on how you can use it:", $translation_ident ); ?>
<ul class="list-group list-group-flush">
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Add/update meta values", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This JSON shows you how to add simple meta values for your license.", $translation_ident ); ?>
        <pre>{
  "meta_1": "test1",
  "meta_2": "test2"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "The key is always the license meta key. On the right, you always have the value for the license meta value. In this example, we add two meta values to the license meta. In case a meta key already exists, it will be updated.", $translation_ident ); ?>
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
        <strong><?php echo WPWHPRO()->helpers->translate( "Add/update/remove serialized meta values", $translation_ident ); ?></strong>
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

<h5><?php echo WPWHPRO()->helpers->translate( "license_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument allows you to fire further native features of the licensing class. Please find further details down below:", $translation_ident ); ?>
<ul class="list-group list-group-flush">
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Enable licenses", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This value allows you to enable the license and all of its child licenses. It does it by checking on the activation count and if some sites are active, it will set the license to <strong>active</strong>, otherwise it will set it to <strong>inactive</strong>.", $translation_ident ); ?>
        <pre>enable</pre>
    </li>
    <li class="list-group-item">
        <strong><?php echo WPWHPRO()->helpers->translate( "Disable licenses", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This value allows you to disable the license and all of its child licenses. It will set the license to <strong>disabled</strong>.", $translation_ident ); ?>
        <pre>disable</pre>
    </li>
</ul>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The do_action argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the edd_update_license action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 3 );
function my_custom_callback_function( $license_id, $license, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$license_id</strong> (Integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the id of the newly created license.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$license</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the EDD_SL_License() object of the license.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "An array containing the information we will send back as the response to the initial webhook caller.", $translation_ident ); ?>
    </li>
</ol>