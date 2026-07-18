<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registre central des elements disponibles dans le constructeur,
 * des mises en page de ligne, et des reglages de ligne.
 * Cette classe est la source unique de verite : elle alimente a la fois
 * le formulaire JS (via wp_localize_script) et la sanitisation/rendu PHP.
 */
class SPB_Elements {

	/**
	 * Mises en page de ligne disponibles : cle => tableau de largeurs (sur 12).
	 */
	public static function get_layouts() {
		return array(
			'12'      => array( 12 ),
			'6-6'     => array( 6, 6 ),
			'4-4-4'   => array( 4, 4, 4 ),
			'3-3-3-3' => array( 3, 3, 3, 3 ),
			'4-8'     => array( 4, 8 ),
			'8-4'     => array( 8, 4 ),
			'3-9'     => array( 3, 9 ),
			'9-3'     => array( 9, 3 ),
		);
	}

	/**
	 * Champs de reglages disponibles pour une ligne.
	 */
	public static function get_row_fields() {
		return array(
			'bg_color'       => array(
				'type'    => 'color',
				'label'   => __( 'Couleur de fond', 'simple-page-builder' ),
				'default' => '',
			),
			'padding_top'    => array(
				'type'    => 'number',
				'label'   => __( 'Espace au-dessus (px)', 'simple-page-builder' ),
				'default' => 40,
			),
			'padding_bottom' => array(
				'type'    => 'number',
				'label'   => __( 'Espace en-dessous (px)', 'simple-page-builder' ),
				'default' => 40,
			),
			'full_width'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Pleine largeur (bord a bord)', 'simple-page-builder' ),
				'default' => false,
			),
			'custom_class'   => array(
				'type'    => 'text',
				'label'   => __( 'Classe CSS personnalisee', 'simple-page-builder' ),
				'default' => '',
			),
		);
	}

	/**
	 * Bibliotheque d'icones (classes dashicons natives de WordPress, deja
	 * disponibles sans dependance supplementaire). Cle = classe CSS,
	 * valeur = libelle affiche dans le selecteur.
	 */
	public static function get_icon_library() {
		return array(
			'dashicons-admin-home'          => __( 'Maison', 'simple-page-builder' ),
			'dashicons-building'            => __( 'Batiment', 'simple-page-builder' ),
			'dashicons-store'               => __( 'Commerce', 'simple-page-builder' ),
			'dashicons-bank'                => __( 'Banque / institution', 'simple-page-builder' ),
			'dashicons-admin-site'          => __( 'Site / commune', 'simple-page-builder' ),
			'dashicons-location'            => __( 'Localisation', 'simple-page-builder' ),
			'dashicons-location-alt'        => __( 'Localisation (alt.)', 'simple-page-builder' ),
			'dashicons-admin-users'         => __( 'Utilisateur', 'simple-page-builder' ),
			'dashicons-groups'              => __( 'Groupe / habitants', 'simple-page-builder' ),
			'dashicons-businessman'         => __( 'Personne', 'simple-page-builder' ),
			'dashicons-id'                  => __( "Carte d'identite", 'simple-page-builder' ),
			'dashicons-id-alt'              => __( 'Carte (alt.)', 'simple-page-builder' ),
			'dashicons-phone'               => __( 'Telephone', 'simple-page-builder' ),
			'dashicons-email'               => __( 'Email', 'simple-page-builder' ),
			'dashicons-email-alt'           => __( 'Email (alt.)', 'simple-page-builder' ),
			'dashicons-share'               => __( 'Partager', 'simple-page-builder' ),
			'dashicons-rss'                 => __( 'Flux RSS', 'simple-page-builder' ),
			'dashicons-facebook'            => __( 'Facebook', 'simple-page-builder' ),
			'dashicons-twitter'             => __( 'Twitter / X', 'simple-page-builder' ),
			'dashicons-calendar-alt'        => __( 'Calendrier', 'simple-page-builder' ),
			'dashicons-clock'               => __( 'Horloge / horaires', 'simple-page-builder' ),
			'dashicons-schedule'            => __( 'Planning', 'simple-page-builder' ),
			'dashicons-megaphone'           => __( 'Annonce', 'simple-page-builder' ),
			'dashicons-flag'                => __( 'Drapeau', 'simple-page-builder' ),
			'dashicons-star-filled'         => __( 'Etoile', 'simple-page-builder' ),
			'dashicons-heart'               => __( 'Coeur', 'simple-page-builder' ),
			'dashicons-yes-alt'             => __( 'Validation', 'simple-page-builder' ),
			'dashicons-info'                => __( 'Information', 'simple-page-builder' ),
			'dashicons-warning'             => __( 'Alerte', 'simple-page-builder' ),
			'dashicons-lightbulb'           => __( 'Idee', 'simple-page-builder' ),
			'dashicons-book'                => __( 'Livre / reglement', 'simple-page-builder' ),
			'dashicons-media-document'      => __( 'Document', 'simple-page-builder' ),
			'dashicons-clipboard'           => __( 'Formulaire', 'simple-page-builder' ),
			'dashicons-forms'               => __( 'Formulaire (alt.)', 'simple-page-builder' ),
			'dashicons-portfolio'           => __( 'Dossier', 'simple-page-builder' ),
			'dashicons-download'            => __( 'Telechargement', 'simple-page-builder' ),
			'dashicons-upload'              => __( 'Depot de fichier', 'simple-page-builder' ),
			'dashicons-search'              => __( 'Recherche', 'simple-page-builder' ),
			'dashicons-visibility'          => __( 'Voir', 'simple-page-builder' ),
			'dashicons-admin-tools'         => __( 'Outils / travaux', 'simple-page-builder' ),
			'dashicons-hammer'              => __( 'Travaux', 'simple-page-builder' ),
			'dashicons-art'                 => __( 'Culture', 'simple-page-builder' ),
			'dashicons-palmtree'            => __( 'Environnement', 'simple-page-builder' ),
			'dashicons-carrot'              => __( 'Agriculture', 'simple-page-builder' ),
			'dashicons-food'                => __( 'Restauration', 'simple-page-builder' ),
			'dashicons-car'                 => __( 'Transport', 'simple-page-builder' ),
			'dashicons-airplane'            => __( 'Voyage', 'simple-page-builder' ),
			'dashicons-camera'              => __( 'Photo', 'simple-page-builder' ),
			'dashicons-video-alt3'          => __( 'Video', 'simple-page-builder' ),
			'dashicons-microphone'          => __( 'Micro / reunion', 'simple-page-builder' ),
			'dashicons-tickets-alt'         => __( 'Billetterie / evenement', 'simple-page-builder' ),
			'dashicons-awards'              => __( 'Recompense', 'simple-page-builder' ),
			'dashicons-money-alt'           => __( 'Finances', 'simple-page-builder' ),
			'dashicons-chart-bar'           => __( 'Statistiques', 'simple-page-builder' ),
			'dashicons-shield'              => __( 'Securite', 'simple-page-builder' ),
			'dashicons-sos'                 => __( 'Urgence', 'simple-page-builder' ),
			'dashicons-universal-access'    => __( 'Accessibilite', 'simple-page-builder' ),
			'dashicons-smiley'              => __( 'Sourire / satisfaction', 'simple-page-builder' ),
			'dashicons-thumbs-up'           => __( 'Pouce leve', 'simple-page-builder' ),
			'dashicons-plus-alt2'           => __( 'Plus', 'simple-page-builder' ),
			'dashicons-arrow-right-alt'     => __( 'Fleche droite', 'simple-page-builder' ),
			'dashicons-arrow-right-alt2'    => __( 'Fleche droite (fine)', 'simple-page-builder' ),
			'dashicons-arrow-left-alt'      => __( 'Fleche gauche', 'simple-page-builder' ),
			'dashicons-arrow-up-alt'        => __( 'Fleche haut', 'simple-page-builder' ),
			'dashicons-arrow-down-alt'      => __( 'Fleche bas', 'simple-page-builder' ),
			'dashicons-external'            => __( 'Lien externe', 'simple-page-builder' ),
			'dashicons-admin-generic'       => __( 'Reglages', 'simple-page-builder' ),
			'dashicons-admin-links'         => __( 'Lien', 'simple-page-builder' ),
			'dashicons-networking'          => __( 'Reseau', 'simple-page-builder' ),
			'dashicons-nametag'             => __( 'Etiquette', 'simple-page-builder' ),
			'dashicons-tag'                 => __( 'Tag', 'simple-page-builder' ),
			'dashicons-vault'               => __( 'Coffre / archives', 'simple-page-builder' ),
			'dashicons-backup'              => __( 'Sauvegarde', 'simple-page-builder' ),
			'dashicons-desktop'             => __( 'Ordinateur', 'simple-page-builder' ),
			'dashicons-smartphone'          => __( 'Mobile', 'simple-page-builder' ),
		);
	}

	/**
	 * Liste des pages/articles publies du site, mise en cache pour la duree
	 * de la requete afin d'eviter de repeter la requete SQL a chaque appel
	 * de get_elements() (celle-ci est appelee plusieurs fois par page).
	 */
	private static $page_options_cache = null;

	public static function get_page_options() {
		if ( null !== self::$page_options_cache ) {
			return self::$page_options_cache;
		}

		$options = array( '0' => __( '— Choisir une page —', 'simple-page-builder' ) );

		if ( function_exists( 'get_posts' ) ) {
			$items = get_posts(
				array(
					'post_type'      => array( 'page', 'post' ),
					'post_status'    => 'publish',
					'numberposts'    => 300,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'suppress_filters' => false,
				)
			);

			foreach ( $items as $item ) {
				$prefix = 'post' === $item->post_type ? __( 'Article', 'simple-page-builder' ) . ' — ' : '';
				$options[ (string) $item->ID ] = $prefix . $item->post_title;
			}
		}

		self::$page_options_cache = $options;

		return $options;
	}

	/**
	 * Registre des types d'elements.
	 */
	public static function get_elements() {
		$align_lcr = array(
			'left'   => __( 'Gauche', 'simple-page-builder' ),
			'center' => __( 'Centre', 'simple-page-builder' ),
			'right'  => __( 'Droite', 'simple-page-builder' ),
		);

		return array(
			'heading' => array(
				'label'  => __( 'Titre', 'simple-page-builder' ),
				'icon'   => 'dashicons-editor-textcolor',
				'fields' => array(
					'text'         => array(
						'type'        => 'text',
						'label'       => __( 'Texte du titre', 'simple-page-builder' ),
						'default'     => '',
						'placeholder' => __( 'Votre titre', 'simple-page-builder' ),
					),
					'level'        => array(
						'type'    => 'select',
						'label'   => __( 'Niveau', 'simple-page-builder' ),
						'options' => array(
							'h1' => 'H1',
							'h2' => 'H2',
							'h3' => 'H3',
							'h4' => 'H4',
						),
						'default' => 'h2',
					),
					'align'        => array(
						'type'    => 'select',
						'label'   => __( 'Alignement', 'simple-page-builder' ),
						'options' => $align_lcr,
						'default' => 'left',
					),
					'color'        => array(
						'type'    => 'color',
						'label'   => __( 'Couleur du texte', 'simple-page-builder' ),
						'default' => '',
					),
					'shape'        => array(
						'type'    => 'select',
						'label'   => __( 'Forme decorative', 'simple-page-builder' ),
						'options' => array(
							'none'      => __( 'Aucune', 'simple-page-builder' ),
							'underline' => __( 'Trait souligne colore', 'simple-page-builder' ),
							'badge'     => __( 'Pastille coloree avant le texte', 'simple-page-builder' ),
							'box'       => __( 'Encadre colore', 'simple-page-builder' ),
						),
						'default' => 'none',
					),
					'shape_color'  => array(
						'type'    => 'color',
						'label'   => __( 'Couleur de la forme', 'simple-page-builder' ),
						'default' => '#2D6AB0',
					),
					'icon_enabled' => array(
						'type'    => 'checkbox',
						'label'   => __( 'Ajouter une icone avant le titre', 'simple-page-builder' ),
						'default' => false,
					),
					'icon'         => array(
						'type'    => 'icon',
						'label'   => __( 'Icone', 'simple-page-builder' ),
						'default' => 'dashicons-star-filled',
					),
					'icon_color'   => array(
						'type'    => 'color',
						'label'   => __( "Couleur de l'icone", 'simple-page-builder' ),
						'default' => '#2D6AB0',
					),
				),
			),

			'text' => array(
				'label'  => __( 'Texte', 'simple-page-builder' ),
				'icon'   => 'dashicons-text-page',
				'fields' => array(
					'content' => array(
						'type'        => 'richtext',
						'label'       => __( 'Contenu', 'simple-page-builder' ),
						'default'     => '',
						'placeholder' => __( 'Saisissez votre texte ici...', 'simple-page-builder' ),
					),
					'align'   => array(
						'type'    => 'select',
						'label'   => __( 'Alignement', 'simple-page-builder' ),
						'options' => array(
							'left'    => __( 'Gauche', 'simple-page-builder' ),
							'center'  => __( 'Centre', 'simple-page-builder' ),
							'right'   => __( 'Droite', 'simple-page-builder' ),
							'justify' => __( 'Justifie', 'simple-page-builder' ),
						),
						'default' => 'left',
					),
				),
			),

			'image' => array(
				'label'  => __( 'Image', 'simple-page-builder' ),
				'icon'   => 'dashicons-format-image',
				'fields' => array(
					'image_id' => array(
						'type'    => 'image',
						'label'   => __( 'Image', 'simple-page-builder' ),
						'default' => 0,
					),
					'link'     => array(
						'type'    => 'url',
						'label'   => __( 'Lien (optionnel)', 'simple-page-builder' ),
						'default' => '',
					),
					'alt'      => array(
						'type'    => 'text',
						'label'   => __( 'Texte alternatif', 'simple-page-builder' ),
						'default' => '',
					),
					'align'    => array(
						'type'    => 'select',
						'label'   => __( 'Alignement', 'simple-page-builder' ),
						'options' => $align_lcr,
						'default' => 'center',
					),
					'width'    => array(
						'type'    => 'select',
						'label'   => __( 'Largeur', 'simple-page-builder' ),
						'options' => array(
							'auto' => __( 'Automatique', 'simple-page-builder' ),
							'25'   => '25%',
							'50'   => '50%',
							'75'   => '75%',
							'100'  => '100%',
						),
						'default' => 'auto',
					),
				),
			),

			'button' => array(
				'label'  => __( 'Bouton', 'simple-page-builder' ),
				'icon'   => 'dashicons-button',
				'fields' => array(
					'text'          => array(
						'type'        => 'text',
						'label'       => __( 'Texte du bouton', 'simple-page-builder' ),
						'default'     => '',
						'placeholder' => __( 'En savoir plus', 'simple-page-builder' ),
					),
					'link_type'     => array(
						'type'    => 'select',
						'label'   => __( 'Type de lien', 'simple-page-builder' ),
						'options' => array(
							'url'  => __( 'URL externe', 'simple-page-builder' ),
							'page' => __( 'Page du site', 'simple-page-builder' ),
						),
						'default' => 'url',
					),
					'url'           => array(
						'type'    => 'url',
						'label'   => __( 'URL (si "URL externe")', 'simple-page-builder' ),
						'default' => '',
					),
					'page_id'       => array(
						'type'    => 'select',
						'label'   => __( 'Page (si "Page du site")', 'simple-page-builder' ),
						'options' => self::get_page_options(),
						'default' => '0',
					),
					'target'        => array(
						'type'    => 'checkbox',
						'label'   => __( 'Ouvrir dans un nouvel onglet', 'simple-page-builder' ),
						'default' => false,
					),
					'style'         => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'simple-page-builder' ),
						'options' => array(
							'primary'   => __( 'Plein (couleur principale)', 'simple-page-builder' ),
							'secondary' => __( 'Plein (couleur secondaire)', 'simple-page-builder' ),
							'outline'   => __( 'Contour', 'simple-page-builder' ),
							'custom'    => __( 'Personnalise (couleurs libres)', 'simple-page-builder' ),
						),
						'default' => 'primary',
					),
					'bg_color'      => array(
						'type'    => 'color',
						'label'   => __( 'Couleur de fond (si style personnalise)', 'simple-page-builder' ),
						'default' => '',
					),
					'text_color'    => array(
						'type'    => 'color',
						'label'   => __( 'Couleur du texte (si style personnalise)', 'simple-page-builder' ),
						'default' => '',
					),
					'size'          => array(
						'type'    => 'select',
						'label'   => __( 'Taille', 'simple-page-builder' ),
						'options' => array(
							'small'  => __( 'Petit', 'simple-page-builder' ),
							'medium' => __( 'Moyen', 'simple-page-builder' ),
							'large'  => __( 'Grand', 'simple-page-builder' ),
						),
						'default' => 'medium',
					),
					'radius'        => array(
						'type'    => 'select',
						'label'   => __( 'Forme des angles', 'simple-page-builder' ),
						'options' => array(
							'square'  => __( 'Carre', 'simple-page-builder' ),
							'rounded' => __( 'Arrondi', 'simple-page-builder' ),
							'pill'    => __( 'Pilule (tout arrondi)', 'simple-page-builder' ),
						),
						'default' => 'rounded',
					),
					'align'         => array(
						'type'    => 'select',
						'label'   => __( 'Alignement', 'simple-page-builder' ),
						'options' => $align_lcr,
						'default' => 'left',
					),
					'full_width'    => array(
						'type'    => 'checkbox',
						'label'   => __( 'Pleine largeur de la colonne', 'simple-page-builder' ),
						'default' => false,
					),
					'icon_enabled'  => array(
						'type'    => 'checkbox',
						'label'   => __( 'Ajouter une icone', 'simple-page-builder' ),
						'default' => false,
					),
					'icon'          => array(
						'type'    => 'icon',
						'label'   => __( 'Icone', 'simple-page-builder' ),
						'default' => 'dashicons-arrow-right-alt',
					),
					'icon_position' => array(
						'type'    => 'select',
						'label'   => __( "Position de l'icone", 'simple-page-builder' ),
						'options' => array(
							'before' => __( 'Avant le texte', 'simple-page-builder' ),
							'after'  => __( 'Apres le texte', 'simple-page-builder' ),
						),
						'default' => 'after',
					),
				),
			),

			'separator' => array(
				'label'  => __( 'Separateur', 'simple-page-builder' ),
				'icon'   => 'dashicons-minus',
				'fields' => array(
					'style'     => array(
						'type'    => 'select',
						'label'   => __( 'Style de ligne', 'simple-page-builder' ),
						'options' => array(
							'solid'  => __( 'Continue', 'simple-page-builder' ),
							'dashed' => __( 'Tirets', 'simple-page-builder' ),
							'dotted' => __( 'Pointilles', 'simple-page-builder' ),
						),
						'default' => 'solid',
					),
					'color'     => array(
						'type'    => 'color',
						'label'   => __( 'Couleur', 'simple-page-builder' ),
						'default' => '#dddddd',
					),
					'thickness' => array(
						'type'    => 'number',
						'label'   => __( 'Epaisseur (px)', 'simple-page-builder' ),
						'default' => 2,
					),
					'width'     => array(
						'type'    => 'select',
						'label'   => __( 'Largeur', 'simple-page-builder' ),
						'options' => array(
							'25'  => '25%',
							'50'  => '50%',
							'75'  => '75%',
							'100' => '100%',
						),
						'default' => '100',
					),
					'align'     => array(
						'type'    => 'select',
						'label'   => __( 'Alignement (si largeur < 100%)', 'simple-page-builder' ),
						'options' => $align_lcr,
						'default' => 'center',
					),
					'spacing'   => array(
						'type'    => 'number',
						'label'   => __( 'Marge verticale (px)', 'simple-page-builder' ),
						'default' => 20,
					),
				),
			),

			'spacer' => array(
				'label'  => __( 'Espacement', 'simple-page-builder' ),
				'icon'   => 'dashicons-editor-expand',
				'fields' => array(
					'height' => array(
						'type'    => 'number',
						'label'   => __( 'Hauteur (px)', 'simple-page-builder' ),
						'default' => 40,
					),
				),
			),

			'video' => array(
				'label'  => __( 'Video', 'simple-page-builder' ),
				'icon'   => 'dashicons-video-alt3',
				'fields' => array(
					'url'   => array(
						'type'    => 'url',
						'label'   => __( 'URL YouTube / Vimeo / MP4', 'simple-page-builder' ),
						'default' => '',
					),
					'ratio' => array(
						'type'    => 'select',
						'label'   => __( 'Format', 'simple-page-builder' ),
						'options' => array(
							'16-9' => '16:9',
							'4-3'  => '4:3',
						),
						'default' => '16-9',
					),
				),
			),

			'icon_box' => array(
				'label'  => __( 'Bloc icone', 'simple-page-builder' ),
				'icon'   => 'dashicons-star-filled',
				'fields' => array(
					'icon'       => array(
						'type'    => 'icon',
						'label'   => __( 'Icone', 'simple-page-builder' ),
						'default' => 'dashicons-info',
					),
					'icon_color' => array(
						'type'    => 'color',
						'label'   => __( "Couleur de l'icone", 'simple-page-builder' ),
						'default' => '#2D6AB0',
					),
					'icon_size'  => array(
						'type'    => 'select',
						'label'   => __( "Taille de l'icone", 'simple-page-builder' ),
						'options' => array(
							'small'  => __( 'Petite', 'simple-page-builder' ),
							'medium' => __( 'Moyenne', 'simple-page-builder' ),
							'large'  => __( 'Grande', 'simple-page-builder' ),
						),
						'default' => 'medium',
					),
					'title'      => array(
						'type'        => 'text',
						'label'       => __( 'Titre', 'simple-page-builder' ),
						'default'     => '',
						'placeholder' => __( 'Titre du bloc', 'simple-page-builder' ),
					),
					'text'       => array(
						'type'    => 'textarea',
						'label'   => __( 'Texte', 'simple-page-builder' ),
						'default' => '',
					),
					'align'      => array(
						'type'    => 'select',
						'label'   => __( 'Alignement', 'simple-page-builder' ),
						'options' => array(
							'left'   => __( 'Gauche', 'simple-page-builder' ),
							'center' => __( 'Centre', 'simple-page-builder' ),
						),
						'default' => 'left',
					),
				),
			),

			'quote' => array(
				'label'  => __( 'Citation', 'simple-page-builder' ),
				'icon'   => 'dashicons-format-quote',
				'fields' => array(
					'text'   => array(
						'type'    => 'textarea',
						'label'   => __( 'Texte de la citation', 'simple-page-builder' ),
						'default' => '',
					),
					'author' => array(
						'type'    => 'text',
						'label'   => __( 'Auteur', 'simple-page-builder' ),
						'default' => '',
					),
				),
			),

			'list' => array(
				'label'  => __( 'Liste', 'simple-page-builder' ),
				'icon'   => 'dashicons-editor-ul',
				'fields' => array(
					'items'        => array(
						'type'        => 'textarea',
						'label'       => __( 'Un element par ligne', 'simple-page-builder' ),
						'default'     => '',
						'placeholder' => __( "Premier element\nDeuxieme element", 'simple-page-builder' ),
					),
					'style'        => array(
						'type'    => 'select',
						'label'   => __( 'Style de puce', 'simple-page-builder' ),
						'options' => array(
							'disc'   => __( 'Puce ronde', 'simple-page-builder' ),
							'check'  => __( 'Coche', 'simple-page-builder' ),
							'arrow'  => __( 'Fleche', 'simple-page-builder' ),
							'number' => __( 'Numerotee', 'simple-page-builder' ),
						),
						'default' => 'disc',
					),
					'icon_color'   => array(
						'type'    => 'color',
						'label'   => __( 'Couleur des puces (coche / fleche)', 'simple-page-builder' ),
						'default' => '#587526',
					),
					'item_spacing' => array(
						'type'    => 'number',
						'label'   => __( 'Espace entre les elements (px)', 'simple-page-builder' ),
						'default' => 6,
					),
				),
			),
		);
	}

	/**
	 * Renvoie les valeurs par defaut pour un type d'element donne.
	 */
	public static function get_defaults( $type ) {
		$elements = self::get_elements();
		$defaults = array();

		if ( isset( $elements[ $type ]['fields'] ) ) {
			foreach ( $elements[ $type ]['fields'] as $key => $field ) {
				$defaults[ $key ] = $field['default'];
			}
		}

		return $defaults;
	}
}
