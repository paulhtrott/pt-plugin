<?php
/*
Plugin Name: Paul Trott Plugin
Description: Paul's first plugin. Following a tutorial on UDEMY. Seems like a good thing to learn.
Version: 1.0
Author: Paul Trott
Author URI: http://www.paulhtrott.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: pt-plugin
*/

// Custom post types using Custom Post Type UI Plugin
//   generates the post type and can export generated code to use in your plugin code.
//
// Custom fields (meta-data - ie. Mood -> Happy) - adding extra information to post type - using Advanced Custom Fields plugin
//   generates the custom field code, BUT would need to include advanced custom fields plugin with your plugin for exported code to work.


// 1. HOOKS

// inject short codes on init
add_action('init', 'slb_register_shortcodes');

// register custom admin headers
add_filter('manage_edit-slb_subscriber_columns', 'slb_subscriber_column_headers');
add_filter('manage_edit-slb_list_columns', 'slb_list_column_headers');

// register custom admin column data
add_filter('manage_slb_subscriber_posts_custom_column', 'slb_subscriber_column_data', 1, 2); // 1 = priority high, 2 = accepted arguments so we get column name and post id.
add_action('admin_head-edit.php', 'slb_register_custom_admin_titles');
add_filter('manage_slb_list_posts_custom_column', 'slb_list_column_data', 1, 2); // 1 = priority high, 2 = accepted arguments so we get column name and post id.


// register ajax actions
add_action('wp_ajax_nopriv_slb_save_subscription', 'slb_save_subscription'); // regular visitor
add_action('wp_ajax_slb_save_subscription', 'slb_save_subscription'); // admin user

// 2. SHORTCODES

//register short codes here
function slb_register_shortcodes() {
  add_shortcode('slb_form', 'slb_form_shortcode');
}

function slb_form_shortcode($args, $content="") {

  // get the list id
  $list_id = 0;
  if( isset($args['id']) ):
    $list_id = (int)$args['id'];
  endif;

  // setup our output variable - the form html
  $output = '
    <div class="slb">
      <form id="slb_form" name="slb_form" class="slb-form" method="post" action="/wp-admin/admin-ajax.php?action=slb_save_subscription">
        <input type="hidden" name="slb_list" value="' . $list_id . '">
        <p class="slb-input-container">
          <label>Your Name</label>
          <input type="text" name="slb_fname" placeholder="First Name" />
          <input type="text" name="slb_lname" placeholder="Last Name" />
        </p>
        <p class="slb-input-container">
          <label>Your Email</label>
          <input type="email" name="slb_email" placeholder="ex. you@gmail.com" />
        </p>

        ';

        if (strlen($args['content'])):
          $output .= '<div class="slb_content">' . wpautop($args['content']) . '</div>';
        endif;

        $output .= '<p class="slb-input-container">
          <input type="submit" name="slb_submit" value="Sign Me Up!" />
        </p>
      </form>
    </div>
';

  //return results
  return $output;
}


// 3. FILTERS

// add custom column headers for subscriber screen
function slb_subscriber_column_headers( $columns ) {

  // creating custom header data
  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => __('Subscriber Name'),
    'email' => __('Email Address'),
  );

  //return new columns
  return $columns;
}

// custom column data for subscriber screen
function slb_subscriber_column_data( $column, $post_id ) {

  // setup our return text
  $output = '';

  switch( $column ) {
    case 'title':
      //get the custom data
      $fname = get_field('slb_fname', $post_id);
      $lname = get_field('slb_lname', $post_id);
      $output .= $fname .  ' ' . $lname;
      break;
    case 'email':
      $email = get_field('slb_email', $post_id);
      $output .= $email;
      break;
  }

  // echo the output
  echo $output;
}

function slb_register_custom_admin_titles() { // FOR CUSTOM TITLE FOR SUBSCRIBERS - NEW WORDPRESS WAY
  add_filter('the_title', 'slb_custom_admin_titles', 99, 2); // 99 so runs last
}

function slb_custom_admin_titles( $title, $post_id ) {
  global $post;

  $output = $title;

  if( isset($post->post_type) ):
    switch( $post->post_type ) {
      case 'slb_subscriber':
        $fname = get_field('slb_fname', $post_id);
        $lname = get_field('slb_lname', $post_id);
        $output = $fname .  ' ' . $lname;
        break;
    }
  endif;

  return $output;
}

// add custom column headers for list screen
function slb_list_column_headers( $columns ) {

  // creating custom header data
  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => __('List Name'),
    'shortcode' => __('Short Code'),
  );

  //return new columns
  return $columns;
}


// custom column data for list screen
function slb_list_column_data( $column, $post_id ) {

  // setup our return text
  $output = '';

  switch( $column ) {
    case 'shortcode':
      $output .= '[slb_form id="'. $post_id . '"]';
      break;
  }

  // echo the output
  echo $output;
}



// 4. EXTERNAL SCRIPTS

// 5. ACTIONS

// saves subscription data to an existing or new subscriber
function slb_save_subscription() {

  // setup default result data
  $result = array(
    'status' => 0,
    'message' => 'Subscription was not saved. ',
  );

  // array for storing errors
  $errors = array();

  try {
    // get list_id
    $list_id = (int)$_POST['slb_list'];

    // prepare subscriber data
    $subscriber_data = array(
      'fname' => esc_attr($_POST['slb_fname']),
      'lname' => esc_attr($_POST['slb_lname']),
      'email' => esc_attr($_POST['slb_email'])
    );

    // attempt to create/save subscriber
    $subscriber_id = slb_save_subscriber($subscriber_data);

    // IF subscriber was saved successfully $subscriber_id will be greate than 0
    if($subscriber_id):
      // If subscriber already has this subscription
      if(slb_subscriber_has_subscription($subscriber_id, $list_id)):
        // get list object
        $list = get_post($list_id);

        // return detailed error
        $result['message'] .= esc_attr($subscriber_data['email'] . ' is already subscribed to ' . $list->post_title . '.');

      else:

        // save new subscription
        $subscription_saved = slb_add_subscription($subscriber_id, $list_id);

        // if subscription saved successfully
        if($subscription_saved):

          //subscription saved
          $result['status'] = 1;
          $result['message'] = 'Subscription saved';
        endif;
      endif;
    endif;
  } catch (Exception $e) {
    // php error
    $result['message'] .= 'Caught exception: ' . $e->getMessage();
  }

  // return result as json
  slb_return_json($result);
}

// creates a new subscriber or updates an existing one
function slb_save_subscriber($subscriber_data) {

  //setup default subscriber id
  //0 means subscriber was not saved
  $subscriber_id = 0;

  try {
    $subscriber_id = slb_get_subscriber_id($subscriber_data['email']);

    // IF the subscriber does not already exist....
    if (!$subscriber_id):

      // add new subscriber to database
      $subscriber_id = wp_insert_post(
        array(
          'post_type' => 'slb_subscriber',
          'post_title' => $subscriber_data['fname'] . ' ' . $subscriber_data['lname'],
          'post_status' => 'publish'
        ),
        true
      );

    endif;

    // add/update custom meta data
    update_field(slb_get_acf_key('slb_fname'), $subscriber_data['fname'], $subscriber_id);
    update_field(slb_get_acf_key('slb_lname'), $subscriber_data['lname'], $subscriber_id);
    update_field(slb_get_acf_key('slb_email'), $subscriber_data['email'], $subscriber_id);

  } catch(Exception $e) {
    // a php error
  }

  // reset the Wordpress post object
  // wp_reset_query();

  // return the subscriber_id
  return $subscriber_id;
}

// 6. HELPERS

// returns true or false
function slb_subscriber_has_subscription($subscriber_id, $list_id) {

  // setup default return value
  $has_subscription = false;

  // get subscriber
  $subscriber = get_post($subscriber_id);

  // get subscriptions
  $subscriptions = slb_get_subscriptions($subscriber_id);

  // check subscriptions for $list_id
  if (in_array($list_id, $subscriptions)):
    // found the $list_id in $subscriptions
    $has_subscription = true;
  endif;

  return $has_subscription;

}

// get subscriber id from email address
function slb_get_subscriber_id($email) {

  $subscriber_id = 0;

  try {

    // check if subscriber already exists
    $subscriber_query = new WP_Query(
      array(
        'post_type' => 'slb_subscriber',
        'posts_per_page' => 1,
        'meta_key' => 'slb_email',
        'meta_query' => array(
          array(
            'key' => 'slb_email',
            'value' => $email,
            'compare' => '='
          )
        )
      )
    );

    if( $subscriber_query->have_posts() ):
      // get the subscriber id
      $subscriber_query->the_post();
      $subscriber_id = get_the_ID();
    endif;

  } catch (Exception $e) {
    // error occured
  }

  // reset the Wordpress post object, clears memory for post object
  wp_reset_query();

  return (int)$subscriber_id;
}

// return the array of list_ids for subscriber
function slb_get_subscriptions($subscriber_id) {
  $subscriptions = array();

  // get subscriptions (returns array of list objects
  $lists = get_field(slb_get_acf_key('slb_subscriptions'), $subscriber_id);

  // if $lists returns something
  if($lists):
    // if lists is an array and there is one or more items
    if(is_array($lists) && count($lists)):
      // build subscriptions: array of list ids
      foreach ( $lists as &$list):
        $subscriptions[]= (int)$list->ID;
      endforeach;
    elseif( is_numeric($lists) ):
      // single result returned
      $subscriptions[]= $lists;
    endif;
  endif;

 return $subscriptions;
}

// add list to subscribers subscription
function slb_add_subscription($subscriber_id, $list_id) {

  //setup default return value
  $subscription_saved = false;

  // if the subscriber does NOT have the current list subscription
  if( !slb_subscriber_has_subscription($subscriber_id, $list_id) ):

    // get subscription and append the new list id
    $subscriptions = slb_get_subscriptions($subscriber_id);
    array_push($subscriptions, $list_id);

    //update slb_subscriptions
    update_field(slb_get_acf_key('slb_subscriptions'), $subscriptions, $subscriber_id);

    // subscriptions updated
    $subscription_saved = true;
  endif;

  //return result
  return $subscription_saved;
}

// gets the unique acf (advanced custom fields) field key from the field name

function slb_get_acf_key($field_name) {

  $field_key = $field_name;

  switch($field_key) {
    case 'slb_fname':
      $field_key = 'field_59e90580b1c31';
      break;
    case 'slb_lname':
      $field_key = 'field_59e905a5b1c32';
      break;
    case 'slb_email':
      $field_key = 'field_59e905c4b1c33';
      break;
    case 'slb_subscriptions':
      $field_key = 'field_59e905ecb1c34';
      break;
  }

  return $field_key;
}

// return json
function slb_return_json($php_array) {
  //encode result as json string
  $json_result = json_encode($php_array);
  // return result and terminate php processing
  exit($json_result);
}

function slb_get_subscriber_data($subscriber_id) {
  //setup subscriber data
  $subscriber_data = array();

  //get subscriber object
  $subscriber = get_post($subscriber_id);

  //if subscriber object is valid
  if ( isset($subscriber->post_type) && $subscriber->post_type == 'slb_subscriber' ):

    $fname = get_field(slb_get_acf_key('slb_fname'), $subscriber_id);
    $lname = get_field(slb_get_acf_key('slb_lname'), $subscriber_id);

    $subscriber_data = array(
      'name' => $fname . ' ' . $lname,
      'fname' => $fname,
      'lname' => $lname,
      'email' => get_field(slb_get_acf_key('slb_email'), $subscriber_id),
      'subscriptions' => slb_get_subscriptions($subscriber_id)
    );
  endif;

  // return subscriber_data
  return $subscriber_data;
}


// 7. CUSTOM POST TYPES
// 8. ADMIN PAGES
// 9. SETTINGS
