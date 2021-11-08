<?php
/**
 * WPD Code Snippets - An easy, clean and simple way to add, manage and share code snippets on your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see https://github.com/mozkomor05/wpd-code-snippets
 *
 * @package   WPD_Code_Snippets
 * @author    WPDistro <info@wpdistro.com>
 * @copyright 2021 WPDistro
 * @license   MIT http://opensource.org/licenses/MIT
 * @version   1.0.0
 * @link      https://github.com/sheabunge/code-snippets
 *
 * @wordpress-plugin
 * Plugin Name: WPDistro Code Snippets
 * Plugin URI:  https://wpdistro.com
 * Description: Manage share and download code snippets with other people. Contribute to the community of WP developers and let them download the amazing hacks on your WordPress.
 * Author:      WPDistro
 * Author URI:  https://github.com/mozkomor05
 * Version:     1.0.0
 * License:     MIT
 * License URI: license.txt
 * Requires PHP: 7.0
 * Text Domain: code-snippets
 * Domain Path: /languages
 **/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( in_array( 'code-snippets/code-snippets.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	die( 'In order for WPD Code Snippets to work, the original Code Snippets plugin must be deactivated.' );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * The full path to the main file of this plugin
 *
 * This can later be passed to functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths
 *
 * @since 2.0
 * @var string
 */
define( 'CODE_SNIPPETS_FILE', __FILE__ );

/**
 * Enable autoloading of plugin classes
 *
 * @param $class_name
 */
if ( ! function_exists( 'code_snippets_autoload' ) ) {
	function code_snippets_autoload( $class_name ) {

		/* Only autoload classes from this plugin */
		if ( 'Code_Snippet' !== $class_name && 'Code_Snippets' !== substr( $class_name, 0, 13 ) ) {
			return;
		}

		/* Remove namespace from class name */
		$class_file = str_replace( 'Code_Snippets_', '', $class_name );

		if ( 'Code_Snippet' === $class_name ) {
			$class_file = 'Snippet';
		}

		/* Convert class name format to file name format */
		$class_file = strtolower( $class_file );
		$class_file = str_replace( '_', '-', $class_file );

		$class_path = dirname( __FILE__ ) . '/php/';

		if ( 'Menu' === substr( $class_name, - 4, 4 ) ) {
			$class_path .= 'admin-menus/';
		}

		/* Load the class */
		require_once $class_path . "class-{$class_file}.php";
	}
}

try {
	spl_autoload_register( 'code_snippets_autoload' );
} catch ( Exception $e ) {
	new WP_Error( $e->getCode(), $e->getMessage() );
}

/**
 * Retrieve the instance of the main plugin class
 *
 * @return Code_Snippets
 * @since 2.6.0
 */
if ( ! function_exists( 'code_snippets' ) ) {
	function code_snippets() {
		static $plugin;

		if ( is_null( $plugin ) ) {
			$plugin = new Code_Snippets( '1.0.0', __FILE__ );
		}

		return $plugin;
	}
}

code_snippets()->load_plugin();

/* Execute the snippets once the plugins are loaded */
add_action( 'plugins_loaded', 'execute_active_snippets', 1 );
