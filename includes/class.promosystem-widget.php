<?php

class Promosystem_Widget extends WP_Widget {
    
    function __construct() {
		load_plugin_textdomain( 'promosystem' );
		
		parent::__construct(
			'promosystem_widget',
			__( 'PromoSystem Widget' , 'promosystem'),
			array( 'description' => __( 'Display the promo-system form' , 'promosystem') )
		);

		if ( is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_head', array( $this, 'css' ) );
		}
	}
    
    function css() {
		?>
		<style type="text/css">
			.a-stats {
				width: auto;
				display: block;
				font-weight: normal;
				color: #FFF;
				white-space: nowrap;
			}
		</style>
		<?php
	}
    
    function widget( $args, $instance ) {
		$count = get_option( 'akismet_spam_count' );

		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = 'Проверка кода';
		}

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}
		//http://promo-system.com/ru/code/validater/
		?>
		
			<div class="a-st">
				<form class="a-stats" method="post" action="http://promo-system.com/ru/code/validater" id="ps_check_form">
					<input type="text" id="code" name="code" placeholder="xxx-xxx-xxx"><input type="submit" value="Проверить" id="check_button">
				</form>
			</div>
		<?php
		echo $args['after_widget'];
	}
}
	
	
    function promosystem_register_widgets() {
        register_widget( 'PromoSystem_Widget' );
    }

    add_action( 'widgets_init', 'promosystem_register_widgets' );