<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Núcleo del plugin: carga módulos y centraliza los ajustes.
 */
final class APF_Plugin {

	const OPTION = 'anderc_apf_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		new APF_Lazy_Load();
		new APF_Script_Manager();

		if ( is_admin() ) {
			new APF_Admin();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'anderc-performance', false, dirname( plugin_basename( ANDERC_APF_FILE ) ) . '/languages' );
	}

	public static function defaults() {
		return array(
			'lazy_iframes'         => 1,
			'lazy_videos'          => 1,
			'disable_emojis'       => 0,
			'disable_embeds'       => 0,
			'remove_jquery_migrate' => 0,
			'remove_query_strings' => 0,
		);
	}

	public static function get_settings() {
		$settings = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
	}

	public static function update_settings( array $settings ) {
		update_option( self::OPTION, $settings );
	}
}
