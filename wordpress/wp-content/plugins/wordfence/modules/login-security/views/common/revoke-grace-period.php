<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$idPrefix = isset($idPrefix) && is_string($idPrefix) ? $idPrefix : 'wfls-';
$buttonID = $idPrefix . 'revoke-grace-period';
$failureMessageID = $idPrefix . 'revoke-grace-period-failed';
$errorMessage = __('Unable to Revoke Authentication Grace Period', 'wordfence');
?>
<div class="wfls-add-top wfls-add-bottom wfls-grace-period-container wfls-flex-horizontal wfls-flex-align-left">
	<div class="wfls-grace-period-button-container">
		<button class="wfls-btn wfls-btn-default" id="<?php echo esc_attr($buttonID); ?>">
			<?php esc_html_e('Revoke Grace Period', 'wordfence') ?>
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
			var button = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($buttonID); ?>');
			function revoke2faGracePeriod(userId, success, failure) {
				var ajaxContext = (typeof WFLS === 'undefined' ? GWFLS : WFLS);
				ajaxContext.ajax(
					'wordfence_ls_revoke_2fa_grace_period',
					{
						user_id: userId
					},
					success,
					failure
				);
			}
			function handleError() {
				if (typeof WFLS === 'object') {
					WFLS.standaloneModal(
						<?php echo json_encode($errorMessage) ?>,
						<?php echo json_encode(__('An unexpected error occurred while attempting to revoke the authentication grace period.', 'wordfence')) ?>
					);
				}
				else {
					failureMessage.show();
				}
				button.prop('disabled', false);
			}
			button.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				button.prop('disabled', true);
				failureMessage.hide();
				revoke2faGracePeriod(
					<?php echo json_encode($user->ID, true) ?>,
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
		});
	})(jQuery);
</script>
