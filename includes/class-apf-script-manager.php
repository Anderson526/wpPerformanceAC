<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Control de scripts: emojis, oEmbed, jQuery Migrate y cadenas de versión.
 */
class APF_Script_Manager {

	public function __construct() {
		$s = APF_Plugin::get_settings();

		if ( ! empty( $s['disable_emojis'] ) ) {
			add_action( 'init', array( $this, 'disable_emojis' ) );
		}
		if ( ! empty( $s['disable_embeds'] ) ) {
			add_action( 'init', array( $this, 'disable_embeds' ), 9999 );
		}
		if ( ! empty( $s['remove_jquery_migrate'] ) ) {
			add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
		}
		if ( ! empty( $s['remove_query_strings'] ) && ! is_admin() ) {
			add_filter( 'script_loader_src', array( $this, 'remove_version_query' ), 15 );
			add_filter( 'style_loader_src', array( $this, 'remove_version_query' ), 15 );
		}
	}

	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'remove_tinymce_emoji' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_dns_prefetch' ), 10, 2 );
	}

	public function remove_tinymce_emoji( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

	public function remove_emoji_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$urls = array_filter(
				$urls,
				function ( $url ) {
					return false === strpos( (string) $url, 'https://s.w.org/images/core/emoji/' );
				}
			);
		}
		return $urls;
	}

	public function disable_embeds() {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter(
			'wp_default_scripts',
			function ( $scripts ) {
				if ( ! empty( $scripts->registered['wp-embed'] ) ) {
					$scripts->registered['wp-embed']->deps = array();
				}
			}
		);
	}

	public function remove_jquery_migrate( $scripts ) {
		if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$script = $scripts->registered['jquery'];
		if ( ! empty( $script->deps ) ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}

	public function remove_version_query( $src ) {
		if ( $src && false !== strpos( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}
}
