<?php

/**
 * Get the attributes for the code editor
 *
 * @param array $override_atts Pass an array of attributes to override the saved ones.
 * @param bool  $json_encode   Encode the data as JSON.
 *
 * @return array|string Array if $json_encode is false, JSON string if it is true
 */
function code_snippets_get_editor_atts( array $override_atts, bool $json_encode ) {
	// default attributes for the Ace editor
	$default_atts = array();

	// add relevant saved setting values to the default attributes
	$settings = code_snippets_get_settings();
	$fields   = code_snippets_get_settings_fields();

	foreach ( $fields['editor'] as $field_id => $field ) {
		$default_atts[ $field['ace'] ] = $settings['editor'][ $field_id ];
	}

	$atts = wp_parse_args( $default_atts, $override_atts );
	$atts = apply_filters( 'code_snippets_ace_atts', $atts );
	$atts = wp_json_encode( $atts, JSON_UNESCAPED_SLASHES );

	return $atts;
}

/**
 * Register and load the CodeMirror library
 *
 * @uses wp_enqueue_style() to add the stylesheets to the queue
 * @uses wp_enqueue_script() to add the scripts to the queue
 */
function code_snippets_enqueue_editor() {
	$url            = plugin_dir_url( CODE_SNIPPETS_FILE );
	$plugin_version = code_snippets()->version;

	/* AceEditor */
	wp_enqueue_style( 'code-snippets-editor', $url . 'css/min/editor.css', array(), $plugin_version );
	wp_enqueue_script( 'code-snippets-editor-ace', $url . 'js/ace/ace.js', array(), $plugin_version, true );
	wp_enqueue_script( 'code-snippets-editor-ace-lang', $url . 'js/ace/ext-language_tools.js', array( 'code-snippets-editor-ace' ), $plugin_version, true );
	wp_enqueue_script( 'code-snippets-editor-ace-beautify', $url . 'js/ace/ext-beautify.js', array( 'code-snippets-editor-ace' ), $plugin_version, true );
}

/**
 * Get the list of available ACE themes
 *
 * @returns array
 */
function code_snippets_get_available_themes() {
	static $themes = null;

	if ( ! is_null( $themes ) ) {
		return $themes;
	}

	$themes       = array();
	$ace_dir      = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'js/ace/';
	$theme_prefix = 'theme-';
	$theme_files  = glob( $ace_dir . $theme_prefix . '*.js' );

	foreach ( $theme_files as $i => $theme ) {
		$theme    = basename( $theme, '.js' );
		$theme    = substr( $theme, strlen( $theme_prefix ) );
		$themes[] = $theme;
	}

	return $themes;
}
