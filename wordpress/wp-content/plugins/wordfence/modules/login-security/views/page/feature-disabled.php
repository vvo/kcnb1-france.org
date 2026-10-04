<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $title The notice title. Required.
 * @var string $message The notice body. Required.
 * @var string $settingsURL The login security settings URL. Optional.
 * @var bool $showSettingsButton Whether to show the settings button. Optional, defaults to true.
 */
if (!isset($showSettingsButton)) {
	$showSettingsButton = true;
}
?>
<div class="wfls-notice">
	<p><strong><?php echo esc_html($title); ?></strong> <?php echo esc_html($message); ?></p>
	<?php if ($showSettingsButton && !empty($settingsURL)): ?>
	<p><a href="<?php echo esc_url($settingsURL); ?>" class="wfls-btn wfls-btn-default" aria-label="<?php esc_attr_e('Manage Login Security Settings', 'wordfence'); ?>"><span class="wfls-btn-label-full" aria-hidden="true"><?php esc_html_e('Manage Login Security Settings', 'wordfence'); ?></span><span class="wfls-btn-label-xs" aria-hidden="true"><?php esc_html_e('Manage', 'wordfence'); ?></span></a></p>
	<?php endif; ?>
</div>
