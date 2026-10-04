<?php
/*
Plugin Name:  Smart Performance & Optimizer - toolkitAC
Plugin URI: https://anderson526.github.io/portfolio-profesional/
Description: Optimizaciones de un clic: lazy load de iframes y vídeos, control de scripts innecesarios y limpieza segura de la base de datos. Parte de la suite AnderC Essential.
Version: 1.0.0
Author: Anderson Chila
Author URI: https://anderson526.github.io/portfolio-profesional/
Text Domain: anderc-performance
Domain Path: /languages
Requires at least: 6.0
Requires PHP: 7.4
License: GPL-2.0-or-later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ANDERC_APF_VERSION', '1.0.0' );
define( 'ANDERC_APF_FILE', __FILE__ );
define( 'ANDERC_APF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANDERC_APF_URL', plugin_dir_url( __FILE__ ) );

// Autoloader estilo PSR-4 (compatible con Composer si se añade vendor/).
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'APF_' ) ) {
			return;
		}
		$file = ANDERC_APF_DIR . 'includes/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

if ( file_exists( ANDERC_APF_DIR . 'vendor/autoload.php' ) ) {
	require_once ANDERC_APF_DIR . 'vendor/autoload.php';
}

APF_Plugin::instance();
