<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 * @var bool $canChangeUsernamePasswordAuth Whether the user's role permits changing the per-user option. Optional.
 * @var bool $canManageSettings Whether the viewer can manage login security settings. Optional.
 * @var string $settingsURL The login security settings URL. Optional.
 */

$passkeyController = \WordfenceLS\Controller_Passkey::shared();
$canChangeUsernamePasswordAuth = isset($canChangeUsernamePasswordAuth) ? (bool) $canChangeUsernamePasswordAuth : $passkeyController->can_change_username_password_auth($user);
$usernamePasswordAuthEnabled = $canChangeUsernamePasswordAuth && $passkeyController->is_username_password_auth_enabled($user);
$ownAccount = get_current_user_id() === (int) $user->ID;
$loginPreferenceTitle = $ownAccount ? __('How do you want to log in?', 'wordfence') : __('How can the user log in?', 'wordfence');
$canManageSettings = isset($canManageSettings) ? (bool) $canManageSettings : \WordfenceLS\Controller_Permissions::shared()->can_manage_settings();
$settingsURL = isset($settingsURL) ? $settingsURL : (is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings'));
$managePasskeyRolesURL = add_query_arg('wfls-settings-anchor', 'wfls-passkey-roles', $settingsURL);

static $passkeyPasswordAuthInstanceCount = 0;
$passkeyPasswordAuthInstanceCount++;
$instanceID = 'wfls-passkey-password-auth-' . (int) $user->ID . '-' . $passkeyPasswordAuthInstanceCount;
$radioName = $instanceID . '-mode';
$fallbackRadioID = $instanceID . '-fallback';
$passkeyOnlyRadioID = $instanceID . '-passkey-only';
$modalTemplateID = $instanceID . '-disable-prompt-template';
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title wfls-passkey-card-title">
				<span class="wfls-passkey-card-title-icon" aria-hidden="true"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('cog')); ?>"></i></span>
				<strong><?php echo esc_html($loginPreferenceTitle); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-top wfls-padding-add-bottom">
		<fieldset id="<?php echo esc_attr($instanceID); ?>" class="wfls-passkey-password-auth-options<?php if (!$canChangeUsernamePasswordAuth): ?> wfls-disabled<?php endif; ?>"<?php if (!$canChangeUsernamePasswordAuth): ?> disabled<?php endif; ?>>
			<legend class="screen-reader-text"><?php echo esc_html($loginPreferenceTitle); ?></legend>
			<div class="wfls-passkey-password-auth-choice">
				<input id="<?php echo esc_attr($fallbackRadioID); ?>" type="radio" class="wfls-option-radio" name="<?php echo esc_attr($radioName); ?>" value="1"<?php checked($usernamePasswordAuthEnabled); ?>>
				<label for="<?php echo esc_attr($fallbackRadioID); ?>">
					<span class="wfls-passkey-password-auth-choice-copy">
						<span class="wfls-passkey-password-auth-choice-title">
							<span><?php esc_html_e('Allow username and password if a passkey fails', 'wordfence'); ?></span>
							<span class="wfls-passkey-password-auth-recommended-badge"><?php esc_html_e('RECOMMENDED', 'wordfence'); ?></span>
						</span>
						<span class="wfls-option-subtitle"><?php esc_html_e('Use your passkey when available, with username and password as a backup.', 'wordfence'); ?></span>
					</span>
				</label>
			</div>
			<div class="wfls-passkey-password-auth-choice">
				<input id="<?php echo esc_attr($passkeyOnlyRadioID); ?>" type="radio" class="wfls-option-radio" name="<?php echo esc_attr($radioName); ?>" value="0"<?php checked(!$usernamePasswordAuthEnabled); ?>>
				<label for="<?php echo esc_attr($passkeyOnlyRadioID); ?>">
					<span class="wfls-passkey-password-auth-choice-copy">
						<span class="wfls-passkey-password-auth-choice-title"><span><?php esc_html_e('Passkeys only', 'wordfence'); ?></span></span>
						<span class="wfls-option-subtitle"><?php esc_html_e('Only registered passkeys can be used to sign in.', 'wordfence'); ?></span>
					</span>
				</label>
			</div>
		</fieldset>
		<div class="wfls-passkey-password-auth-warning" role="alert"<?php if ($usernamePasswordAuthEnabled): ?> hidden<?php endif; ?>>
			<?php if (!$canManageSettings): ?>
				<?php echo wp_kses(__('<strong>Make sure you have a backup.</strong> If none of your registered passkeys are available or working, you may not be able to sign in. A site administrator will need to restore your access.', 'wordfence'), array('strong' => array())); ?>
			<?php elseif ($ownAccount): ?>
				<?php echo wp_kses(__('<strong>Make sure you have a backup.</strong> If none of your registered passkeys are available or working, you may not be able to sign in. Another administrator will need to restore your access. If you\'re the only administrator, you could be locked out of the site.', 'wordfence'), array('strong' => array())); ?>
			<?php else: ?>
				<?php echo wp_kses(__('<strong>Make sure this user has a backup.</strong> If none of their registered passkeys are available or working, they may not be able to sign in. A site administrator will need to restore this user’s access.', 'wordfence'), array('strong' => array())); ?>
			<?php endif; ?>
		</div>
		<?php if (!$canChangeUsernamePasswordAuth): ?>
			<p class="wfls-passkey-password-auth-unavailable" role="status">
				<?php if ($canManageSettings): ?>
					<?php esc_html_e('Cannot be changed because Passkeys are set to Required for this user’s role.', 'wordfence'); ?> <a class="wfls-help-link" href="<?php echo esc_url($managePasskeyRolesURL); ?>"><?php esc_html_e('Manage settings', 'wordfence'); ?> &rarr;</a>
				<?php else: ?>
					<?php esc_html_e('Cannot be changed because your administrator requires passkey-only sign-in for your role.', 'wordfence'); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</div>
<?php if ($ownAccount && $canChangeUsernamePasswordAuth): ?>
<div style="display: none;">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'id' => $modalTemplateID,
		'title' => __('Confirm “Passkeys only”', 'wordfence'),
		'message' => new \WordfenceLS\Text\Model_HTML(
			'<p>' . esc_html__('Selecting “Passkeys only” disables username and password sign-in for your account. If you lose access to your passkeys, you may be unable to sign in.', 'wordfence') . '</p>' .
			'<p>' . esc_html__('Before continuing, test your passkey in another browser or incognito window.', 'wordfence') . '</p>'
		),
		'primaryButton' => array('class' => 'wfls-passkey-password-auth-disable-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#'),
		'secondaryButtons' => array(array('class' => 'wfls-passkey-password-auth-disable-prompt-confirm', 'label' => __('Use Passkeys Only', 'wordfence'), 'link' => '#')),
	))->render();
	?>
</div>
<?php endif; ?>
<?php if ($canChangeUsernamePasswordAuth): ?>
<script type="application/javascript">
	(function($) {
		$(function() {
			var container = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($instanceID); ?>');
			var choices = container.find('input[type="radio"]');
			var warning = container.next('.wfls-passkey-password-auth-warning');
			var requiresDisableConfirmation = <?php echo $ownAccount ? 'true' : 'false'; ?>;
			if (!container.length || choices.length !== 2) {
				return;
			}

			var busy = false;
			var previousState = choices.filter(':checked').val() === '1';

			function updateChoiceState(enabled) {
				choices.filter('[value="1"]').prop('checked', enabled);
				choices.filter('[value="0"]').prop('checked', !enabled);
				warning.prop('hidden', enabled);
			}

			function setBusy(nextBusy) {
				busy = nextBusy;
				choices.prop('disabled', nextBusy);
				container.toggleClass('wfls-disabled', nextBusy);
			}

			function saveChoiceState(nextState) {
				updateChoiceState(nextState);
				setBusy(true);

				WFLS.ajax(
					'wordfence_ls_set_passkey_password_auth',
					{
						user: <?php echo (int) $user->ID; ?>,
						enabled: nextState ? 1 : 0
					},
					function(response) {
						setBusy(false);
						if (response.error) {
							updateChoiceState(previousState);
							WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Updating Passkey Options', 'wordfence')); ?>', response.error);
							return;
						}

						var navigationVisibilityChanged = previousState !== !!response.enabled;
						previousState = !!response.enabled;
						updateChoiceState(previousState);
						if (navigationVisibilityChanged) {
							window.location.reload();
						}
					},
					function() {
						setBusy(false);
						updateChoiceState(previousState);
						WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Updating Passkey Options', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to save the user-specific passkey options. Please try again.', 'wordfence')); ?>');
					}
				);
			}

			function confirmDisable(callback) {
				var content = $('#<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($modalTemplateID); ?>').clone().attr('id', null);
				if (!content.length) {
					callback();
					return;
				}

				WFLS.standaloneModalHTML(content, { onOpen: function(modal) {
					$(modal).find('.wfls-passkey-password-auth-disable-prompt-cancel').on('click', WFLS.closeStandaloneModal);
					$(modal).find('.wfls-passkey-password-auth-disable-prompt-confirm').on('click', function(confirmEvent) {
						confirmEvent.preventDefault();
						confirmEvent.stopPropagation();

						WFLS.closeStandaloneModal();
						callback();
					});
				}});
			}

			choices.on('change', function() {
				if (busy) {
					updateChoiceState(previousState);
					return;
				}

				var nextState = $(this).val() === '1';
				if (nextState === previousState) {
					return;
				}
				if (!nextState && requiresDisableConfirmation) {
					updateChoiceState(previousState);
					confirmDisable(function() {
						saveChoiceState(nextState);
					});
					return;
				}

				saveChoiceState(nextState);
			});
		});
	})(jQuery);
</script>
<?php endif; ?>
