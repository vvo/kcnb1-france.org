<?php
/**
 * Setup wizard import step.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

?>
<header>
	<h1><?php esc_html_e( 'Import SEO Settings', 'rank-math' ); ?></h1>
	<p><?php esc_html_e( 'You can import SEO settings from the following plugins:', 'rank-math' ); ?></p>
</header>

<?php $this->cmb->show_form(); ?>


<div id="import-progress-bar">
	<div id="importProgress">
		<div id="importBar"></div>
	</div>
	<span class="left"><strong><?php echo esc_html__( 'Importing: ', 'rank-math' ); ?></strong><span class="plugin-from"></span></span>
	<span class="right"><span class="number">0</span>% <?php echo esc_html__( 'Completed', 'rank-math' ); ?></span>
</div>
<textarea id="import-progress" class="import-progress-area large-text" disabled="disabled" rows="8"></textarea>
<footer class="form-footer wp-core-ui rank-math-ui">
	<button type="submit" class="button button-secondary button-deactivate-plugins" data-deactivate-message="<?php esc_html_e( 'Deactivating Plugins...', 'rank-math' ); ?>"><?php esc_html_e( 'Skip, Don\'t Import Now', 'rank-math' ); ?></button>
	<button type="submit" class="button button-primary button-continue" style="display:none"><?php esc_html_e( 'Continue', 'rank-math' ); ?></button>
	<button type="submit" class="button button-primary button-import"><?php esc_html_e( 'Start Import', 'rank-math' ); ?></button>
</footer>
