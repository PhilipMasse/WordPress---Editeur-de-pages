/* global jQuery, spbConfig, wp */
( function ( $ ) {
	'use strict';

	var cfg = window.spbConfig || { elements: {}, layouts: {}, rowFields: {}, icons: {}, savedLayout: { rows: [] }, i18n: {} };
	var i18n = cfg.i18n || {};

	// Etat interne : structure identique a celle attendue par le PHP (rows > columns > elements).
	var spbData = ( cfg.savedLayout && cfg.savedLayout.rows ) ? cfg.savedLayout : { rows: [] };

	var $canvas, $app, $hiddenField, $panelOverlay, $panel;

	// Definitions des sous-champs de chaque champ "repeater" actuellement affiche
	// (indexees par cle de champ), utilisees pour construire les nouveaux
	// elements ajoutes dynamiquement (bouton "+ Ajouter").
	var repeaterFieldDefs = {};

	function uid( prefix ) {
		return prefix + '_' + Math.random().toString( 36 ).substr( 2, 9 );
	}

	function esc( str ) {
		return $( '<div>' ).text( str == null ? '' : str ).html();
	}

	/* ------------------------------------------------------------------ */
	/* Recherche dans le modele                                            */
	/* ------------------------------------------------------------------ */

	function findElementById( id ) {
		for ( var r = 0; r < spbData.rows.length; r++ ) {
			var row = spbData.rows[ r ];
			for ( var c = 0; c < row.columns.length; c++ ) {
				var col = row.columns[ c ];
				for ( var e = 0; e < col.elements.length; e++ ) {
					if ( col.elements[ e ].id === id ) {
						return col.elements[ e ];
					}
				}
			}
		}
		return null;
	}

	function findRowById( id ) {
		for ( var r = 0; r < spbData.rows.length; r++ ) {
			if ( spbData.rows[ r ].id === id ) {
				return spbData.rows[ r ];
			}
		}
		return null;
	}

	/* ------------------------------------------------------------------ */
	/* Serialisation                                                       */
	/* ------------------------------------------------------------------ */

	function utf8ToBase64( str ) {
		return window.btoa( unescape( encodeURIComponent( str ) ) );
	}

	function serialize() {
		$hiddenField.val( utf8ToBase64( JSON.stringify( spbData ) ) );
	}

	/* ------------------------------------------------------------------ */
	/* Rendu complet                                                       */
	/* ------------------------------------------------------------------ */

	function render() {
		if ( ! spbData.rows.length ) {
			$canvas.html( '<div class="spb-empty">' + esc( i18n.emptyCanvas || '' ) + '</div>' );
			serialize();
			return;
		}

		var html = '<div id="spb-rows">';
		spbData.rows.forEach( function ( row ) {
			html += renderRowHtml( row );
		} );
		html += '</div>';

		$canvas.html( html );
		initSortables();
		serialize();
	}

	function renderRowHtml( row ) {
		var widths = cfg.layouts[ row.layout ] || [ 12 ];
		var s = row.settings || {};

		var style = '';
		if ( s.bg_color ) {
			style += 'background-color:' + s.bg_color + ';';
		}
		style += 'padding-top:' + ( parseInt( s.padding_top, 10 ) || 0 ) + 'px;';
		style += 'padding-bottom:' + ( parseInt( s.padding_bottom, 10 ) || 0 ) + 'px;';

		var html = '<div class="spb-row" data-row-id="' + row.id + '" style="' + style + '">';
		html += '<div class="spb-row-toolbar">';
		html += '<span class="spb-drag-handle-row" title="Deplacer"><span class="dashicons dashicons-move"></span> Ligne</span>';
		html += '<span class="spb-row-actions">';
		html += '<button type="button" class="spb-icon-btn spb-row-settings-btn" title="' + esc( i18n.rowSettings || '' ) + '"><span class="dashicons dashicons-admin-generic"></span></button>';
		html += '<button type="button" class="spb-icon-btn spb-row-delete-btn" title="' + esc( i18n.delete || '' ) + '"><span class="dashicons dashicons-trash"></span></button>';
		html += '</span></div>';

		html += '<div class="spb-row-inner">';
		widths.forEach( function ( w, index ) {
			var col = row.columns[ index ] || { elements: [] };
			var pct = Math.round( ( w / 12 ) * 10000 ) / 100;
			html += '<div class="spb-col" data-col-index="' + index + '" style="width:' + pct + '%;">';
			html += '<div class="spb-col-elements" data-row-id="' + row.id + '" data-col-index="' + index + '">';

			col.elements.forEach( function ( el ) {
				html += renderElementHtml( el );
			} );

			html += '</div>';
			html += '<button type="button" class="spb-add-element-btn" data-row-id="' + row.id + '" data-col-index="' + index + '"><span class="dashicons dashicons-plus-alt2"></span> ' + esc( i18n.addElement || '' ) + '</button>';
			html += '</div>';
		} );
		html += '</div></div>';

		return html;
	}

	function renderElementHtml( el ) {
		var def = cfg.elements[ el.type ] || { label: el.type, icon: 'dashicons-admin-generic' };
		var preview = elementPreviewText( el );

		var html = '<div class="spb-element" data-el-id="' + el.id + '">';
		html += '<div class="spb-element-head">';
		html += '<span class="spb-drag-handle-el dashicons dashicons-move"></span>';
		html += '<span class="spb-el-icon dashicons ' + esc( def.icon ) + '"></span>';
		html += '<span class="spb-el-label">' + esc( def.label ) + '</span>';
		html += '<span class="spb-element-actions">';
		html += '<button type="button" class="spb-icon-btn spb-el-edit-btn" title="' + esc( i18n.settings || '' ) + '"><span class="dashicons dashicons-edit"></span></button>';
		html += '<button type="button" class="spb-icon-btn spb-el-dup-btn" title="' + esc( i18n.duplicate || '' ) + '"><span class="dashicons dashicons-admin-page"></span></button>';
		html += '<button type="button" class="spb-icon-btn spb-el-del-btn" title="' + esc( i18n.delete || '' ) + '"><span class="dashicons dashicons-no-alt"></span></button>';
		html += '</span></div>';
		html += '<div class="spb-el-preview">' + preview + '</div>';
		html += '</div>';

		return html;
	}

	function elementPreviewText( el ) {
		var s = el.settings || {};
		var empty = '<em class="spb-preview-empty">(a completer)</em>';

		switch ( el.type ) {
			case 'heading':
				return s.text ? esc( s.text ) : empty;
			case 'text':
				var tmp = $( '<div>' ).html( s.content || '' ).text().trim();
				return tmp ? esc( tmp.substring( 0, 80 ) ) : empty;
			case 'button':
				return s.text ? esc( s.text ) : empty;
			case 'image':
				return s.image_id ? esc( 'Image #' + s.image_id ) : esc( 'Aucune image choisie' );
			case 'quote':
				return s.text ? esc( s.text.substring( 0, 80 ) ) : empty;
			case 'icon_box':
				return s.title ? esc( s.title ) : empty;
			case 'list':
				var count = ( s.items || '' ).split( '\n' ).filter( function ( v ) { return v.trim() !== ''; } ).length;
				return count ? esc( count + ' element(s)' ) : empty;
			case 'accordion':
				var qCount = ( s.items || [] ).filter( function ( it ) { return it.question && it.question.trim() !== ''; } ).length;
				return qCount ? esc( qCount + ' question(s)' ) : empty;
			case 'social':
				var platforms = [ 'facebook', 'twitter', 'instagram', 'youtube', 'linkedin', 'rss' ];
				var pCount = platforms.filter( function ( p ) { return s[ p ]; } ).length;
				return pCount ? esc( pCount + ' reseau(x)' ) : empty;
			default:
				return '';
		}
	}

	/* ------------------------------------------------------------------ */
	/* Drag & drop (jQuery UI Sortable)                                    */
	/* ------------------------------------------------------------------ */

	function initSortables() {
		$( '#spb-rows' ).sortable( {
			handle: '.spb-drag-handle-row',
			items: '> .spb-row',
			axis: 'y',
			placeholder: 'spb-row-placeholder',
			stop: syncRowsFromDOM
		} );

		$( '.spb-col-elements' ).sortable( {
			handle: '.spb-drag-handle-el',
			items: '> .spb-element',
			connectWith: '.spb-col-elements',
			placeholder: 'spb-element-placeholder',
			stop: syncElementsFromDOM
		} );
	}

	function syncRowsFromDOM() {
		var newOrder = [];
		$( '#spb-rows > .spb-row' ).each( function () {
			var id = $( this ).data( 'row-id' );
			var row = findRowById( id );
			if ( row ) {
				newOrder.push( row );
			}
		} );
		spbData.rows = newOrder;
		serialize();
	}

	function syncElementsFromDOM() {
		$( '.spb-col-elements' ).each( function () {
			var rowId = $( this ).data( 'row-id' );
			var colIndex = $( this ).data( 'col-index' );
			var row = findRowById( rowId );
			if ( ! row || ! row.columns[ colIndex ] ) {
				return;
			}
			var newArr = [];
			$( this ).children( '.spb-element' ).each( function () {
				var elId = $( this ).data( 'el-id' );
				var el = findElementById( elId );
				if ( el ) {
					newArr.push( el );
				}
			} );
			row.columns[ colIndex ].elements = newArr;
		} );
		serialize();
	}

	/* ------------------------------------------------------------------ */
	/* Modale generique de choix (mise en page / type d'element / icone)  */
	/* ------------------------------------------------------------------ */

	function openChoiceModal( title, items, onPick ) {
		var showSearch = items.length > 12;

		var html = '<div class="spb-modal-overlay"><div class="spb-modal">';
		html += '<div class="spb-modal-head"><h2>' + esc( title ) + '</h2><button type="button" class="spb-modal-close"><span class="dashicons dashicons-no-alt"></span></button></div>';

		if ( showSearch ) {
			html += '<div class="spb-modal-search"><input type="text" class="spb-modal-search-input" placeholder="' + esc( i18n.search || '' ) + '" /></div>';
		}

		html += '<div class="spb-modal-body spb-choice-grid">';
		items.forEach( function ( item ) {
			html += '<button type="button" class="spb-choice-item" data-value="' + esc( item.value ) + '" data-label="' + esc( ( item.label || '' ).toLowerCase() ) + '">';
			if ( item.svg ) {
				html += '<span class="spb-choice-visual">' + item.svg + '</span>';
			} else {
				html += '<span class="dashicons ' + esc( item.icon || 'dashicons-admin-generic' ) + '"></span>';
			}
			html += '<span class="spb-choice-label">' + esc( item.label ) + '</span>';
			html += '</button>';
		} );
		html += '</div></div></div>';

		var $modal = $( html ).appendTo( 'body' );

		$modal.on( 'click', '.spb-modal-close', function () {
			$modal.remove();
		} );
		$modal.on( 'click', function ( e ) {
			if ( e.target === this ) {
				$modal.remove();
			}
		} );
		$modal.on( 'click', '.spb-choice-item', function () {
			var val = $( this ).data( 'value' );
			$modal.remove();
			onPick( val );
		} );

		if ( showSearch ) {
			$modal.find( '.spb-modal-search-input' ).on( 'keyup', function () {
				var q = $( this ).val().toLowerCase();
				$modal.find( '.spb-choice-item' ).each( function () {
					var match = String( $( this ).data( 'label' ) ).indexOf( q ) > -1;
					$( this ).toggle( match );
				} );
			} );
			window.setTimeout( function () {
				$modal.find( '.spb-modal-search-input' ).trigger( 'focus' );
			}, 50 );
		}
	}

	function layoutSvg( widths ) {
		var svg = '<svg viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">';
		var x = 0;
		widths.forEach( function ( w ) {
			var wpx = ( w / 12 ) * 116;
			svg += '<rect x="' + ( x + 2 ) + '" y="2" width="' + ( wpx - 4 ) + '" height="36" rx="2" fill="#c3d8ef" stroke="#2d6ab0" stroke-width="1"></rect>';
			x += wpx;
		} );
		svg += '</svg>';
		return svg;
	}

	function openAddRowModal() {
		var items = Object.keys( cfg.layouts ).map( function ( key ) {
			return { value: key, label: key.replace( /-/g, ' / ' ), svg: layoutSvg( cfg.layouts[ key ] ) };
		} );

		openChoiceModal( i18n.chooseLayout || '', items, function ( layoutKey ) {
			var widths = cfg.layouts[ layoutKey ];
			var row = {
				id: uid( 'row' ),
				layout: layoutKey,
				settings: defaultsForFields( cfg.rowFields ),
				columns: widths.map( function () {
					return { elements: [] };
				} )
			};
			spbData.rows.push( row );
			render();
		} );
	}

	function openAddElementModal( rowId, colIndex ) {
		var items = Object.keys( cfg.elements ).map( function ( type ) {
			var def = cfg.elements[ type ];
			return { value: type, label: def.label, icon: def.icon };
		} );

		openChoiceModal( i18n.chooseElement || '', items, function ( type ) {
			var row = findRowById( rowId );
			if ( ! row ) {
				return;
			}
			var def = cfg.elements[ type ];
			var el = {
				id: uid( 'el' ),
				type: type,
				settings: defaultsForFields( def.fields )
			};
			row.columns[ colIndex ].elements.push( el );
			render();
			openSettingsPanel( 'element', el.id );
		} );
	}

	function openIconPickerModal( onPick ) {
		var items = Object.keys( cfg.icons ).map( function ( key ) {
			return { value: key, label: cfg.icons[ key ], icon: key };
		} );
		openChoiceModal( i18n.chooseIcon || '', items, onPick );
	}

	function defaultsForFields( fieldDefs ) {
		var out = {};
		if ( ! fieldDefs ) {
			return out;
		}
		Object.keys( fieldDefs ).forEach( function ( key ) {
			out[ key ] = fieldDefs[ key ].default;
		} );
		return out;
	}

	/* ------------------------------------------------------------------ */
	/* Panneau lateral de reglages                                        */
	/* ------------------------------------------------------------------ */

	function buildFieldHtml( key, def, value ) {
		var id = 'spb-field-' + key;
		var html = '<div class="spb-field spb-field-' + def.type + '" data-field-key="' + key + '">';
		html += '<label for="' + id + '">' + esc( def.label ) + '</label>';

		switch ( def.type ) {
			case 'text':
			case 'url':
				html += '<input type="text" id="' + id + '" data-key="' + key + '" value="' + esc( value || '' ) + '" placeholder="' + esc( def.placeholder || '' ) + '" />';
				break;

			case 'number':
				html += '<input type="number" id="' + id + '" data-key="' + key + '" value="' + esc( value ) + '" />';
				break;

			case 'textarea':
				html += '<textarea id="' + id + '" data-key="' + key + '" rows="4" placeholder="' + esc( def.placeholder || '' ) + '">' + esc( value || '' ) + '</textarea>';
				break;

			case 'richtext':
				html += '<div class="spb-richtext-toolbar">';
				html += '<select class="spb-rt-format" title="Format">' +
					'<option value="P">Paragraphe</option>' +
					'<option value="H2">Titre H2</option>' +
					'<option value="H3">Titre H3</option>' +
					'<option value="H4">Titre H4</option>' +
					'<option value="BLOCKQUOTE">Citation</option>' +
					'</select>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<button type="button" data-cmd="bold" title="Gras"><span class="dashicons dashicons-editor-bold"></span></button>';
				html += '<button type="button" data-cmd="italic" title="Italique"><span class="dashicons dashicons-editor-italic"></span></button>';
				html += '<button type="button" data-cmd="underline" title="Souligne"><span class="dashicons dashicons-editor-underline"></span></button>';
				html += '<button type="button" data-cmd="strikeThrough" title="Barre"><span class="dashicons dashicons-editor-strikethrough"></span></button>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<label class="spb-rt-color-label" title="Couleur du texte"><span class="dashicons dashicons-editor-textcolor"></span><input type="color" class="spb-rt-forecolor" value="#000000" /></label>';
				html += '<label class="spb-rt-color-label" title="Couleur de surlignage"><span class="dashicons dashicons-admin-customizer"></span><input type="color" class="spb-rt-backcolor" value="#ffff00" /></label>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<button type="button" data-cmd="justifyLeft" title="Aligner a gauche"><span class="dashicons dashicons-editor-alignleft"></span></button>';
				html += '<button type="button" data-cmd="justifyCenter" title="Centrer"><span class="dashicons dashicons-editor-aligncenter"></span></button>';
				html += '<button type="button" data-cmd="justifyRight" title="Aligner a droite"><span class="dashicons dashicons-editor-alignright"></span></button>';
				html += '<button type="button" data-cmd="justifyFull" title="Justifier"><span class="dashicons dashicons-editor-justify"></span></button>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<button type="button" data-cmd="insertUnorderedList" title="Liste a puces"><span class="dashicons dashicons-editor-ul"></span></button>';
				html += '<button type="button" data-cmd="insertOrderedList" title="Liste numerotee"><span class="dashicons dashicons-editor-ol"></span></button>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<button type="button" data-cmd="createLink" title="Inserer un lien"><span class="dashicons dashicons-admin-links"></span></button>';
				html += '<button type="button" data-cmd="unlink" title="Retirer le lien"><span class="dashicons dashicons-editor-unlink"></span></button>';
				html += '<button type="button" data-cmd="removeFormat" title="Effacer la mise en forme"><span class="dashicons dashicons-editor-removeformatting"></span></button>';
				html += '<span class="spb-rt-sep"></span>';
				html += '<button type="button" class="spb-rt-html-toggle" title="Editer le code HTML"><span class="dashicons dashicons-editor-code"></span></button>';
				html += '</div>';
				html += '<div class="spb-richtext-area" id="' + id + '" data-key="' + key + '" data-placeholder="' + esc( def.placeholder || '' ) + '" contenteditable="true">' + ( value || '' ) + '</div>';
				html += '<textarea class="spb-richtext-html" rows="8" style="display:none;">' + esc( value || '' ) + '</textarea>';
				break;

			case 'select':
				html += '<select id="' + id + '" data-key="' + key + '">';
				Object.keys( def.options ).forEach( function ( optKey ) {
					var sel = optKey === value ? ' selected' : '';
					html += '<option value="' + esc( optKey ) + '"' + sel + '>' + esc( def.options[ optKey ] ) + '</option>';
				} );
				html += '</select>';
				break;

			case 'color':
				html += '<input type="text" class="spb-color-field" id="' + id + '" data-key="' + key + '" value="' + esc( value || '' ) + '" />';
				break;

			case 'checkbox':
				var checked = value ? ' checked' : '';
				html += '<label class="spb-checkbox-label"><input type="checkbox" id="' + id + '" data-key="' + key + '"' + checked + ' /> ' + esc( def.label ) + '</label>';
				break;

			case 'icon':
				var iconVal = value || def.default;
				html += '<div class="spb-icon-field" data-key="' + key + '" data-value="' + esc( iconVal ) + '">';
				html += '<button type="button" class="spb-icon-field-btn"><span class="dashicons ' + esc( iconVal ) + '"></span><span class="spb-icon-field-name">' + esc( cfg.icons[ iconVal ] || iconVal ) + '</span></button>';
				html += '</div>';
				break;

			case 'repeater':
				var repItems = ( Array.isArray( value ) && value.length ) ? value : ( def.default || [] );
				repeaterFieldDefs[ key ] = def.item_fields || {};
				html += '<div class="spb-repeater" data-key="' + key + '">';
				html += '<div class="spb-repeater-items">';
				repItems.forEach( function ( item, idx ) {
					html += renderRepeaterItemHtml( def.item_fields || {}, item, idx );
				} );
				html += '</div>';
				html += '<button type="button" class="button spb-repeater-add"><span class="dashicons dashicons-plus-alt2"></span> ' + esc( i18n.addItem || 'Ajouter' ) + '</button>';
				html += '</div>';
				break;

			case 'image':
				html += '<div class="spb-image-field" data-key="' + key + '" data-value="' + ( value || 0 ) + '">';
				html += '<div class="spb-image-preview"></div>';
				html += '<button type="button" class="button spb-choose-image-btn">' + esc( cfg.mediaButton || i18n.chooseImage || '' ) + '</button> ';
				html += '<button type="button" class="button spb-remove-image-btn">' + esc( i18n.delete || '' ) + '</button>';
				html += '</div>';
				break;
		}

		html += '</div>';
		return html;
	}

	/**
	 * Construit le HTML d'un sous-champ a l'interieur d'un element de repeteur
	 * (version simplifiee de buildFieldHtml, pour les types les plus courants).
	 */
	function buildRepeaterSubfieldHtml( subkey, def, value ) {
		var html = '<div class="spb-subfield">';
		html += '<label>' + esc( def.label ) + '</label>';

		if ( 'textarea' === def.type ) {
			html += '<textarea data-subkey="' + subkey + '" rows="3" placeholder="' + esc( def.placeholder || '' ) + '">' + esc( value || '' ) + '</textarea>';
		} else if ( 'select' === def.type ) {
			html += '<select data-subkey="' + subkey + '">';
			Object.keys( def.options ).forEach( function ( ok ) {
				var sel = ok === value ? ' selected' : '';
				html += '<option value="' + esc( ok ) + '"' + sel + '>' + esc( def.options[ ok ] ) + '</option>';
			} );
			html += '</select>';
		} else if ( 'checkbox' === def.type ) {
			var checked = value ? ' checked' : '';
			html += '<label class="spb-checkbox-label"><input type="checkbox" data-subkey="' + subkey + '"' + checked + ' /> ' + esc( def.label ) + '</label>';
		} else if ( 'color' === def.type ) {
			html += '<input type="text" class="spb-color-field" data-subkey="' + subkey + '" value="' + esc( value || '' ) + '" />';
		} else {
			html += '<input type="text" data-subkey="' + subkey + '" value="' + esc( value || '' ) + '" placeholder="' + esc( def.placeholder || '' ) + '" />';
		}

		html += '</div>';
		return html;
	}

	function renderRepeaterItemHtml( itemFields, item, idx ) {
		item = item || {};
		var html = '<div class="spb-repeater-item">';
		html += '<div class="spb-repeater-item-head">';
		html += '<span class="spb-repeater-item-title">#' + ( idx + 1 ) + '</span>';
		html += '<span class="spb-repeater-item-actions">';
		html += '<button type="button" class="spb-icon-btn spb-repeater-up" title="Monter"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
		html += '<button type="button" class="spb-icon-btn spb-repeater-down" title="Descendre"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
		html += '<button type="button" class="spb-icon-btn spb-repeater-remove" title="Supprimer"><span class="dashicons dashicons-trash"></span></button>';
		html += '</span></div>';
		html += '<div class="spb-repeater-item-fields">';
		Object.keys( itemFields ).forEach( function ( fk ) {
			html += buildRepeaterSubfieldHtml( fk, itemFields[ fk ], item[ fk ] );
		} );
		html += '</div></div>';
		return html;
	}

	function reindexRepeaterDisplay( $items ) {
		$items.children( '.spb-repeater-item' ).each( function ( i ) {
			$( this ).find( '.spb-repeater-item-title' ).first().text( '#' + ( i + 1 ) );
		} );
	}

	function openSettingsPanel( kind, id ) {
		var target, fieldDefs, title, values;

		repeaterFieldDefs = {};

		if ( 'row' === kind ) {
			target = findRowById( id );
			fieldDefs = cfg.rowFields;
			title = i18n.rowSettings || '';
			values = target.settings;
		} else {
			target = findElementById( id );
			fieldDefs = cfg.elements[ target.type ].fields;
			title = cfg.elements[ target.type ].label;
			values = target.settings;
		}

		var html = '<div class="spb-panel-head"><h2>' + esc( title ) + '</h2><button type="button" class="spb-panel-close"><span class="dashicons dashicons-no-alt"></span></button></div>';
		html += '<div class="spb-panel-body">';
		Object.keys( fieldDefs ).forEach( function ( key ) {
			html += buildFieldHtml( key, fieldDefs[ key ], values[ key ] );
		} );
		html += '</div>';
		html += '<div class="spb-panel-footer"><button type="button" class="button button-primary spb-panel-apply">' + esc( i18n.save || 'Appliquer' ) + '</button></div>';

		$panel.html( html ).data( 'kind', kind ).data( 'id', id ).addClass( 'open' );
		$panelOverlay.addClass( 'open' );

		// Color pickers WordPress natifs (champs de type "color" du registre).
		$panel.find( '.spb-color-field' ).wpColorPicker();

		// Media uploader.
		var frame;
		$panel.find( '.spb-choose-image-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			var $field = $( this ).closest( '.spb-image-field' );
			frame = wp.media( { title: cfg.mediaTitle, button: { text: cfg.mediaButton }, multiple: false } );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$field.attr( 'data-value', att.id );
				var previewUrl = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
				$field.find( '.spb-image-preview' ).html( '<img src="' + previewUrl + '" />' );
			} );
			frame.open();
		} );
		$panel.find( '.spb-remove-image-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			var $field = $( this ).closest( '.spb-image-field' );
			$field.attr( 'data-value', 0 );
			$field.find( '.spb-image-preview' ).html( '' );
		} );

		// Charge les vignettes des images deja selectionnees (uniquement l'ID est stocke, on recupere l'URL via l'API media).
		$panel.find( '.spb-image-field' ).each( function () {
			var $field = $( this );
			var attId = parseInt( $field.attr( 'data-value' ), 10 );
			if ( attId > 0 ) {
				wp.media.attachment( attId ).fetch().done( function ( data ) {
					var url = data && data.sizes && data.sizes.medium ? data.sizes.medium.url : ( data ? data.url : '' );
					if ( url ) {
						$field.find( '.spb-image-preview' ).html( '<img src="' + url + '" />' );
					}
				} );
			}
		} );

		// Selecteur d'icone visuel.
		$panel.find( '.spb-icon-field-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			var $field = $( this ).closest( '.spb-icon-field' );
			openIconPickerModal( function ( iconClass ) {
				$field.attr( 'data-value', iconClass );
				$field.find( '.spb-icon-field-btn' ).html(
					'<span class="dashicons ' + iconClass + '"></span><span class="spb-icon-field-name">' + esc( cfg.icons[ iconClass ] || iconClass ) + '</span>'
				);
			} );
		} );

		// Neutralise les <br> isoles laisses par le navigateur dans les zones de texte enrichi vides.
		$panel.find( '.spb-richtext-area' ).each( function () {
			normalizeEmptyRichtext( $( this ) );
		} );

		// Bouton : affichage conditionnel du champ URL ou du selecteur de page selon le type de lien choisi.
		if ( fieldDefs.link_type ) {
			var $typeSelect = $panel.find( '[data-field-key="link_type"] select' );
			var toggleLinkFields = function () {
				var val = $typeSelect.val();
				$panel.find( '[data-field-key="url"]' ).toggle( 'url' === val );
				$panel.find( '[data-field-key="page_id"]' ).toggle( 'page' === val );
			};
			$typeSelect.on( 'change', toggleLinkFields );
			toggleLinkFields();
		}
	}

	function normalizeEmptyRichtext( $area ) {
		var content = $area.html().replace( /<br\s*\/?>/gi, '' ).replace( /&nbsp;/gi, '' ).trim();
		if ( '' === content ) {
			$area.empty();
		}
	}

	function closeSettingsPanel() {
		$panel.removeClass( 'open' );
		$panelOverlay.removeClass( 'open' );
	}

	function applySettingsPanel() {
		var kind = $panel.data( 'kind' );
		var id = $panel.data( 'id' );
		var target = 'row' === kind ? findRowById( id ) : findElementById( id );
		if ( ! target ) {
			closeSettingsPanel();
			return;
		}

		$panel.find( '.spb-field' ).each( function () {
			var $wrap = $( this );
			var $input = $wrap.find( '[data-key]' ).first();
			var key = $input.data( 'key' );
			if ( ! key ) {
				return;
			}

			if ( $input.hasClass( 'spb-richtext-area' ) ) {
				var $htmlArea = $wrap.find( '.spb-richtext-html' );
				if ( $htmlArea.length && $htmlArea.is( ':visible' ) ) {
					target.settings[ key ] = $htmlArea.val();
				} else {
					normalizeEmptyRichtext( $input );
					target.settings[ key ] = $input.html();
				}
			} else if ( $input.hasClass( 'spb-image-field' ) ) {
				target.settings[ key ] = parseInt( $input.attr( 'data-value' ), 10 ) || 0;
			} else if ( $input.hasClass( 'spb-icon-field' ) ) {
				target.settings[ key ] = $input.attr( 'data-value' );
			} else if ( $input.hasClass( 'spb-repeater' ) ) {
				var repItems = [];
				$input.find( '> .spb-repeater-items > .spb-repeater-item' ).each( function () {
					var itemObj = {};
					$( this ).find( '[data-subkey]' ).each( function () {
						var subkey = $( this ).data( 'subkey' );
						if ( 'checkbox' === this.type ) {
							itemObj[ subkey ] = $( this ).is( ':checked' );
						} else {
							itemObj[ subkey ] = $( this ).val();
						}
					} );
					repItems.push( itemObj );
				} );
				target.settings[ key ] = repItems;
			} else if ( 'checkbox' === $input.attr( 'type' ) ) {
				target.settings[ key ] = $input.is( ':checked' );
			} else {
				target.settings[ key ] = $input.val();
			}
		} );

		closeSettingsPanel();
		render();
	}

	/* ------------------------------------------------------------------ */
	/* Initialisation + delegation d'evenements                           */
	/* ------------------------------------------------------------------ */

	function toggleBuilderVisibility() {
		var enabled = $( '#spb_enabled' ).is( ':checked' );
		$app.toggle( enabled );
		// Masque l'editeur de contenu classique (WYSIWYG standard) quand le
		// constructeur visuel est actif, puisqu'il en tient lieu. Rien n'est
		// perdu : le contenu classique reste en place, simplement masque, et
		// reapparait si la case est decochee.
		$( '#postdivrich' ).toggle( ! enabled );
	}

	$( function () {
		$app = $( '#spb-builder-app' );
		$canvas = $( '#spb-canvas' );
		$hiddenField = $( '#spb_builder_data' );

		$panelOverlay = $( '<div class="spb-panel-overlay"></div>' ).appendTo( 'body' );
		$panel = $( '<div class="spb-side-panel"></div>' ).appendTo( 'body' );

		render();
		toggleBuilderVisibility();

		$( '#spb_enabled' ).on( 'change', toggleBuilderVisibility );

		$( '#spb-add-row-btn' ).on( 'click', openAddRowModal );

		$canvas.on( 'click', '.spb-add-element-btn', function () {
			openAddElementModal( $( this ).data( 'row-id' ), $( this ).data( 'col-index' ) );
		} );

		$canvas.on( 'click', '.spb-row-delete-btn', function () {
			var rowId = $( this ).closest( '.spb-row' ).data( 'row-id' );
			if ( window.confirm( i18n.confirmDeleteRow || '' ) ) {
				spbData.rows = spbData.rows.filter( function ( r ) {
					return r.id !== rowId;
				} );
				render();
			}
		} );

		$canvas.on( 'click', '.spb-row-settings-btn', function () {
			var rowId = $( this ).closest( '.spb-row' ).data( 'row-id' );
			openSettingsPanel( 'row', rowId );
		} );

		$canvas.on( 'click', '.spb-el-edit-btn', function () {
			var elId = $( this ).closest( '.spb-element' ).data( 'el-id' );
			openSettingsPanel( 'element', elId );
		} );

		$canvas.on( 'click', '.spb-el-del-btn', function () {
			var elId = $( this ).closest( '.spb-element' ).data( 'el-id' );
			if ( ! window.confirm( i18n.confirmDelete || '' ) ) {
				return;
			}
			spbData.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					col.elements = col.elements.filter( function ( el ) {
						return el.id !== elId;
					} );
				} );
			} );
			render();
		} );

		$canvas.on( 'click', '.spb-el-dup-btn', function () {
			var elId = $( this ).closest( '.spb-element' ).data( 'el-id' );
			var original = findElementById( elId );
			if ( ! original ) {
				return;
			}
			var copy = JSON.parse( JSON.stringify( original ) );
			copy.id = uid( 'el' );

			spbData.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					var idx = col.elements.findIndex( function ( el ) {
						return el.id === elId;
					} );
					if ( idx > -1 ) {
						col.elements.splice( idx + 1, 0, copy );
					}
				} );
			} );
			render();
		} );

		$panelOverlay.on( 'click', closeSettingsPanel );
		$( document ).on( 'click', '.spb-panel-close', closeSettingsPanel );
		$( document ).on( 'click', '.spb-panel-apply', applySettingsPanel );

		/* -------- Champ "repeteur" (ex : questions/reponses de l'accordeon) -------- */

		$( document ).on( 'click', '.spb-repeater-add', function ( e ) {
			e.preventDefault();
			var $repeater = $( this ).closest( '.spb-repeater' );
			var key = $repeater.data( 'key' );
			var itemFields = repeaterFieldDefs[ key ] || {};
			var $items = $repeater.find( '.spb-repeater-items' );
			var idx = $items.children( '.spb-repeater-item' ).length;
			var $newItem = $( renderRepeaterItemHtml( itemFields, {}, idx ) );
			$items.append( $newItem );
			$newItem.find( '.spb-color-field' ).wpColorPicker();
		} );

		$( document ).on( 'click', '.spb-repeater-remove', function ( e ) {
			e.preventDefault();
			var $items = $( this ).closest( '.spb-repeater-items' );
			$( this ).closest( '.spb-repeater-item' ).remove();
			reindexRepeaterDisplay( $items );
		} );

		$( document ).on( 'click', '.spb-repeater-up', function ( e ) {
			e.preventDefault();
			var $item = $( this ).closest( '.spb-repeater-item' );
			var $prev = $item.prev( '.spb-repeater-item' );
			if ( $prev.length ) {
				$item.insertBefore( $prev );
			}
			reindexRepeaterDisplay( $item.closest( '.spb-repeater-items' ) );
		} );

		$( document ).on( 'click', '.spb-repeater-down', function ( e ) {
			e.preventDefault();
			var $item = $( this ).closest( '.spb-repeater-item' );
			var $next = $item.next( '.spb-repeater-item' );
			if ( $next.length ) {
				$item.insertAfter( $next );
			}
			reindexRepeaterDisplay( $item.closest( '.spb-repeater-items' ) );
		} );

		/* -------- Barre d'outils du champ "texte enrichi" -------- */

		$( document ).on( 'click', '.spb-richtext-toolbar button[data-cmd]', function ( e ) {
			e.preventDefault();
			var cmd = $( this ).data( 'cmd' );
			var $area = $( this ).closest( '.spb-field' ).find( '.spb-richtext-area' );
			$area.trigger( 'focus' );

			if ( 'createLink' === cmd ) {
				var url = window.prompt( i18n.linkUrl || 'URL du lien :', 'https://' );
				if ( url ) {
					document.execCommand( 'createLink', false, url );
				}
			} else {
				document.execCommand( cmd, false, null );
			}
		} );

		$( document ).on( 'change', '.spb-rt-format', function () {
			var $area = $( this ).closest( '.spb-field' ).find( '.spb-richtext-area' );
			$area.trigger( 'focus' );
			document.execCommand( 'formatBlock', false, $( this ).val() );
		} );

		$( document ).on( 'click', '.spb-rt-html-toggle', function ( e ) {
			e.preventDefault();
			var $field = $( this ).closest( '.spb-field' );
			var $area = $field.find( '.spb-richtext-area' );
			var $htmlArea = $field.find( '.spb-richtext-html' );
			var $otherControls = $field.find( '.spb-richtext-toolbar button:not(.spb-rt-html-toggle), .spb-richtext-toolbar select, .spb-richtext-toolbar input' );

			if ( $area.is( ':visible' ) ) {
				// Bascule vers l'edition du code HTML.
				$htmlArea.val( $area.html() );
				$area.hide();
				$htmlArea.show();
				$otherControls.prop( 'disabled', true );
				$( this ).addClass( 'active' );
			} else {
				// Retour a l'edition visuelle : on reinjecte le code modifie.
				$area.html( $htmlArea.val() );
				normalizeEmptyRichtext( $area );
				$htmlArea.hide();
				$area.show();
				$otherControls.prop( 'disabled', false );
				$( this ).removeClass( 'active' );
			}
		} );

		$( document ).on( 'input', '.spb-rt-forecolor', function () {
			var $area = $( this ).closest( '.spb-field' ).find( '.spb-richtext-area' );
			$area.trigger( 'focus' );
			document.execCommand( 'foreColor', false, $( this ).val() );
		} );

		$( document ).on( 'input', '.spb-rt-backcolor', function () {
			var $area = $( this ).closest( '.spb-field' ).find( '.spb-richtext-area' );
			$area.trigger( 'focus' );
			try {
				document.execCommand( 'hiliteColor', false, $( this ).val() );
			} catch ( err ) {
				document.execCommand( 'backColor', false, $( this ).val() );
			}
		} );

		// Empeche la soumission accidentelle du formulaire WordPress en appuyant sur Entree dans un champ texte du panneau.
		$( document ).on( 'keydown', '.spb-side-panel input[type="text"]', function ( e ) {
			if ( 13 === e.which ) {
				e.preventDefault();
			}
		} );
	} );
} )( jQuery );
