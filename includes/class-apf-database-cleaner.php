<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limpieza segura de la base de datos con $wpdb según la selección del usuario.
 */
class APF_Database_Cleaner {

	/**
	 * Tareas disponibles con etiqueta y descripción.
	 */
	public static function tasks() {
		return array(
			'revisions'          => __( 'Revisiones de entradas', 'anderc-performance' ),
			'auto_drafts'        => __( 'Borradores automáticos', 'anderc-performance' ),
			'trashed_posts'      => __( 'Entradas en la papelera', 'anderc-performance' ),
			'spam_comments'      => __( 'Comentarios marcados como spam', 'anderc-performance' ),
			'trashed_comments'   => __( 'Comentarios en la papelera', 'anderc-performance' ),
			'expired_transients' => __( 'Transients caducados', 'anderc-performance' ),
		);
	}

	/**
	 * Devuelve el número de elementos pendientes de limpiar por tarea.
	 */
	public static function get_counts() {
		global $wpdb;

		return array(
			'revisions'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ), // phpcs:ignore WordPress.DB
			'auto_drafts'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ), // phpcs:ignore WordPress.DB
			'trashed_posts'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ), // phpcs:ignore WordPress.DB
			'spam_comments'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ), // phpcs:ignore WordPress.DB
			'trashed_comments'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ), // phpcs:ignore WordPress.DB
			'expired_transients' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ), // phpcs:ignore WordPress.DB
		);
	}

	/**
	 * Ejecuta las tareas seleccionadas y devuelve cuántas filas eliminó cada una.
	 *
	 * @param string[] $tasks Claves de tareas a ejecutar.
	 * @return array<string,int>
	 */
	public static function clean( array $tasks ) {
		$results = array();

		foreach ( $tasks as $task ) {
			switch ( $task ) {
				case 'revisions':
					$results[ $task ] = self::delete_posts_where( "post_type = 'revision'" );
					break;
				case 'auto_drafts':
					$results[ $task ] = self::delete_posts_where( "post_status = 'auto-draft'" );
					break;
				case 'trashed_posts':
					$results[ $task ] = self::delete_posts_where( "post_status = 'trash'" );
					break;
				case 'spam_comments':
					$results[ $task ] = self::delete_comments_where( "comment_approved = 'spam'" );
					break;
				case 'trashed_comments':
					$results[ $task ] = self::delete_comments_where( "comment_approved = 'trash'" );
					break;
				case 'expired_transients':
					$results[ $task ] = self::delete_expired_transients();
					break;
			}
		}

		return $results;
	}

	/**
	 * Borra entradas con wp_delete_post para limpiar también metadatos y términos.
	 */
	private static function delete_posts_where( $where ) {
		global $wpdb;

		// Lote limitado para evitar timeouts en sitios grandes; repetir la limpieza si es necesario.
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE {$where} LIMIT 500" ); // phpcs:ignore WordPress.DB

		$deleted = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}

	private static function delete_comments_where( $where ) {
		global $wpdb;

		$ids = $wpdb->get_col( "SELECT comment_ID FROM {$wpdb->comments} WHERE {$where} LIMIT 500" ); // phpcs:ignore WordPress.DB

		$deleted = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_comment( (int) $id, true ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}

	private static function delete_expired_transients() {
		global $wpdb;

		$names = $wpdb->get_col( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT 1000",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		$deleted = 0;
		foreach ( $names as $name ) {
			$transient = str_replace( '_transient_timeout_', '', $name );
			if ( delete_transient( $transient ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}
}
