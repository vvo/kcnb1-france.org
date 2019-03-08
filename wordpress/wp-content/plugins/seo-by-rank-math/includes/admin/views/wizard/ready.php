<?php
/**
 * Setup wizard ready step.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

use RankMath\Helper;
use RankMath\KB;
?>
<header>
	<h1>
		<i class="dashicons dashicons-yes"></i> <?php esc_html_e( 'Your site is ready!', 'rank-math' ); ?>
		<?php \RankMath\Admin\Admin_Helper::get_social_share(); ?>
	</h1>
</header>

<div class="wp-rating">
	<a href="<?php KB::the( 'review-rm' ); ?>" target="_blank">
		<span><?php esc_html_e( 'Consider Leaving a 5-Star Review', 'rank-math' ); ?></span>
		<i class="dashicons dashicons-star-filled"></i>
		<i class="dashicons dashicons-star-filled"></i>
		<i class="dashicons dashicons-star-filled"></i>
		<i class="dashicons dashicons-star-filled"></i>
		<i class="dashicons dashicons-star-filled"></i>
	</a>
</div>

<br class="clear">
<?php if ( ! Helper::is_whitelabel() ) : ?>

	<div class="wizard-next-steps wp-clearfix">
		<div class="score-100">
			<a href="<?php KB::the( 'score-100' ); ?>" target="_blank">
				<img src="<?php echo rank_math()->plugin_url(); ?>/assets/admin/img/score-100.png">
			</a>
		</div>
		<div class="learn-more">
			<h2><?php esc_html_e( 'Learn more', 'rank-math' ); ?></h2>
			<ul>
				<li>
					<span class="dashicons dashicons-facebook"></span><a href="<?php KB::the( 'fb-group' ); ?>" target="_blank"><strong><?php esc_html_e( 'Join FREE Facebook Group', 'rank-math' ); ?></strong></a>
				</li>
				<li>
					<span class="dashicons dashicons-welcome-learn-more"></span><a href="<?php KB::the( 'rm-kb' ); ?>" target="_blank"><?php esc_html_e( 'Rank Math Knowledge Base', 'rank-math' ); ?></a>
				</li>
				<li>
					<span class="dashicons dashicons-video-alt3"></span><a href="<?php KB::the( 'wp-error-fixes' ); ?>" target="_blank"><?php esc_html_e( 'Common WordPress Errors & Fixes', 'rank-math' ); ?></a>
				</li>
				<li>
					<span class="dashicons dashicons-sos"></span><a href="<?php KB::the( 'mts-forum' ); ?>" target="_blank"><?php esc_html_e( 'Get 24x7 Support', 'rank-math' ); ?></a>
				</li>
			</ul>
		</div>
	</div>

	<footer class="form-footer wp-core-ui rank-math-ui">
		<a href="<?php echo esc_url( Helper::get_dashboard_url() ); ?>" class="button button-secondary"><?php echo esc_html( $this->strings['return-to-dashboard'] ); ?></a>
		<a href="<?php echo esc_url( Helper::get_admin_url( 'help' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Proceed to Help Page', 'rank-math' ); ?></a>
		<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="button button-primary"><?php esc_html_e( 'Setup Advanced Options', 'rank-math' ); ?></a>
		<?php do_action( 'rank_math/wizard/ready_footer', $this ); ?>
	</footer>
<?php else : ?>
	<p><?php esc_html_e( 'Your site is now optimized.', 'rank-math' ); ?></p>
	<footer class="form-footer wp-core-ui rank-math-ui">
		<a href="<?php echo esc_url( Helper::get_admin_url( 'options-general' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Proceed to Settings', 'rank-math' ); ?></a>
	</footer>
	<?php
endif;
