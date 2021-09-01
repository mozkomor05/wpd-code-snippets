<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Code_Snippets_Manage_Menu $this
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_thickbox();
?>

<div class="wrap">
    <h1><?php
		esc_html_e( 'Snippets', 'code-snippets' );

		printf( '<a href="%2$s" class="page-title-action add-new-h2">%1$s</a>',
			esc_html_x( 'Add New', 'snippet', 'code-snippets' ),
			code_snippets()->get_menu_url( 'add' )
		);

		printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
			esc_html_x( 'Import', 'snippets', 'code-snippets' ),
			code_snippets()->get_menu_url( 'import' )
		);

		$admin = code_snippets()->admin;

		if ( $admin->is_compact_menu() && isset( $admin->menus['settings'] ) ) {
			printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
				esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
				code_snippets()->get_menu_url( 'settings' )
			);
		}

		$this->list_table->search_notice();
		?></h1>

	<?php $this->list_table->views(); ?>

    <form method="get" action="">
		<?php
		$this->list_table->required_form_fields( 'search_box' );
		$this->list_table->search_box( __( 'Search Installed Snippets', 'code-snippets' ), 'search_id' );
		?>
    </form>
    <div id="push-snippet-thickbox" style="display:none;">
        <form id="push-snippet-form">
            <input type="hidden" name="id" id="push-snippet-id">
            <div>
                <h1>Push snippet to remote WPDistro.com</h1>
                <p>
                    Before pushing a snippet to our remote repository, make sure that everything is in order. Please
                    make our work easier with a good description and an easy-to-read code. Thank you for contributing to
                    the WPDistro database!
                </p>
                <h2>Review Fields</h2>
                <div class="form-row">
                    <label for="push-snippet-name">Název:</label>
                    <div class="input">
                        <input type="text" id="push-snippet-name" name="name">
                    </div>
                </div>
                <div class="form-row">
                    <label for="push-snippet-desc">Popis:</label>
                    <div class="input">
                        <textarea id="push-snippet-desc" name="desc"></textarea>
                    </div>
                </div>
            </div>
            <div>
                <div class="push-notice"><small>Notice: The snippet code will be checked and can be modified. For
                        malicious
                        snippets, you can get a push ban.</small></div>
                <div class="push-buttons">
                    <button class="button button-default" type="button" onclick="tb_remove()">Cancel</button>
                    <button class="button button-primary" type="button" id="push-snippet-btn">Push snippet</button>
                </div>
            </div>
        </form>
    </div>
    <form method="post" action="">
        <input type="hidden" id="code_snippets_ajax_nonce"
               value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">

		<?php
		$this->list_table->required_form_fields();
		$this->list_table->display();
		?>
    </form>

	<?php do_action( 'code_snippets/admin/manage' ); ?>
</div>
