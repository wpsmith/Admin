<?php
/**
 * WP Admin Abstract Class.
 *
 * Abstract base class to create menus and settings pages (with or without sortable meta boxes).
 *
 * This class is extended by subclasses that define specific types of admin pages.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * PHP Version 7.2
 *
 * @package   WPS\WP\Admin
 * @author    Travis Smith <t@wpsmith.net>
 * @copyright 2018-2019 Travis Smith
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link      https://github.com/akamai/wp-akamai
 * @since     0.2.0
 */

namespace WPS\WP\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Admin' ) ) {
	/**
	 * Class Admin.
	 *
	 * @package WPS\Admin
	 */
	abstract class Admin {

		/**
		 * Name of the page hook when the menu is registered.
		 *
		 * @since 1.8.0
		 *
		 * @var string Page hook
		 */
		public $pagehook;

		/**
		 * ID of the admin menu and settings page.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $page_id;

		/**
		 * Name of the settings field in the options table.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $settings_field;

		/**
		 * Associative array (field name => values) for the default settings on this
		 * admin page.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $default_settings;

		/**
		 * Associative array of configuration options for the admin menu(s).
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $menu_ops;

		/**
		 * Associative array of configuration options for the settings page.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $page_ops;

		/**
		 * Help view file base.
		 *
		 * @since 2.5.0
		 *
		 * @var string
		 */
		protected $help_base;

		/**
		 * Views path base.
		 *
		 * @since 2.5.0
		 *
		 * @var string
		 */
		protected $views_base;

		/**
		 * Call this method in a subclass constructor to create an admin menu and settings page.
		 *
		 * @since 1.8.0
		 *
		 * @param string $page_id          ID of the admin menu and settings page.
		 * @param array  $menu_ops         Optional. Config options for admin menu(s). Default is empty array.
		 * @param array  $page_ops         Optional. Config options for settings page. Default is empty array.
		 * @param string $settings_field   Optional. Name of the settings field. Default is an empty string.
		 * @param array  $default_settings Optional. Field name => values for default settings. Default is empty array.
		 *
		 * @return void Return early if page ID is not set.
		 */
		public function create( $page_id = '', array $menu_ops = array(), array $page_ops = array(), $settings_field = 'wps-settings', array $default_settings = array() ) {

			$this->page_id = $this->page_id ? $this->page_id : $page_id;

			if ( ! $this->page_id ) {
				return;
			}

			$this->menu_ops         = $this->menu_ops ? $this->menu_ops : $menu_ops;
			$this->page_ops         = $this->page_ops ? $this->page_ops : $page_ops;
			$this->settings_field   = $this->settings_field ? $this->settings_field : $settings_field;
			$this->default_settings = $this->default_settings ? $this->default_settings : $default_settings;
			$this->help_base        = $this->help_base ? $this->help_base : dirname( __FILE__ ) . '/help/' . $page_id . '-';
			$this->views_base       = $this->views_base ? $this->views_base : dirname( __FILE__ );

			$this->page_ops = wp_parse_args(
				$this->page_ops,
				array(
					'save_button_text'  => __( 'Save Changes', 'wps' ),
					'reset_button_text' => __( 'Reset Settings', 'wps' ),
					'saved_notice_text' => __( 'Settings saved.', 'wps' ),
					'reset_notice_text' => __( 'Settings reset.', 'wps' ),
					'error_notice_text' => __( 'Error saving settings.', 'wps' ),
				)
			);

			// Check to make sure there we are only creating one menu per subclass.
			if ( isset( $this->menu_ops['submenu'] ) && ( isset( $this->menu_ops['main_menu'] ) || isset( $this->menu_ops['first_submenu'] ) ) ) {
				/* translators: %s: Admin class name. */
				wp_die( sprintf( __( 'You cannot use %s to create two menus in the same subclass. Please use separate subclasses for each menu.', 'wps' ), __CLASS__ ) );
			}

			// Create the menu(s). Conditional logic happens within the separate methods.
			add_action( 'admin_menu', array( $this, 'maybe_add_main_menu' ), 5 );
			add_action( 'admin_menu', array( $this, 'maybe_add_first_submenu' ), 5 );
			add_action( 'admin_menu', array( $this, 'maybe_add_submenu' ), PHP_INT_MAX );

			// Set up settings and notices.
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_notices', array( $this, 'notices' ) );

			// Load the page content (meta boxes or custom form).
			add_action( 'admin_init', array( $this, 'settings_init' ) );

			// Load help tab.
			add_action( 'admin_init', array( $this, 'load_help' ) );

			// Load contextual assets (registered admin page).
			add_action( 'admin_init', array( $this, 'load_assets' ) );

			add_action( 'admin_print_styles', array( $this, 'load_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			// Add a sanitizer/validator.
			add_filter( 'pre_update_option_' . $this->settings_field, array( $this, 'save' ), 10, 2 );

		}

		public function load_admin_styles() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style(
				'wps_admin_css',
				plugin_dir_url( __FILE__ ) . "assets/css/admin{$suffix}.css",
					null,
					filemtime( plugin_dir_path( __FILE__ ) . "assets/css/admin{$suffix}.css" )
			);

		}

		public function admin_enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script(
				'wps_admin_js',
				plugin_dir_url( __FILE__ ) . "assets/js/admin{$suffix}.js",
					array( 'jquery' ),
					filemtime( plugin_dir_path( __FILE__ ) . "assets/js/admin{$suffix}.js" )
			);

			$strings = array(
				'categoryChecklistToggle' => __( 'Select / Deselect All', 'wps' ),
				'saveAlert'               => __( 'The changes you made will be lost if you navigate away from this page.', 'wps' ),
				'confirmReset'            => __( 'Are you sure you want to reset?', 'wps' ),
			);
			wp_localize_script( 'wps_admin_js', 'wpsL10n', $strings );

			$toggles = array(
				// Checkboxes - when checked, show extra settings.
				'update'                    => array( '#genesis-settings\\[update\\]', '#genesis_update_notification_setting', '_checked' ),
				'content_archive_thumbnail' => array( '#genesis-settings\\[content_archive_thumbnail\\]', '#genesis_image_extras', '_checked' ),
				// Checkboxes - when unchecked, show extra settings.
				'semantic_headings'         => array( '#genesis-seo-settings\\[semantic_headings\\]', '#genesis_seo_h1_wrap', '_unchecked' ),
				// Select toggles.
				'nav_extras'                => array( '#genesis-settings\\[nav_extras\\]', '#genesis_nav_extras_twitter', 'twitter' ),
				'content_archive'           => array( '#genesis-settings\\[content_archive\\]', '#genesis_content_limit_setting', 'full' ),
			);
			wp_localize_script( 'wps_admin_js', 'wps_toggles', apply_filters( 'wps_toggles', $toggles ) );


		}

		/**
		 * Possibly create a new top level admin menu.
		 *
		 * @since 1.8.0
		 */
		public function maybe_add_main_menu() {

			// Maybe add a menu separator.
			if ( isset( $this->menu_ops['main_menu']['sep'] ) ) {
				$sep = wp_parse_args(
					$this->menu_ops['main_menu']['sep'],
					array(
						'sep_position'   => '',
						'sep_capability' => '',
					)
				);

				if ( $sep['sep_position'] && $sep['sep_capability'] ) {
					$GLOBALS['menu'][ $sep['sep_position'] ] = array(
						'',
						$sep['sep_capability'],
						'separator',
						'',
						'wps-separator wp-menu-separator'
					);
				}
			}

			// Maybe add main menu.
			if ( isset( $this->menu_ops['main_menu'] ) && is_array( $this->menu_ops['main_menu'] ) ) {
				$menu = wp_parse_args(
					$this->menu_ops['main_menu'],
					array(
						'page_title' => '',
						'menu_title' => '',
						'capability' => 'edit_theme_options',
						'icon_url'   => '',
						'position'   => '',
					)
				);

				$this->pagehook = add_menu_page(
					$menu['page_title'],
					$menu['menu_title'],
					$menu['capability'],
					$this->page_id,
					array( $this, 'admin' ),
					$menu['icon_url'],
					$menu['position']
				);
			}

		}

		/**
		 * Possibly create the first submenu item.
		 *
		 * Because the main menu and first submenu item are usually linked, if you
		 * don't create them at the same time, something can sneak in between the
		 * two, specifically custom post type menu items that are assigned to the
		 * custom top-level menu.
		 *
		 * Plus, maybe_add_first_submenu takes the guesswork out of creating a
		 * submenu of the top-level menu you just created. It's a shortcut of sorts.
		 *
		 * @since 1.8.0
		 */
		public function maybe_add_first_submenu() {

			// Maybe add first submenu.
			if ( isset( $this->menu_ops['first_submenu'] ) && is_array( $this->menu_ops['first_submenu'] ) ) {
				$menu = wp_parse_args(
					$this->menu_ops['first_submenu'],
					array(
						'page_title' => '',
						'menu_title' => '',
						'capability' => 'edit_theme_options',
					)
				);

				$this->pagehook = add_submenu_page(
					$this->page_id,
					$menu['page_title'],
					$menu['menu_title'],
					$menu['capability'],
					$this->page_id,
					array( $this, 'admin' )
				);
			}

		}

		/**
		 * Possibly create a submenu item.
		 *
		 * @since 1.8.0
		 */
		public function maybe_add_submenu() {

			// Maybe add submenu.
			if ( isset( $this->menu_ops['submenu'] ) && is_array( $this->menu_ops['submenu'] ) ) {
				$menu = wp_parse_args(
					$this->menu_ops['submenu'],
					array(
						'parent_slug' => '',
						'page_title'  => '',
						'menu_title'  => '',
						'capability'  => 'edit_theme_options',
					)
				);

				$this->pagehook = add_submenu_page(
					$menu['parent_slug'],
					$menu['page_title'],
					$menu['menu_title'],
					$menu['capability'],
					$this->page_id,
					array( $this, 'admin' )
				);
			}

		}

		/**
		 * Check that we're targeting a specific admin page.
		 *
		 * The `$pagehook` argument is any admin pagehook.
		 *
		 * @since 1.8.0
		 *
		 * @global string $page_hook Page hook for current page.
		 *
		 * @param string  $pagehook  Page hook string to check.
		 *
		 * @return bool Return `true` if the global `$page_hook` matches given `$pagehook`, `false` otherwise.
		 */
		public static function is_menu_page( $pagehook = '' ) {
			global $page_hook;
			if ( isset( $page_hook ) && $page_hook === $pagehook ) {
				return true;
			}
			// May be too early for $page_hook.
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $pagehook ) {
				return true;
			}

			return false;
		}

		/**
		 * Redirect the user to an admin page, and add query args to the URL string for alerts, etc.
		 *
		 * @param string $page       Menu slug.
		 * @param array  $query_args Optional. Associative array of query string arguments (key => value). Default is an empty array.
		 *
		 * @return void Return early if first argument, `$page`, is falsy.
		 */
		function admin_redirect( $page, array $query_args = array() ) {
			if ( ! $page ) {
				return;
			}
			$url = html_entity_decode( menu_page_url( $page, 0 ) );
			foreach ( (array) $query_args as $key => $value ) {
				if ( empty( $key ) && empty( $value ) ) {
					unset( $query_args[ $key ] );
				}
			}
			$url = add_query_arg( $query_args, $url );
			wp_redirect( esc_url_raw( $url ) );
		}

		/**
		 * Register the database settings for storage.
		 *
		 * @return void Return early if admin page doesn't store settings, or user is not on the correct admin page.
		 */
		public function register_settings() {

			// If this page doesn't store settings, no need to register them.
			if ( ! $this->settings_field ) {
				return;
			}

			register_setting(
				$this->settings_field, $this->settings_field, array(
					'default' => $this->default_settings,
				)
			);

			if ( ! self::is_menu_page( $this->page_id ) ) {
				return;
			}

			if ( self::get_option( 'reset', $this->settings_field ) ) {
				if ( update_option( $this->settings_field, $this->default_settings ) ) {
					self::admin_redirect(
						$this->page_id, array(
							'reset' => 'true',
						)
					);
				} else {
					self::admin_redirect(
						$this->page_id, array(
							'error' => 'true',
						)
					);
				}
				exit;
			}

		}

		/**
		 * Display notices on the save or reset of settings.
		 *
		 * @since 1.8.0
		 *
		 * @return void Return early if not on the correct admin page.
		 */
		public function notices() {

			if ( ! self::is_menu_page( $this->page_id ) ) {
				return;
			}

			if ( isset( $_REQUEST['settings-updated'] ) && 'true' === $_REQUEST['settings-updated'] ) {
				echo '<div id="message" class="updated"><p><strong>' . $this->page_ops['saved_notice_text'] . '</strong></p></div>';
			} elseif ( isset( $_REQUEST['reset'] ) && 'true' === $_REQUEST['reset'] ) {
				echo '<div id="message" class="updated"><p><strong>' . $this->page_ops['reset_notice_text'] . '</strong></p></div>';
			} elseif ( isset( $_REQUEST['error'] ) && 'true' === $_REQUEST['error'] ) {
				echo '<div id="message" class="updated"><p><strong>' . $this->page_ops['error_notice_text'] . '</strong></p></div>';
			}

		}

		/**
		 * Save method.
		 *
		 * Override this method to modify form data (for validation, sanitization, etc.) before it gets saved.
		 *
		 * @since 1.8.0
		 *
		 * @param mixed $newvalue New value to save.
		 * @param mixed $oldvalue Old value.
		 *
		 * @return mixed Value to save.
		 */
		public function save( $newvalue, $oldvalue ) {

			return $newvalue;

		}

		/**
		 * Initialize the settings page.
		 *
		 * This method must be re-defined in the extended classes, to hook in the
		 * required components for the page.
		 *
		 * @since 1.8.0
		 */
		abstract public function settings_init();

		/**
		 * Load the optional help method, if one exists.
		 *
		 * @since 2.1.0
		 */
		public function load_help() {

			if ( method_exists( $this, 'help' ) ) {
				add_action( "load-{$this->pagehook}", array( $this, 'help' ) );
			}

		}

		/**
		 * Add help tab.
		 *
		 * @since 2.5.0
		 *
		 * @param string $id    Help tab id.
		 * @param string $title Help tab title.
		 */
		public function add_help_tab( $id, $title ) {

			get_current_screen()->add_help_tab(
				array(
					'id'       => $this->pagehook . '-' . $id,
					'title'    => $title,
					'content'  => '',
					'callback' => array( $this, 'help_content' ),
				)
			);

		}

		/**
		 * Display a help view file if it exists.
		 *
		 * @since 2.5.0
		 *
		 * @param object $screen Current WP_Screen.
		 * @param array  $tab    Help tab.
		 */
		public function help_content( $screen, $tab ) {

			$hook_len = strlen( $this->pagehook ) + 1;
			$view     = $this->help_base . substr( $tab['id'], $hook_len ) . '.php';

			if ( is_file( $view ) ) {
				include $view;
			}

		}

		/**
		 * Set help sidebar for admin screens.
		 *
		 * @since 2.5.0
		 */
		public function set_help_sidebar() {

			$screen_reader = '<span class="screen-reader-text">. ' . esc_html__( 'Link opens in a new window.', 'wps' ) . '</span>';
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . esc_html__( 'For more information:', 'wps' ) . '</strong></p>' .
				'<p><a href="https://wpsmith.net/contact/" target="_blank">' . esc_html__( 'Get Support', 'wps' ) . $screen_reader . '</a></p>' .
				'<p><a href="https://wpsmith.net/contact/" target="_blank">' . esc_html__( 'Blog', 'wps' ) . $screen_reader . '</a></p>'
			);

		}

		/**
		 * Load script and stylesheet assets via scripts() and styles() methods, if they exist.
		 *
		 * @since 2.1.0
		 */
		public function load_assets() {

			// Hook scripts method.
			if ( method_exists( $this, 'scripts' ) ) {
				add_action( "load-{$this->pagehook}", array( $this, 'scripts' ) );
			}

			// Hook styles method.
			if ( method_exists( $this, 'styles' ) ) {
				add_action( "load-{$this->pagehook}", array( $this, 'styles' ) );
			}

		}

		/**
		 * Output the main admin page.
		 *
		 * This method must be re-defined in the extended class, to output the main
		 * admin page content.
		 *
		 * @since 1.8.0
		 */
		abstract public function admin();

		/**
		 * Helper function that constructs name attributes for use in form fields.
		 *
		 * Within admin pages, the id attributes of form fields are the same as
		 * the name attribute, as since HTML5, [ and ] characters are valid, so this
		 * function is also used to construct the id attribute value too.
		 *
		 * Other page implementation classes may wish to construct and use a
		 * get_field_id() method, if the naming format needs to be different.
		 *
		 * @since 1.8.0
		 *
		 * @param string $name Field name base.
		 *
		 * @return string Full field name.
		 */
		protected function get_field_name( $name ) {

			return sprintf( '%s[%s]', $this->settings_field, $name );

		}

		/**
		 * Echo constructed name attributes in form fields.
		 *
		 * @since 2.1.0
		 *
		 * @param string $name Field name base.
		 */
		protected function field_name( $name ) {

			echo $this->get_field_name( $name );

		}

		/**
		 * Helper function that constructs id attributes for use in form fields.
		 *
		 * @since 1.8.0
		 *
		 * @param string $id Field id base.
		 *
		 * @return string Full field id.
		 */
		protected function get_field_id( $id ) {

			return sprintf( '%s[%s]', $this->settings_field, $id );

		}

		/**
		 * Echo constructed id attributes in form fields.
		 *
		 * @since 2.1.0
		 *
		 * @param string $id Field id base.
		 */
		protected function field_id( $id ) {

			echo $this->get_field_id( $id );

		}

		/**
		 * Helper function that returns a setting value from this form's settings
		 * field for use in form fields.
		 *
		 * @since 1.8.0
		 *
		 * @param string $key Field key.
		 *
		 * @return string Field value.
		 */
		protected function get_field_value( $key ) {

			return self::get_option( $key, $this->settings_field );

		}

		/**
		 * Echo a setting value from this form's settings field for use in form fields.
		 *
		 * @since 2.1.0
		 *
		 * @param string $key Field key.
		 */
		protected function field_value( $key ) {

			echo $this->get_field_value( $key );

		}

		/**
		 * Return option from the options table and cache result.
		 *
		 * Applies `wps_pre_get_option_$key` and `wps_options` filters.
		 *
		 * Values pulled from the database are cached on each request, so a second request for the same value won't cause a
		 * second DB interaction.
		 *
		 *
		 * @param string $key       Option name.
		 * @param string $setting   Optional. Settings field name. Eventually defaults to `wps-settings` if not
		 *                          passed as an argument.
		 * @param bool   $use_cache Optional. Whether to use the cache value or not. Default is true.
		 *
		 * @return mixed The value of the `$key` in the database, or the return from
		 *               `wps_pre_get_option_{$key}` short circuit filter if not `null`.
		 */
		public static function get_option( $key, $setting = null, $use_cache = true ) {

			// The default is set here, so it doesn't have to be repeated in the function arguments.
			$setting = $setting ? $setting : 'wps-settings';

			// Allow child theme to short circuit this function.
			$pre = apply_filters( "wps_pre_get_option_{$key}", null, $setting );
			if ( null !== $pre ) {
				return $pre;
			}

			// Bypass cache if viewing site in Customizer.
			if ( self::is_customizer() ) {
				$use_cache = false;
			}

			// If we need to bypass the cache.
			if ( ! $use_cache ) {
				$options = get_option( $setting );
				if ( ! is_array( $options ) || ! array_key_exists( $key, $options ) ) {
					return '';
				}

				return is_array( $options[ $key ] ) ? $options[ $key ] : wp_kses_decode_entities( $options[ $key ] );
			}

			// Setup caches.
			static $settings_cache = array();
			static $options_cache = array();

			// Check options cache.
			if ( isset( $options_cache[ $setting ][ $key ] ) ) {
				// Option has been cached.
				return $options_cache[ $setting ][ $key ];
			}

			// Check settings cache.
			if ( isset( $settings_cache[ $setting ] ) ) {
				// Setting has been cached.
				$options = apply_filters( 'wps_options', $settings_cache[ $setting ], $setting );
			} else {
				// Set value and cache setting.
				$options = $settings_cache[ $setting ] = apply_filters( 'wps_options', get_option( $setting ), $setting );
			}

			// Check for non-existent option.
			if ( ! is_array( $options ) || ! array_key_exists( $key, (array) $options ) ) {
				// Cache non-existent option.
				$options_cache[ $setting ][ $key ] = '';
			} else {
				// Option has not been previously been cached, so cache now.
				$options_cache[ $setting ][ $key ] = is_array( $options[ $key ] ) ? $options[ $key ] : wp_kses_decode_entities( $options[ $key ] );
			}

			return $options_cache[ $setting ][ $key ];

		}

		/**
		 * Check whether we are currently viewing the site via the WordPress Customizer.
		 *
		 *
		 * @global \WP_Customize_Manager $wp_customize Customizer instance.
		 *
		 * @return bool Return true if viewing page via Customizer, false otherwise.
		 */
		public static function is_customizer() {
			global $wp_customize;

			return is_a( $wp_customize, 'WP_Customize_Manager' ) && $wp_customize->is_preview();
		}

	}
}