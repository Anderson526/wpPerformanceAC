<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Página de administración: optimizaciones de un clic y limpieza de base de datos.
 */
class APF_Admin {

	const SLUG = 'anderc-performance';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_anderc_apf_save', array( $this, 'save' ) );
		add_action( 'admin_post_anderc_apf_clean', array( $this, 'clean' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'AC Performance', 'anderc-performance' ),
			__( 'AC Performance', 'anderc-performance' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-performance',
			64
		);
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'anderc-apf-admin', ANDERC_APF_URL . 'assets/css/admin.css', array(), ANDERC_APF_VERSION );
	}

	public function save() {
		$this->guard( 'anderc_apf_save' );

		$settings = APF_Plugin::get_settings();
		foreach ( array_keys( APF_Plugin::defaults() ) as $key ) {
			$settings[ $key ] = empty( $_POST[ $key ] ) ? 0 : 1;
		}
		APF_Plugin::update_settings( $settings );

		$this->redirect( array( 'msg' => 'saved' ) );
	}

	public function clean() {
		$this->guard( 'anderc_apf_clean' );

		$allowed = array_keys( APF_Database_Cleaner::tasks() );
		$tasks   = isset( $_POST['tasks'] ) ? array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['tasks'] ) ), $allowed ) : array();

		if ( empty( $tasks ) ) {
			$this->redirect( array( 'msg' => 'no_tasks' ) );
		}

		$results = APF_Database_Cleaner::clean( $tasks );
		$total   = array_sum( $results );

		$this->redirect(
			array(
				'msg'     => 'cleaned',
				'cleaned' => $total,
			)
		);
	}

	private function guard( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'anderc-performance' ) );
		}
		check_admin_referer( $nonce_action );
	}

	private function redirect( array $args ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = APF_Plugin::get_settings();
		$counts = APF_Database_Cleaner::get_counts();
		?>
		<div class="wrap anderc-wrap">
			<h1><span class="anderc-badge">AC</span> <?php esc_html_e( 'Smart Performance & Optimizer', 'anderc-performance' ); ?></h1>

			<?php $this->notices(); ?>

			<div class="anderc-columns">
				<div class="anderc-col">
					<h2><?php esc_html_e( 'Optimizaciones automáticas', 'anderc-performance' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="anderc-card">
						<input type="hidden" name="action" value="anderc_apf_save" />
						<?php wp_nonce_field( 'anderc_apf_save' ); ?>

						<label class="anderc-toggle"><input type="checkbox" name="lazy_iframes" value="1" <?php checked( $s['lazy_iframes'] ); ?> />
							<strong><?php esc_html_e( 'Lazy load de iframes', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Carga vídeos de YouTube, mapas y otros iframes solo cuando aparecen en pantalla.', 'anderc-performance' ); ?></span></label>

						<label class="anderc-toggle"><input type="checkbox" name="lazy_videos" value="1" <?php checked( $s['lazy_videos'] ); ?> />
							<strong><?php esc_html_e( 'Lazy load de vídeos', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Evita la precarga de vídeos HTML5 (preload="none").', 'anderc-performance' ); ?></span></label>

						<label class="anderc-toggle"><input type="checkbox" name="disable_emojis" value="1" <?php checked( $s['disable_emojis'] ); ?> />
							<strong><?php esc_html_e( 'Desactivar emojis de WordPress', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Elimina el script de emojis que se carga en todas las páginas.', 'anderc-performance' ); ?></span></label>

						<label class="anderc-toggle"><input type="checkbox" name="disable_embeds" value="1" <?php checked( $s['disable_embeds'] ); ?> />
							<strong><?php esc_html_e( 'Desactivar oEmbed', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Elimina el script wp-embed y los enlaces de descubrimiento.', 'anderc-performance' ); ?></span></label>

						<label class="anderc-toggle"><input type="checkbox" name="remove_jquery_migrate" value="1" <?php checked( $s['remove_jquery_migrate'] ); ?> />
							<strong><?php esc_html_e( 'Quitar jQuery Migrate', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Solo en el frontend. Actívalo si tu tema no depende de código jQuery antiguo.', 'anderc-performance' ); ?></span></label>

						<label class="anderc-toggle"><input type="checkbox" name="remove_query_strings" value="1" <?php checked( $s['remove_query_strings'] ); ?> />
							<strong><?php esc_html_e( 'Quitar cadenas de versión (?ver=)', 'anderc-performance' ); ?></strong>
							<span><?php esc_html_e( 'Mejora la caché de CSS y JS en algunos proxies y CDNs.', 'anderc-performance' ); ?></span></label>

						<?php submit_button( __( 'Guardar cambios', 'anderc-performance' ) ); ?>
					</form>
				</div>

				<div class="anderc-col">
					<h2><?php esc_html_e( 'Limpieza de base de datos', 'anderc-performance' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="anderc-card">
						<input type="hidden" name="action" value="anderc_apf_clean" />
						<?php wp_nonce_field( 'anderc_apf_clean' ); ?>

						<p class="description"><?php esc_html_e( 'Se procesan hasta 500 elementos por tarea en cada ejecución. Haz una copia de seguridad antes de limpiar.', 'anderc-performance' ); ?></p>

						<table class="widefat striped">
							<thead><tr><th></th><th><?php esc_html_e( 'Elemento', 'anderc-performance' ); ?></th><th><?php esc_html_e( 'Pendientes', 'anderc-performance' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( APF_Database_Cleaner::tasks() as $key => $label ) : ?>
								<tr>
									<td><input type="checkbox" name="tasks[]" id="task-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" /></td>
									<td><label for="task-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></td>
									<td><span class="anderc-count <?php echo $counts[ $key ] > 0 ? 'anderc-count--warn' : ''; ?>"><?php echo (int) $counts[ $key ]; ?></span></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>

						<?php submit_button( __( 'Limpiar seleccionados', 'anderc-performance' ), 'delete' ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	private function notices() {
		// phpcs:disable WordPress.Security.NonceVerification
		$msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';

		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ajustes guardados correctamente.', 'anderc-performance' ) . '</p></div>';
		} elseif ( 'no_tasks' === $msg ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'No seleccionaste ninguna tarea de limpieza.', 'anderc-performance' ) . '</p></div>';
		} elseif ( 'cleaned' === $msg ) {
			$cleaned = isset( $_GET['cleaned'] ) ? absint( $_GET['cleaned'] ) : 0;
			/* translators: %d: número de elementos eliminados. */
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'Limpieza completada: %d elementos eliminados.', 'anderc-performance' ), $cleaned ) ) . '</p></div>';
		}
		// phpcs:enable
	}
}
