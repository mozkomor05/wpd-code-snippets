<?php

/**
 * This class handles the browse admin menu
 * @package Code_Snippets
 */
class Code_Snippets_Browse_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Holds the list table class
	 * @var Code_Snippets_List_Table_Browse
	 */
	public $list_table;

	/**
	 * Class constructor
	 */
	function __construct() {
		parent::__construct( 'browse',
			_x( 'Browse', 'menu label', 'code-snippets' ),
			__( 'Browse Snippets', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();

		add_action( 'wp_ajax_browse_code_snippet', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Executed when the admin page is loaded
	 */
	function load() {
		parent::load();

		/* Initialize the list table class */
		$this->list_table = new Code_Snippets_List_Table_Browse();
		$this->list_table->prepare_items();
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		if ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] ) {
			echo '<div id="message" class="error fade"><p>';
			_e( 'An error occurred when processing the import files.', 'code-snippets' );
			echo '</p></div>';
		}
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl    = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'code-snippets-browse',
			plugins_url( "css/min/browse{$rtl}.css", $plugin->file ),
			array(), $plugin->version
		);

		wp_enqueue_script(
			'code-snippets-browse-js',
			plugins_url( "js/min/browse.js", $plugin->file ),
			array(), $plugin->version, true
		);

		wp_localize_script(
			'code-snippets-browse-js',
			'code_snippets_browse_i18n',
			array(
				'waiting'       => __( 'Importing...', 'code-snippets' ),
				'installed'     => __( 'Imported', 'code-snippets' ),
				'install_error' => __( 'Error occurred', 'code-snippets' )
			)
		);
	}

	/**
	 * Handle AJAX requests
	 */
	public function ajax_callback() {
		check_ajax_referer( 'code_snippets_browse_ajax' );

		if ( ! isset( $_POST['field'], $_POST['url'] ) ) {
			wp_send_json_error( array(
				'type'    => 'param_error',
				'message' => 'incomplete request',
			) );
		}

		$field = $_POST['field'];

		switch ( $field ) {
			case 'install':
			{
				if ( ! wpd_install_remote_snippet( $_POST['url'] ) ) {
					wp_send_json_error( array(
						'type'    => 'install_error',
						'message' => 'can\'t install snippet',
					) );
				}

				break;
			}
		}

		wp_send_json_success();
	}
}
