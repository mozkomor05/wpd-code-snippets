<?php

/**
 * Auto upgrades plugin form GitHub repo.
 */
class Code_Snippets_Auto_Upgrader {

	/**
	 * The current plugin version number
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Username and name of repository, i.e. user/wpd-code-snippets
	 *
	 * @var string
	 */
	private $github_repo;

	/**
	 * Basename of the current plugin, i.e. wpd-code-snippets/code-snippets.php
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Initialize a new instance of the WordPress Auto-Update class
	 *
	 * @param string $current_version Current plugin version.
	 * @param string $github_repo     Username and name of repository, ie. user/wpd-code-snippetss.
	 */
	public function __construct( $current_version, $github_repo ) {
		$this->current_version = $current_version;
		$this->github_repo     = $github_repo;
		$this->plugin_basename = plugin_basename( CODE_SNIPPETS_FILE );

		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );

		set_site_transient( 'update_plugins', get_site_transient( 'update_plugins' ) );
	}

	/**
	 * Add our self-hosted auto-update plugin to the filter transient
	 *
	 * @param object $transient Transient.
	 *
	 * @return object Transient.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$new_version = $this->check_new_version();

		if ( false !== $new_version ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'plugin'      => $this->plugin_basename,
				'slug'        => wp_basename( $this->plugin_basename, '.php' ),
				'new_version' => $new_version,
				'url'         => 'https://wpdistro.com',
				'package'     => sprintf( 'https://github.com/%s/releases/latest/download/wpd-code-snippets.zip', $this->github_repo ),
			);
		}

		return $transient;
	}


	/**
	 *  Checks whether new version of the plugin is available.
	 *
	 * @return string|bool The new version string or false
	 */
	private function check_new_version() {
		$request = wp_remote_get( sprintf( 'https://api.github.com/repos/%s/tags', $this->github_repo ) );
		$body    = wp_remote_retrieve_body( $request );

		if ( is_wp_error( $body ) ) {
			return false;
		}

		$body = json_decode( $body, true );

		$versions = array_column( $body, 'name' );

		if ( empty( $versions ) ) {
			return false;
		}

		usort( $versions, 'version_compare' );
		$latest_version = ltrim( end( $versions ), 'v' );

		if ( version_compare( $this->current_version, $latest_version, '<' ) ) {
			return $latest_version;
		}

		return false;
	}
}
