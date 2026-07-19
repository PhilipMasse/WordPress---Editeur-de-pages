<?php
/**
 * Plugin Name: Simple Page Builder
 * Plugin URI: https://berrelesalpes.fr
 * Description: Editeur de page visuel par glisser-deposer (lignes / colonnes / elements), simple d'utilisation, dans l'esprit de WPBakery Page Builder.
 * Version: 1.4.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Mairie de Berre-les-Alpes
 * Text Domain: simple-page-builder
 * Update URI: https://github.com/PhilipMasse/WordPress---Editeur-de-pages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acces direct interdit.
}

define( 'SPB_VERSION', '1.4.1' );
define( 'SPB_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPB_URL', plugin_dir_url( __FILE__ ) );

require_once SPB_PATH . 'includes/class-spb-elements.php';
require_once SPB_PATH . 'includes/class-spb-render.php';
require_once SPB_PATH . 'includes/class-spb-metabox.php';

/**
 * Mises a jour automatiques depuis un depot GitHub (bibliotheque
 * "Plugin Update Checker" par Yahnis Elsts, incluse dans /lib/).
 * Une fois le plugin publie sur GitHub, WordPress verifiera et proposera
 * les mises a jour dans Extensions > Extensions installees, exactement
 * comme pour un plugin du repertoire officiel.
 *
 * A FAIRE : remplacer l'URL ci-dessous par celle de votre depot GitHub.
 */
if ( file_exists( SPB_PATH . 'lib/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once SPB_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

	$spb_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/PhilipMasse/WordPress---Editeur-de-pages/',
		__FILE__,
		'simple-page-builder'
	);

	// Decommenter et renseigner un jeton d'acces personnel GitHub si le
	// depot est PRIVE (Settings > Developer settings > Personal access
	// tokens sur GitHub, droit "repo" en lecture suffit) :
	// $spb_update_checker->setAuthentication( 'ghp_votre_jeton_ici' );
}

register_activation_hook( __FILE__, array( 'Simple_Page_Builder', 'on_activation' ) );

/**
 * Classe principale : demarrage du plugin.
 */
final class Simple_Page_Builder {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Metabox admin (edition).
		SPB_Metabox::instance();

		// Rendu frontend.
		add_filter( 'the_content', array( $this, 'maybe_render_builder_content' ), 20 );

		// Feuille de style frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Remplace l'editeur Gutenberg par le constructeur visuel, uniquement
		// pour les articles/pages qui l'utilisent (voir is_builder_enabled()).
		add_filter( 'use_block_editor_for_post', array( $this, 'maybe_disable_block_editor' ), 10, 2 );

		// Message d'information affiche une seule fois apres l'activation.
		add_action( 'admin_notices', array( $this, 'maybe_show_activation_notice' ) );
	}

	/**
	 * Execute a l'activation du plugin. Ne modifie aucun contenu existant :
	 * enregistre simplement un indicateur pour afficher un message
	 * d'information a l'administrateur, et un horodatage pour distinguer
	 * les pages creees avant/apres l'activation si besoin plus tard.
	 */
	public static function on_activation() {
		add_option( 'spb_show_activation_notice', 'yes' );
		add_option( 'spb_activated_at', time() );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'simple-page-builder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Types de contenus autorises a utiliser le constructeur.
	 */
	public static function allowed_post_types() {
		return apply_filters( 'spb_allowed_post_types', array( 'page', 'post', 'actualite', 'agenda' ) );
	}

	/**
	 * Determine si le constructeur visuel doit etre considere comme actif
	 * pour un article/une page donne :
	 * - explicitement active via la case a cocher (meta '_spb_enabled' = 'yes') ;
	 * - OU nouvelle page/article jamais encore enregistre (brouillon
	 *   automatique), auquel cas le constructeur est actif par defaut.
	 * Les pages existantes qui ont deja du contenu et n'ont jamais ete
	 * enregistrees avec le constructeur ne sont JAMAIS basculees
	 * automatiquement : leur contenu classique reste affiche tel quel.
	 */
	public static function is_builder_enabled( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( ! in_array( $post->post_type, self::allowed_post_types(), true ) ) {
			return false;
		}

		$meta = get_post_meta( $post->ID, '_spb_enabled', true );

		if ( 'yes' === $meta ) {
			return true;
		}

		if ( '' === $meta && 'auto-draft' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Desactive l'editeur de blocs (Gutenberg) au profit du constructeur
	 * visuel, uniquement pour les articles/pages ou celui-ci est actif.
	 * Les autres contenus du site conservent Gutenberg sans aucun changement.
	 */
	public function maybe_disable_block_editor( $use_block_editor, $post ) {
		if ( self::is_builder_enabled( $post ) ) {
			return false;
		}
		return $use_block_editor;
	}

	public function maybe_show_activation_notice() {
		if ( 'yes' !== get_option( 'spb_show_activation_notice' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		delete_option( 'spb_show_activation_notice' );

		echo '<div class="notice notice-success is-dismissible"><p>';
		esc_html_e( 'Simple Page Builder est active.', 'simple-page-builder' );
		echo ' ';
		esc_html_e( 'Le constructeur visuel remplace desormais automatiquement l\'editeur Gutenberg sur toute nouvelle page ou article. Vos pages existantes ne sont pas modifiees : elles continuent d\'utiliser leur editeur habituel jusqu\'a ce que vous cochiez vous-meme "Activer le constructeur visuel" sur celles que vous souhaitez convertir.', 'simple-page-builder' );
		echo '</p></div>';
	}

	/**
	 * Remplace le contenu par le rendu du constructeur si celui-ci est active
	 * pour l'article/la page en cours (uniquement sur la boucle principale).
	 */
	public function maybe_render_builder_content( $content ) {
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( ! $post_id || 'yes' !== get_post_meta( $post_id, '_spb_enabled', true ) ) {
			return $content;
		}

		$layout_json = get_post_meta( $post_id, '_spb_layout', true );

		if ( empty( $layout_json ) ) {
			return $content;
		}

		$layout = SPB_Render::decode_layout( $layout_json );

		if ( ! is_array( $layout ) ) {
			return $content;
		}

		return SPB_Render::render_layout( $layout );
	}

	public function enqueue_frontend_assets() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( $post_id && 'yes' === get_post_meta( $post_id, '_spb_enabled', true ) ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'spb-frontend', SPB_URL . 'assets/css/frontend.css', array(), SPB_VERSION );
		}
	}
}

Simple_Page_Builder::instance();
