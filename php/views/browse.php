<?php

/**
 * HTML code for the Browse Snippets page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Code_Snippets_Manage_Menu $this
 */

/* Bail if accessed directly */
if (!defined('ABSPATH')) {
    return;
}

?>

<div class="wrap">
    <h1><?php
        esc_html_e('Browse Snippets', 'code-snippets');

        printf('<a href="%2$s" class="page-title-action add-new-h2">%1$s</a>',
            esc_html_x('Import custom', 'snippet', 'code-snippets'),
            code_snippets()->get_menu_url('import')
        );
        ?></h1>

    <p>
        <?php
        printf(
        /* translators: %s: https://wordpress.org/plugins/ */
            __('You may review, rate and manually install snippets from the <a href="%s">WPDistro Snippets Database</a>. You can also create your own snippets and add them to the WPDistro database.'),
            __('https://wpdistro.com/snippets-for-wordpress/')
        );
        ?>
    </p>

    <form method="post" id="snippets-filter">
        <input type="hidden" id="code_snippets_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_browse_ajax' ) ); ?>">

        <?php
        $this->list_table->display();
        ?>
    </form>

    <?php do_action('code_snippets/admin/browse'); ?>
</div>
