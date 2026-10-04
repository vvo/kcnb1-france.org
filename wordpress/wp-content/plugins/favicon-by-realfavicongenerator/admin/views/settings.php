<?php
// Copyright 2014-2016 RealFaviconGenerator

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<p>
		<?php
		echo wp_kses(
			sprintf(
				// translators: %s is the URL to the favicon appearance settings page
				__( 'Do you want to setup your favicon? Go to <a href="%s">Appearance &gt; Favicon</a>', 'favicon-by-realfavicongenerator' ),
				esc_url( $favicon_appearance_url )
			),
			array( 'a' => array( 'href' => array() ) )
		);
		?>
	</p>

	<form action="<?php echo esc_html( $favicon_admin_url ) ?>" method="post">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Display update notifications', 'favicon-by-realfavicongenerator' ); ?></th>
					<td>
						<input type="checkbox" name="<?php echo esc_html( Favicon_By_RealFaviconGenerator_Admin::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS ) ?>" 
							value="1" <?php echo( $display_update_notifications ? 'checked="checked"' : '' ); ?>>
						<p class="description">
							<?php esc_html_e( 'Get notifications when RealFaviconGenerator is updated. For example, when Apple releases a new version of iOS or a new platform is supported.', 'favicon-by-realfavicongenerator' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<input type="hidden" name="<?php echo esc_html( Favicon_By_RealFaviconGenerator_Admin::SETTINGS_FORM ) ?>" value="1">

		<input
			type="hidden"
			name="<?php echo esc_html( Favicon_By_RealFaviconGenerator_Admin::NONCE_SETTINGS_UPDATE ) ?>"
			value="<?php echo esc_html( wp_create_nonce( Favicon_By_RealFaviconGenerator_Admin::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) ?>"
		>

		<input name="Submit" type="submit" class="button-primary" value="<?php esc_html_e( 'Save changes', 'favicon-by-realfavicongenerator' ); ?>">
	</form>

</div>
