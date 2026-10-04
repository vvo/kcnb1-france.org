<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $enablePasskeysURL URL that focuses the global Passkeys setting. Required.
 * @var string $settingsURL Login Security settings URL. Required.
 * @var bool $passkeysGloballyEnabled Whether passkeys are globally enabled. Required.
 * @var bool $ownAccount Whether the viewer is managing their own account. Required.
 */
$shieldIconClass = \WordfenceLS\Utility_Style::font_awesome_classes('shield');
?>
<div class="wfls-passkey-disabled-admin"
	<?php if (!$passkeysGloballyEnabled): ?>
	data-enable-message-default="<?php echo esc_attr(__('Passkeys will be enabled and set to Optional for all roles.', 'wordfence')); ?>"
	data-enable-message-role="<?php echo esc_attr(__('Passkeys will be enabled and set to Optional for your role.', 'wordfence')); ?>"
	data-enable-message-saved="<?php echo esc_attr(__('Passkeys will be re-enabled for roles using their last saved settings.', 'wordfence')); ?>"
	<?php endif; ?>>
	<section class="wfls-passkey-disabled-callout">
		<span class="wfls-passkey-disabled-lock" aria-hidden="true"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('lock')); ?>"></i></span>
		<div class="wfls-passkey-disabled-copy">
			<?php if (!$passkeysGloballyEnabled): ?>
				<h3><?php esc_html_e('Passkeys are disabled.', 'wordfence'); ?></h3>
				<p><?php esc_html_e('Passkeys are not enabled for this site. Existing passkeys and role settings are preserved.', 'wordfence'); ?></p>
			<?php elseif ($ownAccount): ?>
				<h3><?php esc_html_e('Passkeys are disabled for your role.', 'wordfence'); ?></h3>
				<p><?php esc_html_e('Passkeys are enabled for this site, but your role does not have access. Update role access in Login Security settings to use passkeys.', 'wordfence'); ?></p>
			<?php else: ?>
				<h3><?php esc_html_e('Passkeys are disabled for this user.', 'wordfence'); ?></h3>
				<p><?php esc_html_e('Passkeys are enabled for this site, but this user\'s role does not have access. Update role access in Login Security settings to manage this user\'s passkeys.', 'wordfence'); ?></p>
			<?php endif; ?>
		</div>
		<div class="wfls-passkey-disabled-actions">
			<?php if (!$passkeysGloballyEnabled): ?>
				<a href="<?php echo esc_url($enablePasskeysURL); ?>" class="wfls-btn wfls-btn-primary wfls-passkey-enable-button"><i class="<?php echo esc_attr($shieldIconClass); ?>" aria-hidden="true"></i> <?php esc_html_e('Enable Passkeys', 'wordfence'); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url($settingsURL); ?>" class="wfls-btn wfls-btn-default"><?php esc_html_e('Manage Login Security', 'wordfence'); ?></a>
		</div>
	</section>

	<section class="wfls-passkey-disabled-card">
		<h3><?php esc_html_e('Passwordless Sign-in with Passkeys', 'wordfence'); ?></h3>
		<div class="wfls-passkey-benefits">
			<div class="wfls-passkey-benefit"><span class="wfls-passkey-disabled-feature-icon wfls-main-icon-passkey" aria-hidden="true"></span><div><strong><?php esc_html_e('Stronger security', 'wordfence'); ?></strong><p><?php esc_html_e('Resistant to phishing, credential stuffing, and password leaks.', 'wordfence'); ?></p></div></div>
			<div class="wfls-passkey-benefit"><span class="wfls-passkey-disabled-feature-icon" aria-hidden="true"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('user')); ?>"></i></span><div><strong><?php esc_html_e('Easier sign-in', 'wordfence'); ?></strong><p><?php esc_html_e('Sign in with your fingerprint, face, or device PIN.', 'wordfence'); ?></p></div></div>
			<div class="wfls-passkey-benefit"><span class="wfls-passkey-disabled-feature-icon" aria-hidden="true"><i class="<?php echo esc_attr($shieldIconClass); ?>"></i></span><div><strong><?php esc_html_e('Built-in protection', 'wordfence'); ?></strong><p><?php esc_html_e('Passkeys are unique to your device and backed by strong encryption.', 'wordfence'); ?></p></div></div>
		</div>
	</section>

	<section class="wfls-passkey-disabled-card">
		<h3><?php esc_html_e('How it works', 'wordfence'); ?></h3>
		<div class="wfls-passkey-steps">
			<?php if (!$passkeysGloballyEnabled): ?>
				<div class="wfls-passkey-step"><span class="wfls-passkey-step-number">1</span><div><strong><?php esc_html_e('Enable passkeys', 'wordfence'); ?></strong><p><?php esc_html_e('Turn on passkeys for your site.', 'wordfence'); ?></p></div></div>
			<?php else: ?>
				<div class="wfls-passkey-step"><span class="wfls-passkey-step-number">1</span><div><strong><?php esc_html_e('Allow passkeys for the role', 'wordfence'); ?></strong><p><?php esc_html_e('Update role access in Login Security settings.', 'wordfence'); ?></p></div></div>
			<?php endif; ?>
			<span class="wfls-passkey-step-arrow" aria-hidden="true"></span>
			<div class="wfls-passkey-step"><span class="wfls-passkey-step-number">2</span><div><strong><?php esc_html_e('Users register a passkey', 'wordfence'); ?></strong><p><?php esc_html_e('Users add a passkey on their device at next sign-in.', 'wordfence'); ?></p></div></div>
			<span class="wfls-passkey-step-arrow" aria-hidden="true"></span>
			<div class="wfls-passkey-step"><span class="wfls-passkey-step-number">3</span><div><strong><?php esc_html_e('Passwordless sign-in', 'wordfence'); ?></strong><p><?php esc_html_e('Users sign in securely with their passkey—no password needed.', 'wordfence'); ?></p></div></div>
		</div>
	</section>
</div>

<?php if (!$passkeysGloballyEnabled): ?>
<div style="display: none;">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'id' => 'wfls-template-enable-passkeys-prompt',
		'title' => __('Enable Passkeys', 'wordfence'),
		'message' => __('Passkeys will be re-enabled for roles using their last saved settings.', 'wordfence'),
		'primaryButton' => array('class' => 'wfls-passkey-enable-prompt-confirm', 'label' => __('Enable Now', 'wordfence'), 'link' => '#'),
		'secondaryButtons' => array(array('class' => 'wfls-passkey-enable-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#')),
	))->render();
	?>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('.wfls-passkey-disabled-admin .wfls-passkey-enable-button').on('click', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var container = $(this).closest('.wfls-passkey-disabled-admin');
				var constants = window.WordfenceLSJSConstants || {};
				var options = constants.options || {};
				var states = constants.roles && constants.roles.states ? constants.roles.states : {};
				var roles = Array.isArray(options.passkey_roles) ? options.passkey_roles : [];
				var currentUser = constants.plugin && constants.plugin.current_user ? constants.plugin.current_user : {};
				var ownRoleNames = Array.isArray(currentUser.passkey_role_option_names) ? currentUser.passkey_role_option_names : [];
				var roleStatesAvailable = typeof states.passkey_disabled !== 'undefined' && typeof states.passkey_optional !== 'undefined' && typeof states.passkey_required !== 'undefined';
				var validRoleStates = roleStatesAvailable ? [states.passkey_disabled, states.passkey_optional, states.passkey_required] : [];
				var rolesAvailable = roles.length > 0 && roleStatesAvailable && roles.every(function(role) {
					return role && typeof role.name === 'string' && role.name.length > 0 && validRoleStates.indexOf(role.state) !== -1;
				});
				var message = container.data('enableMessageSaved');
				var changes = {'enable-passkeys': true};

				if (rolesAvailable && roles.every(function(role) { return role.state === states.passkey_disabled; })) {
					roles.forEach(function(role) {
						changes[role.name] = states.passkey_optional;
					});
					message = container.data('enableMessageDefault');
				}
				else if (rolesAvailable && ownRoleNames.length > 0) {
					var primaryRoles = roles.filter(function(role) { return role.name === ownRoleNames[0]; });
					var primaryRole = primaryRoles.length > 0 ? primaryRoles[0] : null;
					if (primaryRole && primaryRole.state === states.passkey_disabled) {
						changes[primaryRole.name] = states.passkey_optional;
						message = container.data('enableMessageRole');
					}
				}
				var content = $('#wfls-template-enable-passkeys-prompt').clone().attr('id', null);
				content.find('.wfls-modal-content').text(message);
				WFLS.standaloneModalHTML(content, { onOpen: function(modal) {
					$(modal).find('.wfls-passkey-enable-prompt-cancel').on('click', WFLS.closeStandaloneModal);
					$(modal).find('.wfls-passkey-enable-prompt-confirm').on('click', function(confirmEvent) {
						confirmEvent.preventDefault();
						confirmEvent.stopPropagation();

						WFLS.ajax(
							'wordfence_ls_save_options',
							{ changes: JSON.stringify(changes) },
							function(response) {
								if (response.error) {
									WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Saving Option', 'wordfence')); ?>', response.error);
								}
								else {
									window.location.reload();
								}
							},
							function() {
								WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Saving Option', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to enable passkeys. Please try again.', 'wordfence')); ?>');
							}
						);
					});
				}});
			});
		});
	})(jQuery);
</script>
<?php endif; ?>
