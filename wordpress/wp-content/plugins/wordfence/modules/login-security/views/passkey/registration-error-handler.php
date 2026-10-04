<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
?>
var canManageSettings = <?php echo \WordfenceLS\Controller_Permissions::shared()->can_manage_settings() ? 'true' : 'false'; ?>;
var alreadyRegisteredHint = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('This may happen if this passkey or device is already registered for this site.', 'wordfence')); ?>';
var hostnameMismatchAdminMessage = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkey registration could not start because this site\'s passkey credential domain does not match the current page hostname. Check the Passkey Credential Domain setting or try registering from the site\'s primary hostname.', 'wordfence')); ?>';
var hostnameMismatchUserMessage = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkey registration could not start because this site\'s passkey credential domain does not match the current page hostname. Contact the site owner for help.', 'wordfence')); ?>';
var httpsRequiredMessage = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkey registration requires HTTPS. Open this site using https:// and try adding the passkey again.', 'wordfence')); ?>';
var unsupportedRequirementsMessage = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('This browser or device does not support the passkey requirements for this site. Try a different browser, device, or security key.', 'wordfence')); ?>';
var invalidRequestMessage = '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Passkey registration could not be started because the registration request was invalid. Please reload the page and try again.', 'wordfence')); ?>';

function passkeysRequireHTTPS() {
	return window.location && window.location.protocol === 'http:' && window.location.hostname !== 'localhost';
}

function describeError(error, fallback) {
	var message = fallback;
	if (passkeysRequireHTTPS()) {
		return httpsRequiredMessage;
	}
	if (!error) {
		return message;
	}
	if (error.name === 'SecurityError') {
		return canManageSettings ? hostnameMismatchAdminMessage : hostnameMismatchUserMessage;
	}
	if (error.name === 'ConstraintError' || error.name === 'NotSupportedError') {
		return unsupportedRequirementsMessage;
	}
	if (error.name === 'TypeError') {
		return invalidRequestMessage;
	}
	if (error.name && error.message) {
		message = error.name + ': ' + error.message;
	}
	else if (error.message) {
		message = error.message;
	}
	if (error.name === 'InvalidStateError' || error.name === 'UnknownError') {
		message = { html: WFLS.escapeHTML(message) + '<br>' + WFLS.escapeHTML(alreadyRegisteredHint) };
	}
	return message;
}
