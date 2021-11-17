<?php

/**
 * Retrieve the default setting values
 *
 * @return array
 */
function code_snippets_get_default_settings() {
	static $defaults;

	if ( isset( $defaults ) ) {
		return $defaults;
	}

	$defaults = array();

	foreach ( code_snippets_get_settings_fields() as $section_id => $fields ) {
		$defaults[ $section_id ] = array();

		foreach ( $fields as $field_id => $field_atts ) {
			$defaults[ $section_id ][ $field_id ] = $field_atts['default'];
		}
	}

	return $defaults;
}

/**
 * Retrieve the settings fields
 *
 * @return array
 */
function code_snippets_get_settings_fields() {
	static $fields;

	if ( isset( $fields ) ) {
		return $fields;
	}

	$fields = array();

	$fields['general'] = array(
		'activate_by_default' => array(
			'name'    => __( 'Activate by Default', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( "Make the 'Save and Activate' button the default action when saving a snippet.", 'code-snippets' ),
			'default' => true,
		),

		'disable_prism' => array(
			'name'    => __( 'Disable Shortcode Syntax Highlighter', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Disable the syntax highlighting for the [code_snippet] shortcode on the front-end', 'code-snippets' ),
			'default' => false,
		),

		'complete_uninstall' => array(
			'name'    => __( 'Complete Uninstall', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => sprintf(
			/* translators: %s: URL for Plugins admin menu */
				__( 'When the plugin is deleted from the <a href="%s">Plugins</a> menu, also delete all snippets and plugin settings.', 'code-snippets' ),
				self_admin_url( 'plugins.php' )
			),
			'default' => false,
		),
	);

	if ( is_multisite() && ! is_main_site() ) {
		unset( $fields['general']['complete_uninstall'] );
	}

	/* Description Editor settings section */
	$fields['description_editor'] = array(

		'rows' => array(
			'name'    => __( 'Row Height', 'code-snippets' ),
			'type'    => 'number',
			'label'   => __( 'rows', 'code-snippets' ),
			'default' => 5,
			'min'     => 0,
		),

		'use_full_mce' => array(
			'name'    => __( 'Use Full Editor', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable all features of the visual editor', 'code-snippets' ),
			'default' => false,
		),

		'media_buttons' => array(
			'name'    => __( 'Media Buttons', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable the add media buttons', 'code-snippets' ),
			'default' => false,
		),
	);

	/* Code Editor settings section */

	$fields['editor'] = array(
		'theme' => array(
			'name'    => __( 'Theme', 'code-snippets' ),
			'type'    => 'ace_theme_select',
			'default' => 'iplastic',
			'ace'     => 'theme',
		),

		'font_size' => array(
			'name'    => __( 'Font Size', 'code-snippets' ),
			'type'    => 'number',
			'label'   => __( 'px', 'code-snippets' ),
			'default' => 12,
			'min'     => 5,
			'ace'     => 'fontSize',
		),

		'highlight_active_line' => array(
			'name'    => __( 'Highlight Active Line', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable highlighting of the active line', 'code-snippets' ),
			'default' => true,
			'ace'     => 'highlightActiveLine',
		),

		'show_line_numbers' => array(
			'name'    => __( 'Show Line Numbers', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable showing of line numbers', 'code-snippets' ),
			'default' => true,
			'ace'     => 'showLineNumbers',
		),

		'relative_line_numbers' => array(
			'name'    => __( 'Relative Line Numbers', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable relative line numbers to active line', 'code-snippets' ),
			'default' => false,
			'ace'     => 'relativeLineNumbers',
		),

		'show_invisibles' => array(
			'name'    => __( 'Show Invisibles', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable invisible characters', 'code-snippets' ),
			'default' => false,
			'ace'     => 'showInvisibles',
		),

		'enable_auto_indent' => array(
			'name'    => __( 'Enable Auto Indent', 'code-snippets' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable auto adding the blank space in the beginning of an indented line', 'code-snippets' ),
			'default' => true,
			'ace'     => 'enableAutoIndent',
		),

		'font_family' => array(
			'name'    => __( 'Font Family', 'code-snippets' ),
			'type'    => 'ace_font_select',
			'default' => 'monospace',
			'ace'     => 'fontFamily',
		),

	);

	$fields = apply_filters( 'code_snippets_settings_fields', $fields );

	return $fields;
}
