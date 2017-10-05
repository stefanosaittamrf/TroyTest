<?php
/*
    Plugin Name: Marfeel
    Plugin URI:  http://www.marfeel.com
    Description: Marfeel configuration for Wordpress sites.
    Version:     1.6.9
    Author:      Marfeel Team
    Author URI:  http://www.marfeel.com
    License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	GitHub Plugin URI: https://github.com/stefanosaittamrf/TroyTest
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('MARFEEL_OPTIONS', 'marfeel_options');

require_once('marfeel_troy.php');
require_once('marfeel_wordpress_helper.php');
require_once('amp_endpoint_support.php');

register_activation_hook(__FILE__, 'activate_marfeel_plugin');
register_deactivation_hook(__FILE__, 'deactivate_marfeel_plugin');

add_action( 'upgrader_process_complete', 'upgrade_marfeel_plugin', 10, 2 );
add_action('admin_init', 'register_marfeel_options');
add_action('admin_menu', 'register_marfeel_settings_page' );
add_action('wp_head', 'render_marfeel_amp_link' );
add_action('wp', 'render_marfeel_amp_content' );


function activate_marfeel_plugin() {
	$marfeel_domain = MarfeelTroy::getInstance()->get_marfeel_domain_for_uri($_SERVER['SERVER_NAME']);

	if (isset($marfeel_domain)) {
			MarfeelWordpressHelper::getInstance()->save_option('marfeel_domain', $marfeel_domain);
	} else {
			MarfeelWordpressHelper::getInstance()->delete_all_options();
	}
    AmpEndpointSupport::getInstance()->activate_rewrite_strategy();
}

function deactivate_marfeel_plugin() {
	MarfeelWordpressHelper::getInstance()->delete_all_options();
    AmpEndpointSupport::getInstance()->deactivate_rewrite_strategy();
}

function register_marfeel_options() {
    register_setting(MARFEEL_OPTIONS, MARFEEL_OPTIONS, 'validate_marfeel_options');
}

function upgrade_marfeel_plugin( $upgrader_object, $options ) {
	$mrf_plugin = plugin_basename( __FILE__ );
	if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
		foreach( $options['plugins'] as $plugin ) {
			if( $plugin == $mrf_plugin && ! AmpEndpointSupport::getInstance()->check_rewrite_rules_active() ) {
				AmpEndpointSupport::getInstance()->activate_rewrite_strategy();
			}
		}
	}
}

function validate_marfeel_options($options) {
    $sanitized_domain = filter_var(trim($options['marfeel_domain']), FILTER_SANITIZE_SPECIAL_CHARS);

    if (strpos($sanitized_domain, '.marfeel.com') === false && strpos($sanitized_domain, 'amp.') === false) {
        add_settings_error(
            'marfeel_domain',
            'marfeeldomain_texterror',
            'Invalid domain. Please contact support@marfeel.com',
            'error'
        );

        return array();
    }

    $options['marfeel_domain'] = $sanitized_domain;

    return $options;
}

function register_marfeel_settings_page() {
    add_options_page(
        'Marfeel',
        'Marfeel',
        'manage_options',
        MARFEEL_OPTIONS,
        'render_marfeel_settings_page'
    );
}

function render_marfeel_settings_page() {
    $marfeel_domain = MarfeelWordpressHelper::getInstance()->get_option_value('marfeel_domain');

    echo MarfeelTroy::getInstance()->get_settings_page($marfeel_domain);
}

function render_marfeel_amp_link() {
	echo MarfeelTroy::getInstance()->get_amp_link_for_uri();
}

function render_marfeel_amp_content() {
	$data = get_marfeel_amp_content();
	if(!empty($data) && $data !== false) {
		echo $data;
		exit;
	}
}


function get_marfeel_amp_content() {
	if ( AmpEndpointSupport::getInstance()->mrf_is_amp_endpoint() ) {
		$amp_url = MarfeelTroy::getInstance()->get_amp_uri();
		$clean_amp_url = MarfeelWordpressHelper::getInstance()->crop_amp_endpoint($amp_url);
		if(!empty($clean_amp_url)) {
			if(MarfeelWordpressHelper::getInstance()->is_curl_installed()) {
				$ch = curl_init();
				$timeout = 5;
				curl_setopt($ch, CURLOPT_URL, $clean_amp_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				$data = curl_exec($ch);
				curl_close($ch);
			} else {
				//this fallback is taken from here: https://stackoverflow.com/a/4240241/4420152
				$params = array(
					'header'=>'Connection: close\r\n',
					'ignore_errors' => true
				);

				$context = stream_context_create(array('http' => $params));
				$data = file_get_contents($clean_amp_url, false, $context);
			}
			return $data;
		}
		return "";
	}
}
