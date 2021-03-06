<?php
/**
 * WP Admin Boxes Abstract Class.
 *
 * Abstract subclass of Admin which adds support for registering and
 * displaying meta boxes.
 *
 * This class must be extended when creating an admin page with meta boxes, and
 * the settings_metaboxes() method must be defined in the subclass.
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

if ( ! class_exists( __NAMESPACE__ . '\AdminBoxes' ) ) {
	/**
	 * Class AdminBoxes.
	 *
	 * @package WPS\Admin
	 */
	abstract class AdminBoxes extends Admin {

		/**
		 * Register the meta boxes.
		 *
		 * Must be overridden in a subclass, or it obviously won't work.
		 *
		 * @since 1.8.0
		 */
		abstract public function metaboxes();

		/**
		 * Include the necessary sortable meta box scripts.
		 *
		 * @since 1.8.0
		 */
		public function scripts() {

			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );

		}

		/**
		 * Use this as the settings admin callback to create an admin page with sortable meta boxes.
		 * Create a 'settings_boxes' method to add meta boxes.
		 *
		 * @since 1.8.0
		 */
		public function admin() {

			include dirname( __FILE__ ) . '/pages/wps-admin-boxes.php';

		}

		/**
		 * Echo out the do_meta_boxes() and wrapping markup.
		 *
		 * This method can be overwritten in a child class, to adjust the markup surrounding the meta boxes, and optionally
		 * call do_meta_boxes() with other contexts. The overwritten method MUST contain div elements with classes of
		 * `metabox-holder` and `postbox-container`.
		 *
		 * @since 2.0.0
		 *
		 * @global array $wp_meta_boxes Holds all meta boxes data.
		 */
		public function do_metaboxes() {

			include dirname( __FILE__ ) . '/misc/wps-admin-boxes-holder.php';

		}

		/**
		 * Add meta box to the current admin screen.
		 *
		 * @since 2.5.0
		 *
		 * @param string $handle   Meta box handle.
		 * @param string $title    Meta box title.
		 * @param string $priority Optional. Meta box priority.
		 */
		public function add_meta_box( $handle, $title, $priority = 'default' ) {

			add_meta_box( $handle, $title, array( $this, 'do_meta_box' ), $this->pagehook, 'main', $priority );

		}

		/**
		 * Echo out the content of a meta box.
		 *
		 * @since 2.5.0
		 *
		 * @param object $object   Object passed to do_meta_boxes function.
		 * @param array  $meta_box Array of parameters passed to add_meta_box function.
		 */
		public function do_meta_box( $object, $meta_box ) {

			$view = $this->views_base . '/meta-boxes/' . $meta_box['id'] . '.php';
			if ( is_file( $view ) ) {
				include $view;
			}

		}

		/**
		 * Initialize the settings page.
		 *
		 * @since 1.8.0
		 */
		public function settings_init() {

			add_action( 'load-' . $this->pagehook, array( $this, 'metaboxes' ) );
			add_action( $this->pagehook . '_settings_page_boxes', array( $this, 'do_metaboxes' ) );

			if ( method_exists( $this, 'layout_columns' ) ) {
				add_filter( 'screen_layout_columns', array( $this, 'layout_columns' ), 10, 2 );
			}

		}

	}
}