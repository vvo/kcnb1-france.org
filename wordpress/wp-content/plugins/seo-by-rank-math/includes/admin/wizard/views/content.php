<?php
/**
 * Setup wizard content template.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

use RankMath\KB;
?>
<div class="wrapper">

	<div class="logo text-center">
		<a href="<?php KB::the( 'logo' ); ?>" target="_blank"><img src="<?php echo esc_url( rank_math()->plugin_url() . 'assets/admin/img/logo.svg' ); ?>" width="245"></a>
	</div>

	<?php include_once $this->get_view( 'navigation' ); ?>

	<div class="main-content wizard-content--<?php echo esc_attr( $this->step_slug ); ?>">

		<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo 'rank-math-registration' === $this->slug ? 'rank_math_save_registration' : 'rank_math_save_wizard'; ?>">
			<input type="hidden" name="step" value="<?php echo $this->step; ?>">
			<?php wp_nonce_field( 'rank-math-wizard', 'security' ); ?>

			<?php $this->body(); ?>

		</form>

	</div>

</div>

<?php
if ( 'ready' !== $this->step_slug ) :
	echo sprintf( '<div class="return-to-dashboard"><a href="%s"><i class="dashicons dashicons-arrow-left-alt2"></i> %s</a></div>', esc_url( 'rank-math-registration' === $_GET['page'] ? admin_url( '/' ) : RankMath\Helper::get_dashboard_url() ), esc_html__( 'Return to dashboard', 'rank-math' ) );
endif;
?>
