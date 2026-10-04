<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user whose grace-period state is shown. Required.
 * @var bool $gracePeriod Whether the user is currently in the passkey grace period. Required.
 * @var bool $lockedOut Whether the user is locked out for a missing passkey. Required.
 * @var int $requiredAt The passkey requirement activation time. Required when the grace period is active.
 * @var string $uiStyleContext The UI style context to use. Optional.
 */

$ownUser = wp_get_current_user();
$ownAccount = $ownUser->ID == $user->ID;
$canManageGracePeriod = current_user_can(\WordfenceLS\Controller_Permissions::CAP_MANAGE_SETTINGS);
$uiStyleContext = isset($uiStyleContext) && is_string($uiStyleContext)
	? \WordfenceLS\Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext)
	: \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$calendarIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('calendar', $uiStyleContext);
?>
<div class="wfls-block-footer wfls-passkey-grace-period-footer">
	<div class="wfls-block-footer-content">
		<div class="wfls-passkey-grace-period-copy">
			<span class="wfls-passkey-grace-period-icon" aria-hidden="true"><i class="<?php echo esc_attr($calendarIconClass); ?>"></i></span>
			<div class="wfls-block-subtitle">
				<?php if ($gracePeriod): ?>
					<?php
					$requiredDateFormatted = \WordfenceLS\Controller_Time::format_local_time('F j, Y g:i A', $requiredAt);
					echo $ownAccount
						? sprintf(wp_kses(/* translators: Date */ __('Passkey authentication will be required for your account beginning <strong>%s</strong>.', 'wordfence'), array('strong' => array())), $requiredDateFormatted)
						: sprintf(wp_kses(/* translators: 1. Username; 2. Date */ __('Passkey authentication will be required for user <strong>%1$s</strong> beginning <strong>%2$s</strong>.', 'wordfence'), array('strong' => array())), esc_html($user->user_login), $requiredDateFormatted);
					?>
				<?php else: ?>
					<strong><?php esc_html_e('Locked out.', 'wordfence'); ?></strong>
					<?php echo ' ' . ($ownAccount
						? esc_html__('A passkey is required for your account, but has not been configured.', 'wordfence')
						: esc_html__('A passkey is required for this user, but has not been configured.', 'wordfence')); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if ($canManageGracePeriod && ($lockedOut || \WordfenceLS\Controller_Users::shared()->has_revokable_grace_period($user))): ?>
			<div class="wfls-block-footer-action">
				<?php if ($lockedOut): ?>
					<?php echo \WordfenceLS\Model_View::create('common/reset-grace-period', array(
						'user' => $user,
						'gracePeriod' => false,
						'defaultGracePeriod' => \WordfenceLS\Controller_Settings::shared()->get_user_passkey_grace_period(),
						'idPrefix' => 'wfls-passkey-',
					))->render(); ?>
				<?php else: ?>
					<?php echo \WordfenceLS\Model_View::create('common/revoke-grace-period', array(
						'user' => $user,
						'idPrefix' => 'wfls-passkey-',
					))->render(); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
