<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class MarfeelWordpressHelper
{
    const MARFEEL_OPTIONS = 'marfeel_options';

    static $instance = false;

    private function __construct() {

    }

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    function is_article() {
        return is_single();
    }

    function get_current_uri() {
      global $wp;
      $current_url = home_url($wp->request);
      $permalink = get_permalink();

      if(strcmp(substr($permalink, -1), "/") == 0) {
      	$current_url = trailingslashit($current_url);
      }

      return $current_url;
    }

    function get_option_value($option_name) {
		$options = get_option(MARFEEL_OPTIONS, array());
        return isset($options[$option_name]) ? $options[$option_name] : null;
    }

    function save_option($options_name, $option_value) {
        $options = get_option(MARFEEL_OPTIONS, array());

        $options[$options_name] = $option_value;

        update_option(MARFEEL_OPTIONS, $options);
    }

    function remove_protocol($uri) {
		return str_replace(array('http://', 'https://'), array('', ''), $uri);
    }

    function remove_protocol_and_domain($uri) {
		return parse_url($uri)['path'];
	}

	function crop_amp_endpoint($amp_url) {
		return user_trailingslashit(preg_replace('/\/amp\/?$/', '', $amp_url));
	}

    function delete_all_options() {
        delete_option(MARFEEL_OPTIONS);
	}

	function is_curl_installed(){
		return function_exists('curl_version');
	}
}
