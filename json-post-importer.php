<?php
/**
 * Plugin Name: JSON Post Importer
 * Plugin URI:  https://github.com/drhdev/json-post-importer/
 * Description: Import posts via a JSON string pasted into a form. To use, insert the shortcode [json_post_importer_form] into a page. Only logged-in users whose roles are allowed (by default, administrators) can access the form. The plugin validates the JSON (which must include at least “title” and “content”) and displays clear error messages for invalid input. Additional options are available on the settings page for customizing the post type, redirect URL, debug mode (errors are logged to wp-content/debug.log), allowed roles, and the size of the JSON input textarea.
 * Version:     1.3.1
 * Author:      drhdev
 * Author URI:  https://github.com/drhdev/
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class JSON_Post_Importer {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Directory path for storing JSON files.
	 *
	 * @var string
	 */
	private $upload_dir;

	/**
	 * Constructor: initializes options, backend hooks, and shortcode.
	 */
	public function __construct() {
		// Load saved options or use default values.
		$this->options = get_option( 'json_post_importer_options', $this->default_options() );
		// Determine upload directory (wp-content/uploads/json_post_importer).
		$upload           = wp_upload_dir();
		$this->upload_dir = trailingslashit( $upload['basedir'] ) . 'json_post_importer';

		// Add a top-level admin menu page.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register shortcode for the frontend form.
		add_shortcode( 'json_post_importer_form', array( $this, 'render_form_shortcode' ) );

		// Process form submissions.
		add_action( 'init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Default plugin options.
	 *
	 * @return array
	 */
	private function default_options() {
		return array(
			'default_status'      => 'draft',
			'default_categories'  => '',
			'default_tags'        => '',
			'default_post_type'   => 'post',
			'redirect_url'        => '',
			'debug_mode'          => false,
			'allowed_roles'       => 'administrator',
			'confirmation_text'   => 'Post created successfully. View: {view_link} | Edit: {edit_link}',
			'store_json_file'     => false,
			'json_textarea_rows'  => 10,
		);
	}

	/**
	 * Adds a top-level menu page in the WordPress admin.
	 */
	public function add_menu_page() {
		add_menu_page(
			'JSON Post Importer',
			'JSON Post Importer',
			'manage_options',
			'json-post-importer',
			array( $this, 'settings_page_html' ),
			'dashicons-admin-post',
			6
		);
	}

	/**
	 * Outputs the settings page HTML.
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>JSON Post Importer Settings</h1>
			<p>
				Use this page to configure default values and options for the JSON Post Importer plugin.
				To use the plugin, insert the shortcode <code>[json_post_importer_form]</code> into a page.
				Only users with allowed roles (by default, administrators) can access the form.
				Posts will automatically be assigned to the currently logged-in user who submits the form.
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'json_post_importer_options_group' );
				do_settings_sections( 'json-post-importer' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the plugin settings and fields.
	 */
	public function register_settings() {
		register_setting( 'json_post_importer_options_group', 'json_post_importer_options', array( $this, 'sanitize_options' ) );

		add_settings_section(
			'json_post_importer_main_section',
			'Main Settings',
			null,
			'json-post-importer'
		);

		// Default post status.
		add_settings_field(
			'default_status',
			'Default Post Status',
			array( $this, 'default_status_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Default categories.
		add_settings_field(
			'default_categories',
			'Default Categories (Comma separated IDs)',
			array( $this, 'default_categories_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Default tags.
		add_settings_field(
			'default_tags',
			'Default Tags (Comma separated)',
			array( $this, 'default_tags_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Default post type.
		add_settings_field(
			'default_post_type',
			'Default Post Type',
			array( $this, 'default_post_type_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Redirect URL.
		add_settings_field(
			'redirect_url',
			'Redirect URL',
			array( $this, 'redirect_url_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Debug mode.
		add_settings_field(
			'debug_mode',
			'Debug Mode',
			array( $this, 'debug_mode_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Allowed roles.
		add_settings_field(
			'allowed_roles',
			'Allowed Roles',
			array( $this, 'allowed_roles_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Confirmation text.
		add_settings_field(
			'confirmation_text',
			'Confirmation Text (use {view_link} and {edit_link} placeholders)',
			array( $this, 'confirmation_text_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// Store JSON file.
		add_settings_field(
			'store_json_file',
			'Store JSON as File',
			array( $this, 'store_json_file_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);

		// JSON Textarea rows.
		add_settings_field(
			'json_textarea_rows',
			'JSON Textarea Rows',
			array( $this, 'json_textarea_rows_callback' ),
			'json-post-importer',
			'json_post_importer_main_section'
		);
	}

	/**
	 * Sanitizes options before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$new_input = array();
		$new_input['default_status']      = in_array( $input['default_status'], array( 'draft', 'publish' ), true ) ? $input['default_status'] : 'draft';
		$new_input['default_categories']  = sanitize_text_field( $input['default_categories'] );
		$new_input['default_tags']        = sanitize_text_field( $input['default_tags'] );
		$new_input['default_post_type']   = sanitize_key( $input['default_post_type'] );
		$new_input['redirect_url']        = esc_url_raw( trim( $input['redirect_url'] ) );
		$new_input['debug_mode']          = isset( $input['debug_mode'] ) && $input['debug_mode'] ? true : false;
		$new_input['allowed_roles']       = sanitize_text_field( $input['allowed_roles'] );
		$new_input['confirmation_text']   = sanitize_textarea_field( $input['confirmation_text'] );
		$new_input['store_json_file']     = isset( $input['store_json_file'] ) && $input['store_json_file'] ? true : false;
		$new_input['json_textarea_rows']  = isset( $input['json_textarea_rows'] ) ? intval( $input['json_textarea_rows'] ) : 10;
		return $new_input;
	}

	/**
	 * Callback for Default Post Status field.
	 */
	public function default_status_callback() {
		$status = isset( $this->options['default_status'] ) ? $this->options['default_status'] : 'draft';
		?>
		<select name="json_post_importer_options[default_status]">
			<option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
			<option value="publish" <?php selected( $status, 'publish' ); ?>>Publish</option>
		</select>
		<?php
	}

	/**
	 * Callback for Default Categories field.
	 */
	public function default_categories_callback() {
		$categories = isset( $this->options['default_categories'] ) ? $this->options['default_categories'] : '';
		?>
		<input type="text" name="json_post_importer_options[default_categories]" value="<?php echo esc_attr( $categories ); ?>" style="width: 300px;">
		<p class="description">Comma separated category IDs (e.g., 2,3,5).</p>
		<?php
	}

	/**
	 * Callback for Default Tags field.
	 */
	public function default_tags_callback() {
		$tags = isset( $this->options['default_tags'] ) ? $this->options['default_tags'] : '';
		?>
		<input type="text" name="json_post_importer_options[default_tags]" value="<?php echo esc_attr( $tags ); ?>" style="width: 300px;">
		<p class="description">Comma separated tags (e.g., Tag1,Tag2).</p>
		<?php
	}

	/**
	 * Callback for Default Post Type field.
	 */
	public function default_post_type_callback() {
		$current = isset( $this->options['default_post_type'] ) ? $this->options['default_post_type'] : 'post';
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<select name="json_post_importer_options[default_post_type]">
			<?php foreach ( $post_types as $post_type ) : ?>
				<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $current, $post_type->name ); ?>>
					<?php echo esc_html( $post_type->labels->singular_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">Select the post type to create.</p>
		<?php
	}

	/**
	 * Callback for Redirect URL field.
	 */
	public function redirect_url_callback() {
		$url = isset( $this->options['redirect_url'] ) ? $this->options['redirect_url'] : '';
		?>
		<input type="text" name="json_post_importer_options[redirect_url]" value="<?php echo esc_attr( $url ); ?>" style="width: 300px;">
		<p class="description">Enter a custom URL to redirect after submission. Leave blank to reload the same page.</p>
		<?php
	}

	/**
	 * Callback for Debug Mode field.
	 */
	public function debug_mode_callback() {
		$debug = isset( $this->options['debug_mode'] ) ? $this->options['debug_mode'] : false;
		?>
		<input type="checkbox" name="json_post_importer_options[debug_mode]" value="1" <?php checked( $debug, true ); ?>>
		<p class="description">Enable debug mode to log extra error details to <code>wp-content/debug.log</code> (requires WP_DEBUG and WP_DEBUG_LOG to be enabled).</p>
		<?php
	}

	/**
	 * Callback for Allowed Roles field.
	 */
	public function allowed_roles_callback() {
		$roles = isset( $this->options['allowed_roles'] ) ? $this->options['allowed_roles'] : 'administrator';
		?>
		<input type="text" name="json_post_importer_options[allowed_roles]" value="<?php echo esc_attr( $roles ); ?>" style="width: 300px;">
		<p class="description">Enter allowed roles (comma separated, e.g., administrator, editor). Only users with these roles can access the form.</p>
		<?php
	}

	/**
	 * Callback for Confirmation Text field.
	 */
	public function confirmation_text_callback() {
		$text = isset( $this->options['confirmation_text'] ) ? $this->options['confirmation_text'] : 'Post created successfully. View: {view_link} | Edit: {edit_link}';
		?>
		<textarea name="json_post_importer_options[confirmation_text]" rows="4" cols="50"><?php echo esc_textarea( $text ); ?></textarea>
		<?php
	}

	/**
	 * Callback for Store JSON File field.
	 */
	public function store_json_file_callback() {
		$store = isset( $this->options['store_json_file'] ) ? $this->options['store_json_file'] : false;
		?>
		<input type="checkbox" name="json_post_importer_options[store_json_file]" value="1" <?php checked( $store, true ); ?>>
		<p class="description">If checked, the JSON will also be stored as a file in a secure directory.</p>
		<?php
	}

	/**
	 * Callback for JSON Textarea Rows field.
	 */
	public function json_textarea_rows_callback() {
		$rows = isset( $this->options['json_textarea_rows'] ) ? intval( $this->options['json_textarea_rows'] ) : 10;
		?>
		<input type="number" name="json_post_importer_options[json_textarea_rows]" value="<?php echo esc_attr( $rows ); ?>" min="1" style="width: 80px;">
		<p class="description">Set the number of rows for the JSON input textarea. This controls its height. The width is set to 100% for responsiveness.</p>
		<?php
	}

	/**
	 * Checks whether the current user is allowed to view the form.
	 *
	 * @return bool
	 */
	private function is_user_allowed() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$allowed = array_map( 'trim', explode( ',', $this->options['allowed_roles'] ) );
		$current_user = wp_get_current_user();
		if ( ! isset( $current_user->roles ) || ! is_array( $current_user->roles ) ) {
			return false;
		}
		foreach ( $current_user->roles as $role ) {
			if ( in_array( $role, $allowed, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Renders the shortcode: displays the frontend form.
	 *
	 * Only allowed users may view the form.
	 *
	 * @return string
	 */
	public function render_form_shortcode() {
		if ( ! $this->is_user_allowed() ) {
			return '<p>You do not have permission to view this form.</p>';
		}
		ob_start();
		?>
		<form method="post">
			<?php wp_nonce_field( 'json_post_importer_form', 'json_post_importer_nonce' ); ?>
			<label for="json_post_importer_data">Paste your JSON here:</label>
			<textarea id="json_post_importer_data" name="json_post_importer_data" rows="<?php echo esc_attr( $this->options['json_textarea_rows'] ); ?>" style="width:100%;"></textarea>
			<br>
			<input type="submit" name="json_post_importer_submit" value="Create Post">
		</form>
		<?php
		if ( $message = get_transient( 'json_post_importer_confirmation' ) ) {
			echo '<div class="notice notice-success">' . wp_kses_post( $message ) . '</div>';
			delete_transient( 'json_post_importer_confirmation' );
		}
		return ob_get_clean();
	}

	/**
	 * Processes the form submission.
	 *
	 * Only allowed users may process the form.
	 */
	public function handle_form_submission() {
		if ( ! $this->is_user_allowed() ) {
			return;
		}

		if ( isset( $_POST['json_post_importer_submit'] ) ) {
			// Verify nonce.
			if ( ! isset( $_POST['json_post_importer_nonce'] ) || ! wp_verify_nonce( $_POST['json_post_importer_nonce'], 'json_post_importer_form' ) ) {
				set_transient( 'json_post_importer_confirmation', 'Security check failed.', 30 );
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}

			$json_input   = wp_unslash( $_POST['json_post_importer_data'] );
			$decoded_data = json_decode( $json_input, true );

			// Validate JSON.
			if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded_data ) ) {
				$error_message = 'Invalid JSON provided: ' . json_last_error_msg();
				set_transient( 'json_post_importer_confirmation', $error_message, 30 );
				if ( $this->options['debug_mode'] ) {
					error_log( 'JSON Post Importer error: ' . $error_message );
				}
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}

			// Check for required fields.
			if ( empty( $decoded_data['title'] ) || empty( $decoded_data['content'] ) ) {
				$error_message = 'Error: JSON must include both "title" and "content" fields.';
				set_transient( 'json_post_importer_confirmation', $error_message, 30 );
				if ( $this->options['debug_mode'] ) {
					error_log( 'JSON Post Importer error: ' . $error_message );
				}
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}

			$options      = $this->options;
			$post_title   = sanitize_text_field( $decoded_data['title'] );
			$post_content = wp_kses_post( $decoded_data['content'] );
			$post_status  = ( isset( $decoded_data['status'] ) && in_array( $decoded_data['status'], array( 'draft', 'publish' ), true ) )
				? $decoded_data['status']
				: $options['default_status'];
			$post_date    = isset( $decoded_data['date'] ) ? date( 'Y-m-d H:i:s', strtotime( $decoded_data['date'] ) ) : current_time( 'mysql' );
			$post_author  = get_current_user_id();

			// Process categories.
			if ( isset( $decoded_data['categories'] ) && is_array( $decoded_data['categories'] ) ) {
				$categories = array_map( 'intval', $decoded_data['categories'] );
			} elseif ( ! empty( $options['default_categories'] ) ) {
				$categories = array_map( 'intval', array_map( 'trim', explode( ',', $options['default_categories'] ) ) );
			} else {
				$categories = array();
			}

			// Process tags.
			if ( isset( $decoded_data['tags'] ) && is_array( $decoded_data['tags'] ) ) {
				$tags = array_map( 'sanitize_text_field', $decoded_data['tags'] );
			} elseif ( ! empty( $options['default_tags'] ) ) {
				$tags = array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $options['default_tags'] ) ) );
			} else {
				$tags = array();
			}

			// Create the post with the chosen post type.
			$post_id = wp_insert_post( array(
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_status'  => $post_status,
				'post_date'    => $post_date,
				'post_author'  => $post_author,
				'post_type'    => $options['default_post_type'],
			) );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$error_message = 'Error creating post.';
				set_transient( 'json_post_importer_confirmation', $error_message, 30 );
				if ( $options['debug_mode'] ) {
					error_log( 'JSON Post Importer error: ' . $error_message );
				}
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}

			// Set categories and tags.
			if ( ! empty( $categories ) ) {
				wp_set_post_categories( $post_id, $categories );
			}
			if ( ! empty( $tags ) ) {
				wp_set_post_tags( $post_id, $tags );
			}

			// Save additional fields as post meta (excluding known keys).
			foreach ( $decoded_data as $key => $value ) {
				if ( ! in_array( $key, array( 'title', 'content', 'status', 'date', 'categories', 'tags', 'author' ), true ) && ! is_array( $value ) ) {
					update_post_meta( $post_id, sanitize_key( $key ), sanitize_text_field( $value ) );
				}
			}

			// Optionally store the JSON as a file.
			if ( $options['store_json_file'] ) {
				$this->store_json_file( $json_input, $post_title );
			}

			// Prepare confirmation text.
			$view_link = get_permalink( $post_id );
			$edit_link = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
			$confirmation_text = str_replace(
				array( '{view_link}', '{edit_link}' ),
				array( '<a href="' . esc_url( $view_link ) . '">View Post</a>', '<a href="' . esc_url( $edit_link ) . '">Edit Post</a>' ),
				$options['confirmation_text']
			);
			set_transient( 'json_post_importer_confirmation', $confirmation_text, 30 );

			// Redirect.
			$redirect = ! empty( $options['redirect_url'] ) ? $options['redirect_url'] : $_SERVER['REQUEST_URI'];
			wp_redirect( add_query_arg( 'json_post_importer_success', '1', $redirect ) );
			exit;
		}
	}

	/**
	 * Stores the JSON as a file in the format YYYYMMDD_TITLE.json in the defined directory.
	 *
	 * @param string $json_data
	 * @param string $post_title
	 */
	private function store_json_file( $json_data, $post_title ) {
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );
		}
		$sanitized_title = sanitize_title( $post_title );
		$date_prefix     = date( 'Ymd' );
		$filename        = $date_prefix . '_' . $sanitized_title . '.json';
		$filepath        = trailingslashit( $this->upload_dir ) . $filename;
		$result = file_put_contents( $filepath, $json_data, LOCK_EX );
		if ( false === $result ) {
			error_log( 'JSON Post Importer: Failed to write JSON file to ' . $filepath );
		}
	}
}

new JSON_Post_Importer();
