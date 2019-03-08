<?php
/**
 * Setup wizard optimization step.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

use RankMath\KB;
?>
<header>
	<h1><?php esc_html_e( 'SEO Tweaks', 'rank-math' ); ?> </h1>
	<p>
		<?php
			/* translators: Link to How to Optimization KB article */
			printf( esc_html__( 'Automate some of your SEO tasks like making external links nofollow, redirecting attachment pages, etc.. %s', 'rank-math' ), '<a href="' . KB::get( 'seo-tweaks' ) . '" target="_blank">' . esc_html__( 'Learn More', 'rank-math' ) . '</a>' );
		?>
	</p>
</header>

<?php $this->cmb->show_form(); ?>

<footer class="form-footer wp-core-ui rank-math-ui">
	<?php $this->skip_link(); ?>
	<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
</footer>
