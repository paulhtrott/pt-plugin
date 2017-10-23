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


// 2. SHORTCODES

//register short codes here
function slb_register_shortcodes() {
  add_shortcode('slb_form', 'slb_form_shortcode');
}

function slb_form_shortcode($args) {

  // setup our output variable - the form html
  $output = '
    <div class="slb">
      <form id="slb_form" name="slb_form" class="slb-form" method="post">
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
  );

  //return new columns
  return $columns;
}


// custom column data for list screen
function slb_list_column_data( $column, $post_id ) {

  // setup our return text
  $output = '';

  switch( $column ) {
    case 'example':
      //get the custom data
      /*
      $fname = get_field('slb_fname', $post_id);
      $lname = get_field('slb_lname', $post_id);
      $output .= $fname .  ' ' . $lname;
      break;
       */
  }

  // echo the output
  echo $output;
}



// 4. EXTERNAL SCRIPTS

// 5. ACTIONS
// 6. HELPERS
// 7. CUSTOM POST TYPES
// 8. ADMIN PAGES
// 9. SETTINGS
