<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $uiStyleContext The UI style context to use. Optional.
 * @var \WP_User $user The user being edited. Optional unless a grace-period footer is shown.
 * @var bool $gracePeriod Whether the user is currently in the passkey grace period. Optional.
 * @var bool $lockedOut Whether the user is locked out for a missing passkey. Optional.
 * @var int $requiredAt The passkey requirement activation time. Optional.
 */

$uiStyleContext = isset($uiStyleContext) && is_string($uiStyleContext)
	? \WordfenceLS\Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext)
	: \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$addHeaderIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('plus-circle', $uiStyleContext);
$gracePeriod = isset($gracePeriod) ? (bool) $gracePeriod : false;
$lockedOut = isset($lockedOut) ? (bool) $lockedOut : false;
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width wfls-passkey-registration-disabled" data-wfls-ui-style="<?php echo esc_attr($uiStyleContext); ?>">
	<div class="wfls-block-header wfls-block-header-border-bottom" aria-disabled="true">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title wfls-passkey-card-title">
				<span class="wfls-passkey-card-title-icon" aria-hidden="true"><i class="<?php echo esc_attr($addHeaderIconClass); ?>"></i></span>
				<strong><?php esc_html_e('Add a Passkey', 'wordfence'); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content" aria-disabled="true">
		<p class="wfls-passkey-registration-disabled-message"><?php esc_html_e('Passkeys can only be added by the user themselves.', 'wordfence'); ?></p>
	</div>
	<?php if ($gracePeriod || $lockedOut): ?>
		<?php echo \WordfenceLS\Model_View::create('passkey/grace-period', array(
			'user' => $user,
			'gracePeriod' => $gracePeriod,
			'lockedOut' => $lockedOut,
			'requiredAt' => $requiredAt,
			'uiStyleContext' => $uiStyleContext,
		))->render(); ?>
	<?php endif; ?>
</div>
