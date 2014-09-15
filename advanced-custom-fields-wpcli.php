<?php
/*
Plugin Name: Advanced Custom Fields wp-cli Extension
Plugin URI: http://www.advancedcustomfields.com/add-ons/acf-wpcli/
Description: This extension for Advanced Custom Fields makes it possible to manage your field_groups through the console of wp-cli.
Version: 0.1
Author: Hoppinger
Author URI: http://www.hoppinger.com/
License: GPL
Copyright: Hoppinger
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
  //Since version 5 uses json instead of xml, we have a new ACF command for it.
  if ( substr( get_option( 'acf_version' ), 0, 1 ) == 5 ) {
    // Include and register the class as the 'example' command handler
    include 'ACF5_Command.php';
    WP_CLI::add_command( 'acf', 'ACF5_Command' ); 
  }else{
    // Include and register the class as the 'example' command handler
    include 'ACFCommand.php';
    WP_CLI::add_command( 'acf', 'ACFCommand' ); 
  }
}

/*
 * add the php field_groups to our wordpress installation on runtime
 */

if ( ! defined( 'WP_CLI' ) ) {
  function acf_wpcli_register_groups() {
    global $blog_id;
    if ( function_exists( "register_field_group" ) ) :
      global $wpdb;
    $db_field_groups = $wpdb->get_results( "SELECT post_title FROM {$wpdb->posts} WHERE post_type='acf' AND post_status='publish';" );

    $db_field_group_titles = array();
    foreach ( $db_field_groups as $db_group ) :
      $db_field_group_titles[] = $db_group->post_title;
    endforeach;

    $path_pattern = get_stylesheet_directory() . '/field-groups/*/data.php';
    $shared_childs_pattern = ABSPATH . 'field-groups/shared-childs/*/data.php';
    $added_groups           = array();

    function get_data( $f ) {
      if ( ! is_readable( $f ) || ! is_file( $f ) )
        return false;

      include $f;
      return $group;
    }

    // register the field groups specific for this subsite
    foreach ( glob( $path_pattern ) as $file ) {
      $group = get_data( $file );

      // Don't register group when the group is already in the DB
      if ( ! in_array( $group['title'] , $db_field_group_titles ) )
        register_field_group( $group );
      $added_groups[] = $group['title'];
    }

    if ( $blog_id != 1 ) {
      // register the field groups that are shared for all child websites
      foreach ( glob( $shared_childs_pattern ) as $file ) {
        $group = get_data( $file );

        // 1. Don't register group when the group is already in the DB
        // 2. Don't register group when the group has been added from a blog_id specific group
        if ( ! in_array( $group['title'] , $db_field_group_titles ) && ! in_array( $group['title'] , $added_groups ) )
          register_field_group( $group );
      }
    }

    endif;
  }
  add_action( 'plugins_loaded', 'acf_wpcli_register_groups' );
}
