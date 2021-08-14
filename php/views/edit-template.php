<?php

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Code_Snippets_Edit_Menu $this
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}
$snippet = $this->snippet;
$snippet->is_template = true;
include dirname( dirname( __FILE__ ) ) . "/views/edit.php";



