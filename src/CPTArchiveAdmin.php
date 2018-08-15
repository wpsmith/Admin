<?php
/**
 * WP Custom Post Type Admin Class.
 *
 * Register a new admin page, providing content and corresponding menu item for the CPT Archive Settings page.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * PHP Version 7.2
 *
 * @package   WPS\Admin
 * @author    Travis Smith <t@wpsmith.net>
 * @copyright 2018 Travis Smith
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link      https://github.com/akamai/wp-akamai
 * @since     0.2.0
 */

namespace WPS\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Plugins\Fundraising\Admin\CPTArchiveAdmin' ) ) {
	/**
	 * Class CPTArchiveAdmin.
	 *
	 * @package WPS\Plugins\Fundraising\Admin
	 */
	class CPTArchiveAdmin extends AdminBoxes {

		/**
		 * Post type object.
		 *
		 * @var \stdClass
		 */
		protected $post_type;

		/**
		 * Layout selector enabled.
		 *
		 * @var bool
		 */
		protected $layout_enabled;

		/**
		 * Create an archive settings admin menu item and settings page for relevant custom post types.
		 *
		 * @param \WP_Post_Type $post_type The post type object.
		 */
		public function __construct( $post_type ) {
			$this->post_type = $post_type;
			$this->help_base = dirname( __FILE__ ) . '/help/cpt-archive-';

			/**
			 * Filter the enable CPT archive layout setting.
			 *
			 * @since 2.5.0
			 *
			 * @param bool $enable_layout Enable CPT archive layout setting. Default true.
			 */
			$this->layout_enabled = apply_filters( "wps_cpt_archive_layout_setting_enable-{$this->post_type->name}", true );

			$page_id = 'wps-cpt-archive-' . $this->post_type->name;

			$menu_ops = array(
				'submenu' => array(
					'parent_slug' => 'edit.php?post_type=' . $this->post_type->name,
					'page_title'  => apply_filters( 'wps_cpt_archive_settings_page_label', __( 'Archive Settings', 'wps' ) ),
					'menu_title'  => apply_filters( 'wps_cpt_archive_settings_menu_label', __( 'Archive Settings', 'wps' ) ),
					'capability'  => apply_filters( "wps_cpt_archive_settings_capability_{$this->post_type->name}", 'manage_options' ),
				),
			);

			// Handle non-top-level CPT menu items.
			if ( is_string( $this->post_type->show_in_menu ) ) {
				$menu_ops['submenu']['parent_slug']   = $this->post_type->show_in_menu;
				$menu_ops['submenu']['menu_title']    = apply_filters( 'wps_cpt_archive_settings_label', $this->post_type->labels->name . ' ' . __( 'Archive', 'wps' ) );
				$menu_ops['submenu']['menu_position'] = $this->post_type->menu_position;
			}

			$page_ops = array(); // Use defaults.

			$settings_field = 'wps-' . $this->post_type->name;

			$default_settings = apply_filters(
				'wps_cpt_archive_settings_defaults',
				array(
					'headline'    => '',
					'intro_text'  => '',
					'doctitle'    => '',
					'description' => '',
					'keywords'    => '',
					'layout'      => '',
					'body_class'  => '',
					'noindex'     => 0,
					'nofollow'    => 0,
					'noarchive'   => 0,
				),
				$this->post_type->name
			);

			$this->create( $page_id, $menu_ops, $page_ops, $settings_field, $default_settings );

			add_action( 'wps_settings_sanitizer_init', array( $this, 'sanitizer_filters' ) );
		}

		/**
		 * Register each of the settings with a sanitization filter type.
		 *
		 * @see   \SettingSanitizer::add_filter()
		 */
		public function sanitizer_filters() {

			SettingsSanitizer::get_instance()->add_filter(
				'no_html',
				$this->settings_field,
				array(
					'headline',
					'doctitle',
					'description',
					'keywords',
					'body_class',
					'layout',
				)
			);
			SettingsSanitizer::get_instance()->add_filter(
				'unfiltered_or_safe_html',
				$this->settings_field,
				array(
					'intro_text',
				)
			);
			SettingsSanitizer::get_instance()->add_filter(
				'one_zero',
				$this->settings_field,
				array(
					'noindex',
					'nofollow',
					'noarchive',
				)
			);
		}

		/**
		 * Register meta boxes on the CPT Archive pages.
		 *
		 * Some of the meta box additions are dependent on certain theme support or user capabilities.
		 *
		 * The 'wps_cpt_archives_settings_metaboxes' action hook is called at the end of this function.
		 */
		public function metaboxes() {

			$this->add_meta_box( 'wps-cpt-archives-settings', __( 'Archive Settings', 'wps' ) );

			/**
			 * Fires after CPT archive settings meta boxes have been added.
			 *
			 * @since 2.0.0
			 *
			 * @param string $pagehook Page hook for the CPT archive settings page.
			 */
			do_action( 'wps_cpt_archives_settings_metaboxes', $this->pagehook );

		}

		/**
		 * Add contextual help content for the archive settings page.
		 */
		public function help() {

			$this->add_help_tab( 'archive', __( 'Archive Settings', 'wps' ) );

		}
	}
}