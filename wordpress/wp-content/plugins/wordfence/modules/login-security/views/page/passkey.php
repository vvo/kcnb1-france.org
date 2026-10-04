<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

/**
 * @var \WP_User $user The user being edited. Required.
 * @var bool $canRegisterPasskeys Whether the viewer can register a new passkey for this user. Optional, defaults to true.
 * @var bool $passkeysEnabledForUser Whether passkeys are enabled for this user's role. Optional, defaults to true.
 * @var bool $targetUserPermissionDenied Whether the viewer is not allowed to view or edit the target user's passkeys. Optional, defaults to false.
 * @var string $settingsURL The login security settings URL. Optional.
 * @var string $enablePasskeysURL URL that focuses the global Passkeys setting. Optional.
 * @var bool $showSettingsButton Whether to show the settings button when passkeys are disabled for this user's role. Optional, defaults to true.
 * @var string[] $initialAllowedHostnames The hostnames that will be saved as Allowed Passkey Hostnames. Optional.
 */

if (!isset($canRegisterPasskeys)) {
	$canRegisterPasskeys = true;
}
if (!isset($passkeysEnabledForUser)) {
	$passkeysEnabledForUser = true;
}
if (!isset($targetUserPermissionDenied)) {
	$targetUserPermissionDenied = false;
}
if (!isset($settingsURL)) {
	$settingsURL = is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings');
}
if (!isset($enablePasskeysURL)) {
	$settingsBaseURL = is_multisite() ? network_admin_url('admin.php?page=WFLS') : admin_url('admin.php?page=WFLS');
	$enablePasskeysURL = add_query_arg('wfls-settings-anchor', 'enable-passkeys', $settingsBaseURL) . '#top#settings';
}
if (!isset($showSettingsButton)) {
	$showSettingsButton = true;
}

$ownAccount = false;
$ownUser = wp_get_current_user();
if ($ownUser->ID == $user->ID) {
	$ownAccount = true;
}
$passkeysGloballyEnabled = \WordfenceLS\Controller_Settings::shared()->are_passkeys_enabled();
?>

<?php if ($targetUserPermissionDenied): ?>
	<?php
	echo \WordfenceLS\Model_View::create('page/feature-disabled', array(
		'title' => __('Permission Denied', 'wordfence'),
		'message' => __('You are not allowed to view or edit that user.', 'wordfence'),
		'showSettingsButton' => false,
	))->render();
	return;
	?>
<?php endif; ?>
<?php if (!$passkeysEnabledForUser): ?>
	<?php
	if ($showSettingsButton) {
		echo \WordfenceLS\Model_View::create('page/passkey-disabled-admin', array(
			'enablePasskeysURL' => $enablePasskeysURL,
			'settingsURL' => $settingsURL,
			'passkeysGloballyEnabled' => $passkeysGloballyEnabled,
			'ownAccount' => $ownAccount,
		))->render();
	}
	else {
		echo \WordfenceLS\Model_View::create('page/feature-disabled', array(
			'title' => !$passkeysGloballyEnabled ? __('Passkeys are disabled.', 'wordfence') : ($ownAccount ? __('Passkeys are disabled.', 'wordfence') : __('Passkeys are disabled for this user.', 'wordfence')),
			'message' => !$passkeysGloballyEnabled ? __('Signing in using a passkey is currently disabled for this site. Existing passkeys and role settings are preserved.', 'wordfence') : ($ownAccount ? __('Your role does not have permission to use passkeys.', 'wordfence') : ($showSettingsButton ? __('Enable passkeys on the settings page for this user\'s role to manage the user\'s passkeys.', 'wordfence') : __('Passkeys are not enabled for this user\'s role.', 'wordfence'))),
			'settingsURL' => $settingsURL,
			'showSettingsButton' => $showSettingsButton,
		))->render();
	}
	return;
	?>
<?php endif; ?>
<?php
$hasPasskey = \WordfenceLS\Controller_Users::shared()->has_passkey_active($user);
$requiresPasskey = \WordfenceLS\Controller_Users::shared()->requires_passkey($user, $inPasskeyGracePeriod, $passkeyRequiredAt);
$passkeyLockedOut = $requiresPasskey && !$hasPasskey;
$uiStyleContext = \WordfenceLS\Controller_WordfenceLS::shared()->ui_style_context();
$passkeyController = \WordfenceLS\Controller_Passkey::shared();
$passkeys = $passkeyController->get_passkeys($user);
$canRegisterPasskeys = $canRegisterPasskeys && $passkeyController->has_passkey_capacity($passkeys);
$showAddPasskeyPanel = $canRegisterPasskeys || !$ownAccount;
$canChangeUsernamePasswordAuth = $passkeyController->can_change_username_password_auth($user);
$showUsernamePasswordAuthOptions = !empty($passkeys) || !$canChangeUsernamePasswordAuth;
?>
<div class="wfls-passkey-enabled-page">
	<div id="wfls-passkey-management-controls" class="wfls-passkey-card-grid">
	<?php if ($showAddPasskeyPanel): ?>
		<!-- begin register passkey -->
		<div class="wfls-passkey-add-column wfls-passkey-card-column">
			<?php
			if ($canRegisterPasskeys) {
				echo \WordfenceLS\Model_View::create('passkey/register', array(
					'user' => $user,
					'uiStyleContext' => $uiStyleContext,
					'initialAllowedHostnames' => isset($initialAllowedHostnames) ? $initialAllowedHostnames : array(),
					'gracePeriod' => $inPasskeyGracePeriod,
					'lockedOut' => $passkeyLockedOut,
					'requiredAt' => $passkeyRequiredAt,
				))->render();
			}
			else {
				echo \WordfenceLS\Model_View::create('passkey/register-disabled', array(
					'uiStyleContext' => $uiStyleContext,
					'user' => $user,
					'gracePeriod' => $inPasskeyGracePeriod,
					'lockedOut' => $passkeyLockedOut,
					'requiredAt' => $passkeyRequiredAt,
				))->render();
			}
			?>
		</div>
		<!-- end register passkey -->
	<?php endif; ?>
		<!-- begin active content -->
		<div class="wfls-passkey-registered-column wfls-passkey-card-column">
			<?php
			echo \WordfenceLS\Model_View::create('passkey/active', array(
				'user' => $user,
				'passkeys' => $passkeys,
				'uiStyleContext' => $uiStyleContext,
				'ownAccount' => $ownAccount,
			))->render();
			?>
		</div>
		<!-- end active content -->
	</div>
	<?php echo \WordfenceLS\Model_View::create('passkey/information', array('adminLayout' => true))->render(); ?>
	<?php if ($showUsernamePasswordAuthOptions): ?>
	<div id="wfls-passkey-options-controls" class="wfls-passkey-card-grid wfls-passkey-card-grid-single">
		<div class="wfls-passkey-card-column">
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
</div>
