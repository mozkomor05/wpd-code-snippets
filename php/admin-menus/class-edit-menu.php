<?php

/**
 * This class handles the add/edit menu
 */
class Code_Snippets_Edit_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * The snippet object currently being edited
	 *
	 * @var Code_Snippet
	 * @see Code_Snippets_Edit_Menu::load_snippet_data()
	 */
	protected $snippet = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'edit',
			_x( 'Edit Snippet', 'menu label', 'code-snippets' ),
			__( 'Edit Snippet', 'code-snippets' )
		);
	}

	public function run() {
		parent::run();

		if ( isset( $_REQUEST['result'] ) && $_REQUEST['result'] === 'code-error' ) {
			session_start();
		}
	}

	/**
	 * Register the admin menu
	 */
	public function register() {
		parent::register();

		/* Only preserve the edit menu if we are currently editing a snippet */
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->slug ) {
			remove_submenu_page( $this->base_slug, $this->slug );
		}

		/* Add New Snippet menu */
		$this->add_menu(
			code_snippets()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-snippets' ),
			__( 'Add New Snippet', 'code-snippets' )
		);
	}

	/**
	 * Executed when the menu is loaded
	 */
	public function load() {
		parent::load();

		// Retrieve the current snippet object
		$this->load_snippet_data();

		$screen    = get_current_screen();
		$edit_hook = get_plugin_page_hookname( $this->slug, $this->base_slug );
		if ( $screen->in_admin( 'network' ) ) {
			$edit_hook .= '-network';
		}

		/* Don't allow visiting the edit snippet page without a valid ID */
		if ( $screen->base === $edit_hook && ( ! isset( $_REQUEST['id'] ) || 0 === $this->snippet->id ) ) {
			wp_redirect( code_snippets()->get_menu_url( 'add' ) );
			exit;
		}

		/* Load the contextual help tabs */
		$contextual_help = new Code_Snippets_Contextual_Help( 'edit' );
		$contextual_help->load();

		/* Register action hooks */
		add_action( 'code_snippets/admin/single', array( $this, 'render_debug_console' ), 10 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_snippet_settings' ), 11 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_description_editor' ), 12 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_tags_editor' ), 13 );

		if ( is_network_admin() ) {
			add_action( 'code_snippets/admin/single', array( $this, 'render_multisite_sharing_setting' ), 1 );
		}

		$this->process_actions();
	}

	/**
	 * Load the data for the snippet currently being edited
	 */
	public function load_snippet_data() {
		$edit_id       = isset( $_REQUEST['id'] ) && intval( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
		$this->snippet = get_snippet( $edit_id );
	}

	/**
	 * Process data sent from the edit page
	 */
	private function process_actions() {

		/* Check for a valid nonce */
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'save_snippet' ) ) {
			return;
		}

		if ( isset( $_POST['save_snippet'] ) || isset( $_POST['save_snippet_execute'] ) ||
		     isset( $_POST['save_snippet_activate'] ) || isset( $_POST['save_snippet_deactivate'] ) ) {
			$this->save_posted_snippet();
		}

		if ( isset( $_POST['snippet_id'] ) ) {

			/* Delete the snippet if the button was clicked */
			if ( isset( $_POST['delete_snippet'] ) ) {
				delete_snippet( $_POST['snippet_id'] );
				wp_redirect( add_query_arg( 'result', 'delete', code_snippets()->get_menu_url( 'manage' ) ) );
				exit;
			}

			/* Export the snippet if the button was clicked */
			if ( isset( $_POST['export_snippet'] ) ) {
				export_snippets( array( $_POST['snippet_id'] ) );
			}

			/* Push the snippet if the button was clicked */
			if ( isset( $_POST['push_snippet'] ) ) {
				wpd_push_snippet( $_POST['snippet_id'] );
			}

			/* Download the snippet if the button was clicked */
			if ( isset( $_POST['download_snippet'] ) ) {
				download_snippets( array( $_POST['snippet_id'] ) );
			}
		}
	}

	/**
	 * Remove the sharing status from a network snippet
	 *
	 * @param int $snippet_id
	 */
	private function unshare_network_snippet( $snippet_id ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );

		if ( ! in_array( $snippet_id, $shared_snippets, true ) ) {
			return;
		}

		/* Remove the snippet ID from the array */
		$shared_snippets = array_diff( $shared_snippets, array( $snippet_id ) );
		update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );

		/* Deactivate on all sites */
		global $wpdb;
		if ( $sites = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) ) {

			foreach ( $sites as $site ) {
				switch_to_blog( $site );
				$active_shared_snippets = get_option( 'active_shared_network_snippets' );

				if ( is_array( $active_shared_snippets ) ) {
					$active_shared_snippets = array_diff( $active_shared_snippets, array( $snippet_id ) );
					update_option( 'active_shared_network_snippets', $active_shared_snippets );
				}
			}

			restore_current_blog();
		}
	}

	private function code_error_callback( $out ) {
		$error = error_get_last();

		if ( is_null( $error ) ) {
			return $out;
		}

		$m = '<h3>' . __( "Don't Panic", 'code-snippets' ) . '</h3>';
		/* translators: %d: line where error was produced */
		$m .= '<p>' . sprintf( __( 'The code snippet you are trying to save produced a fatal error on line %d:', 'code-snippets' ), $error['line'] ) . '</p>';
		$m .= '<strong>' . $error['message'] . '</strong>';
		$m .= '<p>' . __( 'The previous version of the snippet is unchanged, and the rest of this site should be functioning normally as before.', 'code-snippets' ) . '</p>';
		$m .= '<p>' . __( 'Please use the back button in your browser to return to the previous page and try to fix the code error.', 'code-snippets' );
		$m .= ' ' . __( 'If you prefer, you can close this page and discard the changes you just made. No changes will be made to this site.', 'code-snippets' ) . '</p>';

		return $m;
	}

	/**
	 * Validate the snippet code before saving to database
	 *
	 * @param Code_Snippet $snippet
	 *
	 * @return array true if code produces errors
	 */
	private function test_code( Code_Snippet $snippet ): array {

		if ( empty( $snippet->code ) ) {
			return array();
		}

		$code   = process_snippet_macros( $snippet->code, $snippet->macros );
		$result = code_snippets()->console->evaluate_code( $code, true );

		if ( ! isset( $result['error'] ) ) {
			return array();
		}

		return array(
			'line'    => $result['error']['line'],
			'message' => $result['error']['message'],
		);
	}

	/**
	 * Save the posted snippet data to the database and redirect
	 */
	private function save_posted_snippet() {

		/* Build snippet object from fields with 'snippet_' prefix */
		if ( isset( $_POST['snippet_id'] ) ) {
			$snippet = get_snippet( $_POST['snippet_id'] );
		} else {
			$snippet = new Code_Snippet();
		}

		foreach ( $_POST as $field => $value ) {
			if ( 'snippet_' === substr( $field, 0, 8 ) ) {

				/* Remove the 'snippet_' prefix from field name and set it on the object */
				$snippet->set_field( substr( $field, 8 ), stripslashes( $value ) );
			}
		}

		if ( isset( $_POST['save_snippet_execute'] ) && 'single-use' !== $snippet->scope ) {
			unset( $_POST['save_snippet_execute'] );
			$_POST['save_snippet'] = 'yes';
		}

		/* Activate or deactivate the snippet before saving if we clicked the button */

		if ( isset( $_POST['save_snippet_execute'] ) ) {
			$snippet->active = 1;
		} elseif ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
			// Shared network snippets cannot be network activated
			$snippet->active = 0;
			unset( $_POST['save_snippet_activate'], $_POST['save_snippet_deactivate'] );
		} elseif ( isset( $_POST['save_snippet_activate'] ) ) {
			$snippet->active = 1;
		} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
			$snippet->active = 0;
		}

		/* Deactivate snippet if code contains errors */
		if ( $snippet->active && 'single-use' !== $snippet->scope ) {
			$code_error = $this->test_code( $snippet );

			if ( ! empty( $code_error ) ) {
				$snippet->active = 0;
			}
		}

		$snippet_id = save_snippet( $snippet );

		/* Update the shared network snippets if necessary */
		if ( $snippet_id && is_network_admin() ) {

			if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
				$shared_snippets = get_site_option( 'shared_network_snippets', array() );

				/* Add the snippet ID to the array if it isn't already */
				if ( ! in_array( $snippet_id, $shared_snippets, true ) ) {
					$shared_snippets[] = $snippet_id;
					update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );
				}
			} else {
				$this->unshare_network_snippet( $snippet_id );
			}
		}

		/* If the saved snippet ID is invalid, display an error message */
		if ( ! $snippet_id || $snippet_id < 1 ) {
			/* An error occurred */
			wp_redirect( add_query_arg( 'result', 'save-error', code_snippets()->get_menu_url( 'add' ) ) );
			exit;
		}

		/* Display message if a parse error occurred */
		if ( isset( $code_error ) && ! empty( $code_error ) ) {
			session_start();

			$code_error['code']             = $snippet->code;
			$_SESSION['last_snippet_error'] = $code_error;
			$result                         = 'code-error';
		} else {
			/* Set the result depending on if the snippet was just added */
			$result = isset( $_POST['snippet_id'] ) ? 'updated' : 'added';

			/* Append a suffix if the snippet was activated or deactivated */
			if ( isset( $_POST['save_snippet_activate'] ) ) {
				$result .= '-and-activated';
			} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
				$result .= '-and-deactivated';
			} elseif ( isset( $_POST['save_snippet_execute'] ) ) {
				$result .= '-and-executed';
			}
		}

		/* Redirect to edit snippet page */
		$redirect_uri = add_query_arg(
			array( 'id' => $snippet_id, 'result' => $result ),
			code_snippets()->get_menu_url( 'edit' )
		);

		wp_redirect( esc_url_raw( $redirect_uri ) );
		exit;
	}

	/**
	 * Add a description editor to the single snippet page
	 *
	 * @param Code_Snippet $snippet The snippet being used for this page
	 */
	function render_description_editor( Code_Snippet $snippet ) {
		$settings = code_snippets_get_settings();
		$settings = $settings['description_editor'];
		$heading  = __( 'Description', 'code-snippets' );

		/* Hack to remove space between heading and editor tabs */
		if ( ! $settings['media_buttons'] && 'false' !== get_user_option( 'rich_editing' ) ) {
			$heading = "<div>$heading</div>";
		}

		echo '<h2><label for="snippet_description">', $heading, '</label></h2>';

		remove_editor_styles(); // stop custom theme styling interfering with the editor

		wp_editor(
			$snippet->desc,
			'description',
			apply_filters( 'code_snippets/admin/description_editor_settings', array(
				'textarea_name' => 'snippet_description',
				'textarea_rows' => $settings['rows'],
				'teeny'         => ! $settings['use_full_mce'],
				'media_buttons' => $settings['media_buttons'],
			) )
		);
	}

	/**
	 * Render the interface for editing snippet tags
	 *
	 * @param Code_Snippet $snippet the snippet currently being edited
	 */
	function render_tags_editor( Code_Snippet $snippet ) {

		?>
        <h2 style="margin: 25px 0 10px;">
            <label for="snippet_tags" style="cursor: auto;">
				<?php esc_html_e( 'Tags', 'code-snippets' ); ?>
            </label>
        </h2>

        <input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;"
               placeholder="<?php esc_html_e( 'Enter a list of tags; separated by commas', 'code-snippets' ); ?>"
               value="<?php echo esc_attr( $snippet->tags_list ); ?>"/>
		<?php
	}

	/**
	 * Render debug console for snippet code execution
	 *
	 * @param Code_Snippet $snippet the snippet currently being edited
	 */
	public function render_debug_console( Code_Snippet $snippet ) {
		?>
        <div id="snippet_execute_div" class="editor_section">
            <h2><?php esc_html_e( 'Debug / Execution' ); ?></h2>
            <div class="collapsible">
                <div class="button_wrapper">
                    <label for="snippet_output">Output</label>
                    <div class="snippet_execute_buttons">
                        <div class="switch-wrapper">
                            <label class="round-switch">
                                <input type="checkbox" id="wpd_execute_render_html">
                                <span class="slider"></span>
                            </label>
							<?php esc_html_e( 'Render HTML', 'code-snippets' ); ?>
                        </div>
                        <button type="button" id="wpd_execute"
                                class="button button-primary"><?php esc_html_e( 'Run Code' ); ?></button>
                        <button type="button" id="wpd_execute_clear"
                                class="button button-default"><?php esc_html_e( 'Clear' ); ?></button>
                    </div>
                </div>
                <div id="snippet_output" contenteditable="true" oncut="return false"
                     onpaste="return false"
                     onkeydown="return !!event.metaKey; "></div>
            </div>
        </div>
		<?php
	}

	/**
	 * Render the snippet scope setting
	 *
	 * @param Code_Snippet $snippet the snippet currently being edited.
	 */
	public function render_snippet_settings( Code_Snippet $snippet ) {
		?>
        <div id="snippet_setting_div" class="editor_section">
            <h2><?php esc_html_e( 'Settings and Macros' ); ?></h2>
            <div class="collapsible">
                <strong><?php esc_html_e( 'Macros' ); ?></strong>
                <p><?php _e( 'Macros are predefined strings that will be placed into the code before execution. Macros are identified by <code>${{<i>&lt;value&gt;</i>}}</code> syntax, where <i>&lt;value&gt;</i> is a unique identifier of your macro. You can use macros the same way you use variables. All available macros will be listed below. ' ); ?></p>
                <table id="snippet_macros" class="widefat fixed">
                    <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Macro Name' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Data Type' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Value' ); ?></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input name="snippet_macros" id="snippet_macros_input" type="hidden"
                       value="<?= esc_attr( wp_json_encode( $snippet->macros ) ); ?>">
                <p class="snippet-priority"
                   title="<?php esc_attr_e( 'Snippets with a lower priority number will run before those with a higher number.', 'code-snippets' ); ?>">
                    <label for="snippet_priority"><?php esc_html_e( 'Priority', 'code-snippets' ); ?></label>

                    <input name="snippet_priority" type="number" id="snippet_priority"
                           value="<?php echo intval( $snippet->priority ); ?>">
                </p>
				<?php
				$icons = Code_Snippet::get_scope_icons();

				$labels = array(
					'global'     => __( 'Run snippet everywhere', 'code-snippets' ),
					'admin'      => __( 'Only run in administration area', 'code-snippets' ),
					'front-end'  => __( 'Only run on site front-end', 'code-snippets' ),
					'single-use' => __( 'Only run once', 'code-snippets' ),
				);

				echo '<h2 class="screen-reader-text">' . esc_html__( 'Scope', 'code-snippets' ) . '</h2><p class="snippet-scope">';

				foreach ( Code_Snippet::get_all_scopes() as $scope ) {
					printf( '<label><input type="radio" name="snippet_scope" value="%s"', $scope );
					checked( $scope, $snippet->scope );
					printf( '> <span class="dashicons dashicons-%s"></span> %s</label>', $icons[ $scope ], esc_html( $labels[ $scope ] ) );
				}

				echo '</p>';
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Render the setting for shared network snippets
	 *
	 * @param object $snippet The snippet currently being edited
	 */
	function render_multisite_sharing_setting( $snippet ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );
		?>

        <div class="snippet-sharing-setting">
            <h2 class="screen-reader-text"><?php _e( 'Sharing Settings', 'code-snippets' ); ?></h2>
            <label for="snippet_sharing">
                <input type="checkbox" name="snippet_sharing"
					<?php checked( in_array( $snippet->id, $shared_snippets, true ) ); ?>>
				<?php esc_html_e( 'Allow this snippet to be activated on individual sites on the network', 'code-snippets' ); ?>
            </label>
        </div>

		<?php
	}

	/**
	 * Retrieve the first error in a snippet's code
	 *
	 * @param $snippet_id
	 *
	 * @return array|bool
	 */
	private function get_snippet_error( $snippet_id ) {

		if ( ! intval( $snippet_id ) ) {
			return false;
		}

		$snippet = get_snippet( intval( $snippet_id ) );

		if ( '' === $snippet->code ) {
			return false;
		}

		$validator = new Code_Snippets_Validator( $snippet->code );

		if ( $error = $validator->validate() ) {
			return $error;
		}

		ob_start();
		$result = eval( $snippet->code );
		ob_end_clean();

		if ( false !== $result ) {
			return false;
		}

		$error = error_get_last();

		if ( is_null( $error ) ) {
			return false;
		}

		return $error;
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		if ( ! isset( $_REQUEST['result'] ) ) {
			return;
		}

		$result = $_REQUEST['result'];

		if ( 'code-error' === $result ) {

			if ( isset( $_REQUEST['id'] ) && isset( $_SESSION['last_snippet_error'] ) ) {
				$last_snippet_error = $_SESSION['last_snippet_error'];

				printf(
					'<div id="message" class="error fade"><p>%s</p><p><strong>%s</strong></p></div>',
					/* translators: %d: line of file where error originated */
					sprintf( __( 'The snippet has been deactivated due to an error on line %d:', 'code-snippets' ), $last_snippet_error['line'] ),
					$last_snippet_error['message']
				);

			} else {
				echo '<div id="message" class="error fade"><p>', __( 'The snippet has been deactivated due to an error in the code.', 'code-snippets' ), '</p></div>';
			}

			return;
		}

		if ( 'save-error' === $result ) {
			echo '<div id="message" class="error fade"><p>', __( 'An error occurred when saving the snippet.', 'code-snippets' ), '</p></div>';

			return;
		}

		$messages = array(
			'added'                   => __( 'Snippet <strong>added</strong>.', 'code-snippets' ),
			'updated'                 => __( 'Snippet <strong>updated</strong>.', 'code-snippets' ),
			'added-and-activated'     => __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-executed'    => __( 'Snippet <strong>added</strong> and <strong>executed</strong>.', 'code-snippets' ),
			'updated-and-activated'   => __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-deactivated' => __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ),
		);

		if ( isset( $messages[ $result ] ) ) {
			echo '<div id="message" class="updated fade"><p>', $messages[ $result ], '</p></div>';
		}
	}

	/**
	 * Enqueue assets for the edit menu
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl    = is_rtl() ? '-rtl' : '';

		code_snippets_enqueue_editor();

		wp_enqueue_style(
			'code-snippets-edit',
			plugins_url( "css/min/edit{$rtl}.css", $plugin->file ),
			array(), $plugin->version
		);

		wp_enqueue_script(
			'code-snippets-edit-menu',
			plugins_url( 'js/min/edit.js', $plugin->file ),
			array(), $plugin->version, true
		);

		$atts          = code_snippets_get_editor_atts( array(), true );
		$inline_script = 'var code_snippets_editor_atts = ' . $atts . ';';

		wp_add_inline_script( 'code-snippets-edit-menu', $inline_script, 'before' );

		wp_enqueue_script(
			'code-snippets-edit-menu-tags',
			plugins_url( 'js/min/edit-tags.js', $plugin->file ),
			array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-widget',
				'jquery-ui-position',
				'jquery-ui-autocomplete',
				'jquery-effects-blind',
				'jquery-effects-highlight',
			),
			$plugin->version, true
		);

		$snippet_tags  = wp_json_encode( get_all_snippet_tags() );
		$inline_script = 'var code_snippets_all_tags = ' . $snippet_tags . ';';

		wp_add_inline_script( 'code-snippets-edit-menu-tags', $inline_script, 'before' );
	}

	/**
	 * Retrieve a list of submit actions for a given snippet
	 *
	 * @param Code_Snippet $snippet
	 * @param bool         $extra_actions
	 *
	 * @return array
	 */
	public function get_actions_list( $snippet, $extra_actions = true ) {
		$actions = array(
			'save_snippet' => __( 'Save Changes', 'code-snippets' ),
		);

		if ( 'single-use' === $snippet->scope ) {
			$actions['save_snippet_execute'] = __( 'Save Changes and Execute Once', 'code-snippets' );

		} elseif ( ! $snippet->shared_network || ! is_network_admin() ) {

			if ( $snippet->active ) {
				$actions['save_snippet_deactivate'] = __( 'Save Changes and Deactivate', 'code-snippets' );
			} else {
				$actions['save_snippet_activate'] = __( 'Save Changes and Activate', 'code-snippets' );
			}
		}

		// Make the 'Save and Activate' button the default if the setting is enabled
		if ( ! $snippet->active && 'single-use' !== $snippet->scope &&
		     code_snippets_get_setting( 'general', 'activate_by_default' ) ) {
			$actions = array_reverse( $actions );
		}

		if ( $extra_actions && 0 !== $snippet->id ) {

			if ( apply_filters( 'code_snippets/enable_downloads', true ) ) {
				$actions['download_snippet'] = __( 'Download', 'code-snippets' );
			}

			$actions['export_snippet'] = __( 'Export', 'code-snippets' );
			$actions['push_snippet']   = __( 'Push', 'code-snippets' );
			$actions['delete_snippet'] = __( 'Delete', 'code-snippets' );
		}

		return $actions;
	}

	/**
	 * Render the submit buttons for a code snippet
	 *
	 * @param Code_Snippet $snippet
	 * @param string       $size
	 * @param bool         $extra_actions
	 */
	public function render_submit_buttons( $snippet, $size = '', $extra_actions = true ) {

		$actions = $this->get_actions_list( $snippet, $extra_actions );
		$type    = 'primary';
		$size    = $size ? ' ' . $size : '';

		foreach ( $actions as $action => $label ) {
			$other = null;

			if ( 'delete_snippet' === $action ) {

				$other = sprintf( 'onclick="%s"', esc_js(
					sprintf(
						'return confirm("%s");',
						__( 'You are about to permanently delete this snippet.', 'code-snippets' ) . "\n" .
						__( "'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
					)
				) );
			}

			submit_button( $label, $type . $size, $action, false, $other );

			if ( 'primary' === $type ) {
				$type = 'secondary';
			}
		}
	}
}
