<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class MarfeelTroy
{
    static $instance = false;

    private function __construct() {

    }

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    public function get_marfeel_domain_for_uri($domain) {
        $marfeel_domain = null;

		if ($this->uri_exists('https://bc.marfeel.com/'.$domain.'/')) {
            $marfeel_domain = 'https://bc.marfeel.com/amp/';
        } else if ($this->uri_exists('https://b.marfeel.com/'.$domain.'/')) {
            $marfeel_domain = 'https://b.marfeel.com/amp/';
        }

        return $marfeel_domain;
    }

    private function uri_exists($uri) {
        if (MarfeelWordpressHelper::getInstance()->is_curl_installed()) {
            $curl = curl_init();
            curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_URL => $uri ));
            curl_exec( $curl );
            $response_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
            curl_close($curl);

            return $response_code == 200;
        } else {
            $headers = get_headers($uri);
            return strpos($headers[0], '200 OK') !== false;
        }
    }

    public function get_settings_page($marfeel_domain) {
        ob_start();

        ?>
        <div class="wrap">
            <object class=header__logo data="https://www.marfeel.com/wp-content/themes/guile/assets/marfeel_logo_rgb.svg" type="image/svg+xml"></object>
            <form method="post" action="options.php">
                <?php settings_fields('marfeel_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Domain</th>
                        <td>
                            <?php if (isset($marfeel_domain)) {
                                    echo $marfeel_domain;
                                } else { ?>
                                <input type="text" name="<?=MARFEEL_OPTIONS?>[marfeel_domain]"/>
                            <?php } ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">AMP</th>
                        <td><?php echo isset($marfeel_domain) ? 'Activated' : 'Deactivated'; ?></td>
                    </tr>
                </table>

                <?php if (!isset($marfeel_domain)) { ?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                </p>
                <?php } ?>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function get_amp_uri() {
        $is_article = MarfeelWordpressHelper::getInstance()->is_article();
        if ($is_article) {
            $current_uri = MarfeelWordpressHelper::getInstance()->get_current_uri();
            $marfeel_domain = MarfeelWordpressHelper::getInstance()->get_option_value('marfeel_domain');

            if (strpos($marfeel_domain, 'amp.') !== false) {
                $current_uri = MarfeelWordpressHelper::getInstance()->remove_protocol_and_domain($current_uri);
            } else {
                $current_uri = MarfeelWordpressHelper::getInstance()->remove_protocol($current_uri);
            }
        }

        if(isset($current_uri) && isset($marfeel_domain)) {
            return $marfeel_domain.$current_uri;
        }

        return "";
	}

    public function get_amp_link_for_uri() {
		$current_uri = MarfeelWordpressHelper::getInstance()->get_current_uri();
		$current_uri = rtrim($current_uri, '/') . '/';
		$amp_end_point = user_trailingslashit('amp', '');

        if(isset($current_uri)) {
          return '<link rel="amphtml" href="'.$current_uri.$amp_end_point.'">';
        }
        return '';
    }
}
