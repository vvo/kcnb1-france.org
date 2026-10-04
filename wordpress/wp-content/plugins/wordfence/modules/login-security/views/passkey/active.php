<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 * @var array $passkeys The registered passkeys. Required.
 * @var string $uiStyleContext The UI style context to use. Optional.
 * @var bool $ownAccount Whether the viewer is managing their own account. Optional.
 */
$uiStyleContext = isset($uiStyleContext) && is_string($uiStyleContext)
	? \WordfenceLS\Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext)
	: \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$ownAccount = isset($ownAccount) ? (bool) $ownAccount : get_current_user_id() === (int) $user->ID;
$passkeyListTitle = $ownAccount ? __('Your Passkeys', 'wordfence') : __('Registered Passkeys', 'wordfence');
$passkeyController = \WordfenceLS\Controller_Passkey::shared();
$canChangeUsernamePasswordAuth = $passkeyController->can_change_username_password_auth($user);
$passwordAuthWillBeRestored = count($passkeys) === 1 && $canChangeUsernamePasswordAuth && !$passkeyController->is_username_password_auth_enabled($user);
$removePromptMessage = __('Are you sure you want to remove this passkey? Deleting a passkey here does not necessarily remove it from your device\'s password manager.', 'wordfence');
if ($passwordAuthWillBeRestored) {
	$removePromptMessage = $ownAccount
		? __('This is your only passkey. If you remove it, you’ll return to signing in with your username and password. Removing it here may not remove it from your device’s password manager.', 'wordfence')
		: __('This is the user\'s only passkey. If you remove it, the user will return to signing in with a username and password. Removing it here will not remove it from the user\'s password manager.', 'wordfence');
}
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title wfls-passkey-card-title">
				<span class="wfls-passkey-card-title-icon wfls-main-icon-passkey" aria-hidden="true"></span>
				<strong><?php echo esc_html($passkeyListTitle); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p id="wfls-passkey-empty"<?php if (!empty($passkeys)): ?> style="display: none;"<?php endif; ?>><?php esc_html_e('No passkeys are registered for this user yet.', 'wordfence'); ?></p>
		<div id="wfls-passkey-list">
			<?php foreach ($passkeys as $passkey): ?>
				<?php echo \WordfenceLS\Model_View::create('passkey/item', array('passkey' => $passkey, 'uiStyleContext' => $uiStyleContext))->render(); ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<div style="display: none;">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'id' => 'wfls-template-passkey-remove-prompt',
		'title' => __('Remove Passkey', 'wordfence'),
		'message' => $removePromptMessage,
		'primaryButton' => array('class' => 'wfls-passkey-remove-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#'),
		'secondaryButtons' => array(array('class' => 'wfls-passkey-remove-prompt-confirm', 'label' => __('Remove', 'wordfence'), 'link' => '#')),
	))->render();
	?>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var list = $('#wfls-passkey-list');
			var empty = $('#wfls-passkey-empty');

			function updateEmptyState() {
				var isEmpty = list.find('.wfls-passkey-item').length === 0;
				empty.toggle(isEmpty);
			}

			list.on('click', '.wfls-passkey-remove', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var item = $(this).closest('.wfls-passkey-item');
				var passkeyId = $(this).data('passkey-id');
				var content = $('#wfls-template-passkey-remove-prompt').clone().attr('id', null);
				WFLS.standaloneModalHTML(content, { onOpen: function(modal) {
						$(modal).find('.wfls-passkey-remove-prompt-cancel').on('click', WFLS.closeStandaloneModal);
						$(modal).find('.wfls-passkey-remove-prompt-confirm').on('click', function(confirmEvent) {
							confirmEvent.preventDefault();
							confirmEvent.stopPropagation();

							WFLS.ajax(
								'wordfence_ls_remove_passkey',
								{
									user: <?php echo (int) $user->ID; ?>,
									passkey_id: passkeyId
								},
								function(response) {
									if (response.error) {
										WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Removing Passkey', 'wordfence')); ?>', response.error);
										return;
									}

									WFLS.closeStandaloneModal();
									item.remove();
									if (list.find('.wfls-passkey-item').length === 0) {
										window.location.reload();
										return;
									}
									updateEmptyState();
								},
								function() {
									WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Removing Passkey', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to remove the passkey. Please try again.', 'wordfence')); ?>');
								}
							);
						});
					}});
			});
		});
	})(jQuery);
</script>
