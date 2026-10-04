<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 * @var bool $stacked Whether to stack columns. Optional.
 * @var array $passkeys The registered passkeys. Required.
 * @var bool $canRegisterPasskeys Whether the viewer can register a new passkey for this user. Optional.
 * @var bool $passkeysEnabledForUser Whether passkeys are enabled for this user's role. Optional.
 * @var string $settingsURL The login security settings URL. Optional.
 * @var bool $showSettingsButton Whether to show the settings button when passkeys are disabled for this user's role. Optional.
 * @var string[] $initialAllowedHostnames The hostnames that will be saved as Allowed Passkey Hostnames. Optional.
 */

$stacked = isset($stacked) ? $stacked : false;
$ownUser = wp_get_current_user();
$ownAccount = $ownUser->ID == $user->ID;
$canRegisterPasskeys = isset($canRegisterPasskeys) ? $canRegisterPasskeys : true;
$canRegisterPasskeys = $canRegisterPasskeys && \WordfenceLS\Controller_Passkey::shared()->has_passkey_capacity($passkeys);
$showAddPasskeyPanel = $canRegisterPasskeys || !$ownAccount;
$passkeysEnabledForUser = isset($passkeysEnabledForUser) ? $passkeysEnabledForUser : true;
$settingsURL = isset($settingsURL) ? $settingsURL : (is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings'));
$showSettingsButton = isset($showSettingsButton) ? $showSettingsButton : true;
$containerClasses = 'wfls-flex-row ' . ($stacked ? 'wfls-flex-row-wrapped' : 'wfls-flex-row-wrappable wfls-flex-row-equal-heights');
$columnClasses = 'wfls-flex-row wfls-flex-item-xs-100 ' . ($stacked ? '' : 'wfls-flex-row-equal-heights');
$hasPasskey = \WordfenceLS\Controller_Users::shared()->has_passkey_active($user);
$requiresPasskey = \WordfenceLS\Controller_Users::shared()->requires_passkey($user, $inPasskeyGracePeriod, $passkeyRequiredAt);
$passkeyLockedOut = $requiresPasskey && !$hasPasskey;
$passkeyListTitle = $ownAccount ? __('Your Passkeys', 'wordfence') : __('Registered Passkeys', 'wordfence');
$uiStyleContext = \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$passkeyController = \WordfenceLS\Controller_Passkey::shared();
$canChangeUsernamePasswordAuth = $passkeyController->can_change_username_password_auth($user);
$showUsernamePasswordAuthOptions = !empty($passkeys) || !$canChangeUsernamePasswordAuth;
$passwordAuthWillBeRestored = count($passkeys) === 1 && $canChangeUsernamePasswordAuth && !$passkeyController->is_username_password_auth_enabled($user);
$removePromptMessage = __('Are you sure you want to remove this passkey? Deleting a passkey here does not necessarily remove it from your device\'s password manager.', 'wordfence');
if ($passwordAuthWillBeRestored) {
	$removePromptMessage = $ownAccount
		? __('This is your only passkey. If you remove it, you’ll return to signing in with your username and password. Removing it here may not remove it from your device’s password manager.', 'wordfence')
		: __('This is the user\'s only passkey. If you remove it, the user will return to signing in with a username and password. Removing it here will not remove it from the user\'s password manager.', 'wordfence');
}
$addIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('plus', $uiStyleContext);
?>
<div id="wfls-passkey-management-embedded" data-wfls-ui-style="<?php echo esc_attr($uiStyleContext); ?>"<?php if ($stacked): ?> class="stacked"<?php endif ?>>
	<p class="wfls-passkey-embedded-introduction"><?php esc_html_e('Passkeys let you sign in securely using your fingerprint, face, device PIN, or password.', 'wordfence'); ?></p>
	<?php if (!$passkeysEnabledForUser): ?>
		<?php
		$globallyEnabled = \WordfenceLS\Controller_Settings::shared()->are_passkeys_enabled();
		echo \WordfenceLS\Model_View::create('page/feature-disabled', array(
			'title' => !$globallyEnabled ? __('Passkeys are disabled.', 'wordfence') : ($ownAccount ? __('Passkeys are disabled.', 'wordfence') : __('Passkeys are disabled for this user.', 'wordfence')),
			'message' => !$globallyEnabled ? __('Signing in using a passkey is currently disabled for this site. Existing passkeys and role settings are preserved.', 'wordfence') : ($ownAccount ? __('Your role does not have permission to use passkeys.', 'wordfence') : ($showSettingsButton ? __('Enable passkeys on the settings page for this user\'s role to manage the user\'s passkeys.', 'wordfence') : __('Passkeys are not enabled for this user\'s role.', 'wordfence'))),
			'settingsURL' => $settingsURL,
			'showSettingsButton' => $showSettingsButton,
		))->render();
		?>
	</div>
		<?php return; ?>
	<?php endif; ?>
	<div class="<?php echo $containerClasses ?>">
		<div class="<?php echo $columnClasses ?> wfls-passkey-management-column wfls-passkey-registered-column<?php if (!$stacked): ?> <?php echo $showAddPasskeyPanel ? 'wfls-flex-item-sm-50' : 'wfls-flex-item-full-width'; ?><?php endif ?>">
			<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
				<div class="wfls-block-header wfls-block-header-border-bottom">
					<div class="wfls-block-header-content">
						<div class="wfls-block-title">
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
			</div>
		<?php if ($canRegisterPasskeys): ?>
			<div class="<?php echo $columnClasses ?> wfls-passkey-management-column wfls-passkey-add-column<?php if (!$stacked): ?> wfls-flex-item-sm-50<?php endif ?>">
				<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
					<div class="wfls-block-header wfls-block-header-border-bottom">
						<div class="wfls-block-header-content">
							<div class="wfls-block-title">
								<strong><?php esc_html_e('Add a Passkey', 'wordfence'); ?></strong>
							</div>
						</div>
					</div>
					<div class="wfls-block-content">
						<p><?php esc_html_e('Create a passkey and give it a name so you can identify it later. You may need a separate passkey for each device unless your passkeys sync through a password manager.', 'wordfence'); ?></p>
						<div class="wfls-passkey-add-row wfls-add-top wfls-add-bottom">
							<input type="text"
								   id="wfls-passkey-label"
								   class="input wfls-input-text wfls-passkey-add-input"
								   maxlength="255"
								   aria-label="<?php echo esc_attr__('Passkey Label', 'wordfence'); ?>"
								   placeholder="<?php echo esc_attr__('Passkey Name', 'wordfence'); ?>">
							<a href="#"
							   id="wfls-passkey-add"
							   class="wfls-btn wfls-btn-default wfls-passkey-add-button wfls-disabled"
							   aria-label="<?php echo esc_attr__('Add Passkey', 'wordfence'); ?>"
							   aria-disabled="true"
							   title="<?php echo esc_attr__('Add Passkey', 'wordfence'); ?>">
								<i class="<?php echo esc_attr($addIconClass); ?>" aria-hidden="true"></i>
								<span class="sr-only"><?php esc_html_e('Add Passkey', 'wordfence'); ?></span>
							</a>
						</div>
						<?php
							echo \WordfenceLS\Model_View::create('passkey/initial-allowed-hostnames', array(
								'initialAllowedHostnames' => isset($initialAllowedHostnames) ? $initialAllowedHostnames : array(),
							))->render();
							?>
						</div>
						<?php if ($inPasskeyGracePeriod || $passkeyLockedOut): ?>
							<?php echo \WordfenceLS\Model_View::create('passkey/grace-period', array(
								'user' => $user,
								'gracePeriod' => $inPasskeyGracePeriod,
								'lockedOut' => $passkeyLockedOut,
								'requiredAt' => $passkeyRequiredAt,
								'uiStyleContext' => $uiStyleContext,
							))->render(); ?>
						<?php endif; ?>
					</div>
				</div>
		<?php elseif (!$ownAccount): ?>
			<div class="<?php echo $columnClasses ?> wfls-passkey-management-column wfls-passkey-add-column<?php if (!$stacked): ?> wfls-flex-item-sm-50<?php endif ?>">
				<?php echo \WordfenceLS\Model_View::create('passkey/register-disabled', array(
					'uiStyleContext' => $uiStyleContext,
					'user' => $user,
					'gracePeriod' => $inPasskeyGracePeriod,
					'lockedOut' => $passkeyLockedOut,
					'requiredAt' => $passkeyRequiredAt,
				))->render(); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php echo \WordfenceLS\Model_View::create('passkey/information')->render(); ?>
	<?php if ($showUsernamePasswordAuthOptions): ?>
	<div class="<?php echo $containerClasses ?>">
		<div class="<?php echo $columnClasses ?> wfls-flex-item-full-width">
			<?php
			echo \WordfenceLS\Model_View::create('passkey/options', array(
				'user' => $user,
				'canChangeUsernamePasswordAuth' => $canChangeUsernamePasswordAuth,
				'canManageSettings' => $showSettingsButton,
				'settingsURL' => $settingsURL,
			))->render();
			?>
		</div>
	</div>
	<?php endif; ?>
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
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var button = $('#wfls-passkey-add');
			var labelField = $('#wfls-passkey-label');
			var list = $('#wfls-passkey-list');
			var empty = $('#wfls-passkey-empty');
			var container = list.closest('#wfls-passkey-management-embedded');
			var uiStyleContext = container.attr('data-wfls-ui-style') || 'wfls';
			var busy = false;

			function canSubmit() {
				return labelField.val().trim().length > 0;
			}

			function updateButtonState() {
				var disabled = busy || !canSubmit();
				button.toggleClass('wfls-disabled', disabled);
				button.attr('aria-disabled', disabled ? 'true' : 'false');
			}

			function setBusy(nextBusy) {
				busy = nextBusy;
				updateButtonState();
			}

			function updateEmptyState() {
				var isEmpty = list.find('.wfls-passkey-item').length === 0;
				empty.toggle(isEmpty);
			}

			<?php echo \WordfenceLS\Model_View::create('passkey/registration-error-handler')->render(); ?>

			if (button.length && labelField.length) {
				labelField.on('input change', updateButtonState);

				button.on('click', function(event) {
					event.preventDefault();
					event.stopPropagation();

					if (busy || !canSubmit()) {
						return;
					}
					if (!window.PublicKeyCredential || !navigator.credentials || !navigator.credentials.create) {
						if (passkeysRequireHTTPS()) {
							WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkeys Not Available', 'wordfence')); ?>', httpsRequiredMessage);
							return;
						}
						WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkeys Not Available', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('This browser does not support passkey registration.', 'wordfence')); ?>');
						return;
					}

					setBusy(true);
					var label = labelField.val();
					WFLS.ajax(
						'wordfence_ls_begin_passkey_registration',
						{
							user: <?php echo (int) $user->ID; ?>,
							label: label
						},
						function(response) {
							if (response.error) {
								setBusy(false);
								WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Starting Passkey Registration', 'wordfence')); ?>', response.error);
								return;
							}

							var publicKey = WFLS.publicKeyOptionsFromJSON(response.options);
							navigator.credentials.create({ publicKey: publicKey }).then(function(credential) {
								WFLS.ajax(
									'wordfence_ls_finish_passkey_registration',
									{
										user: <?php echo (int) $user->ID; ?>,
										label: label,
										token: response.token,
										credential: WFLS.credentialToJSON(credential),
										ui_style_context: uiStyleContext
									},
									function(finishResponse) {
										setBusy(false);
										if (finishResponse.error) {
											WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Adding Passkey', 'wordfence')); ?>', finishResponse.error);
											return;
										}

										var hadPasskeys = list.find('.wfls-passkey-item').length > 0;
										if (!hadPasskeys) {
											window.location.reload();
											return;
										}
										list.append(finishResponse.item_html);
										labelField.val('');
										updateButtonState();
										updateEmptyState();
									},
									function() {
										setBusy(false);
										WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Adding Passkey', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to save the new passkey. Please try again.', 'wordfence')); ?>');
									}
								);
							}).catch(function(error) {
								setBusy(false);
								if (error && error.name === 'NotAllowedError') {
									return;
								}
								WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Adding Passkey', 'wordfence')); ?>', describeError(error, '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkey registration could not be completed.', 'wordfence')); ?>'));
							});
						},
						function() {
							setBusy(false);
							WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Starting Passkey Registration', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to start passkey registration. Please try again.', 'wordfence')); ?>');
						}
					);
				});

				updateButtonState();
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
