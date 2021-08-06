<?php

/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

/**
 * Retrieve a list of snippets from the database
 *
 * @param array $ids The IDs of the snippets to fetch
 * @param bool|null $multisite Retrieve multisite-wide or site-wide snippets?
 *
 * @param array $args {
 *                               Optional. Arguments to specify which sorts of snippets to retrieve.
 *
 * @type bool $active_only Whether to only fetch active snippets. Default false (will fetch both active and inactive snippets).
 * @type int $limit Limit the number of retrieved snippets. Default 0, which will not impose a limit on the results.
 * @type string $orderby Sort the retrieved snippets by a particular field. Example fields include 'id', 'priority', and 'name'.
 * @type string $order Designates ascending or descending order of snippets. Default 'DESC'. Accepts 'ASC', 'DESC'.
 * }
 *
 * @return array An array of Snippet objects.
 *
 * @uses  $wpdb to query the database for snippets
 * @uses  code_snippets()->db->get_table_name() to dynamically retrieve the snippet table name
 *
 * @since 2.0
 */
function get_snippets( array $ids = array(), $multisite = null, array $args = array() ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'active_only' => false,
		'limit'       => 0,
		'orderby'     => '',
		'order'       => 'desc',
	) );

	$db        = code_snippets()->db;
	$multisite = $db->validate_network_param( $multisite );
	$table     = $db->get_table_name( $multisite );

	$ids_count = count( $ids );

	/* If only one ID has been passed in, defer to the get_snippet() function */
	if ( 1 === $ids_count ) {
		return array( get_snippet( $ids[0] ) );
	}

	$where = $order = $limit = '';

	/* Build a query containing the specified IDs if there are any */
	if ( $ids_count > 1 ) {
		$where = $wpdb->prepare( sprintf(
			' AND id IN (%s)',
			implode( ',', array_fill( 0, $ids_count, '%d' ) )
		), $ids );
	}

	/* Restrict the active status of retrieved snippets if requested */
	if ( $args['active_only'] ) {
		$where = ' AND active=1';
	}

	/* Apply custom ordering if requested */
	if ( $args['orderby'] ) {
		$order_dir = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$order     = $wpdb->prepare( ' ORDER BY %s %s', $args['orderby'], $order_dir );
	}

	/* Limit the number of retrieved snippets if requested */
	if ( intval( $args['limit'] ) > 0 ) {
		$limit = sprintf( ' LIMIT %d', intval( $args['limit'] ) );
	}

	/* Retrieve the results from the database */
	$sql      = "SELECT * FROM $table WHERE 1=1 $where $order $limit;";
	$snippets = $wpdb->get_results( $sql, ARRAY_A );

	/* Convert snippets to snippet objects */
	foreach ( $snippets as $index => $snippet ) {
		$snippet['network'] = $multisite;
		$snippets[ $index ] = new Code_Snippet( $snippet );
	}

	return apply_filters( 'code_snippets/get_snippets', $snippets, $multisite );
}

/**
 * Gets all of the used tags from the database
 * @since 2.0
 */
function get_all_snippet_tags() {
	/** @var wpdb $wpdb */
	global $wpdb;

	/* Grab all tags from the database */
	$tags     = array();
	$table    = code_snippets()->db->get_table_name();
	$all_tags = $wpdb->get_col( "SELECT `tags` FROM $table" );

	/* Merge all tags into a single array */
	foreach ( $all_tags as $snippet_tags ) {
		$snippet_tags = code_snippets_build_tags_array( $snippet_tags );
		$tags         = array_merge( $snippet_tags, $tags );
	}

	/* Remove duplicate tags */

	return array_values( array_unique( $tags, SORT_REGULAR ) );
}

/**
 * Make sure that the tags are a valid array
 *
 * @param mixed $tags The tags to convert into an array
 *
 * @return array The converted tags
 *
 * @since 2.0
 */
function code_snippets_build_tags_array( $tags ) {

	/* If there are no tags set, return an empty array */
	if ( empty( $tags ) ) {
		return array();
	}

	/* If the tags are set as a string, convert them into an array */
	if ( is_string( $tags ) ) {
		$tags = strip_tags( $tags );
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one */

	return (array) $tags;
}

/**
 * Retrieve a single snippets from the database.
 * Will return empty snippet object if no snippet
 * ID is specified
 *
 * @param int $id The ID of the snippet to retrieve. 0 to build a new snippet
 * @param boolean|null $multisite Retrieve a multisite-wide or site-wide snippet?
 *
 * @return Code_Snippet A single snippet object
 * @since 2.0
 */
function get_snippet( $id = 0, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$id    = absint( $id );
	$table = code_snippets()->db->get_table_name( $multisite );

	if ( 0 !== $id ) {

		/* Retrieve the snippet from the database */
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		/* Unescape the snippet data, ready for use */
		$snippet = new Code_Snippet( $snippet );

	} else {

		/* Get an empty snippet object */
		$snippet = new Code_Snippet();
	}

	$snippet->network = $multisite;

	return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
}

/**
 * Activates a snippet
 *
 * @param int $id The ID of the snippet to activate
 * @param bool|null $multisite Are the snippets multisite-wide or site-wide?
 *
 * @return int
 *
 * @since 2.0
 * @uses  $wpdb to set the snippet's active status
 */
function activate_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$db    = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/* Retrieve the snippet code from the database for validation before activating */
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT code FROM $table WHERE id = %d;", $id ) );
	if ( ! $row ) {
		return false;
	}

	$validator = new Code_Snippets_Validator( $row->code );
	if ( $validator->validate() ) {
		return false;
	}

	$wpdb->update( $table, array( 'active' => '1' ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table === $db->ms_table && $shared_network_snippets = get_site_option( 'shared_network_snippets', false ) ) {
		$shared_network_snippets = array_diff( $shared_network_snippets, array( $id ) );
		update_site_option( 'shared_network_snippets', $shared_network_snippets );
	}

	do_action( 'code_snippets/activate_snippet', $id, $multisite );

	return true;
}

/**
 * Activates multiple snippet.
 *
 * @param array $ids The IDs of the snippets to activate.
 * @param bool|null $multisite Are the snippets multisite-wide or site-wide?
 *
 * @return array The IDs of the snippets which were successfully activated.
 *
 * @since 2.0
 * @uses  $wpdb to set the snippet's active status
 */
function activate_snippets( array $ids, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$db    = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/* Build a SQL query containing all the provided snippet IDs */
	$ids_format = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$sql        = sprintf( 'SELECT id, code FROM %s WHERE id IN (%s);', $table, $ids_format );
	$rows       = $wpdb->get_results( $wpdb->prepare( $sql, $ids ) );

	if ( ! $rows ) {
		return array();
	}

	/* Loop through each snippet code and validate individually */
	$valid_ids = array();

	foreach ( $rows as $row ) {
		$validator  = new Code_Snippets_Validator( $row->code );
		$code_error = $validator->validate();

		if ( ! $code_error ) {
			$valid_ids[] = $row->id;
		}
	}

	/* If there are no valid snippets, then we're done */
	if ( ! $valid_ids ) {
		return $valid_ids;
	}

	/* Build a SQL query containing all the valid snippet IDs and activate the valid snippets */
	$ids_format = implode( ',', array_fill( 0, count( $valid_ids ), '%d' ) );
	$sql        = sprintf( 'UPDATE %s SET active = 1 WHERE id IN (%s);', $table, $ids_format );
	$wpdb->query( $wpdb->prepare( $sql, $valid_ids ) );

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table === $db->ms_table && $shared_network_snippets = get_site_option( 'shared_network_snippets', false ) ) {
		$shared_network_snippets = array_diff( $shared_network_snippets, $valid_ids );
		update_site_option( 'shared_network_snippets', $shared_network_snippets );
	}

	do_action( 'code_snippets/activate_snippets', $valid_ids, $multisite );

	return $valid_ids;
}

/**
 * Deactivate a snippet
 *
 * @param int $id The ID of the snippet to deactivate
 * @param bool|null $multisite Are the snippets multisite-wide or site-wide?
 *
 * @since 2.0
 * @uses  $wpdb to set the snippets' active status
 *
 */
function deactivate_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$db    = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/* Set the snippet to active */

	$wpdb->update( $table, array( 'active' => '0' ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

	/* Update the recently active list */

	$recently_active = array( $id => time() );

	if ( $table === $db->table ) {

		update_option(
			'recently_activated_snippets',
			$recently_active + (array) get_option( 'recently_activated_snippets', array() )
		);

	} elseif ( $table === $db->ms_table ) {

		update_site_option(
			'recently_activated_snippets',
			$recently_active + (array) get_site_option( 'recently_activated_snippets', array() )
		);
	}

	do_action( 'code_snippets/deactivate_snippet', $id, $multisite );
}

/**
 * Deletes a snippet from the database
 *
 * @param int $id The ID of the snippet to delete
 * @param bool|null $multisite Delete from site-wide or network-wide table?
 *
 * @since 2.0
 */
function delete_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$wpdb->delete(
		code_snippets()->db->get_table_name( $multisite ),
		array( 'id' => $id ),
		array( '%d' )
	);

	do_action( 'code_snippets/delete_snippet', $id, $multisite );
}

/**
 * Saves a snippet to the database.
 *
 * @param Code_Snippet $snippet The snippet to add/update to the database
 *
 * @return int The ID of the snippet
 * @since 2.0
 *
 * @uses  $wpdb to update/add the snippet to the database
 */
function save_snippet( Code_Snippet $snippet ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $snippet->network );

	/* Update the last modification date and the creation date if necessary */
	$snippet->update_modified();

	/* Build array of data to insert */
	$data = array(
		'name'        => $snippet->name,
		'description' => $snippet->desc,
		'code'        => $snippet->code,
		'tags'        => $snippet->tags_list,
		'scope'       => $snippet->scope,
		'priority'    => $snippet->priority,
		'active'      => intval( $snippet->active ),
		'modified'    => $snippet->modified,
		'snippet_settings'    => serialize($snippet->snippet_settings),
		'snippet_values'    => serialize($snippet->snippet_values),
		'remote' => $snippet->remote,
		'remote_id' => $snippet->remote_id,
	);

	/* Create a new snippet if the ID is not set */
	if ( 0 === $snippet->id ) {
		$wpdb->insert( $table, $data, '%s' );
		$snippet->id = $wpdb->insert_id;

		do_action( 'code_snippets/create_snippet', $snippet->id, $table );
	} else {

		/* Otherwise update the snippet data */
		$wpdb->update( $table, $data, array( 'id' => $snippet->id ), null, array( '%d' ) );

		do_action( 'code_snippets/update_snippet', $snippet->id, $table );
	}

	return $snippet->id;
}

/**
 * Update a snippet entry given a list of fields
 *
 * @param int $snippet_id The ID of the snippet to update
 * @param array $fields An array of fields mapped to their values
 * @param bool $network Whether the snippet is network-wide or site-wide
 */
function update_snippet_fields( $snippet_id, $fields, $network = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $network );

	/* Build a new snippet object for the validation */
	$snippet     = new Code_Snippet();
	$snippet->id = $snippet_id;

	/* Validate fields through the snippet class and copy them into a clean array */
	$clean_fields = array();

	foreach ( $fields as $field => $value ) {

		if ( $snippet->set_field( $field, $value ) ) {
			$clean_fields[ $field ] = $snippet->$field;
		}
	}

	/* Update the snippet in the database */
	$wpdb->update( $table, $clean_fields, array( 'id' => $snippet->id ), null, array( '%d' ) );
	do_action( 'code_snippets/update_snippet', $snippet->id, $table );
}


function filter_snippet($code, $settings, $values){
	if($settings == null) return;
	if(count($settings) == 0) return;
	foreach($settings as $setting){
		$code = str_replace($setting['replace'], $values[$setting['replace']], $code);
	}
	return $code;
}


/**
 * Execute a snippet
 *
 * Code must NOT be escaped, as
 * it will be executed directly
 *
 * @param string $code The snippet code to execute
 * @param int $id The snippet ID
 * @param bool $catch_output Whether to attempt to suppress the output of execution using buffers
 *
 * @return mixed The result of the code execution
 * @since 2.0
 *
 */
function execute_snippet( $code, $id = 0, $catch_output = true ) {

	if ( empty( $code ) || defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
		return false;
	}

	if ( $catch_output ) {
		ob_start();
	}

	$result = eval( $code );

	if ( $catch_output ) {
		ob_end_clean();
	}

	do_action( 'code_snippets/after_execute_snippet', $id, $code, $result );

	return $result;
}

/**
 * Run the active snippets
 *
 * @return bool true on success, false on failure
 * @since 2.0
 */
function execute_active_snippets() {

	/* Bail early if safe mode is active */
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE || ! apply_filters( 'code_snippets/execute_snippets', true ) ) {
		return false;
	}

	/** @var wpdb $wpdb */
	global $wpdb;
	$db = code_snippets()->db;

	$current_scope = is_admin() ? 'admin' : 'front-end';
	$queries       = array();

	$sql_format = "SELECT id, code, scope, snippet_settings, snippet_values FROM %s WHERE scope IN ('global', 'single-use', %%s) ";
	$order      = 'ORDER BY priority ASC, id ASC';

	/* Fetch snippets from site table */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$db->table'" ) === $db->table ) {
		$queries[ $db->table ] = $wpdb->prepare( sprintf( $sql_format, $db->table ) . 'AND active=1 ' . $order, $current_scope );
	}

	/* Fetch snippets from the network table */
	if ( is_multisite() && $wpdb->get_var( "SHOW TABLES LIKE '$db->ms_table'" ) === $db->ms_table ) {
		$active_shared_ids = get_option( 'active_shared_network_snippets', array() );

		/* If there are active shared snippets, include them in the query */
		if ( is_array( $active_shared_ids ) && count( $active_shared_ids ) ) {

			/* Build a list of "%d, %d, %d ..." for every active network shared snippet we have */
			$active_shared_ids_format = implode( ',', array_fill( 0, count( $active_shared_ids ), '%d' ) );

			/* Include them in the query */
			$sql = sprintf( $sql_format, $db->ms_table ) . " AND (active=1 OR id IN ($active_shared_ids_format)) $order";

			/* Add the scope number to the IDs array, so that it is the first variable in the query */
			array_unshift( $active_shared_ids, $current_scope );
			$queries[ $db->ms_table ] = $wpdb->prepare( $sql, $active_shared_ids );
			array_shift( $active_shared_ids ); // remove it afterwards as we need this variable later

		} else {
			$sql                      = sprintf( $sql_format, $db->ms_table ) . 'AND active=1 ' . $order;
			$queries[ $db->ms_table ] = $wpdb->prepare( $sql, $current_scope );
		}
	}

	foreach ( $queries as $table_name => $query ) {
		$active_snippets = $wpdb->get_results( $query, 'ARRAY_A' );

		if ( ! is_array( $active_snippets ) ) {
			continue;
		}

		/* Loop through the returned snippets and execute the PHP code */
		foreach ( $active_snippets as $snippet ) {
			$snippet_id = intval( $snippet['id'] );
			$code       = $snippet['code'];

			// if the snippet is a single-use snippet, deactivate it before execution to ensure that the process always happens
			if ( 'single-use' === $snippet['scope'] ) {
				if ( $table_name === $db->ms_table && isset( $active_shared_ids ) &&
				     false !== ( $key = array_search( $snippet_id, $active_shared_ids, true ) ) ) {
					unset( $active_shared_ids[ $key ] );
					$active_shared_ids = array_values( $active_shared_ids );
					update_option( 'active_shared_network_snippets', $active_shared_ids );
				} else {
					$wpdb->update( $table_name, array( 'active' => '0' ), array( 'id' => $snippet_id ), array( '%d' ), array( '%d' ) );
				}
			}

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
				execute_snippet( filter_snippet($code, unserialize($snippet['snippet_settings']), unserialize($snippet['snippet_values'])), $snippet_id );
			}
		}
	}

	return true;
}


/**
 * Pushes a snippet to the database
 *
 * @param int $id The ID of the snippet to push
 *
 */

// Snippet popup - work in progress
function prepare_snippet_to_push( $id ){

    $snippet_id = $id;

    echo "
    <div onclick=this.style.display='none';document.getElementById('push-popup').style.display='none'; style='z-index: 999;position: fixed;left: 0;top: 0;width: 100%;height: 100%;background: rgba(0,0,0,.5);'></div>
    <div style='z-index:1000;position: fixed;left: 50%;top: 50%;width:400px;padding: 2px;background: white;border-radius: 2px;transform: translate(-50%, -50%);' id='push-popup'>
        <div style='width: calc(100% - 20px);height: 50px;padding-left:20px;color:white;font-size:20px;line-height:50px;background: #2271b1;border-radius: 2px 2px 0 0'>Push snippet</div>
        <form method='post' style='padding: 20px'>
            <label style='width: 40%;float: left;font-size: 16px;font-weight: 600'>Description:</label>
            <textarea placeholder='Snippet description' type='text' name='desc' style='float:left;width: calc(60% - 2px);' rows='4'></textarea>
            <div style='float:right;display:block;width:100%;margin:10px 0 20px 0'>
                <button type='submit' style='cursor: pointer;float: right;width: 25%;height:30px;border: none;background: #2c3338;color: white' name='push-snippet-final' value='" . $snippet_id . "'>Push</button>
            </div>
        </form>
    </div>
    ";


}

function push_snippet( $id ){

    $site_url = get_home_url();

    $snippet = get_snippet( $id );

    $snippet_url = preg_replace('/[[:space:]]+/', '-', strtolower($snippet->name));

    if(isset($_POST['desc'])){
        $snippet_desc = $_POST['desc'];
    }else{
        $snippet_desc = "";
    }

    $username = 'pavel';
    $password = '2OOj$^o8RsDCNXSZz)F@b!XU';
    $rest_api_url_create = 'https://wpdistro.com/wp-json/wp/v2/posts';

    $data_string = json_encode([
        'title'             => $snippet->name,
        'content'           => $snippet_desc . '
                <br>
                | This snippet was pushed from <strong>' . $site_url . '</strong>
            ',
        'status'            => 'publish',
        'featured_media'    => '499',
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rest_api_url_create);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string),
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $response = json_decode($result, true);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        var_dump($error_msg);
    }

    curl_close($ch);

    $rest_api_url_edit = 'https://wpdistro.com/wp-json/acf/v3/posts/'. $response["id"]. '/code';
    $code = json_encode([
        'fields' => [
            'code' => $snippet->code
        ]
    ]);

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $rest_api_url_edit);
    curl_setopt($ch2, CURLOPT_PUT, 0);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $code);

    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($code),
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
    ]);

    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    if (curl_errno($ch2)) {
        $error_msg = curl_error($ch2);
        var_dump($error_msg);
    }
    $result = curl_exec($ch2);
    curl_close($ch2);


    // Set remote_id in the database

    global $wpdb;
    $table = "wp_snippets";

    $remote_id = $response["id"];

    $wpdb->update( $table, array( 'remote' => '1' ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
    $wpdb->update( $table, array( 'remote_id' => $remote_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

}



