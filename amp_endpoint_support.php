<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define( 'AMP_QUERY_VAR', apply_filters( 'amp_query_var', 'amp' ) );
define( 'AMP_PLUGIN', 'amp/amp.php' );
function add_query_vars_filter( $vars ){
  $vars[] = AMP_QUERY_VAR;
  return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

class AmpEndpointSupport {



    static $instance = false;

    private function __construct() {

    }

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    function activate_rewrite_strategy() {
        if(!is_plugin_active(AMP_PLUGIN)) {
            
            add_rewrite_endpoint( 'AMP_QUERY_VAR', EP_PERMALINK );
            add_post_type_support( 'post', AMP_QUERY_VAR );
            echo "YO!";
            $a = $this->check_rewrite_rules();
            var_dump($a);die;
            add_filter( 'request', 'mrf_amp_force_query_var_value' );
            flush_rewrite_rules();
	    }
    }

    function check_rewrite_rules_active() {
        global $wp_rewrite;
        foreach ( $wp_rewrite->endpoints as $index => $endpoint ) {
            return AMP_QUERY_VAR === $endpoint[1];
        }
    }

    function deactivate_rewrite_strategy() {
        global $wp_rewrite;
        echo "YO!";
        var_dump($wp_rewrite->endpoints);
        foreach ( $wp_rewrite->endpoints as $index => $endpoint ) {
            if ( AMP_QUERY_VAR === $endpoint[1] ) {
                unset( $wp_rewrite->endpoints[ $index ] );
                break;
            }
        }
        flush_rewrite_rules();
    }

    function mrf_amp_force_query_var_value( $query_vars ) {
        if ( isset( $query_vars[ AMP_QUERY_VAR ] ) && '' === $query_vars[ AMP_QUERY_VAR ] ) {
            $query_vars[ AMP_QUERY_VAR ] = 1;
        }
        return $query_vars;
    }

    function mrf_is_amp_endpoint() {
        if ( !is_singular() || is_feed() ) {
            return;
        }
        return false !== get_query_var( AMP_QUERY_VAR, false );
    }

    function mrf_post_supports_amp( $post ) {
        if ( ! post_type_supports( $post->post_type, AMP_QUERY_VAR ) ) {
            return false;
        }

        if ( post_password_required( $post ) ) {
            return false;
        }

        return true;
    }
}
