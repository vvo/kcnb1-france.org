<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var array $passkey The passkey record. Required.
 * @var string $uiStyleContext The UI style context to use. Optional.
 */

$uiStyleContext = isset($uiStyleContext) && is_string($uiStyleContext)
	? \WordfenceLS\Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext)
	: \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$trashIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('trash', $uiStyleContext);
$dateSwitchThreshold = 2 * DAY_IN_SECONDS;
?>
<div class="wfls-passkey-item wfls-add-top" data-passkey-id="<?php echo (int) $passkey['id']; ?>">
	<div class="wfls-passkey-item-row">
		<span class="wfls-passkey-item-icon wfls-main-icon-passkey" aria-hidden="true"></span>
		<div class="wfls-passkey-item-details">
			<strong class="wfls-passkey-item-label"><?php echo esc_html($passkey['label']); ?></strong><br>
			<small>
				<?php
				echo esc_html(sprintf(
					/* translators: 1. Creation time; 2. Last used time */
					$passkey['last_used_at'] ? __('Added %1$s | Last used %2$s', 'wordfence') : __('Added %1$s | Not used yet', 'wordfence'),
					\WordfenceLS\Controller_Time::format_time_ago($passkey['ctime'], $dateSwitchThreshold, false, 1),
					$passkey['last_used_at'] ? \WordfenceLS\Controller_Time::format_time_ago($passkey['last_used_at'], $dateSwitchThreshold, false, 1) : ''
				));
				?>
			</small>
		</div>
		<div class="wfls-passkey-item-action">
			<a href="#"
			   class="wfls-btn wfls-btn-default wfls-passkey-remove"
			   data-passkey-id="<?php echo (int) $passkey['id']; ?>"
			   aria-label="<?php echo esc_attr__('Remove Passkey', 'wordfence'); ?>"
			   title="<?php echo esc_attr__('Remove Passkey', 'wordfence'); ?>">
				<i class="<?php echo esc_attr($trashIconClass); ?>" aria-hidden="true"></i>
				<span class="sr-only"><?php esc_html_e('Remove', 'wordfence'); ?></span>
			</a>
		</div>
	</div>
</div>
