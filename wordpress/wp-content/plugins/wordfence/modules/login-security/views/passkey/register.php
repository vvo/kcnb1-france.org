<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 * @var string $uiStyleContext The UI style context to use. Optional.
 * @var string[] $initialAllowedHostnames The hostnames that will be saved as Allowed Passkey Hostnames. Optional.
 * @var bool $gracePeriod Whether the user is currently in the passkey grace period. Optional.
 * @var bool $lockedOut Whether the user is locked out for a missing passkey. Optional.
 * @var int $requiredAt The passkey requirement activation time. Optional.
 */

$uiStyleContext = isset($uiStyleContext) && is_string($uiStyleContext)
	? \WordfenceLS\Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext)
	: \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$addIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('plus', $uiStyleContext);
$addHeaderIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('plus-circle', $uiStyleContext);
$gracePeriod = isset($gracePeriod) ? (bool) $gracePeriod : false;
$lockedOut = isset($lockedOut) ? (bool) $lockedOut : false;
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width" data-wfls-ui-style="<?php echo esc_attr($uiStyleContext); ?>">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title wfls-passkey-card-title">
				<span class="wfls-passkey-card-title-icon" aria-hidden="true"><i class="<?php echo esc_attr($addHeaderIconClass); ?>"></i></span>
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
<script type="application/javascript">
	(function($) {
		$(function() {
			var addButton = $('#wfls-passkey-add');
			var labelField = $('#wfls-passkey-label');
			var passkeyList = $('#wfls-passkey-list');
			var empty = $('#wfls-passkey-empty');
			var uiStyleContext = addButton.closest('[data-wfls-ui-style]').attr('data-wfls-ui-style') || 'wfls';
			var busy = false;

			function canSubmit() {
				return labelField.val().trim().length > 0;
			}

			function updateButtonState() {
				var disabled = busy || !canSubmit();
				addButton.toggleClass('wfls-disabled', disabled);
				addButton.attr('aria-disabled', disabled ? 'true' : 'false');
			}

			function setBusy(nextBusy) {
				busy = nextBusy;
				updateButtonState();
			}

			function updateEmptyState() {
				var isEmpty = passkeyList.find('.wfls-passkey-item').length === 0;
				empty.toggle(isEmpty);
			}

			<?php echo \WordfenceLS\Model_View::create('passkey/registration-error-handler')->render(); ?>

			labelField.on('input change', updateButtonState);

			addButton.on('click', function(event) {
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
				WFLS.ajax('wordfence_ls_begin_passkey_registration', { user: <?php echo (int) $user->ID; ?>, label: label },
					function(response) {
						if (response.error) {
							setBusy(false);
							WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Starting Passkey Registration', 'wordfence')); ?>', response.error);
							return;
						}

						var publicKey = WFLS.publicKeyOptionsFromJSON(response.options);
						navigator.credentials.create({ publicKey: publicKey }).then(function(credential) {
							WFLS.ajax('wordfence_ls_finish_passkey_registration',
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

									var hadPasskeys = passkeyList.find('.wfls-passkey-item').length > 0;
									if (!hadPasskeys) {
										window.location.reload();
										return;
									}
									passkeyList.append(finishResponse.item_html);
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
		});
	})(jQuery);
</script>
