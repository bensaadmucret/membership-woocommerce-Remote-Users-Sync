<?php

/*
Plugin Name: Remote-woocommerce-memberships-sync
Plugin URI:
Description: Plugin that will allow you to manage several WooCommerce Memberships plan on wordpress sites.
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


if (!defined('MZB_PLUGIN_PATH')) {
    define('MZB_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('MZB_PLUGIN_URL')) {
    define('MZB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MZB_PLUGIN_BASENAME')) {
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
    echo '<p> Ajouter votre plan id memberships woocommerce </p>';
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
        update_option('mzb_id_plan', $plan_id);
    }
}

class Wprus_Api_Update_Mzb extends Wprus_Api_Abstract
{
    public function __construct()
    {
        add_action('wprus_after_handle_action_notification', array($this, 'notify_remote'), PHP_INT_MAX, 2);
    }

    public function notify_remote($old_userdata, $user_id)
    {
        $ID = $user_id['username'];

        $user = get_user_by('login', $ID);

        $memberships = wc_memberships_get_user_memberships($user->id);
        $roles   = $user->roles;

        if (in_array('site_member', $roles)) {
            foreach ($memberships as $membership) {
                wp_delete_post($membership->get_id());
            }
        }

        if (in_array('subscriber', $roles)) {
            $args = array(
                'plan_id'   => get_option('mzb_id_plan'),
                'user_id'   => $user->id,
            );
            $membership = wc_memberships_create_user_membership($args);
        }
        if (in_array('customer', $roles)) {
            if (count($memberships) > 0) {
                return;
            }

            $args = array(
                'plan_id' => get_option('mzb_id_plan'), 'user_id' => $user->id,
            );
            wc_memberships_create_user_membership($args);
        }
    }
}

new Wprus_Api_Update_Mzb();
