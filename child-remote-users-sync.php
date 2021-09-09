<?php

/*
Plugin Name: Child Remote Users Sync
Plugin URI:
Description: Synchronise WordPress Users
membership woocommerce across Multiple Sites.
Version: 1.
dependance: WP Remote Users Sync
Author: Mohammed Bensaad
Author URI: https://fr.linkedin.com/in/mohammed-bensaad-developpeur

Text Domain: mzb
Domain Path: /languages
*/


if (!defined('WPINC')) {
    die;
}

if (class_exists('class-wprus.php')) {
    require ABSPATH . 'wp-content/plugins/wprus.php';
}
    

if (! defined('MZB_PLUGIN_PATH')) {
    define('MZB_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (! defined('MZB_PLUGIN_URL')) {
    define('MZB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (! defined('MZB_PLUGIN_BASENAME')) {
    define('MZB_PLUGIN_BASENAME', plugin_basename(__FILE__));
}


require_once ABSPATH . 'wp-content/plugins/wp-remote-users-sync/wprus.php';
require_once ABSPATH . 'wp-content/plugins/wp-remote-users-sync/inc/api/class-wprus-api-abstract.php';

register_activation_hook(__FILE__, 'mzb_activate');
register_deactivation_hook(__FILE__, 'mzb_deactivate');


function mzb_activate()
{
    if (!is_plugin_active('wp-remote-users-sync/wprus.php')) {
        wp_die('Please activate WP Remote Users Sync plugin first.');
    }
}


function mzb_deactivate()
{
    if (!is_plugin_active('wp-remote-users-sync/wprus.php')) {
        wp_die('Please activate WP Remote Users Sync plugin first.');
    }
}


 // action_hooks mzb_change_user_role
 add_action('wc_memberships_user_membership_status_changed', 'mzb_change_user_role', 10, 3);
 add_filter('wc_memberships_grant_access_to_purchaser', 'mzb_grant_access_to_purchaser', 10, 3);


 function mzb_grant_access_to_purchase($grant_access, $args)
 {
     $user_id = $args['user_id'];
     $product_id = $args['product_id'];
     $order_id = $args['order_id'];

     $user_memberships = wc_memberships_get_user_memberships($user_id);
     $user_membership = $user_memberships[0];
     $user_membership_plan = $user_membership->get_plan();
     $user_membership_plan_id = $user_membership_plan->get_id();

     $order = wc_get_order($order_id);
     $order_items = $order->get_items();
     $order_item = $order_items[0];
     $product_id = $order_item->get_product_id();

     $product = wc_get_product($product_id);
     $product_plan_id = $product->get_id();

     if ($user_membership_plan_id == $product_plan_id) {
         $grant_access = false;
     }
     
     return $grant_access;
 }


// action_hooks update-profile-user
 add_action('profile_update', 'mzb_update_profile_user', 10, 2);

 function mzb_update_profile_user($user_id, $old_user_data)
 {
     $user = get_user_by('id', $user_id);
     $user->set_role('customer');
 }
 
 // importe class Wprus_Api_Update extends Wprus_Api_Abstract
 class Wprus_Api_Update extends Wprus_Api_Abstract
 {
     /**
      * @var string
      */

     protected $action = 'update';
     
     /**
      * @var string
      */
     protected $method = 'POST';

     /**
      * @var string
      */
     protected $endpoint = 'update';

     /**
      * @var array
      */
     protected $required_params = array(
         'user_id',
         'user_email',
         'user_login',
         'user_nicename',
         'user_url',
         'display_name',
         'first_name',
         'last_name',
         'nickname',
         'description',
         'rich_editing',
         'comment_shortcuts',
         'admin_color',
         'use_ssl',
         'show_admin_bar_front',
         'locale',
         'show_admin_bar_admin',
         'role',
         'avatar_url',
         'avatar_urls',
         'meta',
     );

     /**
      * @var array
      */
     protected $optional_params = array(
         'user_pass',
         'user_registered',
         'user_activation_key',
         'user_status',
         'spam',
         'deleted',
         'email',
         'url',
         'nicename',
         'capabilities',
         'extra_capabilities',
         'first_name',
         'last_name',
         'nickname',
         'description',
         'rich_editing',
         'comment_shortcuts',
         'admin_color',
         'use_ssl',
         'show_admin_bar_front',
         'locale',
         'show_admin_bar_admin',
         'role',
         'avatar_url',
         'avatar_urls',
         'meta',
     );


     public function init_notification_hooks()
     {
         add_action('wprus_update_user_notification', array($this, 'notify_user'), 10, 2);
     }
   
     public function notify_user($user_id)
     {
         $user = get_user_by('id', $user_id);
        
         // get all active memberships for the purchaser, regardless of status
         $memberships = wc_memberships_get_user_memberships($user);

         // affectation des variables
         //  $user_id = $users
         //$wp_user = get_userdata($user_id);
         $roles   = $user->roles;

 

 

         // Si user role est subscriber stop acces
         if (in_array('site_member', $roles)) {
             foreach ($memberships as $membership) {
                 wp_delete_post($membership->get_id());
             }
             foreach ($roles as $role) {
                 echo '<script type="text/javascript">' .
          'console.log(' . $role . ');</script>';
             }
         }

         if (in_array('subscriber', $roles)) {
             $args = array(
        // Enter the ID (post ID) of the plan to grant at registration
        'plan_id'   => 1356,
        'user_id'   => $user,
        );
         
             wc_memberships_create_user_membership($args);
             foreach ($roles as $role) {
                 echo '<script type="text/javascript">' .
        'console.log(' . $role . ');</script>';
             }
             var_dump($roles);
         }

         // Si user role est customer give acces
         if (in_array('customer', $roles)) {
        
         // if there are any memberships returned, do not grant access from purchase
             //var_dump($user_memberships);
             if (! empty($memberships)) {
                 return false;
             }

 


             $args = array(
        // Enter the ID (post ID) of the plan to grant at registration
        'plan_id'   => 1356,
        'user_id'   => $args1->id,
        );
         
             wc_memberships_create_user_membership($args);
             echo '<script type="text/javascript">' . 'console.log(' . $roles . ');</script>';
             //die();
         }
     }
 }
