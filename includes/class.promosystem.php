<?php

class Promosystem {
    
    const API_HOST = 'promo-system.com';
	const API_PORT = 80;
    const API_KEY = '';
    
    private static $initiated = false;
    
    public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}
    
    private static function init_hooks() {
		self::$initiated = true;
        
		add_action( 'wp_enqueue_scripts', array( 'Promosystem', 'load_form_js' ) );
		add_action( 'plugins_loaded', array( 'Promosystem', 'inject_ak_js' ) );
		add_action( 'ps_code_check', array( 'Promosystem', 'code_check' ), 10, 1 );
        
        Promosystem::log( 'init_huuks' );
    }
    
    public static function code_check($args){
        $res = self::http_post( self::build_query( $args ), '/ru/code/validater');
        Promosystem::log( print_r( $res, true ) );
    }
    
    public static function build_query( $args ) {
		return _http_build_query( $args, '', '&' );
	}
    
	public static function load_form_js() {
		Promosystem::log('load_form_js');
		wp_register_script( 'promosystem-form', PS_URL . 'assets/js/promosystem-form.js', array(), '0.1', true );
		add_action( 'wp_footer', array( 'Promosystem', 'print_form_js' ) );
		
	}
	
	public static function print_form_js() {
		//Promosystem::load_form_js();
		//wp_print_scripts( 'promosystem-form' );
		wp_enqueue_script('promosystem-form' );
		
	}
    
    public static function inject_ak_js( $fields ) {
		echo '<p style="display: none;">';
		echo '<input type="hidden" id="ak_js" name="ak_js" value="' . mt_rand( 0, 250 ) . '"/>';
		echo '</p>';
	}
    
    public static function plugin_activation() {
        if ( version_compare( $GLOBALS['wp_version'], PS__MINIMUM_WP_VERSION, '<' ) ) {
            load_plugin_textdomain( 'promosystem' );
            $message = '<strong>'.sprintf(esc_html__( 'Promosystem %s requires WordPress %s or higher.' , 'promosystem'), PS_VERSION, PS__MINIMUM_WP_VERSION ).'</strong> ';
            $message .= sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 0.0 ))) of the Promosystem plugin</a>.', 'promosystem'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://wordpress.org/extend/plugins/promosystem/download/');
            Promosystem::bail_on_activation( $message );
        }
	}

	public static function plugin_deactivation( ) {
		return true;
	}
    
    public static function http_post( $request, $path, $ip=null ) {

		$promosystem_ua = sprintf( 'WordPress/%s | Promosystem/%s', $GLOBALS['wp_version'], constant( '0.1' ) );
		$promosystem_ua = apply_filters( 'promosystem_ua', $promosystem_ua );

		$content_length = strlen( $request );

		$api_key   = self::API_KEY;
		$host      = self::API_HOST;

		if ( !empty( $api_key ) )
			$host = $api_key.'.'.$host;

		$http_host = $host;
		
		if ( $ip && long2ip( ip2long( $ip ) ) ) {
			$http_host = $ip;
		}

		$http_args = array(
			'body' => $request,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'Host' => $host,
				'User-Agent' => $promosystem_ua,
			),
			'httpversion' => '1.0',
			'timeout' => 15
		);

		$promosystem_url = $http_promosystem_url = "http://{$http_host}/{$path}";

		/**
		 * Try SSL first; if that fails, try without it and don't try it again for a while.
		 */

		$ssl = $ssl_failed = false;

		// Check if SSL requests were disabled fewer than X hours ago.
		$ssl_disabled = get_option( 'promosystem_ssl_disabled' );

		if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
			$ssl_disabled = false;
			delete_option( 'promosystem_ssl_disabled' );
		}
		else if ( $ssl_disabled ) {
			do_action( 'promosystem_ssl_disabled' );
		}

		if ( ! $ssl_disabled && function_exists( 'wp_http_supports') && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
			$promosystem_url = set_url_scheme( $promosystem_url, 'https' );

			do_action( 'promosystem_https_request_pre' );
		}

		$response = wp_remote_post( $promosystem_url, $http_args );

		Promosystem::log( compact( 'promosystem_url', 'http_args', 'response' ) );

		if ( $ssl && is_wp_error( $response ) ) {
			do_action( 'promosystem_https_request_failure', $response );

			// Intermittent connection problems may cause the first HTTPS
			// request to fail and subsequent HTTP requests to succeed randomly.
			// Retry the HTTPS request once before disabling SSL for a time.
			$response = wp_remote_post( $promosystem_url, $http_args );
			
			Promosystem::log( compact( 'promosystem_url', 'http_args', 'response' ) );

			if ( is_wp_error( $response ) ) {
				$ssl_failed = true;

				do_action( 'promosystem_https_request_failure', $response );

				do_action( 'promosystem_http_request_pre' );

				// Try the request again without SSL.
				$response = wp_remote_post( $http_promosystem_url, $http_args );

				Promosystem::log( compact( 'http_promosystem_url', 'http_args', 'response' ) );
			}
		}

		if ( is_wp_error( $response ) ) {
			do_action( 'promosystem_request_failure', $response );

			return array( '', '' );
		}

		if ( $ssl_failed ) {
			// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
			update_option( 'promosystem_ssl_disabled', time() );
			
			do_action( 'promosystem_https_disabled' );
		}
		
		$simplified_response = array( $response['headers'], $response['body'] );
		
		self::update_alert( $simplified_response );

		return $simplified_response;
	}
    
    private static function update_alert( $response ) {
		$code = $msg = null;
		if ( isset( $response[0]['x-ps-alert-code'] ) ) {
			$code = $response[0]['x-ps-alert-code'];
			$msg  = $response[0]['x-ps-alert-msg'];
		}

		// only call update_option() if the value has changed
		if ( $code != get_option( 'promosystem_alert_code' ) ) {
			if ( ! $code ) {
				delete_option( 'promosystem_alert_code' );
				delete_option( 'promosystem_alert_msg' );
			}
			else {
				update_option( 'promosystem_alert_code', $code );
				update_option( 'promosystem_alert_msg', $msg );
			}
		}
	}
    
    public static function updated_option( $old_value, $value ) {
		if ( ! class_exists( 'WPCOM_JSON_API_Update_Option_Endpoint' ) ) {
			return;
		}
		if ( $old_value !== $value ) {
			// resave key
            //self::verify_key( $value );
		}
	}
    
    public static function log( $promosystem_debug ) {
		if ( apply_filters( 'promosystem_debug_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
			error_log( print_r( compact( 'promosystem_debug' ), true ) );
		}
	}
    
    private static function bail_on_activation( $message, $deactivate = true ) {
        ?>
        <!doctype html>
        <html>
        <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <style>
        * {
            text-align: center;
            margin: 0;
            padding: 0;
            font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
        }
        p {
            margin-top: 1em;
            font-size: 18px;
        }
        </style>
        <body>
        <p><?php echo esc_html( $message ); ?></p>
        </body>
        </html>
        <?php
		if ( $deactivate ) {
			$plugins = get_option( 'active_plugins' );
			$ps = plugin_basename( PS_DIR . 'promosystem.php' );
			$update  = false;
			foreach ( $plugins as $i => $plugin ) {
				if ( $plugin === $ps ) {
					$plugins[$i] = false;
					$update = true;
				}
			}
			if ( $update ) {
				update_option( 'active_plugins', array_filter( $plugins ) );
			}
		}
		exit;
	}
}
