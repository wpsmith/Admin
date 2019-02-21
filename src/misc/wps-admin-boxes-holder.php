<?php
/**
 * WP Admin Boxes Template.
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

global $wp_meta_boxes;
?>
<div class="metabox-holder">
	<div class="postbox-container">
		<?php
		/**
		 * Fires inside meta box holder view, before the meta boxes.
		 *
		 * @since 1.8.0
		 *
		 * @param string $page_hook Page hook.
		 */
		do_action( 'wps_admin_before_metaboxes', $this->pagehook );
		do_meta_boxes( $this->pagehook, 'main', null );
		if ( isset( $wp_meta_boxes[ $this->pagehook ]['column2'] ) )
			do_meta_boxes( $this->pagehook, 'column2', null );

		/**
		 * Fires inside meta box holder view, after the meta boxes.
		 *
		 * @since 1.8.0
		 *
		 * @param string $page_hook Page hook.
		 */
		do_action( 'wps_admin_after_metaboxes', $this->pagehook );
		?>
	</div>
</div>