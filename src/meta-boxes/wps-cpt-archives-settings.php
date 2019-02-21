<?php
/**
 * CPT Archive Metaboxes.
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
<p>
	<?php
	$archive = '<a href="' . get_post_type_archive_link( $this->post_type->name ) . '">';
	/* translators: Opn and close post type archive link, post type name. */
	printf( esc_html__( 'View the %1$s%3$s archive%2$s.', 'wps' ), $archive, '</a>', $this->post_type->name );
	?>
</p>

<table class="form-table">
	<tbody>

	<tr valign="top">
		<th scope="row"><label for="<?php $this->field_id( 'headline' ); ?>"><b><?php esc_html_e( 'Archive Headline', 'wps' ); ?></b></label></th>
		<td>
			<p><input class="large-text" type="text" name="<?php $this->field_name( 'headline' ); ?>" id="<?php $this->field_id( 'headline' ); ?>" value="<?php echo esc_attr( $this->get_field_value( 'headline' ) ); ?>" /></p>
			<p class="description">
				<?php
				esc_html_e( 'Leave empty if you do not want to display a headline.', 'wps' );
				?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="<?php $this->field_id( 'intro_text' ); ?>"><b><?php esc_html_e( 'Archive Intro Text', 'wps' ); ?></b></label></th>
		<td>
			<?php
			wp_editor(
				$this->get_field_value( 'intro_text' ),
				$this->settings_field . '-intro-text',
				array(
					'textarea_name' => $this->get_field_name( 'intro_text' ),
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'Leave empty if you do not want to display any intro text.', 'wps' ); ?></p>
		</td>
	</tr>

	</tbody>
</table>