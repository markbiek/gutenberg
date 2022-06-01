<?php
/**
 * WP_Theme_JSON_6_1 class
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of structures that adhere to the theme.json spec.
 *
 * This class is for internal core usage and is not supposed to be used by extenders (plugins and/or themes).
 * This is a low-level API that may need to do breaking changes. Please,
 * use get_global_settings, get_global_styles, and get_global_stylesheet instead.
 *
 * @access private
 */
class WP_Theme_JSON_6_1 extends WP_Theme_JSON_6_0 {

	/**
	 * Whitelist which defines which pseudo selectors are enabled for
	 * which elements.
	 * Note: this will effect both top level and block level elements.
	 */
	const VALID_ELEMENT_PSEUDO_SELECTORS = array(
		'link' => array( ':hover', ':focus', ':active' ),
	);

	/**
	 * Metadata for style properties.
	 *
	 * Each element is a direct mapping from the CSS property name to the
	 * path to the value in theme.json & block attributes.
	 */
	const PROPERTIES_METADATA = array(
		'background'                 => array( 'color', 'gradient' ),
		'background-color'           => array( 'color', 'background' ),
		'border-radius'              => array( 'border', 'radius' ),
		'border-top-left-radius'     => array( 'border', 'radius', 'topLeft' ),
		'border-top-right-radius'    => array( 'border', 'radius', 'topRight' ),
		'border-bottom-left-radius'  => array( 'border', 'radius', 'bottomLeft' ),
		'border-bottom-right-radius' => array( 'border', 'radius', 'bottomRight' ),
		'border-color'               => array( 'border', 'color' ),
		'border-width'               => array( 'border', 'width' ),
		'border-style'               => array( 'border', 'style' ),
		'border-top-color'           => array( 'border', 'top', 'color' ),
		'border-top-width'           => array( 'border', 'top', 'width' ),
		'border-top-style'           => array( 'border', 'top', 'style' ),
		'border-right-color'         => array( 'border', 'right', 'color' ),
		'border-right-width'         => array( 'border', 'right', 'width' ),
		'border-right-style'         => array( 'border', 'right', 'style' ),
		'border-bottom-color'        => array( 'border', 'bottom', 'color' ),
		'border-bottom-width'        => array( 'border', 'bottom', 'width' ),
		'border-bottom-style'        => array( 'border', 'bottom', 'style' ),
		'border-left-color'          => array( 'border', 'left', 'color' ),
		'border-left-width'          => array( 'border', 'left', 'width' ),
		'border-left-style'          => array( 'border', 'left', 'style' ),
		'color'                      => array( 'color', 'text' ),
		'font-family'                => array( 'typography', 'fontFamily' ),
		'font-size'                  => array( 'typography', 'fontSize' ),
		'font-style'                 => array( 'typography', 'fontStyle' ),
		'font-weight'                => array( 'typography', 'fontWeight' ),
		'letter-spacing'             => array( 'typography', 'letterSpacing' ),
		'line-height'                => array( 'typography', 'lineHeight' ),
		'margin'                     => array( 'spacing', 'margin' ),
		'margin-top'                 => array( 'spacing', 'margin', 'top' ),
		'margin-right'               => array( 'spacing', 'margin', 'right' ),
		'margin-bottom'              => array( 'spacing', 'margin', 'bottom' ),
		'margin-left'                => array( 'spacing', 'margin', 'left' ),
		'padding'                    => array( 'spacing', 'padding' ),
		'padding-top'                => array( 'spacing', 'padding', 'top' ),
		'padding-right'              => array( 'spacing', 'padding', 'right' ),
		'padding-bottom'             => array( 'spacing', 'padding', 'bottom' ),
		'padding-left'               => array( 'spacing', 'padding', 'left' ),
		'text-decoration'            => array( 'typography', 'textDecoration' ),
		'text-transform'             => array( 'typography', 'textTransform' ),
		'filter'                     => array( 'filter', 'duotone' ),
	);

	const ELEMENTS = array(
		'link'   => 'a',
		'h1'     => 'h1',
		'h2'     => 'h2',
		'h3'     => 'h3',
		'h4'     => 'h4',
		'h5'     => 'h5',
		'h6'     => 'h6',
		'button' => '.wp-element-button, .wp-block-button__link', // We have the .wp-block-button__link class so that this will target older buttons that have been serialized.
	);

	const __EXPERIMENTAL_ELEMENT_CLASS_NAMES = array(
		'button' => 'wp-element-button',
	);

	/**
	 * Given an element name, returns a class name.
	 *
	 * @param string $element The name of the element.
	 *
	 * @return string The name of the class.
	 *
	 * @since 6.1.0
	 */
	public static function get_element_class_name( $element ) {
		return array_key_exists( $element, static::__EXPERIMENTAL_ELEMENT_CLASS_NAMES ) ? static::__EXPERIMENTAL_ELEMENT_CLASS_NAMES[ $element ] : '';
	}

	/**
	 * Sanitizes the input according to the schemas.
	 *
	 * @since 5.8.0
	 * @since 5.9.0 Added the `$valid_block_names` and `$valid_element_name` parameters.
	 *
	 * @param array $input               Structure to sanitize.
	 * @param array $valid_block_names   List of valid block names.
	 * @param array $valid_element_names List of valid element names.
	 * @return array The sanitized output.
	 */
	protected static function sanitize( $input, $valid_block_names, $valid_element_names ) {

		$output = array();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		// Preserve only the top most level keys.
		$output = array_intersect_key( $input, array_flip( static::VALID_TOP_LEVEL_KEYS ) );

		// Remove any rules that are annotated as "top" in VALID_STYLES constant.
		// Some styles are only meant to be available at the top-level (e.g.: blockGap),
		// hence, the schema for blocks & elements should not have them.
		$styles_non_top_level = static::VALID_STYLES;
		foreach ( array_keys( $styles_non_top_level ) as $section ) {
			foreach ( array_keys( $styles_non_top_level[ $section ] ) as $prop ) {
				if ( 'top' === $styles_non_top_level[ $section ][ $prop ] ) {
					unset( $styles_non_top_level[ $section ][ $prop ] );
				}
			}
		}

		// Build the schema based on valid block & element names.
		$schema                 = array();
		$schema_styles_elements = array();

		// Set allowed element pseudo selectors based on per element allow list.
		// Target data structure in schema:
		// e.g.
		// - top level elements: `$schema['styles']['elements']['link'][':hover']`.
		// - block level elements: `$schema['styles']['blocks']['core/button']['elements']['link'][':hover']`.
		foreach ( $valid_element_names as $element ) {
			$schema_styles_elements[ $element ] = $styles_non_top_level;

			if ( array_key_exists( $element, static::VALID_ELEMENT_PSEUDO_SELECTORS ) ) {
				foreach ( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $element ] as $pseudo_selector ) {
					$schema_styles_elements[ $element ][ $pseudo_selector ] = $styles_non_top_level;
				}
			}
		}

		$schema_styles_blocks   = array();
		$schema_settings_blocks = array();
		foreach ( $valid_block_names as $block ) {
			$schema_settings_blocks[ $block ]           = static::VALID_SETTINGS;
			$schema_styles_blocks[ $block ]             = $styles_non_top_level;
			$schema_styles_blocks[ $block ]['elements'] = $schema_styles_elements;
		}

		$schema['styles']             = static::VALID_STYLES;
		$schema['styles']['blocks']   = $schema_styles_blocks;
		$schema['styles']['elements'] = $schema_styles_elements;
		$schema['settings']           = static::VALID_SETTINGS;
		$schema['settings']['blocks'] = $schema_settings_blocks;

		// Remove anything that's not present in the schema.
		foreach ( array( 'styles', 'settings' ) as $subtree ) {
			if ( ! isset( $input[ $subtree ] ) ) {
				continue;
			}

			if ( ! is_array( $input[ $subtree ] ) ) {
				unset( $output[ $subtree ] );
				continue;
			}

			$result = static::remove_keys_not_in_schema( $input[ $subtree ], $schema[ $subtree ] );

			if ( empty( $result ) ) {
				unset( $output[ $subtree ] );
			} else {
				$output[ $subtree ] = $result;
			}
		}

		return $output;
	}

	/**
	 * Removes insecure data from theme.json.
	 *
	 * @since 5.9.0
	 *
	 * @param array $theme_json Structure to sanitize.
	 * @return array Sanitized structure.
	 */
	public static function remove_insecure_properties( $theme_json ) {
		$sanitized = array();

		$theme_json = WP_Theme_JSON_Schema::migrate( $theme_json );

		$valid_block_names   = array_keys( static::get_blocks_metadata() );
		$valid_element_names = array_keys( static::ELEMENTS );

		$theme_json = static::sanitize( $theme_json, $valid_block_names, $valid_element_names );

		$blocks_metadata = static::get_blocks_metadata();
		$style_nodes     = static::get_style_nodes( $theme_json, $blocks_metadata );

		foreach ( $style_nodes as $metadata ) {
			$input = _wp_array_get( $theme_json, $metadata['path'], array() );
			if ( empty( $input ) ) {
				continue;
			}

			$output = static::remove_insecure_styles( $input );

			// Get a reference to element name from path.
			// $metadata['path'] = array('styles','elements','link');.
			$current_element = $metadata['path'][ count( $metadata['path'] ) - 1 ];

			// $output is stripped of pseudo selectors. Readd and process them
			// for insecure styles here.
			if ( isset( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $current_element ] ) ) {

				foreach ( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $current_element ] as $pseudo_selector ) {
					if ( isset( $input[ $pseudo_selector ] ) ) {
						$output[ $pseudo_selector ] = static::remove_insecure_styles( $input[ $pseudo_selector ] );
					}
				}
			}

			if ( ! empty( $output ) ) {
				_wp_array_set( $sanitized, $metadata['path'], $output );
			}
		}

		$setting_nodes = static::get_setting_nodes( $theme_json );
		foreach ( $setting_nodes as $metadata ) {
			$input = _wp_array_get( $theme_json, $metadata['path'], array() );
			if ( empty( $input ) ) {
				continue;
			}

			$output = static::remove_insecure_settings( $input );
			if ( ! empty( $output ) ) {
				_wp_array_set( $sanitized, $metadata['path'], $output );
			}
		}

		if ( empty( $sanitized['styles'] ) ) {
			unset( $theme_json['styles'] );
		} else {
			$theme_json['styles'] = $sanitized['styles'];
		}

		if ( empty( $sanitized['settings'] ) ) {
			unset( $theme_json['settings'] );
		} else {
			$theme_json['settings'] = $sanitized['settings'];
		}

		return $theme_json;
	}

	/**
	 * The valid properties under the settings key.
	 *
	 * @var array
	 */
	const VALID_SETTINGS = array(
		'appearanceTools' => null,
		'border'          => array(
			'color'  => null,
			'radius' => null,
			'style'  => null,
			'width'  => null,
		),
		'color'           => array(
			'background'       => null,
			'custom'           => null,
			'customDuotone'    => null,
			'customGradient'   => null,
			'defaultDuotone'   => null,
			'defaultGradients' => null,
			'defaultPalette'   => null,
			'duotone'          => null,
			'gradients'        => null,
			'link'             => null,
			'palette'          => null,
			'text'             => null,
		),
		'custom'          => null,
		'layout'          => array(
			'contentSize' => null,
			'definitions' => null,
			'wideSize'    => null,
		),
		'spacing'         => array(
			'blockGap' => null,
			'margin'   => null,
			'padding'  => null,
			'units'    => null,
		),
		'typography'      => array(
			'customFontSize' => null,
			'dropCap'        => null,
			'fontFamilies'   => null,
			'fontSizes'      => null,
			'fontStyle'      => null,
			'fontWeight'     => null,
			'letterSpacing'  => null,
			'lineHeight'     => null,
			'textDecoration' => null,
			'textTransform'  => null,
		),
	);

	/**
	 * The valid properties under the styles key.
	 *
	 * @var array
	 */
	const VALID_STYLES = array(
		'border'     => array(
			'color'  => null,
			'radius' => null,
			'style'  => null,
			'width'  => null,
			'top'    => null,
			'right'  => null,
			'bottom' => null,
			'left'   => null,
		),
		'color'      => array(
			'background' => null,
			'gradient'   => null,
			'text'       => null,
		),
		'filter'     => array(
			'duotone' => null,
		),
		'spacing'    => array(
			'margin'   => null,
			'padding'  => null,
			'blockGap' => null,
		),
		'typography' => array(
			'fontFamily'     => null,
			'fontSize'       => null,
			'fontStyle'      => null,
			'fontWeight'     => null,
			'letterSpacing'  => null,
			'lineHeight'     => null,
			'textDecoration' => null,
			'textTransform'  => null,
		),
	);

	/**
	 * Returns the metadata for each block.
	 *
	 * Example:
	 *
	 *     {
	 *       'core/paragraph': {
	 *         'selector': 'p',
	 *         'elements': {
	 *           'link' => 'link selector',
	 *           'etc'  => 'element selector'
	 *         }
	 *       },
	 *       'core/heading': {
	 *         'selector': 'h1',
	 *         'elements': {}
	 *       },
	 *       'core/image': {
	 *         'selector': '.wp-block-image',
	 *         'duotone': 'img',
	 *         'elements': {}
	 *       }
	 *     }
	 *
	 * @return array Block metadata.
	 */
	protected static function get_blocks_metadata() {
		if ( null !== static::$blocks_metadata ) {
			return static::$blocks_metadata;
		}

		static::$blocks_metadata = array();

		$registry = WP_Block_Type_Registry::get_instance();
		$blocks   = $registry->get_all_registered();
		foreach ( $blocks as $block_name => $block_type ) {
			if (
				isset( $block_type->supports['__experimentalSelector'] ) &&
				is_string( $block_type->supports['__experimentalSelector'] )
			) {
				static::$blocks_metadata[ $block_name ]['selector'] = $block_type->supports['__experimentalSelector'];
			} else {
				static::$blocks_metadata[ $block_name ]['selector'] = '.wp-block-' . str_replace( '/', '-', str_replace( 'core/', '', $block_name ) );
			}

			if (
				isset( $block_type->supports['color']['__experimentalDuotone'] ) &&
				is_string( $block_type->supports['color']['__experimentalDuotone'] )
			) {
				static::$blocks_metadata[ $block_name ]['duotone'] = $block_type->supports['color']['__experimentalDuotone'];
			}

			// Assign defaults, then overwrite those that the block sets by itself.
			// If the block selector is compounded, will append the element to each
			// individual block selector.
			$block_selectors = explode( ',', static::$blocks_metadata[ $block_name ]['selector'] );
			foreach ( static::ELEMENTS as $el_name => $el_selector ) {
				$element_selector = array();
				foreach ( $block_selectors as $selector ) {
					if ( $selector === $el_selector ) {
						$element_selector = array( $el_selector );
						break;
					}

					// This converts selectors like '.wp-element-button, .wp-block-button__link'
					// to an array, so that the block selector is added to both parts of the selector.
					$el_selectors = explode( ',', $el_selector );
					foreach ( $el_selectors as $el_selector_item ) {
						$element_selector[] = $selector . ' ' . $el_selector_item;
					}
				}
				static::$blocks_metadata[ $block_name ]['elements'][ $el_name ] = implode( ',', $element_selector );
			}
		}

		return static::$blocks_metadata;
	}

	/**
	 * Builds metadata for the style nodes, which returns in the form of:
	 *
	 *     [
	 *       [
	 *         'path'     => [ 'path', 'to', 'some', 'node' ],
	 *         'selector' => 'CSS selector for some node',
	 *         'duotone'  => 'CSS selector for duotone for some node'
	 *       ],
	 *       [
	 *         'path'     => ['path', 'to', 'other', 'node' ],
	 *         'selector' => 'CSS selector for other node',
	 *         'duotone'  => null
	 *       ],
	 *     ]
	 *
	 * @since 5.8.0
	 *
	 * @param array $theme_json The tree to extract style nodes from.
	 * @param array $selectors  List of selectors per block.
	 * @return array
	 */
	protected static function get_style_nodes( $theme_json, $selectors = array() ) {
		$nodes = array();
		if ( ! isset( $theme_json['styles'] ) ) {
			return $nodes;
		}

		// Top-level.
		$nodes[] = array(
			'path'     => array( 'styles' ),
			'selector' => static::ROOT_BLOCK_SELECTOR,
		);

		if ( isset( $theme_json['styles']['elements'] ) ) {

			foreach ( $theme_json['styles']['elements'] as $element => $node ) {

				// Handle element defaults.
				$nodes[] = array(
					'path'     => array( 'styles', 'elements', $element ),
					'selector' => static::ELEMENTS[ $element ],
				);

				// Handle any pseudo selectors for the element.
				if ( isset( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $element ] ) ) {
					foreach ( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $element ] as $pseudo_selector ) {

						if ( isset( $theme_json['styles']['elements'][ $element ][ $pseudo_selector ] ) ) {
							$nodes[] = array(
								'path'     => array( 'styles', 'elements', $element ),
								'selector' => static::ELEMENTS[ $element ] . $pseudo_selector,
							);
						}
					}
				}
			}
		}

		// Blocks.
		if ( ! isset( $theme_json['styles']['blocks'] ) ) {
			return $nodes;
		}

		$nodes = array_merge( $nodes, static::get_block_nodes( $theme_json, $selectors ) );

		// This filter allows us to modify the output of WP_Theme_JSON so that we can do things like loading block CSS independently.
		return apply_filters( 'gutenberg_get_style_nodes', $nodes );
	}

	/**
	 * A public helper to get the block nodes from a theme.json file.
	 *
	 * @return array The block nodes in theme.json.
	 */
	public function get_styles_block_nodes() {
		return static::get_block_nodes( $this->theme_json );
	}

	/**
	 * An internal method to get the block nodes from a theme.json file.
	 *
	 * @param array $theme_json The theme.json converted to an array.
	 * @param array $selectors  Optional list of selectors per block.
	 *
	 * @return array The block nodes in theme.json.
	 */
	private static function get_block_nodes( $theme_json, $selectors = array() ) {
		$selectors = empty( $selectors ) ? static::get_blocks_metadata() : $selectors;
		$nodes     = array();
		if ( ! isset( $theme_json['styles'] ) ) {
			return $nodes;
		}

		// Blocks.
		if ( ! isset( $theme_json['styles']['blocks'] ) ) {
			return $nodes;
		}

		foreach ( $theme_json['styles']['blocks'] as $name => $node ) {
			$selector = null;
			if ( isset( $selectors[ $name ]['selector'] ) ) {
				$selector = $selectors[ $name ]['selector'];
			}

			$duotone_selector = null;
			if ( isset( $selectors[ $name ]['duotone'] ) ) {
				$duotone_selector = $selectors[ $name ]['duotone'];
			}

			$nodes[] = array(
				'name'     => $name,
				'path'     => array( 'styles', 'blocks', $name ),
				'selector' => $selector,
				'duotone'  => $duotone_selector,
			);

			if ( isset( $theme_json['styles']['blocks'][ $name ]['elements'] ) ) {
				foreach ( $theme_json['styles']['blocks'][ $name ]['elements'] as $element => $node ) {
					$nodes[] = array(
						'path'     => array( 'styles', 'blocks', $name, 'elements', $element ),
						'selector' => $selectors[ $name ]['elements'][ $element ],
					);

					// Handle any psuedo selectors for the element.
					if ( isset( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $element ] ) ) {
						foreach ( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $element ] as $pseudo_selector ) {
							if ( isset( $theme_json['styles']['blocks'][ $name ]['elements'][ $element ][ $pseudo_selector ] ) ) {
								$nodes[] = array(
									'path'     => array( 'styles', 'blocks', $name, 'elements', $element ),
									'selector' => $selectors[ $name ]['elements'][ $element ] . $pseudo_selector,
								);
							}
						}
					}
				}
			}
		}

		return $nodes;
	}

	/**
	 * Gets the CSS rules for a particular block from theme.json.
	 *
	 * @param array $block_metadata Metadata about the block to get styles for.
	 *
	 * @return string Styles for the block.
	 */
	public function get_styles_for_block( $block_metadata ) {

		$node = _wp_array_get( $this->theme_json, $block_metadata['path'], array() );

		$selector = $block_metadata['selector'];
		$settings = _wp_array_get( $this->theme_json, array( 'settings' ) );

		// Attempt to parse a pseudo selector (e.g. ":hover") from the $selector ("a:hover").
		$pseudo_matches = array();
		preg_match( '/:[a-z]+/', $selector, $pseudo_matches );
		$pseudo_selector = isset( $pseudo_matches[0] ) ? $pseudo_matches[0] : null;

		// Get a reference to element name from path.
		// $block_metadata['path'] = array('styles','elements','link');
		// Make sure that $block_metadata['path'] describes an element node, like ['styles', 'element', 'link'].
		// Skip non-element paths like just ['styles'].
		$is_processing_element = in_array( 'elements', $block_metadata['path'], true );

		$current_element = $is_processing_element ? $block_metadata['path'][ count( $block_metadata['path'] ) - 1 ] : null;

		// If the current selector is a pseudo selector that's defined in the allow list for the current
		// element then compute the style properties for it.
		// Otherwise just compute the styles for the default selector as normal.
		if ( $pseudo_selector && isset( $node[ $pseudo_selector ] ) && isset( static::VALID_ELEMENT_PSEUDO_SELECTORS[ $current_element ] ) && in_array( $pseudo_selector, static::VALID_ELEMENT_PSEUDO_SELECTORS[ $current_element ], true ) ) {
			$declarations = static::compute_style_properties( $node[ $pseudo_selector ], $settings );
		} else {
			$declarations = static::compute_style_properties( $node, $settings );
		}

		$block_rules = '';

		// 1. Separate the ones who use the general selector
		// and the ones who use the duotone selector.
		$declarations_duotone = array();
		foreach ( $declarations as $index => $declaration ) {
			if ( 'filter' === $declaration['name'] ) {
				unset( $declarations[ $index ] );
				$declarations_duotone[] = $declaration;
			}
		}

		/*
		 * Reset default browser margin on the root body element.
		 * This is set on the root selector **before** generating the ruleset
		 * from the `theme.json`. This is to ensure that if the `theme.json` declares
		 * `margin` in its `spacing` declaration for the `body` element then these
		 * user-generated values take precedence in the CSS cascade.
		 * @link https://github.com/WordPress/gutenberg/issues/36147.
		 */
		if ( static::ROOT_BLOCK_SELECTOR === $selector ) {
			$block_rules .= 'body { margin: 0; }';
		}

		// 2. Generate and append the rules that use the general selector.
		$block_rules .= static::to_ruleset( $selector, $declarations );

		// 3. Generate and append the rules that use the duotone selector.
		if ( isset( $block_metadata['duotone'] ) && ! empty( $declarations_duotone ) ) {
			$selector_duotone = static::scope_selector( $block_metadata['selector'], $block_metadata['duotone'] );
			$block_rules     .= static::to_ruleset( $selector_duotone, $declarations_duotone );
		}

		// 4. Generate Layout block gap styles.
		$has_block_gap_support = _wp_array_get( $this->theme_json, array( 'settings', 'spacing', 'blockGap' ) ) !== null;
		$has_block_gap_value   = _wp_array_get( $node, array( 'spacing', 'blockGap' ), false );

		if (
			static::ROOT_BLOCK_SELECTOR !== $selector &&
			$has_block_gap_support &&
			$has_block_gap_value &&
			! empty( $metadata['name'] )
		) {
			$block_rules .= $this->get_layout_styles( $metadata );
		}

		if ( static::ROOT_BLOCK_SELECTOR === $selector ) {
			$block_rules .= '.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }';
			$block_rules .= '.wp-site-blocks > .alignright { float: right; margin-left: 2em; }';
			$block_rules .= '.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }';

			$has_block_gap_support = _wp_array_get( $this->theme_json, array( 'settings', 'spacing', 'blockGap' ) ) !== null;
			if ( $has_block_gap_support ) {
				$block_rules .= '.wp-site-blocks > * { margin-block-start: 0; margin-block-end: 0; }';
				$block_rules .= '.wp-site-blocks > * + * { margin-block-start: var( --wp--style--block-gap ); }';
				// For backwards compatibility, ensure the legacy block gap CSS variable is still available.
				$block_rules .= "$selector { --wp--style--block-gap: $block_gap_value; }";
			}
			$block_rules .= $this->get_layout_styles( $metadata );
		}

		return $block_rules;
	}

	/**
	 * Converts each style section into a list of rulesets
	 * containing the block styles to be appended to the stylesheet.
	 *
	 * See glossary at https://developer.mozilla.org/en-US/docs/Web/CSS/Syntax
	 *
	 * For each section this creates a new ruleset such as:
	 *
	 *   block-selector {
	 *     style-property-one: value;
	 *   }
	 *
	 * @param array $style_nodes Nodes with styles.
	 * @return string The new stylesheet.
	 */
	protected function get_block_classes( $style_nodes ) {
		$block_rules = '';

		foreach ( $style_nodes as $metadata ) {
			if ( null === $metadata['selector'] ) {
				continue;
			}
			$block_rules .= static::get_styles_for_block( $metadata );
		}

		return $block_rules;
	}

	/**
	 * Get the CSS layout rules for a particular block from theme.json.
	 *
	 * @param array $block_metadata Metadata about the block to get styles for.
	 *
	 * @return string Layout styles for the block.
	 */
	protected function get_layout_styles( $block_metadata ) {
		$block_rules           = '';
		$selector              = isset( $block_metadata['selector'] ) ? $block_metadata['selector'] : '';
		$has_block_gap_support = _wp_array_get( $this->theme_json, array( 'settings', 'spacing', 'blockGap' ) ) !== null;
		$node                  = _wp_array_get( $this->theme_json, $block_metadata['path'], array() );

		// TODO: We might still need to output rules if blockGap isn't enabled — for example, for fallback gap on flex layout blocks.
		if ( $has_block_gap_support ) {
			$fallback_gap_value = '0.5em';
			$block_gap_value    = _wp_array_get( $node, array( 'spacing', 'blockGap' ), $fallback_gap_value );

			// Support split row / column values and concatenate to a shorthand value.
			if ( is_array( $block_gap_value ) ) {
				$gap_row         = isset( $block_gap_value['top'] ) ? $block_gap_value['top'] : $fallback_gap_value;
				$gap_column      = isset( $block_gap_value['left'] ) ? $block_gap_value['left'] : $fallback_gap_value;
				$block_gap_value = $gap_row === $gap_column ? $gap_row : $gap_row . ' ' . $gap_column;
			}
			$layout_definitions = _wp_array_get( $this->theme_json, array( 'settings', 'layout', 'definitions' ), array() );

			if ( $block_gap_value ) {
				foreach ( $layout_definitions as $layout_definition ) {
					$declarations        = array();
					$class_name          = _wp_array_get( $layout_definition, array( 'className' ), false );
					$block_gap_selector  = _wp_array_get( $layout_definition, array( 'blockGapSelector' ), '' );
					$block_gap_prop      = _wp_array_get( $layout_definition, array( 'blockGapProp' ), false );

					if (
						is_string( $class_name ) &&
						is_string( $block_gap_prop ) &&
						is_string( $block_gap_selector )
					) {
						$declarations[]  = array(
							'name'  => $block_gap_prop,
							'value' => $block_gap_value,
						);
						$format          = static::ROOT_BLOCK_SELECTOR === $selector ? '%s .%s%s' : '%s.%s%s';
						$layout_selector = sprintf(
							$format,
							$selector,
							$class_name,
							$block_gap_selector
						);
						$block_rules    .= static::to_ruleset( $layout_selector, $declarations );
					}
				}
			}
		}
		return $block_rules;
	}
}
