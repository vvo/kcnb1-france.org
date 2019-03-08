<?php
/**
 * Setup wizard search console step.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

use RankMath\KB;
?>
<header>
	<h1><?php esc_html_e( 'Google&trade; Search Console', 'rank-math' ); ?> </h1>
	<p>
		<?php
		/* translators: Link to How to Setup Google Search Console KB article */
		printf( esc_html__( 'Verify your site on Google Search Console and connect it here to see crawl error notifications, keyword statistics and other important information right in your WordPress dashboard. %s', 'rank-math' ), '<a href="' . KB::get( 'search-console' ) . '" target="_blank">' . esc_html__( 'Read more about it here.', 'rank-math' ) . '</a>' );
		?>
	</p>
</header>

<?php $this->cmb->show_form(); ?>

<footer class="form-footer wp-core-ui rank-math-ui">
	<?php $this->skip_link(); ?>
	<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
</footer>
