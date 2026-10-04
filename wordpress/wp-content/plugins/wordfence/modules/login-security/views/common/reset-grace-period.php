<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$idPrefix = isset($idPrefix) && is_string($idPrefix) ? $idPrefix : 'wfls-';
$overrideInputID = $idPrefix . 'user-grace-period-override';
$buttonID = $idPrefix . 'reset-grace-period';
$failureMessageID = $idPrefix . 'reset-grace-period-failed';
if (!isset($defaultGracePeriod)) {
	$defaultGracePeriod = \WordfenceLS\Controller_Settings::shared()->get_user_2fa_grace_period();
}
$savedGracePeriodOverride = \WordfenceLS\Controller_Users::shared()->get_grace_period_override($user);
if ($savedGracePeriodOverride !== null) {
	$defaultGracePeriod = $savedGracePeriodOverride;
}
$defaultGracePeriod = max($defaultGracePeriod, 1);
$errorMessage = $gracePeriod === null ? __('Unable to Activate Authentication Grace Period', 'wordfence') : __('Unable to Reset Authentication Grace Period', 'wordfence');
?>
<div class="wfls-grace-period-container wfls-flex-horizontal wfls-flex-align-left">
	<div class="wfls-grace-period-input-container">
		<label for="<?php echo esc_attr($overrideInputID); ?>" style="display: none"><?php esc_html_e('Grace Period Override', 'wordfence') ?></label>
		<input type="text" id="<?php echo esc_attr($overrideInputID); ?>" maxlength="2" pattern="[0-9]+" value="<?php echo (int) $defaultGracePeriod ?>">
		<label for="<?php echo esc_attr($overrideInputID); ?>"><?php esc_html_e('days', 'wordfence') ?></label>
	</div>
	<div class="wfls-grace-period-button-container">
		<button class="wfls-btn wfls-btn-default" id="<?php echo esc_attr($buttonID); ?>">
			<?php echo $gracePeriod === null ? esc_html__('Activate Grace Period', 'wordfence') : esc_html__('Reset Grace Period', 'wordfence') ?>
		</button>

	</div>
</div>
<div>
	<p id="<?php echo esc_attr($failureMessageID); ?>" style="display: none"><strong><?php echo esc_html($errorMessage) ?></strong></p>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var failureMessage = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($failureMessageID); ?>');
			var overrideInput = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($overrideInputID); ?>');
			var button = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($buttonID); ?>');
			function reset2faGracePeriod(userId, gracePeriodOverride, success, failure) {
				var ajaxContext = (typeof WFLS === 'undefined' ? GWFLS : WFLS);
				ajaxContext.ajax(
					'wordfence_ls_reset_2fa_grace_period',
					{
						user_id: userId,
						grace_period_override: gracePeriodOverride
					},
					success,
					failure
				);
			}
			function handleError() {
				if (typeof WFLS === 'object') {
					WFLS.standaloneModal(
						<?php echo json_encode($errorMessage) ?>,
						<?php echo json_encode($gracePeriod === null ? __('An unexpected error occurred while attempting to activate the authentication grace period.', 'wordfence') : __('An unexpected error occurred while attempting to reset the authentication grace period.', 'wordfence')) ?>
					);
				}
				else {
					failureMessage.show();
				}
				button.prop('disabled', false);
				overrideInput.prop('disabled', false);
			}
			button.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				button.prop('disabled', true);
				overrideInput.prop('disabled', true);
				failureMessage.hide();
				reset2faGracePeriod(
					<?php echo json_encode($user->ID, true) ?>,
					overrideInput.val(),
					function(data) {
						if ('error' in data) {
							handleError();
							return;
						}
						if (typeof WFLS === 'undefined')
							window.location.href = '#wfls-user-settings';
						window.location.reload();
					},
					handleError
				);
			});
			overrideInput.on('input', function(e) {
				var value = $(this).val();
				value = value.replace(/[^0-9]/g, '');
				value = parseInt(value);
				if (isNaN(value) || value === 0)
					value = '';
				button.prop('disabled', value < 1);
				$(this).val(value);
			}).trigger('input');
		});
	})(jQuery);
</script>
