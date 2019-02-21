<?php
/**
 * Admin metaboxes template.
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

?>
<div class="wrap wps-metaboxes">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form method="post" action="options.php">

		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php settings_fields( $this->settings_field ); ?>


		<?php
		/**
		 * Fires inside meta box admin page, inside the form element, before the bottom buttons.
		 *
		 * The dynamic part of the hook name is the page hook.
		 *
		 * @since ???
		 *
		 * @param string $page_hook Page hook.
		 */
		do_action( "{$this->pagehook}_settings_page_boxes", $this->pagehook ); // WPCS: prefix ok.
		?>

		<div class="bottom-buttons">
			<?php submit_button( $this->page_ops['save_button_text'], 'primary', 'submit', false ); ?>
			<?php submit_button( $this->page_ops['reset_button_text'], 'secondary wps-js-confirm-reset', $this->get_field_name( 'reset' ), false ); ?>
		</div>
	</form>
</div>
<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready( function ($) {
        // close postboxes that should be closed
        $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
        // postboxes setup
        postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
    });
    //]]>
</script>