<?php

if ( !defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_action( 'plugins_loaded', [ Codeable_Users_Table::get_instance(), 'codeable_users_table_load_plugin_textdomain' ] );

register_activation_hook( __FILE__, [ Codeable_Users_Table::get_instance(), 'plugin_activated' ] );
register_deactivation_hook( __FILE__, [ Codeable_Users_Table::get_instance(), 'plugin_deactivated' ] );

class Codeable_Users_Table {

	private static $instance;
	private $plugin_dir;
	private $plugin_path;

	public function __construct() {

		$this->plugin_dir = plugin_dir_url( dirname(__FILE__) );
		$this->plugin_path = plugin_dir_path( dirname(__FILE__) );

		// load text domains
		add_action( 'init', [ $this, 'codeable_users_table_load_plugin_textdomain' ] );

		// register shortcode
		add_shortcode( 'codeable_users_table', [ $this, 'codeable_users_table_shortcode' ] );
		//enqueue front assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_assets' ] );
		// ajax fallbacks
		add_action( 'wp_ajax_codeable_users_table_datatables', [ $this, 'codeable_users_table_datatables_callback' ] );
		add_action( 'wp_ajax_nopriv_codeable_users_table_datatables', [ $this, 'codeable_users_table_datatables_callback' ] );
		// add additional filter column for users
		add_filter( 'user_search_columns', [ $this, 'filter_function_name' ], 10, 3 );

	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Codeable_Users_Table();
		}

		return self::$instance;
	}

	function codeable_users_table_load_plugin_textdomain() {
		load_plugin_textdomain( 'codeable-user-tables', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 *
	 * Plugin activation hook.
	 * will create a test page named "Codeable Users Table Shortcode Page" with shortcode in content ready for preview
	 */
	public static function plugin_activated() {

		// define page array
		$page_definitions = [
			'codeable-users-table-shortcode-page' => [
				'title' => __( 'codeable-users-table-shortcode-page', 'codeable-user-tables' ),
				'content' => '[codeable_users_table]'
			],
		];

		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already
			$query = new WP_Query( 'pagename=' . $slug );
			if ( ! $query->have_posts() ) {
				// Add the page using the data from the array above
				wp_insert_post(
					[
						'post_content'   => $page['content'],
						'post_name'      => $slug,
						'post_title'     => $page['title'],
						'post_status'    => 'publish',
						'post_type'      => 'page',
						'ping_status'    => 'closed',
						'comment_status' => 'closed',
					]
				);
			}
		}
	}

	/**
	 *
	 * Plugin deactivation hook.
	 * will delete the test page named "Codeable Users Table Shortcode Page"
	 */
	public static function plugin_deactivated() {

		// define page array
		$page_definitions = [
			'codeable-users-table-shortcode-page' => [
				'title' => __( 'codeable-users-table-shortcode-page', 'codeable-user-tables' ),
				'content' => '[codeable_users_table]'
			],
		];

		foreach ( $page_definitions as $slug => $page ) {
			// remove all the data we created
			wp_delete_post( get_page_by_path( $slug ), true );
		}

	}

	/**
	* Enqueue the static assets front-end (js/css)
	*/
	public function enqueue_front_assets() {
		global $post;
		if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'codeable_users_table' ) ) {
			// jquery is dependency
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'front-script', $this->plugin_dir . 'assets/js/front-script.js', [ 'jquery' ], '1.0', true );
			wp_localize_script( 'front-script', 'ajax_url', admin_url( 'admin-ajax.php?action=codeable_users_table_datatables' ) );
			wp_register_script( 'datatables', $this->plugin_dir . 'assets/js/jquery.dataTables.min.js', [
				'jquery',
				'front-script'
			], '1.0', true );
			wp_enqueue_script( 'datatables' );
			wp_register_style( 'datatables_style', $this->plugin_dir . 'assets/css/jquery.dataTables.min.css' );
			wp_enqueue_style( 'datatables_style' );
		}
	}

	/**
     * @param array $atts
     * @param null $content
     * Shortcode callback for displaying the output
     */
	public function codeable_users_table_shortcode( $atts, $content = null ) {
		ob_start();
		if ( current_user_can( 'install_plugins' ) ) {
			include( $this->plugin_path . 'views/front/codeable-user-tables.php' );
		} else {
			echo __( 'You\'re not authorized to see the content', 'codeable-user-tables' );
		}
		return ob_get_clean();
	}

	/**
	 * @param void
     * ajax call callback fetching the users by filters
	 */
	public function codeable_users_table_datatables_callback() {

		header("Content-Type: application/json");

		$request= $_REQUEST;

		$columns = [
			0 => 'user_login',
			1 => 'display_name',
			2 => 'meta_value'
		];

		// WP_User_Query arguments
		$args = [
			'search' => '*' . esc_attr( $request['search']['value'] ) . '*',
			'search_columns' => [ 'user_login', 'display_name' ],
			'order' => ( isset( $request['order'][0]['dir'] ) && '' !== $request['order'][0]['dir'] ) ? $request['order'][0]['dir'] : $request['order'][0]['dir'],
			'orderby' => $columns[$request['order'][0]['column']],
            'number' => intval( $request['length'] ),
			'offset' => intval( $request['start'] ),
		];

		if ( isset( $request['columns'][2]['search']['value'] ) && '' !== $request['columns'][2]['search']['value'] ) {
			$args['role__in'] = [ $request['columns'][2]['search']['value'] ];
		}

		if ( $request['order'][0]['column'] == 2 ) {

			global $wpdb;
			$blog_id = get_current_blog_id();

			$args['meta_query'] = [
				'relation' => 'AND',
				[
					'key' => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
					'value' => $request['search']['value'],
					'compare' => 'like'
				]
			];
		}

		// Create the WP_User_Query object
		$wp_user_query = new WP_User_Query( $args );

		// Get the results
		$users = $wp_user_query->get_results();

		$totalData = $wp_user_query->get_total();

		if ( ! empty( $users ) ) {

			foreach ( $users as $user ) {
				$user_info = get_userdata($user->ID);
				$nestedData = [];

				$nestedData[] = $user->user_login;
				$nestedData[] = $user->display_name;
				$nestedData[] = implode(', ', $user_info->roles);

				$data[] = $nestedData;
			}

			$json_data = [
				"draw" => intval( $request['draw'] ),
				"recordsTotal" => intval( $totalData ),
				"recordsFiltered" => intval( $totalData ),
				"data" => $data,
			];

			echo wp_json_encode( $json_data );
		} else {

			$json_data = [
				"data" => []
			];

			echo wp_json_encode( $json_data );
		}
		wp_die();
	}

	/**
	 * @param array $search_columns
     * @param string $search
     * @param object $wp_user_query
	 * filter for extending the user search by option in the user query by additional db table column
	 */
	public function filter_function_name( $search_columns, $search, $wp_user_query ) {
		$search_columns[] = 'display_name';
		return $search_columns;
	}

}