<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lazy load de iframes y vídeos: sustituye `src` por `data-src` en `the_content`
 * y los carga con IntersectionObserver cuando entran en pantalla.
 */
class APF_Lazy_Load {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'filter_content' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	public function enqueue_script() {
		$s = APF_Plugin::get_settings();
		if ( empty( $s['lazy_iframes'] ) && empty( $s['lazy_videos'] ) ) {
			return;
		}
		wp_enqueue_script( 'anderc-apf-lazyload', ANDERC_APF_URL . 'assets/js/lazyload.js', array(), ANDERC_APF_VERSION, true );
	}

	public function filter_content( $content ) {
		if ( is_admin() || is_feed() || is_preview() || wp_doing_ajax() ) {
			return $content;
		}

		$s = APF_Plugin::get_settings();

		if ( ! empty( $s['lazy_iframes'] ) ) {
			$content = preg_replace_callback(
				'/<iframe\b[^>]*>/i',
				array( $this, 'lazy_iframe_tag' ),
				$content
			);
		}

		if ( ! empty( $s['lazy_videos'] ) ) {
			$content = preg_replace_callback(
				'/<video\b[^>]*>/i',
				array( $this, 'lazy_video_tag' ),
				$content
			);
		}

		return $content;
	}

	private function lazy_iframe_tag( $matches ) {
		$tag = $matches[0];

		if ( false !== strpos( $tag, 'data-anderc-src' ) ) {
			return $tag;
		}

		$lazy = preg_replace( '/\ssrc=/i', ' data-anderc-src=', $tag, 1 );

		if ( false === strpos( $lazy, 'loading=' ) ) {
			$lazy = str_replace( '<iframe', '<iframe loading="lazy"', $lazy );
		}

		return $lazy;
	}

	private function lazy_video_tag( $matches ) {
		$tag = $matches[0];

		if ( false !== strpos( $tag, 'preload=' ) ) {
			return preg_replace( '/preload=("|\')[^"\']*("|\')/i', 'preload="none"', $tag );
		}

		return str_replace( '<video', '<video preload="none"', $tag );
	}
}
