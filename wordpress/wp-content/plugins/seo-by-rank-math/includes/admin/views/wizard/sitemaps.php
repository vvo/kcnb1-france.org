<?php
/**
 * Setup wizard sitemap step.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

use RankMath\KB;
?>
<header>
	<h1><?php esc_html_e( 'Sitemap', 'rank-math' ); ?> </h1>
	<p>
		<?php
		/* translators: Link to How to Setup Sitemap KB article */
		printf( esc_html__( 'Choose your Sitemap configuration and select which type of posts or pages you want to include in your Sitemaps. %s', 'rank-math' ), '<a href="' . KB::get( 'configure-sitemaps' ) . '" target="_blank">' . esc_html__( 'Learn more.', 'rank-math' ) . '</a>' );
		?>
	</p>
</header>

<?php $this->cmb->show_form(); ?>

<footer class="form-footer wp-core-ui rank-math-ui">
	<?php $this->skip_link(); ?>
	<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
</footer>
