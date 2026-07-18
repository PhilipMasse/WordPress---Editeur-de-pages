<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitisation et rendu HTML de la mise en page du constructeur.
 * Le JSON envoye par le navigateur n'est JAMAIS affiche tel quel :
 * il est systematiquement reconstruit champ par champ ici selon le
 * registre SPB_Elements, avec les fonctions d'echappement WordPress.
 */
class SPB_Render {

	/**
	 * Encode une structure de mise en page pour le stockage en base de
	 * donnees (meta '_spb_layout'). Le JSON est encode en base64 : cet
	 * alphabet ne contenant ni guillemet ni antislash, il est impossible
	 * qu'un mecanisme d'echappement (WordPress, cache, plugin de securite...)
	 * corrompe accidentellement les sequences d'echappement JSON (\n, \u00e9...)
	 * en cours de route.
	 */
	public static function encode_layout( $layout ) {
		return base64_encode( wp_json_encode( $layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Decode une valeur stockee dans la meta '_spb_layout'. Gere aussi,
	 * pour compatibilite ascendante, les donnees enregistrees par une
	 * version anterieure du plugin (JSON brut, sans encodage base64).
	 */
	public static function decode_layout( $stored ) {
		if ( empty( $stored ) || ! is_string( $stored ) ) {
			return null;
		}

		$decoded = base64_decode( $stored, true );

		if ( false !== $decoded ) {
			$layout = json_decode( $decoded, true );
			if ( is_array( $layout ) ) {
				return $layout;
			}
		}

		// Repli : ancienne donnee stockee en JSON brut (versions < 1.2.2).
		$layout = json_decode( $stored, true );

		return is_array( $layout ) ? $layout : null;
	}

	/**
	 * Nettoie une structure de mise en page brute (issue du formulaire admin)
	 * et renvoie une structure sure, pret a etre stockee en meta.
	 */
	public static function sanitize_layout( $raw ) {
		$clean = array( 'rows' => array() );

		if ( empty( $raw['rows'] ) || ! is_array( $raw['rows'] ) ) {
			return $clean;
		}

		$layouts = SPB_Elements::get_layouts();
		$row_fields = SPB_Elements::get_row_fields();

		foreach ( $raw['rows'] as $row ) {
			if ( empty( $row['layout'] ) || ! isset( $layouts[ $row['layout'] ] ) ) {
				continue;
			}

			$clean_row = array(
				'id'       => 'row_' . substr( md5( uniqid( '', true ) ), 0, 8 ),
				'layout'   => sanitize_text_field( $row['layout'] ),
				'settings' => self::sanitize_fields( isset( $row['settings'] ) ? $row['settings'] : array(), $row_fields ),
				'columns'  => array(),
			);

			$widths = $layouts[ $row['layout'] ];
			$raw_columns = isset( $row['columns'] ) && is_array( $row['columns'] ) ? $row['columns'] : array();

			foreach ( $widths as $index => $width ) {
				$raw_col = isset( $raw_columns[ $index ] ) ? $raw_columns[ $index ] : array();
				$clean_col = array( 'elements' => array() );

				if ( ! empty( $raw_col['elements'] ) && is_array( $raw_col['elements'] ) ) {
					foreach ( $raw_col['elements'] as $el ) {
						$clean_el = self::sanitize_element( $el );
						if ( $clean_el ) {
							$clean_col['elements'][] = $clean_el;
						}
					}
				}

				$clean_row['columns'][] = $clean_col;
			}

			$clean['rows'][] = $clean_row;
		}

		return $clean;
	}

	private static function sanitize_element( $el ) {
		if ( empty( $el['type'] ) ) {
			return null;
		}

		$elements = SPB_Elements::get_elements();
		$type = sanitize_key( $el['type'] );

		if ( ! isset( $elements[ $type ] ) ) {
			return null;
		}

		$fields = isset( $elements[ $type ]['fields'] ) ? $elements[ $type ]['fields'] : array();
		$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();

		return array(
			'id'       => 'el_' . substr( md5( uniqid( '', true ) ), 0, 8 ),
			'type'     => $type,
			'settings' => self::sanitize_fields( $settings, $fields ),
		);
	}

	/**
	 * Nettoie un tableau de valeurs selon une definition de champs (type par type).
	 */
	private static function sanitize_fields( $values, $field_defs ) {
		$clean = array();

		foreach ( $field_defs as $key => $def ) {
			$raw_value = isset( $values[ $key ] ) ? $values[ $key ] : $def['default'];

			switch ( $def['type'] ) {
				case 'richtext':
					$clean[ $key ] = self::sanitize_richtext( $raw_value );
					break;

				case 'textarea':
					$clean[ $key ] = self::sanitize_multiline_text( $raw_value );
					break;

				case 'url':
					$clean[ $key ] = esc_url_raw( $raw_value );
					break;

				case 'color':
					$clean[ $key ] = self::sanitize_hex( $raw_value );
					break;

				case 'number':
					$clean[ $key ] = intval( $raw_value );
					break;

				case 'checkbox':
					$clean[ $key ] = ! empty( $raw_value ) && 'false' !== $raw_value;
					break;

				case 'image':
					$clean[ $key ] = absint( $raw_value );
					break;

				case 'icon':
					$clean[ $key ] = self::sanitize_icon( $raw_value, $def['default'] );
					break;

				case 'select':
					$options = isset( $def['options'] ) ? array_map( 'strval', array_keys( $def['options'] ) ) : array();
					$raw_str = (string) $raw_value;
					$clean[ $key ] = in_array( $raw_str, $options, true ) ? $raw_str : (string) $def['default'];
					break;

				case 'text':
				default:
					$clean[ $key ] = sanitize_text_field( $raw_value );
					break;
			}
		}

		return $clean;
	}

	private static function sanitize_hex( $color ) {
		if ( empty( $color ) ) {
			return '';
		}
		$color = sanitize_hex_color( $color );
		return $color ? $color : '';
	}

	/**
	 * Sanitise un texte multi-lignes (ex : items de liste) en nettoyant
	 * chaque ligne independamment (suppression des balises, entites...)
	 * puis en reassemblant avec de vrais retours a la ligne "\n".
	 * Cette approche explicite garantit que les retours a la ligne
	 * saisis par l'utilisateur sont toujours preserves.
	 */
	private static function sanitize_multiline_text( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		$lines = preg_split( '/\r\n|\r|\n/', $value );
		$lines = array_map( 'sanitize_text_field', $lines );

		return implode( "\n", $lines );
	}

	private static function sanitize_icon( $icon, $default ) {
		if ( is_string( $icon ) && preg_match( '/^dashicons-[a-z0-9-]+$/', $icon ) ) {
			return $icon;
		}
		return $default;
	}

	/**
	 * Sanitisation dediee au champ "texte enrichi" : autorise un jeu de
	 * balises de mise en forme (gras, italique, listes, liens, titres...)
	 * ainsi qu'un attribut style strictement limite aux proprietes
	 * color / background-color en hexadecimal ou rgb(), pour permettre la
	 * mise en couleur du texte sans ouvrir la porte a une injection CSS.
	 */
	private static function sanitize_richtext( $html ) {
		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			return '';
		}

		$allowed = array(
			'p'          => array( 'style' => true ),
			'br'         => array(),
			'strong'     => array(),
			'b'          => array(),
			'em'         => array(),
			'i'          => array(),
			'u'          => array(),
			's'          => array(),
			'strike'     => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'blockquote' => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'a'          => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
			'span'       => array( 'style' => true ),
			'div'        => array( 'style' => true ),
		);

		$html = wp_kses( $html, $allowed );

		// Restreint tout attribut style aux seules proprietes color / background-color.
		$html = preg_replace_callback(
			'/style\s*=\s*"([^"]*)"/i',
			function ( $matches ) {
				$props = explode( ';', $matches[1] );
				$safe = array();

				foreach ( $props as $prop ) {
					if ( preg_match( '/^\s*(color|background-color)\s*:\s*(#[0-9a-fA-F]{3,8}|rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\))\s*$/', $prop, $pm ) ) {
						$safe[] = $pm[1] . ':' . $pm[2];
					}
				}

				return $safe ? 'style="' . esc_attr( implode( ';', $safe ) ) . '"' : '';
			},
			$html
		);

		return $html;
	}

	/* ------------------------------------------------------------------ */
	/*  RENDU HTML FRONTEND                                                */
	/* ------------------------------------------------------------------ */

	public static function render_layout( $layout ) {
		if ( empty( $layout['rows'] ) || ! is_array( $layout['rows'] ) ) {
			return '';
		}

		$html = '<div class="spb-canvas-front">';
		foreach ( $layout['rows'] as $row ) {
			$html .= self::render_row( $row );
		}
		$html .= '</div>';

		return $html;
	}

	private static function render_row( $row ) {
		$settings = isset( $row['settings'] ) ? $row['settings'] : array();
		$layouts  = SPB_Elements::get_layouts();
		$widths   = isset( $layouts[ $row['layout'] ] ) ? $layouts[ $row['layout'] ] : array( 12 );

		$style = '';
		if ( ! empty( $settings['bg_color'] ) ) {
			$style .= 'background-color:' . esc_attr( $settings['bg_color'] ) . ';';
		}
		$style .= 'padding-top:' . intval( isset( $settings['padding_top'] ) ? $settings['padding_top'] : 40 ) . 'px;';
		$style .= 'padding-bottom:' . intval( isset( $settings['padding_bottom'] ) ? $settings['padding_bottom'] : 40 ) . 'px;';

		$classes = array( 'spb-row' );
		if ( ! empty( $settings['full_width'] ) ) {
			$classes[] = 'spb-row-full';
		}
		if ( ! empty( $settings['custom_class'] ) ) {
			$classes[] = sanitize_html_class( $settings['custom_class'] );
		}

		$out  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '">';
		$out .= '<div class="spb-row-inner">';

		$columns = isset( $row['columns'] ) && is_array( $row['columns'] ) ? $row['columns'] : array();

		foreach ( $widths as $index => $width ) {
			$col = isset( $columns[ $index ] ) ? $columns[ $index ] : array( 'elements' => array() );
			$pct = round( ( $width / 12 ) * 100, 4 );

			$out .= '<div class="spb-col" style="width:' . esc_attr( $pct ) . '%;">';
			$out .= '<div class="spb-col-inner">';

			if ( ! empty( $col['elements'] ) ) {
				foreach ( $col['elements'] as $el ) {
					$out .= self::render_element( $el );
				}
			}

			$out .= '</div></div>';
		}

		$out .= '</div></div>';

		return $out;
	}

	private static function render_element( $el ) {
		if ( empty( $el['type'] ) ) {
			return '';
		}

		$type = $el['type'];
		$s    = isset( $el['settings'] ) ? $el['settings'] : array();

		switch ( $type ) {

			case 'heading':
				return self::render_heading( $s );

			case 'text':
				return sprintf(
					'<div class="spb-el spb-text spb-align-%1$s">%2$s</div>',
					esc_attr( $s['align'] ),
					self::sanitize_richtext( $s['content'] )
				);

			case 'image':
				if ( empty( $s['image_id'] ) ) {
					return '';
				}
				$img = wp_get_attachment_image(
					$s['image_id'],
					'large',
					false,
					array( 'alt' => esc_attr( $s['alt'] ) )
				);
				if ( ! $img ) {
					return '';
				}
				$width_style = '' !== $s['width'] && 'auto' !== $s['width'] ? ' style="width:' . intval( $s['width'] ) . '%;"' : '';
				$img = '<span class="spb-image-wrap"' . $width_style . '>' . $img . '</span>';
				if ( ! empty( $s['link'] ) ) {
					$img = '<a href="' . esc_url( $s['link'] ) . '">' . $img . '</a>';
				}
				return '<div class="spb-el spb-image spb-align-' . esc_attr( $s['align'] ) . '">' . $img . '</div>';

			case 'button':
				return self::render_button( $s );

			case 'separator':
				return self::render_separator( $s );

			case 'spacer':
				return sprintf( '<div class="spb-el spb-spacer" style="height:%dpx;"></div>', intval( $s['height'] ) );

			case 'video':
				if ( empty( $s['url'] ) ) {
					return '';
				}
				$ratio_class = '4-3' === $s['ratio'] ? 'spb-ratio-4-3' : 'spb-ratio-16-9';
				$embed = wp_oembed_get( esc_url_raw( $s['url'] ) );
				if ( ! $embed ) {
					$embed = '<a href="' . esc_url( $s['url'] ) . '">' . esc_html( $s['url'] ) . '</a>';
				}
				return '<div class="spb-el spb-video ' . esc_attr( $ratio_class ) . '">' . $embed . '</div>';

			case 'icon_box':
				return self::render_icon_box( $s );

			case 'quote':
				$out = '<blockquote class="spb-el spb-quote">' . esc_html( $s['text'] );
				if ( ! empty( $s['author'] ) ) {
					$out .= '<cite>' . esc_html( $s['author'] ) . '</cite>';
				}
				$out .= '</blockquote>';
				return $out;

			case 'list':
				return self::render_list( $s );

			default:
				return '';
		}
	}

	private static function render_heading( $s ) {
		$level = in_array( $s['level'], array( 'h1', 'h2', 'h3', 'h4' ), true ) ? $s['level'] : 'h2';
		$shape = in_array( $s['shape'], array( 'none', 'underline', 'badge', 'box' ), true ) ? $s['shape'] : 'none';
		$shape_color = ! empty( $s['shape_color'] ) ? $s['shape_color'] : '#2D6AB0';

		$text_style = '';
		if ( ! empty( $s['color'] ) ) {
			$text_style .= 'color:' . esc_attr( $s['color'] ) . ';';
		}

		$icon_html = '';
		if ( ! empty( $s['icon_enabled'] ) && ! empty( $s['icon'] ) ) {
			$icon_color_style = ! empty( $s['icon_color'] ) ? ' style="color:' . esc_attr( $s['icon_color'] ) . ';"' : '';
			$icon_html = '<span class="spb-heading-icon dashicons ' . esc_attr( $s['icon'] ) . '"' . $icon_color_style . '></span> ';
		}

		$badge_html = '';
		if ( 'badge' === $shape ) {
			$badge_html = '<span class="spb-heading-badge" style="background-color:' . esc_attr( $shape_color ) . ';"></span> ';
		}

		$heading_tag = sprintf(
			'<%1$s class="spb-heading" style="%2$s">%3$s%4$s%5$s</%1$s>',
			tag_escape( $level ),
			esc_attr( $text_style ),
			$icon_html,
			$badge_html,
			esc_html( $s['text'] )
		);

		if ( 'box' === $shape ) {
			$heading_tag = '<div class="spb-heading-box" style="background-color:' . esc_attr( $shape_color ) . ';">' . $heading_tag . '</div>';
		}

		$out = '<div class="spb-el spb-heading-wrap spb-shape-' . esc_attr( $shape ) . ' spb-align-' . esc_attr( $s['align'] ) . '">' . $heading_tag;

		if ( 'underline' === $shape ) {
			$out .= '<span class="spb-heading-underline" style="background-color:' . esc_attr( $shape_color ) . ';"></span>';
		}

		$out .= '</div>';

		return $out;
	}

	private static function render_button( $s ) {
		$target = ! empty( $s['target'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';

		$style_key = in_array( $s['style'], array( 'primary', 'secondary', 'outline', 'custom' ), true ) ? $s['style'] : 'primary';
		$size = in_array( $s['size'], array( 'small', 'medium', 'large' ), true ) ? $s['size'] : 'medium';
		$radius = in_array( $s['radius'], array( 'square', 'rounded', 'pill' ), true ) ? $s['radius'] : 'rounded';

		$classes = array( 'spb-button', 'spb-button-' . $style_key, 'spb-button-size-' . $size, 'spb-button-radius-' . $radius );
		if ( ! empty( $s['full_width'] ) ) {
			$classes[] = 'spb-button-full';
		}

		$style_attr = '';
		if ( 'custom' === $style_key ) {
			if ( ! empty( $s['bg_color'] ) ) {
				$style_attr .= 'background-color:' . esc_attr( $s['bg_color'] ) . ';border-color:' . esc_attr( $s['bg_color'] ) . ';';
			}
			if ( ! empty( $s['text_color'] ) ) {
				$style_attr .= 'color:' . esc_attr( $s['text_color'] ) . ';';
			}
		}

		// Determine le lien final : page interne (permalien recalcule a chaque affichage) ou URL externe saisie.
		$link_type = isset( $s['link_type'] ) && 'page' === $s['link_type'] ? 'page' : 'url';
		$href = '';
		if ( 'page' === $link_type && ! empty( $s['page_id'] ) && '0' !== (string) $s['page_id'] ) {
			$permalink = get_permalink( intval( $s['page_id'] ) );
			$href = $permalink ? $permalink : '';
		} else {
			$href = isset( $s['url'] ) ? $s['url'] : '';
		}

		$icon_html = '';
		if ( ! empty( $s['icon_enabled'] ) && ! empty( $s['icon'] ) ) {
			$position = 'before' === $s['icon_position'] ? 'before' : 'after';
			$icon_html = '<span class="dashicons ' . esc_attr( $s['icon'] ) . ' spb-button-icon spb-button-icon-' . esc_attr( $position ) . '"></span>';
		}

		$label = esc_html( $s['text'] );
		$content = ( ! empty( $s['icon_enabled'] ) && 'before' === $s['icon_position'] ) ? $icon_html . $label : $label . $icon_html;

		return sprintf(
			'<div class="spb-el spb-button-wrap spb-align-%1$s"><a class="%2$s" href="%3$s" style="%4$s"%5$s>%6$s</a></div>',
			esc_attr( $s['align'] ),
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $href ),
			esc_attr( $style_attr ),
			$target,
			$content
		);
	}

	private static function render_separator( $s ) {
		$width = in_array( $s['width'], array( '25', '50', '75', '100' ), true ) ? $s['width'] : '100';
		$align = in_array( $s['align'], array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'center';

		$style = 'border-top:' . intval( $s['thickness'] ) . 'px ' . esc_attr( $s['style'] ) . ' ' . esc_attr( $s['color'] ) . ';';
		$style .= 'margin-top:' . intval( $s['spacing'] ) . 'px;margin-bottom:' . intval( $s['spacing'] ) . 'px;';
		$style .= 'width:' . esc_attr( $width ) . '%;';

		if ( 'center' === $align ) {
			$style .= 'margin-left:auto;margin-right:auto;';
		} elseif ( 'right' === $align ) {
			$style .= 'margin-left:auto;margin-right:0;';
		} else {
			$style .= 'margin-left:0;margin-right:auto;';
		}

		return '<div class="spb-el spb-separator" style="' . esc_attr( $style ) . '"></div>';
	}

	private static function render_icon_box( $s ) {
		$icon_class = self::sanitize_icon( $s['icon'], 'dashicons-info' );
		$icon_color = ! empty( $s['icon_color'] ) ? $s['icon_color'] : '#2D6AB0';
		$size_class = 'spb-icon-size-' . ( in_array( $s['icon_size'], array( 'small', 'medium', 'large' ), true ) ? $s['icon_size'] : 'medium' );

		return sprintf(
			'<div class="spb-el spb-icon-box spb-align-%1$s"><span class="spb-icon dashicons %2$s %3$s" style="color:%4$s;"></span><h4>%5$s</h4><p>%6$s</p></div>',
			esc_attr( $s['align'] ),
			esc_attr( $icon_class ),
			esc_attr( $size_class ),
			esc_attr( $icon_color ),
			esc_html( $s['title'] ),
			esc_html( $s['text'] )
		);
	}

	private static function render_list( $s ) {
		$items = preg_split( '/\r\n|\r|\n/', (string) $s['items'] );
		$style = in_array( $s['style'], array( 'disc', 'check', 'arrow', 'number' ), true ) ? $s['style'] : 'disc';
		$tag = 'number' === $style ? 'ol' : 'ul';

		$marker_style = '';
		if ( in_array( $style, array( 'check', 'arrow' ), true ) ) {
			$marker_style = ' style="--spb-list-marker-color:' . esc_attr( ! empty( $s['icon_color'] ) ? $s['icon_color'] : '#587526' ) . ';"';
		}

		$out = '<' . $tag . ' class="spb-el spb-list spb-list-' . esc_attr( $style ) . '"' . $marker_style . '>';

		foreach ( $items as $item ) {
			$item = trim( $item );
			if ( '' === $item ) {
				continue;
			}
			$out .= '<li style="margin-bottom:' . intval( $s['item_spacing'] ) . 'px;">' . esc_html( $item ) . '</li>';
		}

		$out .= '</' . $tag . '>';

		return $out;
	}
}
