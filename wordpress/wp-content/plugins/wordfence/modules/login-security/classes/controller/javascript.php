<?php

namespace WordfenceLS;

use WordfenceLS\Controller_WordfenceLS;
use WordfenceLS\Controller_Settings;
use WordfenceLS\Model_Asset;
use WordfenceLS\Model_Request;
use WordfenceLS\Controller_Permissions;
use WordfenceLS\Controller_Support;
use WordfenceLS\Controller_Time;
use WordfenceLS\Controller_Passkey;

class Controller_Javascript {
	/**
	 * Returns a mapping of translation strings for the Javascript frontend to use, populated via the WordPress
	 * translation system.
	 *
	 * It would be nice to be less redundant here, but the support for that is in WP 5.0 and unavailable in our
	 * current oldest supported version.
	 *
	 * @return array
	 */
	public static function i18nStrings() {
		return array(
			'%s ago' => /* translators: Relative time duration. */ __('%s ago', 'wordfence'),
			'(definitely a human)' => __('(definitely a human)', 'wordfence'),
			'(probably a bot)' => __('(probably a bot)', 'wordfence'),
			'(probably a human)' => __('(probably a human)', 'wordfence'),
			'or' => __('or', 'wordfence'),
			'2FA' => __('2FA', 'wordfence'),
			'2FA Notifications' => __('2FA Notifications', 'wordfence'),
			'2FA Relative URL (optional)' => __('2FA Relative URL (optional)', 'wordfence'),
			'2FA Role' => __('2FA Role', 'wordfence'),
			'2FA Roles' => __('2FA Roles', 'wordfence'),
			'Passkey/2FA management shortcode' => __('Passkey/2FA management shortcode', 'wordfence'),
			'A reCAPTCHA score equal to or higher than this value will be considered human. Anything lower will be treated as a bot and require additional verification for login and registration.' => __('A reCAPTCHA score equal to or higher than this value will be considered human. Anything lower will be treated as a bot and require additional verification for login and registration.', 'wordfence'),
			'When no override is set, Wordfence uses this site\'s main domain ("%s") so passkeys can work across its subdomains. To limit passkeys to a specific login hostname, enter it here. For example, "example.com" allows "www.example.com"; "www.example.com" does not allow "example.com" or "login.example.com".' => /* translators: default passkey RP hostname */ __('When no override is set, Wordfence uses this site\'s main domain ("%s") so passkeys can work across its subdomains. To limit passkeys to a specific login hostname, enter it here. For example, "example.com" allows "www.example.com"; "www.example.com" does not allow "example.com" or "login.example.com".', 'wordfence'),
			'Authentication' => __('Authentication', 'wordfence'),
			'Advanced Settings' => __('Advanced Settings', 'wordfence'),
			'Advanced settings' => __('Advanced settings', 'wordfence'),
			'2FA is available to all roles.' => __('2FA is available to all roles.', 'wordfence'),
			'2FA is required for one or more roles.' => __('2FA is required for one or more roles.', 'wordfence'),
			'Add Login Security to WooCommerce and custom account pages. Testing WooCommerce forms after enabling or changing these is recommended to ensure plugin compatibility.' => __('Add Login Security to WooCommerce and custom account pages. Testing WooCommerce forms after enabling or changing these is recommended to ensure plugin compatibility.', 'wordfence'),
			'All roles are set to Optional.' => __('All roles are set to Optional.', 'wordfence'),
			'All roles are set to Required.' => __('All roles are set to Required.', 'wordfence'),
			'Allow users to protect password sign-in with an additional authentication method.' => __('Allow users to protect password sign-in with an additional authentication method.', 'wordfence'),
			'Allow users to register and sign in with passkeys.' => __('Allow users to register and sign in with passkeys.', 'wordfence'),
			'Choose whether 2FA is required, optional, or not allowed for each role.' => __('Choose whether 2FA is required, optional, or not allowed for each role.', 'wordfence'),
			'Choose whether passkeys are disabled, optional, or required for each role.' => __('Choose whether passkeys are disabled, optional, or required for each role.', 'wordfence'),
			'Configure Grace Period' => __('Configure Grace Period', 'wordfence'),
			'Enable 2FA' => __('Enable 2FA', 'wordfence'),
			'Enable passkeys' => __('Enable passkeys', 'wordfence'),
			'Notifications are disabled because no roles are set to "Required" for passkeys or 2FA.' => __('Notifications are disabled because no roles are set to "Required" for passkeys or 2FA.', 'wordfence'),
			'Learn more about 2FA' => __('Learn more about 2FA', 'wordfence'),
			'Learn more about passkeys' => __('Learn more about passkeys', 'wordfence'),
			'Manage shared login security behavior and administrative options.' => __('Manage shared login security behavior and administrative options.', 'wordfence'),
			'Off' => __('Off', 'wordfence'),
			'On' => __('On', 'wordfence'),
			'Passkeys are available to all roles.' => __('Passkeys are available to all roles.', 'wordfence'),
			'Passkeys will be enabled and set to Optional for all roles.' => __('Passkeys will be enabled and set to Optional for all roles.', 'wordfence'),
			'Passkeys will be enabled and set to Optional for your role.' => __('Passkeys will be enabled and set to Optional for your role.', 'wordfence'),
			'Roles are set to a mix of Optional and Required.' => __('Roles are set to a mix of Optional and Required.', 'wordfence'),
			'Protect login and registration forms from automated abuse.' => __('Protect login and registration forms from automated abuse.', 'wordfence'),
			'Show the Login Security menu to all users, even when both passkeys and 2FA are disabled.' => __('Show the Login Security menu to all users, even when both passkeys and 2FA are disabled.', 'wordfence'),
			'Two-Factor Authentication' => __('Two-Factor Authentication', 'wordfence'),
			'Who can use passkeys?' => __('Who can use passkeys?', 'wordfence'),
			'Who can use 2FA?' => __('Who can use 2FA?', 'wordfence'),
			'Always show Login Security menu' => __('Always show Login Security menu', 'wordfence'),
			'Allow remembering device for 30 days' => __('Allow remembering device for 30 days', 'wordfence'),
			'Allowlisted IP addresses that bypass 2FA, passkey requirements, and reCAPTCHA' => __('Allowlisted IP addresses that bypass 2FA, passkey requirements, and reCAPTCHA', 'wordfence'),
			'Requests from these IPs bypass 2FA, passkey requirements, username/password restrictions for passkey-required accounts, and reCAPTCHA.' => __('Requests from these IPs bypass 2FA, passkey requirements, username/password restrictions for passkey-required accounts, and reCAPTCHA.', 'wordfence'),
			'Allowlisted IPs must be placed on separate lines. You can specify ranges using the following formats: 127.0.0.1/24, 127.0.0.[1-100], or 127.0.0.1-127.0.1.100.' => __('Allowlisted IPs must be placed on separate lines. You can specify ranges using the following formats: 127.0.0.1/24, 127.0.0.[1-100], or 127.0.0.1-127.0.1.100.', 'wordfence'),
			'An error occurred' => __('An error occurred', 'wordfence'),
			'An error was encountered while trying to disable NTP. Please try again.' => __('An error was encountered while trying to disable NTP. Please try again.', 'wordfence'),
			'An error was encountered while trying to reset the NTP state. Please try again.' => __('An error was encountered while trying to reset the NTP state. Please try again.', 'wordfence'),
			'An error was encountered while trying to send the notification. Please try again.' => __('An error was encountered while trying to send the notification. Please try again.', 'wordfence'),
			'Cancel' => __('Cancel', 'wordfence'),
			'Cancel Changes' => __('Cancel Changes', 'wordfence'),
			'Close' => __('Close', 'wordfence'),
			'Confirm Requiring Passkeys for Your Role' => __('Confirm Requiring Passkeys for Your Role', 'wordfence'),
			'Hostnames allowed to register and use passkeys. Most sites can leave the default values unchanged.' => __('Hostnames allowed to register and use passkeys. Most sites can leave the default values unchanged.', 'wordfence'),
			'Count' => __('Count', 'wordfence'),
			'Detected IP(s)' => __('Detected IP(s)', 'wordfence'),
			'days' => __('days', 'wordfence'),
			'Delete Login Security tables and data on deactivation' => __('Delete Login Security tables and data on deactivation', 'wordfence'),
			'Disable' => __('Disable', 'wordfence'),
			'Disable XML-RPC authentication' => __('Disable XML-RPC authentication', 'wordfence'),
			'Edit trusted proxies' => __('Edit trusted proxies', 'wordfence'),
			'e.g., /my-account/' => __('e.g., /my-account/', 'wordfence'),
			'Enable reCAPTCHA on the login and user registration pages' => __('Enable reCAPTCHA on the login and user registration pages', 'wordfence'),
			'Enable Now' => __('Enable Now', 'wordfence'),
			'Enable Passkeys' => __('Enable Passkeys', 'wordfence'),
			'An error was encountered while trying to authenticate with a passkey. Please try again.' => __('An error was encountered while trying to authenticate with a passkey. Please try again.', 'wordfence'),
			'An error was encountered while trying to remove the passkey. Please try again.' => __('An error was encountered while trying to remove the passkey. Please try again.', 'wordfence'),
			'An error was encountered while trying to save the new passkey. Please try again.' => __('An error was encountered while trying to save the new passkey. Please try again.', 'wordfence'),
			'An error was encountered while trying to save the user-specific passkey options. Please try again.' => __('An error was encountered while trying to save the user-specific passkey options. Please try again.', 'wordfence'),
			'An error was encountered while trying to start passkey login. Please try again.' => __('An error was encountered while trying to start passkey login. Please try again.', 'wordfence'),
			'An error was encountered while trying to start passkey registration. Please try again.' => __('An error was encountered while trying to start passkey registration. Please try again.', 'wordfence'),
			'Are you sure you want to remove this passkey?' => __('Are you sure you want to remove this passkey?', 'wordfence'),
			'Passkey login requires HTTPS. Open this site using https:// and try again.' => __('Passkey login requires HTTPS. Open this site using https:// and try again.', 'wordfence'),
			'Allow username/password authentication' => __('Allow username/password authentication', 'wordfence'),
			'Allow Update' => __('Allow Update', 'wordfence'),
			'Cancel' => __('Cancel', 'wordfence'),
			'Add Passkey' => __('Add Passkey', 'wordfence'),
			'Allowed Passkey Hostnames' => __('Allowed Passkey Hostnames', 'wordfence'),
			'Existing passkeys may be blocked on any hostnames or ports removed from this list. Make sure every hostname and port users use to log in remains listed.' => __('Existing passkeys may be blocked on any hostnames or ports removed from this list. Make sure every hostname and port users use to log in remains listed.', 'wordfence'),
			'Changing the passkey credential domain can block existing passkeys. If the new domain is different from, or does not include, the domain existing passkeys were created for, affected passkeys must be re-registered.' => __('Changing the passkey credential domain can block existing passkeys. If the new domain is different from, or does not include, the domain existing passkeys were created for, affected passkeys must be re-registered.', 'wordfence'),
			'Error Adding Passkey' => __('Error Adding Passkey', 'wordfence'),
			'Error Disabling NTP' => __('Error Disabling NTP', 'wordfence'),
			'Error Removing Passkey' => __('Error Removing Passkey', 'wordfence'),
			'Error Resetting NTP' => __('Error Resetting NTP', 'wordfence'),
			'Error Resetting reCAPTCHA Statistics' => __('Error Resetting reCAPTCHA Statistics', 'wordfence'),
			'Error Updating Public Suffix List' => __('Error Updating Public Suffix List', 'wordfence'),
			'Error Updating Passkey Options' => __('Error Updating Passkey Options', 'wordfence'),
			'Error Saving Option' => __('Error Saving Option', 'wordfence'),
			'Error Saving Options' => __('Error Saving Options', 'wordfence'),
			'Error Sending Notification' => __('Error Sending Notification', 'wordfence'),
			'Error Starting Passkey Registration' => __('Error Starting Passkey Registration', 'wordfence'),
			'Give users time to set up required passkeys or 2FA before access is restricted. Admin users must have the grace period manually enabled.' => __('Give users time to set up required passkeys or 2FA before access is restricted. Admin users must have the grace period manually enabled.', 'wordfence'),
			'<strong>Before requiring passkeys:</strong> Make sure users have a backup passkey or another way to access their account' => __('<strong>Before requiring passkeys:</strong> Make sure users have a backup passkey or another way to access their account', 'wordfence'),
			'General' => __('General', 'wordfence'),
			'Grace Period' => __('Grace Period', 'wordfence'),
			'Grace period' => __('Grace period', 'wordfence'),
			'How to get IPs' => __('How to get IPs', 'wordfence'),
			'If enabled, users with 2FA enabled may choose to be prompted for a code only once every 30 days per device.' => __('If enabled, users with 2FA enabled may choose to be prompted for a code only once every 30 days per device.', 'wordfence'),
			'If enabled, XML-RPC calls that require authentication will also require a valid 2FA code to be appended to the password. You must choose the "Skipped" option if you use the WordPress app, the Jetpack plugin, or other services that require XML-RPC.' => __('If enabled, XML-RPC calls that require authentication will also require a valid 2FA code to be appended to the password. You must choose the "Skipped" option if you use the WordPress app, the Jetpack plugin, or other services that require XML-RPC.', 'wordfence'),
			'If disabled, logging in with a username and password will be blocked for this account when a passkey is registered.' => __('If disabled, logging in with a username and password will be blocked for this account when a passkey is registered.', 'wordfence'),
			'If disabled, XML-RPC requests that attempt authentication will be rejected, whether the user has 2FA enabled or not.' => __('If disabled, XML-RPC requests that attempt authentication will be rejected, whether the user has 2FA enabled or not.', 'wordfence'),
			'If enabled, all settings and 2FA records will be deleted on deactivation. If later reactivated, all users that previously had 2FA active will need to set it up again.' => __('If enabled, all settings and 2FA records will be deleted on deactivation. If later reactivated, all users that previously had 2FA active will need to set it up again.', 'wordfence'),
			'Learn More' => __('Learn More', 'wordfence'),
			'Learn more' => __('Learn more', 'wordfence'),
			'Log In with a Passkey' => __('Log In with a Passkey', 'wordfence'),
			'Manage Login Security' => __('Manage Login Security', 'wordfence'),
			'On multisite installations, passkey roles are currently limited to Super Administrators only' => __('On multisite installations, passkey roles are currently limited to Super Administrators only', 'wordfence'),
			'NTP' => __('NTP', 'wordfence'),
			'NTP is a protocol that allows for remote time synchronization. Wordfence Login Security uses this protocol to ensure that it has the most accurate time which is necessary for TOTP-based two-factor authentication.' => __('NTP is a protocol that allows for remote time synchronization. Wordfence Login Security uses this protocol to ensure that it has the most accurate time which is necessary for TOTP-based two-factor authentication.', 'wordfence'),
			'NTP is currently <strong>enabled</strong>.' => __('NTP is currently <strong>enabled</strong>.', 'wordfence'),
			'NTP is currently disabled as %d subsequent attempts have failed.' => /* translators: number of attempts */ __('NTP is currently disabled as %d subsequent attempts have failed.', 'wordfence'),
			'NTP updates are currently failing.' => __('NTP updates are currently failing.', 'wordfence'),
			'NTP was manually disabled.' => __('NTP was manually disabled.', 'wordfence'),
			'NTP will be automatically disabled after %d more attempts.' => /* translators: number of attempts */ __('NTP will be automatically disabled after %d more attempts.', 'wordfence'),
			'NTP will be automatically disabled after 1 more attempt.' => __('NTP will be automatically disabled after 1 more attempt.', 'wordfence'),
			'Note: This feature requires a free site key and secret for the <a href="https://www.google.com/recaptcha/about/" target="_blank" rel="noopener noreferrer">Google reCAPTCHA v3 Service</a>. To set up new reCAPTCHA keys, log into your Google account and go to the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">reCAPTCHA admin page</a>.' => __('Note: This feature requires a free site key and secret for the <a href="https://www.google.com/recaptcha/about/" target="_blank" rel="noopener noreferrer">Google reCAPTCHA v3 Service</a>. To set up new reCAPTCHA keys, log into your Google account and go to the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">reCAPTCHA admin page</a>.', 'wordfence'),
			'Notification Results' => __('Notification Results', 'wordfence'),
			'Notification Sent' => __('Notification Sent', 'wordfence'),
			'Not recommended' => __('Not recommended', 'wordfence'),
			'Optional:' => __('Optional:', 'wordfence'),
			'RECOMMENDED' => __('RECOMMENDED', 'wordfence'),
			'Required:' => __('Required:', 'wordfence'),
			'Test passkey sign-in for each enabled role to check for plugin or theme conflicts' => __('Test passkey sign-in for each enabled role to check for plugin or theme conflicts', 'wordfence'),
			'Users can sign in with their username and password during the grace period only. After it ends, they must use a passkey' => __('Users can sign in with their username and password during the grace period only. After it ends, they must use a passkey', 'wordfence'),
			'Users can use passkeys but still can sign in with their password' => __('Users can use passkeys but still can sign in with their password', 'wordfence'),
			'Notify' => __('Notify', 'wordfence'),
			'optional' => __('optional', 'wordfence'),
			'Passkey Credential Domain' => __('Passkey Credential Domain', 'wordfence'),
			'Passkey Name' => __('Passkey Name', 'wordfence'),
			'One hostname per line. HTTPS uses port 443 by default. Specify other ports explicitly.' => __('One hostname per line. HTTPS uses port 443 by default. Specify other ports explicitly.', 'wordfence'),
			'Controls which domain passkeys are created for. Enter a hostname here only if you need to use a different credential domain. Most sites should leave this unchanged.' => __('Controls which domain passkeys are created for. Enter a hostname here only if you need to use a different credential domain. Most sites should leave this unchanged.', 'wordfence'),
			'Confirm Disabling Passkeys' => __('Confirm Disabling Passkeys', 'wordfence'),
			'Confirm Disabling Two-Factor Authentication' => __('Confirm Disabling Two-Factor Authentication', 'wordfence'),
			'Disabling passkeys will prevent all users from registering or signing in with passkeys. Existing passkeys and role settings will be preserved. Users may sign in using only their username and password without any additional protection.' => __('Disabling passkeys will prevent all users from registering or signing in with passkeys. Existing passkeys and role settings will be preserved. Users may sign in using only their username and password without any additional protection.', 'wordfence'),
			'Disabling two-factor authentication will prevent all users from signing in with 2FA. Existing 2FA credentials and role settings will be preserved. Users may sign in using only their username and password without any additional protection.' => __('Disabling two-factor authentication will prevent all users from signing in with 2FA. Existing 2FA credentials and role settings will be preserved. Users may sign in using only their username and password without any additional protection.', 'wordfence'),
			/* translators: Default passkey credential domain. */
			'By default, Wordfence uses this site\'s main domain ("%s").' => __('By default, Wordfence uses this site\'s main domain ("%s").', 'wordfence'),
			'Passkey registration could not be completed.' => __('Passkey registration could not be completed.', 'wordfence'),
			'Passkey Roles' => __('Passkey Roles', 'wordfence'),
			'Passkey Sign-In Counter Validation' => __('Passkey Sign-In Counter Validation', 'wordfence'),
			'Determines how Wordfence handles authenticator sign-in counters used to detect cloned passkeys.' => __('Determines how Wordfence handles authenticator sign-in counters used to detect cloned passkeys.', 'wordfence'),
			'Allow any counter value' => __('Allow any counter value', 'wordfence'),
			'Reject non-zero counters that do not increase (recommended)' => __('Reject non-zero counters that do not increase (recommended)', 'wordfence'),
			'Reject counters that do not increase or reset to zero' => __('Reject counters that do not increase or reset to zero', 'wordfence'),
			'Public Suffix List Up to Date' => __('Public Suffix List Up to Date', 'wordfence'),
			'Public Suffix List Update' => __('Public Suffix List Update', 'wordfence'),
			'Public Suffix List Updated' => __('Public Suffix List Updated', 'wordfence'),
			'Public Suffix List' => __('Public Suffix List', 'wordfence'),
			'reCAPTCHA' => __('reCAPTCHA', 'wordfence'),
			'reCAPTCHA human/bot threshold score' => __('reCAPTCHA human/bot threshold score', 'wordfence'),
			'reCAPTCHA Score History' => __('reCAPTCHA Score History', 'wordfence'),
			'reCAPTCHA v3 does not make users solve puzzles or click a checkbox like previous versions. The only visible part is the reCAPTCHA logo. If a visitor\'s browser fails the CAPTCHA, Wordfence will send an email to the user\'s address with a link they can click to verify that they are a user of your site. You can read further details <a href="%s" target="_blank" rel="noopener noreferrer">in our documentation</a>.' => /* translators: Support URL */ __('reCAPTCHA v3 does not make users solve puzzles or click a checkbox like previous versions. The only visible part is the reCAPTCHA logo. If a visitor\'s browser fails the CAPTCHA, Wordfence will send an email to the user\'s address with a link they can click to verify that they are a user of your site. You can read further details <a href="%s" target="_blank" rel="noopener noreferrer">in our documentation</a>.', 'wordfence'),
			'reCAPTCHA v3 Secret' => __('reCAPTCHA v3 Secret', 'wordfence'),
			'reCAPTCHA v3 Site Key' => __('reCAPTCHA v3 Site Key', 'wordfence'),
			'Requests' => __('Requests', 'wordfence'),
			'Required' => __('Required', 'wordfence'),
			'Require' => __('Require', 'wordfence'),
			'Require Passkeys' => __('Require Passkeys', 'wordfence'),
			'Passkey login could not be completed.' => __('Passkey login could not be completed.', 'wordfence'),
			'Passkey settings will be added here.' => __('Passkey settings will be added here.', 'wordfence'),
			'Passkeys' => __('Passkeys', 'wordfence'),
			'Enable passkeys to choose which user roles can use them and configure additional settings.' => __('Enable passkeys to choose which user roles can use them and configure additional settings.', 'wordfence'),
			'Relative URL' => __('Relative URL', 'wordfence'),
			'Some customers may have trouble with passkeys. "Optional" lets them sign in with a password or passkey.' => __('Some customers may have trouble with passkeys. "Optional" lets them sign in with a password or passkey.', 'wordfence'),
			'Requiring passkeys for your role will make your account more secure. However, if you lose access to your passkey, you will not be able to log in to this account at all. We recommend testing your login using another browser or incognito window before you log out in this window.' => __('Requiring passkeys for your role will make your account more secure. However, if you lose access to your passkey, you will not be able to log in to this account at all. We recommend testing your login using another browser or incognito window before you log out in this window.', 'wordfence'),
			'Some customers may have trouble with 2FA. "Optional" lets them choose whether to enable it.' => __('Some customers may have trouble with 2FA. "Optional" lets them choose whether to enable it.', 'wordfence'),
			'Reset' => __('Reset', 'wordfence'),
			'Reset Score Statistics' => __('Reset Score Statistics', 'wordfence'),
			'Require 2FA for XML-RPC call authentication' => __('Require 2FA for XML-RPC call authentication', 'wordfence'),
			'Remove' => __('Remove', 'wordfence'),
			'Remove Passkey' => __('Remove Passkey', 'wordfence'),
			'Run reCAPTCHA in test mode' => __('Run reCAPTCHA in test mode', 'wordfence'),
			'Role' => __('Role', 'wordfence'),
			'Roles' => __('Roles', 'wordfence'),
			'Users' => __('Users', 'wordfence'),
			'Active' => __('Active', 'wordfence'),
			'Inactive' => __('Inactive', 'wordfence'),
			'Total' => __('Total', 'wordfence'),
			'View users' => __('View users', 'wordfence'),
			'* User counts currently only reflect the main site on multisite installations.' => __('* User counts currently only reflect the main site on multisite installations.', 'wordfence'),
			'User counts are hidden by default on sites with large numbers of users in order to improve performance.' => __('User counts are hidden by default on sites with large numbers of users in order to improve performance.', 'wordfence'),
			'User counts are currently disabled as the most recent attempt to count users failed to complete successfully.' => __('User counts are currently disabled as the most recent attempt to count users failed to complete successfully.', 'wordfence'),
			'Show User Counts' => __('Show User Counts', 'wordfence'),
			'Try Again' => __('Try Again', 'wordfence'),
			'Save' => __('Save', 'wordfence'),
			'Save Changes' => __('Save Changes', 'wordfence'),
			'Save your changes before switching tabs?' => __('Save your changes before switching tabs?', 'wordfence'),
			'Send Anyway' => __('Send Anyway', 'wordfence'),
			'Send Notifications' => __('Send Notifications', 'wordfence'),
			'Email users in the selected role to remind them to set up required authentication. Optionally specify the URL to be sent in the email; blank defaults to Wordfence’s Login Security page.' => __('Email users in the selected role to remind them to set up required authentication. Optionally specify the URL to be sent in the email; blank defaults to Wordfence’s Login Security page.', 'wordfence'),
			'Setting the grace period to 0 will prevent users in roles where 2FA or a passkey is required, including newly created users, from logging in if they have not already enabled it.' => __('Setting the grace period to 0 will prevent users in roles where 2FA or a passkey is required, including newly created users, from logging in if they have not already enabled it.', 'wordfence'),
			'Skipped' => __('Skipped', 'wordfence'),
			'Show Wordfence Passkeys and Wordfence 2FA menus on WooCommerce Account page' => __('Show Wordfence Passkeys and Wordfence 2FA menus on WooCommerce Account page', 'wordfence'),
			'Show last login column on WP Users page' => __('Show last login column on WP Users page', 'wordfence'),
			'The constant WORDFENCE_LS_DISABLE_NTP is defined which disables NTP entirely. Remove this constant or set it to a falsy value to enable NTP.' => __('The constant WORDFENCE_LS_DISABLE_NTP is defined which disables NTP entirely. Remove this constant or set it to a falsy value to enable NTP.', 'wordfence'),
			'The public suffix list is already up to date.' => __('The public suffix list is already up to date.', 'wordfence'),
			'The public suffix list update could not be completed. Please try again.' => __('The public suffix list update could not be completed. Please try again.', 'wordfence'),
			'The public suffix list was updated successfully.' => __('The public suffix list was updated successfully.', 'wordfence'),
			'These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.' => __('These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.', 'wordfence'),
			'Two Factor Authentication is required only for username/password login when enabled. Passkeys are a substitute for this secondary authentication method.' => __('Two Factor Authentication is required only for username/password login when enabled. Passkeys are a substitute for this secondary authentication method.', 'wordfence'),
			'Trusted Proxies' => __('Trusted Proxies', 'wordfence'),
			'Keep Editing' => __('Keep Editing', 'wordfence'),
			'just now' => __('just now', 'wordfence'),
			'less than 1 second' => __('less than 1 second', 'wordfence'),
			'opens in new tab' => __('opens in new tab', 'wordfence'),
			'Unsaved Changes' => __('Unsaved Changes', 'wordfence'),
			'Update Public Suffix List' => __('Update Public Suffix List', 'wordfence'),
			'Updating the public suffix list would change this site\'s default passkey credential domain. Existing passkeys may stop working unless a Passkey Credential Domain is set. Allow this update to be saved?' => __('Updating the public suffix list would change this site\'s default passkey credential domain. Existing passkeys may stop working unless a Passkey Credential Domain is set. Allow this update to be saved?', 'wordfence'),
			'This browser does not support passkey login.' => __('This browser does not support passkey login.', 'wordfence'),
			'This browser does not support passkey registration.' => __('This browser does not support passkey registration.', 'wordfence'),
			'This may happen if this passkey or device is already registered for this site.' => __('This may happen if this passkey or device is already registered for this site.', 'wordfence'),
			'This option is unavailable because passkeys are required for one or more of this user\'s roles.' => __('This option is unavailable because passkeys are required for one or more of this user\'s roles.', 'wordfence'),
			'NOTE: This option is currently overridden by the global role requirement' => __('NOTE: This option is currently overridden by the global role requirement', 'wordfence'),
			'This option cannot be changed because passkeys are required for one or more of this user\'s roles.' => __('This option cannot be changed because passkeys are required for one or more of this user\'s roles.', 'wordfence'),
			'Enable 2FA to choose which user roles can use it and configure additional settings.' => __('Enable 2FA to choose which user roles can use it and configure additional settings.', 'wordfence'),
			'User Options' => __('User Options', 'wordfence'),
			'Use single-column layout for WooCommerce/shortcode Login Security management interface' => __('Use single-column layout for WooCommerce/shortcode Login Security management interface', 'wordfence'),
			'When enabled, separate Wordfence Passkeys and Wordfence 2FA entries will be added to the WooCommerce account menu so users can manage their credentials outside of the WordPress admin area.' => __('When enabled, separate Wordfence Passkeys and Wordfence 2FA entries will be added to the WooCommerce account menu so users can manage their credentials outside of the WordPress admin area.', 'wordfence'),
			'When enabled, reCAPTCHA and 2FA prompt support will be added to WooCommerce login and registration forms in addition to the default WordPress forms.' => __('When enabled, reCAPTCHA and 2FA prompt support will be added to WooCommerce login and registration forms in addition to the default WordPress forms.', 'wordfence'),
			'When enabled, the "wordfence_passkey_management" and "wordfence_2fa_management" shortcodes may be used to provide access for users to manage passkey and 2FA credentials on custom pages.' => __('When enabled, the "wordfence_passkey_management" and "wordfence_2fa_management" shortcodes may be used to provide access for users to manage passkey and 2FA credentials on custom pages.', 'wordfence'),
			'When enabled, roles can see the Login Security menu even when both passkeys and 2FA are disabled for that role. This also controls whether users will see messages indicating any disabled authentication methods.' => __('When enabled, roles can see the Login Security menu even when both passkeys and 2FA are disabled for that role. This also controls whether users will see messages indicating any disabled authentication methods.', 'wordfence'),
			'Used to determine the default passkey credential domain. Usually does not need to be updated manually.' => __('Used to determine the default passkey credential domain. Usually does not need to be updated manually.', 'wordfence'),
			'When enabled, the passkey and 2FA management interfaces embedded through the WooCommerce integration or via a shortcode will use a vertical stacked layout as opposed to horizontal columns. Adjust this setting as appropriate to match your theme. This may be overridden using the "stacked" attribute for individual shortcodes.' => __('When enabled, the passkey and 2FA management interfaces embedded through the WooCommerce integration or via a shortcode will use a vertical stacked layout as opposed to horizontal columns. Adjust this setting as appropriate to match your theme. This may be overridden using the "stacked" attribute for individual shortcodes.', 'wordfence'),
			'When enabled, the last login timestamp will be displayed for each user on the WP Users page. When used in conjunction with reCAPTCHA, the most recent score will also be displayed for each user.' => __('When enabled, the last login timestamp will be displayed for each user on the WP Users page. When used in conjunction with reCAPTCHA, the most recent score will also be displayed for each user.', 'wordfence'),
			'While in test mode, reCAPTCHA will score login and registration requests but not actually block them. The scores will be recorded and can be used to select a human/bot threshold value.' => __('While in test mode, reCAPTCHA will score login and registration requests but not actually block them. The scores will be recorded and can be used to select a human/bot threshold value.', 'wordfence'),
			'Wordfence Login Security Installed' => __('Wordfence Login Security Installed', 'wordfence'),
			'You have just installed the Wordfence Login Security plugin. It contains a subset of the functionality found in the full Wordfence plugin: Two-factor Authentication, XML-RPC Protection and Login Page CAPTCHA.' => __('You have just installed the Wordfence Login Security plugin. It contains a subset of the functionality found in the full Wordfence plugin: Two-factor Authentication, XML-RPC Protection and Login Page CAPTCHA.', 'wordfence'),
			'If you\'re looking for a more comprehensive solution, the <a href="https://wordpress.org/plugins/wordfence/" target="_blank" rel="noopener noreferrer">full Wordfence plugin</a> includes all of the features in this plugin as well as a full-featured WordPress firewall, a security scanner, live traffic, and more. The standard installation includes a robust set of free features that can be upgraded via a Premium license key.' => __('If you\'re looking for a more comprehensive solution, the <a href="https://wordpress.org/plugins/wordfence/" target="_blank" rel="noopener noreferrer">full Wordfence plugin</a> includes all of the features in this plugin as well as a full-featured WordPress firewall, a security scanner, live traffic, and more. The standard installation includes a robust set of free features that can be upgraded via a Premium license key.', 'wordfence'),
			'Your IP with this setting' => __('Your IP with this setting', 'wordfence'),
			'WooCommerce & Custom Integrations' => __('WooCommerce & Custom Integrations', 'wordfence'),
			'WooCommerce integration' => __('WooCommerce integration', 'wordfence'),
		);
	}
	
	/**
	 * Returns an array of constants/initial state values for use on the Javascript frontend to avoid hardcoding values.
	 *
	 * @param bool $includeUserSummary Whether to include the settings-page user summary data.
	 * @return array
	 */
	public static function jsConstants($includeUserSummary = false) {
		if (!Controller_Permissions::shared()->can_manage_settings()) {
			return array();
		}

		$currentUser = wp_get_current_user();
		$currentUserPasskeyRoleOptionNames = array();
		if ($currentUser instanceof \WP_User) {
			if (is_multisite()) {
				if (is_super_admin($currentUser->ID)) {
					$currentUserPasskeyRoleOptionNames[] = 'passkey-enabled-roles.super-admin';
				}
			}
			else if (isset($currentUser->roles) && is_array($currentUser->roles)) {
				foreach ($currentUser->roles as $role) {
					$currentUserPasskeyRoleOptionNames[] = 'passkey-enabled-roles.' . $role;
				}
			}
		}

		$response = array();
		
		$response['plugin'] = array(
			'current_user' => array(
				'passkey_role_option_names' => $currentUserPasskeyRoleOptionNames,
			),
			'ip' => array(
				'current' => Model_Request::current()->ip(),
				'preview' => Model_Request::current()->detected_ip_preview(),
			),
			'ls_from_core' => defined('WORDFENCE_LS_FROM_CORE') && WORDFENCE_LS_FROM_CORE,
			'ntp' => array(
				'constant_disabled' => Controller_Settings::shared()->is_ntp_disabled_via_constant(),
				'cron_disabled' => Controller_Settings::shared()->is_ntp_cron_disabled($failureCount),
				'cron_failure_count' => $failureCount,
				'max_failures' => Controller_Time::FAILURE_LIMIT,
			),
			'should_use_core_font_awesome' => Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles(),
			'server' => array(
				'has_woocommerce' => class_exists('woocommerce'),
				'has_active_passkeys' => Controller_Passkey::shared()->any_passkeys_active(),
				'is_multisite' => is_multisite(),
				'default_passkey_rp' => Controller_Passkey::shared()->defaultRP(),
			),
		);
		
		$response['roles'] = array(
			'labels' => array(
				Controller_Settings::STATE_2FA_DISABLED => __('Disabled', 'wordfence'),
				Controller_Settings::STATE_2FA_OPTIONAL => __('Optional', 'wordfence'),
				Controller_Settings::STATE_2FA_REQUIRED => __('Required', 'wordfence'),
				
				Controller_Settings::STATE_PASSKEY_DISABLED => __('Disabled', 'wordfence'),
				Controller_Settings::STATE_PASSKEY_OPTIONAL => __('Optional', 'wordfence'),
				Controller_Settings::STATE_PASSKEY_REQUIRED => __('Required', 'wordfence'),
			),
			'states' => array(
				'disabled' => Controller_Settings::STATE_2FA_DISABLED,
				'optional' => Controller_Settings::STATE_2FA_OPTIONAL,
				'required' => Controller_Settings::STATE_2FA_REQUIRED,
				
				'passkey_disabled' => Controller_Settings::STATE_PASSKEY_DISABLED,
				'passkey_optional' => Controller_Settings::STATE_PASSKEY_OPTIONAL,
				'passkey_required' => Controller_Settings::STATE_PASSKEY_REQUIRED,
			),
		);
		
		$response['support'] = array(
			'url' => Controller_Support::supportURLs(),
		);
		
		$roles = new \WP_Roles();
		$roleOptions = array();
		$passkeyRoleOptions = array();
		if (is_multisite()) {
			$roleOptions[] = array(
				'role' => 'super-admin',
				'name' => 'enabled-roles.super-admin',
				'title' => __('Super Administrator', 'wordfence'),
				'editable' => true,
				'allow_disabling' => false,
				'state' => Controller_Settings::shared()->get_required_2fa_role_activation_time('super-admin') !== false ? Controller_Settings::STATE_2FA_REQUIRED : Controller_Settings::STATE_2FA_OPTIONAL,
			);
			
			$passkeyRoleOptions[] = array(
				'role' => 'super-admin',
				'name' => 'passkey-enabled-roles.super-admin',
				'title' => __('Super Administrator', 'wordfence'),
				'editable' => true,
				'allow_disabling' => false,
				'state' => Controller_Settings::shared()->get_required_passkey_role_activation_time('super-admin') !== false ? Controller_Settings::STATE_PASSKEY_REQUIRED : Controller_Settings::STATE_PASSKEY_OPTIONAL,
			);
		}
		
		foreach ($roles->role_objects as $name => $r) {
			/** @var \WP_Role $r */
			$roleOptions[] = array(
				'role' => $name,
				'name' => 'enabled-roles.' . $name,
				'title' => $roles->role_names[$name],
				'editable' => true,
				'allow_disabling' => true,
				'state' => Controller_Settings::shared()->get_required_2fa_role_activation_time($name) !== false ? Controller_Settings::STATE_2FA_REQUIRED : ($r->has_cap(Controller_Permissions::CAP_ACTIVATE_2FA_SELF) ? Controller_Settings::STATE_2FA_OPTIONAL : Controller_Settings::STATE_2FA_DISABLED)
			);
			
			if (!is_multisite()) {
				$passkeyRoleOptions[] = array(
					'role' => $name,
					'name' => 'passkey-enabled-roles.' . $name,
					'title' => $roles->role_names[$name],
					'editable' => true,
					'allow_disabling' => true,
					'state' => Controller_Settings::shared()->get_required_passkey_role_activation_time($name) !== false ? Controller_Settings::STATE_PASSKEY_REQUIRED : ($r->has_cap(Controller_Permissions::CAP_MANAGE_PASSKEY_SELF) ? Controller_Settings::STATE_PASSKEY_OPTIONAL : Controller_Settings::STATE_PASSKEY_DISABLED)
				);
			}
		}

		if ($includeUserSummary) {
			$userSummaryData = Controller_Users::shared()->get_user_summary_data();
			$counts = $userSummaryData['counts'];
			$twoFactorEnabled = $userSummaryData['2fa_enabled'];
			$passkeysEnabled = $userSummaryData['passkeys_enabled'];
			$roleCounts = array();
			if (is_array($counts)) {
				$roleNames = $roles->get_names();
				$roleNames['super-admin'] = __('Super Administrator', 'wordfence');
				$roleNames[Controller_Users::TRUNCATED_ROLE_KEY] = __('Custom Capabilities / Multiple Roles', 'wordfence');
				foreach ($counts['avail_roles'] as $roleTag => $count) {
					$count = (int) $count;
					if ($count === 0) {
						continue;
					}
					$activeCount = $twoFactorEnabled && isset($counts['active_avail_roles'][$roleTag]) ? (int) $counts['active_avail_roles'][$roleTag] : 0;
					$inactiveCount = max($count - $activeCount, 0);
					$passkeyActiveCount = $passkeysEnabled && isset($counts['passkey_active_avail_roles'][$roleTag]) ? (int) $counts['passkey_active_avail_roles'][$roleTag] : 0;
					$roleName = isset($roleNames[$roleTag]) ? $roleNames[$roleTag] : $roleTag;
					$viewUsersURL = null;
					if ($inactiveCount > 0 && Controller_Settings::shared()->get_required_2fa_role_activation_time($roleTag) !== false) {
						$viewUsersBaseURL = 'admin.php?' . http_build_query(array('page' => 'WFLS', 'role' => $roleTag));
						$viewUsersURL = is_multisite() ? network_admin_url($viewUsersBaseURL) : admin_url($viewUsersBaseURL);
					}
					$roleCounts[] = array(
						'role' => $roleTag,
						'title' => translate_user_role($roleName),
						'total_users' => number_format_i18n($count),
						'active_2fa_users' => number_format_i18n($activeCount),
						'inactive_2fa_users' => number_format_i18n($inactiveCount),
						'active_passkey_users' => number_format_i18n($passkeyActiveCount),
						'inactive_passkey_users' => number_format_i18n(max($count - $passkeyActiveCount, 0)),
						'view_inactive_2fa_users_url' => $viewUsersURL,
					);
				}
			}

			$totalUsers = is_array($counts) ? (int) $counts['total_users'] : 0;
			$active2FAUsers = (int) $userSummaryData['active_2fa_users'];
			$activePasskeyUsers = (int) $userSummaryData['active_passkey_users'];
			$response['user_summary'] = array(
				'counts_available' => is_array($counts),
				'counts_deferred' => $counts === null,
				'force_counts' => Controller_Users::shared()->should_force_user_counts(),
				'is_multisite' => is_multisite(),
				'count_action_url' => add_query_arg('wfls-show-user-counts', 'true') . '#top#settings',
				'roles' => $roleCounts,
				'totals' => array(
					'total_users' => number_format_i18n($totalUsers),
					'active_2fa_users' => number_format_i18n($active2FAUsers),
					'inactive_2fa_users' => number_format_i18n(max($totalUsers - $active2FAUsers, 0)),
					'active_passkey_users' => number_format_i18n($activePasskeyUsers),
					'inactive_passkey_users' => number_format_i18n(max($totalUsers - $activePasskeyUsers, 0)),
				),
			);
		}

		$response['options'] = array(
			'roles' => $roleOptions,
			'passkey_roles' => $passkeyRoleOptions,
			'passkey_sign_count_modes' => array(
				array('value' => Controller_Settings::PASSKEY_SIGN_COUNT_ALLOW, 'label' => __('Allow any counter value', 'wordfence')),
				array('value' => Controller_Settings::PASSKEY_SIGN_COUNT_REJECT_LOWER, 'label' => __('Reject non-zero counters that do not increase (recommended)', 'wordfence')),
				array('value' => Controller_Settings::PASSKEY_SIGN_COUNT_REJECT_LOWER_AND_ZERO, 'label' => __('Reject counters that do not increase or reset to zero', 'wordfence')),
			),
			'ip_source' => array(
				array('value' => Model_Request::IP_SOURCE_AUTOMATIC, 'label' => __('Use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites.', 'wordfence'), 'recommended' => true),
				array('value' => Model_Request::IP_SOURCE_REMOTE_ADDR, 'label' => __('Use PHP\'s built in REMOTE_ADDR and don\'t use anything else. Very secure if this is compatible with your site.', 'wordfence')),
				array('value' => Model_Request::IP_SOURCE_X_FORWARDED_FOR, 'label' => __('Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence')),
				array('value' => Model_Request::IP_SOURCE_X_REAL_IP, 'label' => __('Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence')),
			),
			'value' => self::_prefixOptions(Controller_Settings::shared()->all()),
		);
		
		return $response;
	}
	
	/**
	 * Prefixes all keys in the given options with "wfls-" to avoid name collisions with the main plugin.
	 */
	private static function _prefixOptions($options) {
		$result = array();
		foreach ($options as $key => $value) {
			$result['wfls-' . $key] = $value;
		}
		return $result;
	}
	
	/**
	 * Returns the importmap array for our bundled modules.
	 *
	 * @return array
	 */
	public static function importMap() {
		return array('imports' => array(
			'vue' => Model_Asset::js('vue.esm-browser.prod.js'),
		));
	}
}
