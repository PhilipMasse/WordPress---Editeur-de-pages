<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gere la boite d'edition (metabox) du constructeur dans l'ecran d'edition,
 * le chargement des scripts/styles admin et l'enregistrement des donnees.
 */
class SPB_Metabox {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function register_metabox() {
		foreach ( Simple_Page_Builder::allowed_post_types() as $post_type ) {
			add_meta_box(
				'spb_builder_box',
				__( 'Constructeur de page (glisser-deposer)', 'simple-page-builder' ),
				array( $this, 'render_metabox' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $post;
		if ( ! $post || ! in_array( $post->post_type, Simple_Page_Builder::allowed_post_types(), true ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style( 'spb-admin-builder', SPB_URL . 'assets/css/admin-builder.css', array( 'wp-color-picker' ), SPB_VERSION );

		wp_enqueue_script(
			'spb-admin-builder',
			SPB_URL . 'assets/js/admin-builder.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
			SPB_VERSION,
			true
		);

		$layout_json = get_post_meta( $post->ID, '_spb_layout', true );
		$saved_layout = $layout_json ? SPB_Render::decode_layout( $layout_json ) : array( 'rows' => array() );
		if ( ! is_array( $saved_layout ) ) {
			$saved_layout = array( 'rows' => array() );
		}

		wp_localize_script(
			'spb-admin-builder',
			'spbConfig',
			array(
				'elements'   => SPB_Elements::get_elements(),
				'layouts'    => SPB_Elements::get_layouts(),
				'rowFields'  => SPB_Elements::get_row_fields(),
				'icons'      => SPB_Elements::get_icon_library(),
				'savedLayout'=> is_array( $saved_layout ) ? $saved_layout : array( 'rows' => array() ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'mediaTitle' => __( 'Choisir une image', 'simple-page-builder' ),
				'mediaButton'=> __( 'Utiliser cette image', 'simple-page-builder' ),
				'i18n'       => array(
					'addRow'        => __( 'Ajouter une ligne', 'simple-page-builder' ),
					'chooseLayout'  => __( 'Choisissez une mise en page', 'simple-page-builder' ),
					'addElement'    => __( 'Ajouter un element', 'simple-page-builder' ),
					'chooseElement' => __( 'Choisissez un element', 'simple-page-builder' ),
					'chooseIcon'    => __( 'Choisissez une icone', 'simple-page-builder' ),
					'search'        => __( 'Rechercher...', 'simple-page-builder' ),
					'settings'      => __( 'Reglages', 'simple-page-builder' ),
					'save'          => __( 'Appliquer', 'simple-page-builder' ),
					'cancel'        => __( 'Annuler', 'simple-page-builder' ),
					'delete'        => __( 'Supprimer', 'simple-page-builder' ),
					'duplicate'     => __( 'Dupliquer', 'simple-page-builder' ),
					'rowSettings'   => __( 'Reglages de la ligne', 'simple-page-builder' ),
					'confirmDelete' => __( 'Supprimer cet element ?', 'simple-page-builder' ),
					'confirmDeleteRow' => __( 'Supprimer cette ligne et tout son contenu ?', 'simple-page-builder' ),
					'emptyCanvas'   => __( 'Aucune ligne pour le moment. Cliquez sur "Ajouter une ligne" pour commencer.', 'simple-page-builder' ),
					'emptyColumn'   => __( 'Colonne vide', 'simple-page-builder' ),
					'chooseImage'   => __( 'Choisir une image', 'simple-page-builder' ),
					'linkUrl'       => __( 'URL du lien :', 'simple-page-builder' ),
					'addItem'       => __( 'Ajouter une question', 'simple-page-builder' ),
				),
			)
		);
	}

	public function render_metabox( $post ) {
		wp_nonce_field( 'spb_save_builder', 'spb_builder_nonce' );

		$is_enabled = Simple_Page_Builder::is_builder_enabled( $post );
		?>
		<div class="spb-metabox-wrap">
			<p class="spb-toggle-row">
				<label>
					<input type="checkbox" id="spb_enabled" name="spb_enabled" value="yes" <?php checked( $is_enabled, true ); ?> />
					<strong><?php esc_html_e( "Activer le constructeur visuel pour cette page (remplace l'editeur classique)", 'simple-page-builder' ); ?></strong>
				</label>
			</p>

			<div id="spb-builder-app" class="spb-builder-app">
				<div class="spb-toolbar">
					<button type="button" class="button button-primary" id="spb-add-row-btn">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Ajouter une ligne', 'simple-page-builder' ); ?>
					</button>
				</div>

				<div id="spb-canvas"></div>
			</div>

			<textarea id="spb_builder_data" name="spb_builder_data" style="display:none;"><?php echo esc_textarea( base64_encode( wp_json_encode( array( 'rows' => array() ) ) ) ); ?></textarea>
		</div>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['spb_builder_nonce'] ) || ! wp_verify_nonce( $_POST['spb_builder_nonce'], 'spb_save_builder' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, Simple_Page_Builder::allowed_post_types(), true ) ) {
			return;
		}

		$enabled = isset( $_POST['spb_enabled'] ) && 'yes' === $_POST['spb_enabled'] ? 'yes' : 'no';
		update_post_meta( $post_id, '_spb_enabled', $enabled );

		if ( isset( $_POST['spb_builder_data'] ) ) {
			// Les donnees sont transmises encodees en base64 depuis le JS : cet
			// alphabet (A-Z a-z 0-9 + / =) ne contient aucun caractere que
			// WordPress echappe ou desechappe (guillemets, antislash), ce qui
			// elimine definitivement tout risque de corruption des sauts de
			// ligne ou des guillemets lors du passage par $_POST.
			$decoded = base64_decode( trim( (string) $_POST['spb_builder_data'] ), true );
			$raw = false !== $decoded ? json_decode( $decoded, true ) : null;

			if ( is_array( $raw ) ) {
				$clean = SPB_Render::sanitize_layout( $raw );
				update_post_meta( $post_id, '_spb_layout', SPB_Render::encode_layout( $clean ) );
			}
		}
	}
}
