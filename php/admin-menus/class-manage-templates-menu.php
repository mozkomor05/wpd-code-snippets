<?php

/**
 * This class handles the manage snippets menu
 * @since   2.4.0
 * @package Code_Snippets
 */
class Code_Snippets_Manage_Templates_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Holds the list table class
	 * @var Code_Snippets_List_Table
	 */
	public $list_table;

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct( 'manage-templates',
			_x( 'All Snippet Templates', 'menu label', 'code-snippets' ),
			__( 'Snippet Templates', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();

		if ( code_snippets()->admin->is_compact_menu() ) {
			//add_action( 'admin_menu', array( $this, 'register_compact_menu' ), 2 );
			//add_action( 'network_admin_menu', array( $this, 'register_compact_menu' ), 2 );
		}
        add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_snippet_template', array( $this, 'ajax_callback' ) );
	}


	/**
	 * Executed when the admin page is loaded
	 */
	function load() {
		parent::load();

		/* Load the contextual help tabs */
		$contextual_help = new Code_Snippets_Contextual_Help( 'manage-templates' );
		$contextual_help->load();

		/* Initialize the list table class */
		$this->list_table = new Code_Snippets_List_Table_Templates();
		$this->list_table->prepare_items();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'code-snippets-manage',
			plugins_url( "css/min/manage{$rtl}.css", $plugin->file ),
			array(), $plugin->version
		);

		wp_enqueue_script(
			'code-snippets-manage-js',
			plugins_url( 'js/min/manage.js', $plugin->file ),
			array(), $plugin->version, true
		);

		wp_localize_script(
			'code-snippets-manage-js',
			'code_snippets_manage_i18n',
			array(
				'activate'         => __( 'Activate', 'code-snippets' ),
				'deactivate'       => __( 'Deactivate', 'code-snippets' ),
				'activation_error' => __( 'An error occurred when attempting to activate', 'code-snippets' ),
			)
		);
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		/* Output a warning if safe mode is active */
		if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
			echo '<div id="message" class="error fade"><p>';
			_e( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://github.com/sheabunge/code-snippets/wiki/Safe-Mode" target="_blank">Help</a>', 'code-snippets' );
			echo '</p></div>';
		}

		echo $this->get_result_message(
			array(
				'executed'          => __( 'Snippet <strong>executed</strong>.', 'code-snippets' ),
				'activated'         => __( 'Snippet <strong>activated</strong>.', 'code-snippets' ),
				'activated-multi'   => __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ),
				'deactivated'       => __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ),
				'deactivated-multi' => __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ),
				'deleted'           => __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ),
				'deleted-multi'     => __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ),
				'cloned'            => __( 'Snippet <strong>cloned</strong>.', 'code-snippets' ),
				'cloned-multi'      => __( 'Selected snippets <strong>cloned</strong>.', 'code-snippets' ),
			)
		);
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param mixed  $status
	 * @param string $option The screen option name
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	function save_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Handle AJAX requests
	 */
	public function ajax_callback() {
		check_ajax_referer( 'code_snippets_manage_ajax' );

		if ( ! isset( $_POST['field'], $_POST['snippet'] ) ) {
			wp_send_json_error( array(
				'type'    => 'param_error',
				'message' => 'incomplete request',
			) );
		}

		$snippet_data = json_decode( stripslashes( $_POST['snippet'] ), true );

		$snippet = new Code_Snippet( $snippet_data );
		$field = $_POST['field'];

		switch ($field) {
            case 'priority': {
                if ( ! isset( $snippet_data['priority'] ) || ! is_numeric( $snippet_data['priority'] ) ) {
                    wp_send_json_error( array(
                        'type'    => 'param_error',
                        'message' => 'missing snippet priority data',
                    ) );
                }

                global $wpdb;

                $wpdb->update(
                    code_snippets()->db->get_table_name( $snippet->network ),
                    array( 'priority' => $snippet->priority ),
                    array( 'id' => $snippet->id ),
                    array( '%d' ),
                    array( '%d' )
                );
                break;
            }
            case 'active': {

                if ( ! isset( $snippet_data['active'] ) ) {
                    wp_send_json_error( array(
                        'type'    => 'param_error',
                        'message' => 'missing snippet active data',
                    ) );
                }

                /*if ( $snippet->shared_network ) {
                    $active_shared_snippets = get_option( 'active_shared_network_snippets', array() );

                    if ( in_array( $snippet->id, $active_shared_snippets, true ) !== $snippet->active ) {

                        $active_shared_snippets = $snippet->active ?
                            array_merge( $active_shared_snippets, array( $snippet->id ) ) :
                            array_diff( $active_shared_snippets, array( $snippet->id ) );

                        update_option( 'active_shared_network_snippets', $active_shared_snippets );
                    }
                } else {

                    if ( $snippet->active ) {
                        $result = activate_snippet( $snippet->id, $snippet->network );
                        if ( ! $result ) {
                            wp_send_json_error( array(
                                'type'    => 'action_error',
                                'message' => 'error activating snippet',
                            ) );
                        }
                    } else {
                        deactivate_snippet( $snippet->id, $snippet->network );
                    }
                }*/
                break;
            }
        }

		wp_send_json_success();
	}
}
