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


require_once ABSPATH . 'wp-includes/capabilities.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-includes/user.php';
require_once ABSPATH . 'wp-includes/functions.php';




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

/*
function mzb_deactivate()
{
    if (!is_plugin_active('wp-remote-users-sync/wprus.php')) {
        wp_die('Please activate WP Remote Users Sync plugin first.');
    }
}
*/
add_action('admin_menu', 'mzb_add_admin_menu');
function mzb_add_admin_menu()
{
    add_menu_page(
        'Child Remote Users Sync',
        'Child Remote Users Sync',
        'manage_options',
        'mzb-plugin',
        'mzb_plugin_options',
        'dashicons-pressthis',
        6
    );
}


function mzb_plugin_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    // création d'un formulaire avec un champs texte pour ajouter un id plan

    echo '<div class="wrap">';
    echo '<h1>Child Remote Users Sync</h1>';
    echo '<form method="post" action="options.php">';
    echo '<h3> Ajouter votre plan id produit woocommerce </h3>';
    echo '<p> Pour plus d\'info voir la doc de Woocommerce 
    <a href="https://docs.woocommerce.com/document/woocommerce-memberships-plans/"> 
    cliquez ici </a> </p>';
   
    settings_fields('mzb_options');
    do_settings_sections('mzb-plugin');
    submit_button();
    echo '</form>';
    echo '</div>';
}

add_action('admin_init', 'mzb_settings_init');
function mzb_settings_init()
{
    register_setting('mzb_options', 'mzb_id_plan');
    add_settings_section(
        'mzb_section_id',
        '',
        'mzb_settings_section_callback',
        'mzb-plugin'
    );
    add_settings_field(
        'mzb_id_plan',
        'Plan ID',
        'mzb_id_plan_callback',
        'mzb-plugin',
        'mzb_section_id'
    );
}


function mzb_settings_section_callback()
{
    echo '<p> Ajouter votre plan id produit woocommerce </p>';
}


function mzb_id_plan_callback()
{
    printf(
        '<input type="text" id="mzb_id_plan" name="mzb_id_plan" value="%s" />',
        get_option('mzb_id_plan')
    );
}


add_action('admin_init', 'mzb_add_plan_id');
function mzb_add_plan_id()
{
    $plan_id = get_option('mzb_id_plan');
    if (!empty($plan_id)) {
        $plan_id = get_option('mzb_id_plan');
        $plan_id = explode(',', $plan_id);
        $plan_id = array_map('trim', $plan_id);
        $plan_id = array_map('intval', $plan_id);
        $plan_id = array_unique($plan_id);
        $plan_id = array_filter($plan_id);
        $plan_id = implode(',', $plan_id);
        update_option('mzb_id_plan', $plan_id);
    }
}







//create function to add roles
function mzb_add_roles()
{
    add_role('site_member', 'Site Member', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ));
}
add_action('init', 'mzb_add_roles');
add_action('admin_init', 'mzb_add_roles');


// create function to remove roles
function mzb_remove_roles()
{
    remove_role('site_member');
    remove_role('Site Member'); //remove role from the database
}
//add_action('init', 'mzb_remove_roles');



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



 add_action('profile_update', 'mzb_update_profile_user', 10, 2);

 function mzb_update_profile_user($user_id, $old_user_data)
 {
     $user = get_user_by('id', $user_id);
     $user->set_role('customer');
 }
 

 class Wprus_Api_Update extends Wprus_Api_Abstract
 {
     public function __construct()
     {
         add_action('wprus_update_user_notification', array($this, 'notify_user'), 10, 2);
     }
   
     public function notify_user($user_id)
     {
         $user = get_user_by('id', $user_id);
        
         $memberships = wc_memberships_get_user_memberships($user);
         //$wp_user = get_userdata($user_id);
         $roles   = $user->roles;

         if (in_array('site_member', $roles)) {
             foreach ($memberships as $membership) {
                 wp_delete_post($membership->get_id());
             }
         }

         if (in_array('subscriber', $roles)) {
             $args = array(
            // Enter the ID (post ID) of the plan to grant at registration
            'plan_id'   => get_option('mzb_id_plan'),
            'user_id'   => $user,
        );
         
             // create a new membership
             $membership = wc_memberships_create_user_membership($args);
             var_dump($membership);
         }

         
    
         // Si user role est customer give acces
         if (in_array('customer', $roles)) {
        
         //if there are any memberships returned, do not grant access from purchase
             if (count($memberships) > 0) {
                 return;
             }
        
             $args = array('plan_id' => get_option('mzb_id_plan'),'user_id' => $user,
        );
             wc_memberships_create_user_membership($args);
             echo '<script type="text/javascript">' . 'console.log(' . $roles . ');</script>';
             //die();
         }
     }
 }
