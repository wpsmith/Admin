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
 * @copyright 2018 Travis Smith
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link      https://github.com/akamai/wp-akamai
 * @since     0.2.0
 */

namespace WPS\WP\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\AdminForm' ) ) {
	/**
	 * Class Admin.
	 *
	 * @package WPS\WP\Admin
	 */
	abstract class AdminForm extends Admin {

		/**
		 * Output settings page form elements.
		 *
		 * Must be overridden in a subclass, or it obviously won't work.
		 *
		 * @since 1.8.0
		 */
		abstract public function form();

		/**
		 * Normal settings page admin.
		 *
		 * Includes the necessary markup, form elements, etc.
		 * Hook to {$this->pagehook}_settings_page_form to insert table and settings form.
		 *
		 * Can be overridden in a child class to achieve complete control over the settings page output.
		 *
		 * @since 1.8.0
		 */
		public function admin() {

			include dirname( __FILE__ ) . '/pages/wps-admin-form.php';

		}

		/**
		 * Initialize the settings page, by hooking the form into the page.
		 *
		 * @since 1.8.0
		 */
		public function settings_init() {

			add_action( "{$this->pagehook}_settings_page_form", array( $this, 'form' ) );

		}

	}
}
