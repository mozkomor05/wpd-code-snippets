<?php

/**
 * This file handles the editor preview setting
 *
 * @since   2.0
 * @package Code_Snippets
 */

/**
 * Load the CSS and JavaScript for the editor preview field
 */
function code_snippets_editor_settings_preview_assets() {
	$plugin = code_snippets();

	// Enqueue scripts for the editor preview
	code_snippets_enqueue_editor();

	// Enqueue the menu scripts
	wp_enqueue_script(
		'code-snippets-settings-menu',
		plugins_url( 'js/min/settings.js', $plugin->file ),
		array(), $plugin->version, true
	);

	// Extract the CodeMirror-specific editor settings
	$setting_fields = code_snippets_get_settings_fields();
	$editor_fields  = array();

	foreach ( $setting_fields['editor'] as $name => $field ) {
		if ( empty( $field['ace'] ) ) {
			continue;
		}

		$editor_fields[] = array(
			'name'       => $name,
			'type'       => $field['type'],
			'ace' => addslashes( $field['ace'] ),
		);
	}

	// Pass the saved options to the external JavaScript file
	$inline_script = 'var code_snippets_editor_atts = ' . code_snippets_get_editor_atts( array(), true ) . ';';
	$inline_script .= "\n" . 'var code_snippets_editor_settings = ' . wp_json_encode( $editor_fields ) . ';';

	wp_add_inline_script( 'code-snippets-settings-menu', $inline_script, 'before' );
}

/**
 * Render a theme select field
 *
 * @param array $atts
 */
function code_snippets_ace_theme_select_field( $atts ) {

	$saved_value = code_snippets_get_setting( $atts['section'], $atts['id'] );

	echo '<select name="code_snippets_settings[editor][theme]">';

	// print a dropdown entry for each theme
	foreach ( code_snippets_get_available_themes() as $theme ) {

		printf(
			'<option value="%s"%s>%s</option>',
			$theme,
			selected( $theme, $saved_value, false ),
			ucwords( str_replace( array( '-', '_' ), ' ', $theme ) )
		);
	}

	echo '</select>';
}

/**
 * Render a font select field
 *
 * @param array $atts
 */
function code_snippets_ace_font_select_field( $atts ) {
	$saved_value = code_snippets_get_setting( $atts['section'], $atts['id'] );

	echo '<select name="code_snippets_settings[editor][font_family]">';

	// print a dropdown entry for each font
	foreach ( code_snippets_get_available_fonts() as $font ) {

		printf(
			'<option value="%s"%s>%s</option>',
			$font,
			selected( $font, $saved_value, false ),
			ucwords( str_replace( '_', ' ', $font ) )
		);
	}

	echo '</select>';
}

/**
 * Render the editor preview setting
 */
function code_snippets_settings_editor_preview() {
	?>
    <div class="snippet-editor">
        <div id="code_snippets_editor_preview" spellcheck="false"></div>
    </div>
	<?php
}
