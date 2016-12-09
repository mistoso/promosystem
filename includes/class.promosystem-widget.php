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
				color: inherit;
			}
			.a-stats .check_result, a-stats .check_loading {
				display: none;
			}
			.a-st {max-width: 200px;}
		</style>
		<?php
	}
    
    function widget( $args, $instance ) {

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
				<form class="a-stats" method="post" action="" id="ps_check_form">
				    <div class="check_result"></div>
					<input type="hidden" name="action" value="ps_code_check">
					<input type="text" id="code" name="code" placeholder="код">
					<input type="text" id="ps_phone" name="ps_phone" placeholder="тел">
					<input type="text" id="ps_name" name="ps_name" placeholder="имя" >
					<input type="submit" value="Проверить" id="check_button">
					<div class="check_loading" style="display:none;">Зачекайте</div>
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
