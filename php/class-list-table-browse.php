<?php

/**
 * Contains the class for handling the snippets table
 *
 * @package    Code_Snippets
 */

/* The WP_List_Table base class is not included by default, so we need to load it */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WPD_Snippet' ) ) {
	require_once dirname( __FILE__ ) . '/wpd_snippet.php';
}

/**
 * This class handles the table for the manage snippets menu
 *
 * @since   1.5
 * @package Code_Snippets
 */
class Code_Snippets_List_Table_Browse extends WP_List_Table {


	public function __construct() {
		parent::__construct( array(
			'ajax'     => true,
			'plural'   => 'snippets',
			'singular' => 'snippet',
		) );
	}

	public function process_requested_actions() {
		if ( isset( $_GET['action'] ) ) {
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'url' ) );
		}

		$result = false;

		switch ( $this->current_action() ) {

			case 'install':
				if ( ! empty( $_GET['action'] ) && wpd_install_remote_snippet( $_GET['url'] ) ) {
					$result = 'installed';
				} else {
					$result = 'not-installed';
				}
				break;
		}

		if ( $result ) {
			wp_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	/**
	 * @global array $tabs
	 * @global string $tab
	 * @global int $paged
	 * @global string $type
	 * @global string $term
	 */
	public function prepare_items() {
		$this->process_requested_actions();

		global $tabs, $tab, $paged, $type, $term;
		wp_reset_vars( array( 'tab' ) );

		$paged    = $this->get_pagenum();
		$per_page = 20;
		$tabs     = array();

		if ( 'search' === $tab ) {
			$tabs['search'] = __( 'Search Results', 'code-snippets' );
		}

		$tabs['new']         = _x( 'New', 'Snippets Filter', 'code-snippets' );
		$tabs['popular']     = _x( 'Popular', 'Snippets Filter', 'code-snippets' );
		$tabs['recommended'] = _x( 'Recommended', 'Snippets Filter', 'code-snippets' );

		if ( empty( $tab ) || ( ! isset( $tabs[ $tab ] ) ) ) {
			$tab = key( $tabs );
		}

		$args = array(
			'page'     => $paged,
			'per_page' => $per_page,
		);

		switch ( $tab ) {
			case 'search':
				$type = isset( $_REQUEST['type'] ) ? wp_unslash( $_REQUEST['type'] ) : 'term';
				$term = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';

				switch ( $type ) {
					case 'term':
						$args['search'] = $term;
						break;
					case 'author':
					{
						$authors = wpd_list_authors();
						$author  = 0;
						foreach ( $authors as $person ) {
							if ( $person['name'] === $term ) {
								$author = $person['id'];
								break;
							}
						}
						$args['author'] = $author;
						break;
					}
				}

				break;

			case 'popular':
				$args['orderby'] = 'popular';
				break;

			default:
				$args['orderby'] = 'date';
		}

		$snippets = apply_filters( 'code_snippets/list_table/wpd_get_snippets', wpd_list_posts( $args, $total ) );

		$this->items = $snippets;

		$this->set_pagination_args(
			array(
				'total_items' => (int) $total,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', $this->_args['plural'] );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array();
	}


	public function display() {
		$singular = $this->_args['singular'];

		$data_attr = " data-wp-lists='list:$singular'";

		$this->display_tablenav( 'top' );

		?>
        <div class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<?php
			$this->screen->render_screen_reader_content( 'heading_list' );
			?>
            <div id="the-list"<?php echo $data_attr; ?>>
				<?php $this->display_rows_or_placeholder(); ?>
            </div>
        </div>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_referer_field();
			?>
            <div class="tablenav top">
                <div class="alignleft actions">
                </div>
				<?php $this->pagination( $which ); ?>
                <br class="clear"/>
            </div>
		<?php } else { ?>
            <div class="tablenav bottom">
				<?php $this->pagination( $which ); ?>
                <br class="clear"/>
            </div>
			<?php
		}
	}

	/**
	 * Retrieve a URL to perform an action on a snippet
	 *
	 * @param string $action Name of action to perform.
	 * @param Code_Snippet|string $snippet Snippet object.
	 * @param bool $escape Whether to escape the generated URL for output.
	 *
	 * @return string
	 */
	public function get_action_link( string $action, $snippet, bool $escape = true ): string {
		$query_args = array( 'action' => $action );

		if ( gettype( $snippet ) !== 'string' ) {
			$query_args['id'] = $snippet->id;
		} else {
			$query_args['url'] = $snippet;
		}

		$url = add_query_arg( $query_args );

		return $escape ? esc_url( $url ) : $url;
	}

	public function display_rows() {
		$group = null;

		foreach ( (array) $this->items as $wpd_snippet ) {
			if ( is_object( $wpd_snippet ) ) {
				$wpd_snippet = (array) $wpd_snippet;
			}

			$snippet = new WPD_Snippet( $wpd_snippet );

			$title       = $snippet->name;
			$description = wp_strip_all_tags( $snippet->description );
			$name        = wp_strip_all_tags( $title );
			$author_info = $snippet->request_author();
			$author_name = $author_info->name;
			$author      = '';

			if ( $author_info ) {
				/* translators: %s: Plugin author. */
				$author = ' <cite>' . __( 'By', 'code-snippets' ) . sprintf( ' <a href="%s" aria-label="%s" data-title="%s">%s</a>',
						esc_url( $author_info->link ),
						esc_attr( sprintf( __( 'Author of %s' ), $name ) ),
						esc_attr( $name ),
						$author_name ) . '</cite>';
			}

			$requires_php = $wpd_snippet['requires_php'] ?? null;
			$requires_wp  = $wpd_snippet['requires'] ?? null;

			$compatible_php = is_php_version_compatible( $requires_php );
			$compatible_wp  = is_wp_version_compatible( $requires_wp );
			$action_links   = array();

			$num_ratings = empty( $wpd_snippet['rating_reviews_count'] ) ? 0 : $wpd_snippet['rating_reviews_count'];
			$tags        = $snippet->request_tags();

			$status = 'install';

			if ( wpd_remote_snippet_exists( $snippet->id ) ) {
				$status = 'installed';
			}

			switch ( $status ) {
				case 'install':
					if ( $compatible_php && $compatible_wp ) {
						$action_links[] = sprintf(
							'<a class="action-button button snippet-install-button" data-endpoint="%s" href="%s" aria-label="%s">%s</a>',
							esc_attr( $snippet->self_endpoint ),
							$this->get_action_link( 'install', $snippet->self_endpoint ),
							esc_attr( sprintf( _x( 'Import %s now', 'code-snippets' ), $name ) ),
							__( 'Import Now', 'code-snippets' )
						);
					}
					break;

				case 'installed':
					$action_links[] = sprintf(
						'<a class="action-button button snippet-installed-button" href="#">%s</a>',
						__( 'Installed!', 'code-snippets' )
					);
					break;
			}

			$action_links[] = sprintf(
				'<a href="%s" aria-label="%s" data-title="%s">%s</a>',
				esc_url( $snippet->url ),
				/* translators: %s: Plugin name and version. */
				esc_attr( sprintf( __( 'View snippet site of  %s' ), $name ) ),
				esc_attr( $name ),
				__( 'View snippet site' )
			);

			if ( ! empty( $wpd_snippet['featured_media'] ) ) {
				$plugin_icon_url = $wpd_snippet['featured_image_src'];
			} else {
				$plugin_icon_url = '';
			}

			?>
            <div class="snippet-card plugin-card plugin-card-<?php echo sanitize_html_class( $wpd_snippet['slug'] ); ?>">
				<?php
				if ( ! $compatible_php || ! $compatible_wp ) {
					echo '<div class="notice inline notice-error notice-alt"><p>';
					if ( ! $compatible_php && ! $compatible_wp ) {
						_e( 'This plugin doesn&#8217;t work with your versions of WordPress and PHP.' );
						if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
							printf(
							/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
								' ' . __( '<a href="%1$s">Please update WordPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
								self_admin_url( 'update-core.php' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						} elseif ( current_user_can( 'update_core' ) ) {
							printf(
							/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update WordPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						} elseif ( current_user_can( 'update_php' ) ) {
							printf(
							/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
					} elseif ( ! $compatible_wp ) {
						_e( 'This snippet doesn&#8217;t work with your version of WordPress.' );
						if ( current_user_can( 'update_core' ) ) {
							printf(
							/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update WordPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						}
					} elseif ( ! $compatible_php ) {
						_e( 'This snippet doesn&#8217;t work with your version of PHP.' );
						if ( current_user_can( 'update_php' ) ) {
							printf(
							/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
					}
					echo '</p></div>';
				}
				?>
                <div class="plugin-card-top">
                    <div class="name column-name">
                        <h3>
                            <a href="<?php echo esc_url( $snippet->url ); ?>">
								<?php echo $title; ?>
								<?php
								if ( ! empty( $plugin_icon_url ) ) :
									?>
                                    <img src="<?php echo esc_attr( $plugin_icon_url ); ?>" class="plugin-icon" alt=""/>
								<?php
								else :
									?>
                                    <div class="default-snippet-icon plugin-icon"></div>
								<?php
								endif;
								?>
                            </a>
                        </h3>
                    </div>
                    <div class="action-links">
						<?php
						if ( $action_links ) {
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
						}
						?>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo $description; ?></p>
                        <p class="authors"><?php echo $author; ?></p>
                    </div>
                </div>
                <div class="plugin-card-bottom">
                    <div class="vers column-rating">
						<?php
						wp_star_rating(
							array(
								'rating' => empty( $wpd_snippet['meta']['_jet_reviews_average_rating'] ) ? 0 : $wpd_snippet['meta']['_jet_reviews_average_rating'],
								'type'   => 'rating',
								'number' => $num_ratings,
							)
						);
						?>
                        <span class="num-ratings"
                              aria-hidden="true">(<?php echo number_format_i18n( $num_ratings ); ?>)</span>
                    </div>
                    <div class="column-updated">
                        <strong><?php _e( 'Tags:' ); ?></strong>
						<?php
						foreach ( $tags as $tag ) {
							echo sprintf( '<a href="%s">%s</a>',
								$tag->link,
								$tag->name
							);
						}
						?>
                    </div>
                    <div class="column-downloaded">
                    </div>
                    <div class="column-compatibility">
                    </div>
                </div>
            </div>
			<?php
		}
	}

	/**
	 * @return array
	 * @global string $tab
	 *
	 * @global array $tabs
	 */
	protected function get_views(): array {
		global $tabs, $tab;

		$display_tabs = array();
		foreach ( (array) $tabs as $action => $text ) {
			$current_link_attributes                      = ( $action === $tab ) ? ' class="current" aria-current="page"' : '';
			$href                                         = self_admin_url( 'admin.php?page=browse-snippets&tab=' . $action );
			$display_tabs[ 'snippet-install-' . $action ] = "<a href='$href'$current_link_attributes>$text</a>";
		}

		return $display_tabs;
	}

	/**
	 * Override parent views so we can use the filter bar display.
	 */
	public function views() {
		$views = $this->get_views();
		$views = apply_filters( "views_{$this->screen->id}", $views );

		$this->screen->render_screen_reader_content( 'heading_views' );

		$type = isset( $_REQUEST['type'] ) ? wp_unslash( $_REQUEST['type'] ) : 'term';
		$term = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
		?>
        <div class="wp-filter">
            <ul class="filter-links">
				<?php
				if ( ! empty( $views ) ) {
					foreach ( $views as $class => $view ) {
						$views[ $class ] = "\t<li class='$class'>$view";
					}
					echo implode( " </li>\n", $views ) . "</li>\n";
				}
				?>
            </ul>
            <form class="search-form search-plugins" method="get">
                <input type="hidden" name="tab" value="search"/>
                <input type="hidden" name="page" value="browse-snippets"/>

                <label class="screen-reader-text"
                       for="typeselector"><?php _e( 'Search snippets by:', 'code-snippets' ); ?></label>
                <select name="type" id="typeselector">
                    <option value="term"<?php selected( 'term', $type ); ?>><?php _e( 'Keyword', 'code-snippets' ); ?></option>
                    <option value="author"<?php selected( 'author', $type ); ?>><?php _e( 'Author', 'code-snippets' ); ?></option>
                </select>
                <label class="screen-reader-text"
                       for="search-plugins"><?php _e( 'Search Snippets', 'code-snippets' ); ?></label>
                <input type="search" name="s" id="search-plugins" value="<?php echo esc_attr( $term ); ?>"
                       class="wp-filter-search"
                       placeholder="<?php esc_attr_e( 'Search snippets...', 'code-snippets' ); ?>"/>
				<?php submit_button( __( 'Search Snippets', 'code-snippets' ), 'hide-if-js', false, false, array( 'id' => 'search-submit' ) ); ?>
            </form>
        </div>
		<?php
	}
}

