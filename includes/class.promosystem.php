<?php

class Promosystem {
    
    const API_HOST = 'promo-system.com';
	const API_PORT = 81;
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
		add_action( 'wp_ajax_ps_code_check', array( 'Promosystem', 'code_check' ) );
		add_action( 'wp_ajax_nopriv_ps_code_check', array( 'Promosystem', 'code_check' ) );
		add_action( 'ps_request_failure', array( 'Promosystem', 'request_failure'));

    }
    
    public static function code_check(){
		unset( $_POST['action']);
		$body = array(
			'code'	=>	$_POST['code'],
			'phone'	=> $_POST['ps_phone'],
			'name'	=> $_POST['ps_name'],
			'lang'	=>	'ru',
			'token'	=> 'Jgq2vSca1dKOuv4OYbv4u2mZBdsbRU4VyPAwOc3DIvl7iMhq11jepijAGs9zxgxLsoP1bieEjfODLCP2fuVxxtBAgfqqq9aLLQEV5kEVgtKwo1qUbYveVLww1847ciy3'
		);
       // $res = Promosystem::http_post(  $body , 'ru/api/ps/code');
		$res = Promosystem::curl( 'ru/api/ps/code', $body);
		$my_res = json_encode(json_decode( $res, true )) ;
		//Promosystem::log( $res, ' возврат апи: ');
		echo $res;
		exit;
		
    }
	
    public static function curl($url, $fields = array()) {
		$url = 'http://' . self::API_HOST . "/" . $url;
		header('Content-type: text/plain; charset=utf-8');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		
		$info = curl_getinfo($ch);
		
		curl_close($ch);
		
		Promosystem::log( compact('result','info'), 'curl: ');

		return $result;
	}
	
    public static function build_query( $args ) {
		return _http_build_query( $args, '', '&' );
	}
    
	public static function load_form_js() {
		wp_register_script( 'promosystem-form', PS_URL . 'assets/js/promosystem-form.js', array(), '0.1', true );
		add_action( 'wp_footer', array( 'Promosystem', 'print_form_js' ) );
		
	}
	
	public static function print_form_js() {
		
		wp_enqueue_script('promosystem-form' );
		wp_localize_script( 'promosystem-form', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
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

		$promosystem_ua = sprintf( 'WordPress/%s | Promosystem/%s', $GLOBALS['wp_version'], PS_VERSION );
		$promosystem_ua = apply_filters( 'promosystem_ua', $promosystem_ua );
		//$content_length = strlen( $request );
		$api_key   = self::API_KEY;
		$host      = self::API_HOST;

		if ( $ip && long2ip( ip2long( $ip ) ) ) { $host = $ip; }

		$http_args = array(
			'body'		=>	$request,
			'headers'	=>	array(
				'Content-Type'	=>	'text/plain; charset=' . get_option( 'blog_charset' ),
				'Host'	=>	$host,
				'User-Agent'	=>	$promosystem_ua,
			),
			'httpversion'	=>	'1.0',
			'timeout'	=>	15,
			'method'	=>	'POST',
		);

		$promosystem_url = "http://{$host}/{$path}";

		$response = wp_remote_post( $promosystem_url, $http_args );

		Promosystem::log( $http_args , ' возврат апи: ');

		//if ( is_wp_error( $response ) ) {
			//do_action( 'ps_request_failure', $response );

		//}

		$response = $response['body'];
		
//		Promosystem::update_alert( $simplified_response );

		return $response;
	}
    
	public static function request_failure ( $response ){
		$error_message = $response->get_error_message();
		Promosystem::log( "Что-то пошло не так: $error_message", 'после возврата ' );	
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
    
    public static function log( $promosystem_debug , $caption = 'Debug : ') {
		if ( apply_filters( 'promosystem_debug_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
			error_log( $caption . "  " . print_r( compact( 'promosystem_debug' ), true ) );
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
