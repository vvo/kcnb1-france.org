<?php

namespace WordfenceLS;

/**
 * Registers Wordfence Login Security WP-CLI namespaces and commands.
 */
class Controller_CLI {
	/**
	 * Returns the singleton Controller_CLI.
	 *
	 * @return Controller_CLI
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_CLI();
		}
		return $_shared;
	}

	/**
	 * Registers WP-CLI commands when WP-CLI is available.
	 *
	 * @return void
	 */
	public function init() {
		if (!defined('WP_CLI') || !WP_CLI || !class_exists('\WP_CLI')) {
			return;
		}

		if (class_exists('\WP_CLI\Dispatcher\CommandNamespace')) {
			$this->add_namespace_command('wordfence', 'WordfenceLS\Controller_CLI_Wordfence_Namespace', 'Manage settings and functionality within Wordfence.');
			$this->add_namespace_command('wordfence login-security', 'WordfenceLS\Controller_CLI_Login_Security_Namespace', 'Manage login security settings, permissions, and secondary authentication credentials.');
		}

		$this->add_command('wordfence login-security passkeys', new Controller_CLI_Passkeys(), array(
			'shortdesc' => 'Manage passkeys for users.',
		));
		$this->add_command('wordfence login-security passkey-roles', new Controller_CLI_Passkey_Roles(), array(
			'shortdesc' => 'Manage role-based passkey availability and requirements.',
		));
		$this->add_command('wordfence login-security grace-period', new Controller_CLI_Grace_Period(), array(
			'shortdesc' => 'Manage user grace periods for required login security.',
		));
	}

	/**
	 * Adds a WP-CLI command when its parent namespace can accept subcommands.
	 *
	 * @param string $path The WP-CLI command path.
	 * @param mixed $command The command callback, object, or class name.
	 * @param array $args Additional WP-CLI command registration arguments.
	 * @return void
	 */
	private function add_command($path, $command, $args = array()) {
		if (!$this->can_register_command_path($path)) {
			return;
		}

		\WP_CLI::add_command($path, $command, $args);
	}

	/**
	 * Adds a metadata-only WP-CLI namespace without replacing concrete commands.
	 *
	 * @param string $path The WP-CLI namespace path.
	 * @param string $class The CommandNamespace class name.
	 * @param string $shortdesc The namespace short description.
	 * @return void
	 */
	private function add_namespace_command($path, $class, $shortdesc) {
		if (!$this->can_register_command_path($path)) {
			return;
		}

		$existingCommand = $this->get_registered_command($path);
		if ($existingCommand !== null && !($existingCommand instanceof \WP_CLI\Dispatcher\CommandNamespace)) {
			return;
		}

		\WP_CLI::add_command($path, $class, array(
			'shortdesc' => $shortdesc,
		));
	}

	/**
	 * Returns whether a command path's existing parents can have subcommands.
	 *
	 * @param string $path The WP-CLI command path.
	 * @return bool
	 */
	private function can_register_command_path($path) {
		if (!method_exists('\WP_CLI', 'get_root_command')) {
			return true;
		}

		$command = \WP_CLI::get_root_command();
		$parts = preg_split('/\s+/', trim((string) $path));
		if (!is_array($parts)) {
			return true;
		}
		array_pop($parts);

		foreach ($parts as $part) {
			if ($part === '') {
				continue;
			}
			if (!is_object($command) || !method_exists($command, 'get_subcommands')) {
				return false;
			}
			$subcommands = $command->get_subcommands();
			if (!is_array($subcommands) || !isset($subcommands[$part])) {
				return true;
			}
			$command = $subcommands[$part];
			if (method_exists($command, 'can_have_subcommands') && !$command->can_have_subcommands()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets an already registered WP-CLI command for a path.
	 *
	 * @param string $path The WP-CLI command path.
	 * @return object|null
	 */
	private function get_registered_command($path) {
		if (!method_exists('\WP_CLI', 'get_root_command')) {
			return null;
		}

		$command = \WP_CLI::get_root_command();
		$parts = preg_split('/\s+/', trim((string) $path));
		if (!is_array($parts)) {
			return null;
		}

		foreach ($parts as $part) {
			if ($part === '') {
				continue;
			}
			if (!is_object($command) || !method_exists($command, 'get_subcommands')) {
				return null;
			}
			$subcommands = $command->get_subcommands();
			if (!is_array($subcommands) || !isset($subcommands[$part])) {
				return null;
			}
			$command = $subcommands[$part];
		}

		return is_object($command) ? $command : null;
	}
}

if (class_exists('\WP_CLI\Dispatcher\CommandNamespace')) {
	/**
	 * Manage settings and functionality within Wordfence
	 */
	class Controller_CLI_Wordfence_Namespace extends \WP_CLI\Dispatcher\CommandNamespace {
	}

	/**
	 * Manage login security settings, permissions, and secondary authentication credentials.
	 */
	class Controller_CLI_Login_Security_Namespace extends \WP_CLI\Dispatcher\CommandNamespace {
	}
}

/**
 * Provides shared helpers for Wordfence Login Security WP-CLI commands.
 */
abstract class Controller_CLI_Command {
	/**
	 * Resolves a user by ID, login, or email address.
	 *
	 * @param string|int $identifier User ID, login, or email address.
	 * @return \WP_User
	 */
	protected function get_user($identifier) {
		$identifier = (string) $identifier;
		$user = false;

		if (ctype_digit($identifier)) {
			$user = get_user_by('id', (int) $identifier);
		}
		if (!$user) {
			$user = get_user_by('login', $identifier);
		}
		if (!$user && is_email($identifier)) {
			$user = get_user_by('email', $identifier);
		}

		if (!($user instanceof \WP_User) || !$user->exists()) {
			\WP_CLI::error(sprintf('User not found: %s', $identifier));
		}

		return $user;
	}

	/**
	 * Formats a user for CLI output.
	 *
	 * @param \WP_User $user The user.
	 * @return string
	 */
	protected function user_label($user) {
		return sprintf('%s (ID %d)', $user->user_login, (int) $user->ID);
	}

	/**
	 * Parses a CLI integer value after strict range validation.
	 *
	 * @param mixed $value The value to parse.
	 * @param int $min The minimum allowed value.
	 * @param int|null $max The maximum allowed value, if any.
	 * @param string $message The error message to use for invalid values.
	 * @return int
	 */
	protected function parse_integer($value, $min, $max, $message) {
		if (!Utility_Number::isInteger($value, $min, $max)) {
			\WP_CLI::error($message);
		}

		return (int) $value;
	}

	/**
	 * Parses a CLI enabled/disabled state value.
	 *
	 * @param string $value The state value.
	 * @return bool
	 */
	protected function parse_enabled_state($value) {
		$value = strtolower(trim((string) $value));
		if (in_array($value, array('1', 'true', 'yes', 'on', 'enable', 'enabled'), true)) {
			return true;
		}
		if (in_array($value, array('0', 'false', 'no', 'off', 'disable', 'disabled'), true)) {
			return false;
		}

		\WP_CLI::error('State must be one of: enabled, disabled.');
	}
}

/**
 * Manage passkeys for users.
 */
class Controller_CLI_Passkeys extends Controller_CLI_Command {
	/**
	 * Lists passkeys registered for a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, login, or email address.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security passkeys list 123
	 *     wp wordfence login-security passkeys list admin --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function list_($args, $assoc_args) {
		if (!isset($args[0])) {
			\WP_CLI::error('A user ID, login, or email address is required.');
		}

		$user = $this->get_user($args[0]);
		$items = array();

		foreach (Controller_Passkey::shared()->get_passkeys($user) as $passkey) {
			$items[] = $this->format_passkey($passkey);
		}

		$format = isset($assoc_args['format']) && is_string($assoc_args['format']) ? $assoc_args['format'] : 'table';
		if (empty($items) && $format === 'table') {
			\WP_CLI::line(sprintf('No passkeys are registered for %s.', $this->user_label($user)));
			return;
		}

		\WP_CLI\Utils\format_items($format, $items, array(
			'id',
			'label',
			'credential_id',
			'transports',
			'sign_count',
			'created',
			'last_used',
		));
	}

	/**
	 * Removes a passkey from a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, login, or email address.
	 *
	 * <passkey-id>
	 * : Passkey ID from the passkey list command.
	 *
	 * [--yes]
	 * : Do not prompt for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security passkeys remove 123 4 --yes
	 *     wp wordfence login-security passkeys remove admin 4
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function remove($args, $assoc_args) {
		if (!isset($args[0])) {
			\WP_CLI::error('A user ID, login, or email address is required.');
		}
		if (!isset($args[1])) {
			\WP_CLI::error('A passkey ID is required.');
		}

		$user = $this->get_user($args[0]);
		$passkeyID = $this->parse_integer($args[1], 1, null, 'A valid passkey ID is required.');

		$passkey = $this->get_passkey_by_id($user, $passkeyID);
		if (!$passkey) {
			\WP_CLI::error(sprintf('Passkey %d is not registered for %s.', $passkeyID, $this->user_label($user)));
		}

		if (!isset($assoc_args['yes'])) {
			\WP_CLI::confirm(sprintf('Remove passkey %d (%s) from %s?', $passkeyID, $this->passkey_label($passkey), $this->user_label($user)));
		}

		$result = Controller_Passkey::shared()->remove_passkey($user, $passkeyID);
		if (is_wp_error($result)) {
			\WP_CLI::error($result->get_error_message());
		}

		\WP_CLI::success(sprintf('Removed passkey %d from %s.', $passkeyID, $this->user_label($user)));
	}

	/**
	 * Changes whether username/password login is allowed while the user has passkeys.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, login, or email address.
	 *
	 * <state>
	 * : Password login state. Accepted values: enabled, disabled.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security passkeys password-login admin disabled
	 *     wp wordfence login-security passkeys password-login 123 enabled
	 *
	 * @subcommand password-login
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function password_login($args, $assoc_args) {
		if (!isset($args[0])) {
			\WP_CLI::error('A user ID, login, or email address is required.');
		}
		if (!isset($args[1])) {
			\WP_CLI::error('A password login state is required.');
		}

		$user = $this->get_user($args[0]);
		$enabled = $this->parse_enabled_state($args[1]);
		$passkeyController = Controller_Passkey::shared();
		if (!$passkeyController->can_change_username_password_auth($user)) {
			\WP_CLI::error('This option cannot be changed because passkeys are required for one or more of this user\'s roles.');
		}
		if (!$passkeyController->set_username_password_auth_enabled($user, $enabled)) {
			\WP_CLI::error('Unable to save the user-specific passkey options.');
		}
		$enabled = $passkeyController->is_username_password_auth_enabled($user);

		\WP_CLI::success(sprintf(
			'Username/password login is now %s for %s.',
			$enabled ? 'enabled' : 'disabled',
			$this->user_label($user)
		));
	}

	/**
	 * Finds a passkey belonging to a user by its internal database ID.
	 *
	 * @param \WP_User $user The user.
	 * @param int $passkeyID The passkey ID.
	 * @return array|null
	 */
	private function get_passkey_by_id($user, $passkeyID) {
		foreach (Controller_Passkey::shared()->get_passkeys($user) as $passkey) {
			if (isset($passkey['id']) && (int) $passkey['id'] === (int) $passkeyID) {
				return $passkey;
			}
		}
		return null;
	}

	/**
	 * Formats a stored passkey row for CLI output.
	 *
	 * @param array $passkey The stored passkey.
	 * @return array
	 */
	private function format_passkey($passkey) {
		return array(
			'id' => isset($passkey['id']) ? (int) $passkey['id'] : 0,
			'label' => $this->passkey_label($passkey),
			'credential_id' => isset($passkey['credential_id']) ? bin2hex($passkey['credential_id']) : '',
			'transports' => isset($passkey['transports']) ? (string) $passkey['transports'] : '',
			'sign_count' => isset($passkey['sign_count']) ? (int) $passkey['sign_count'] : 0,
			'created' => $this->format_time(isset($passkey['ctime']) ? $passkey['ctime'] : 0),
			'last_used' => $this->format_time(isset($passkey['last_used_at']) ? $passkey['last_used_at'] : 0),
		);
	}

	/**
	 * Gets the display label for a passkey.
	 *
	 * @param array $passkey The stored passkey.
	 * @return string
	 */
	private function passkey_label($passkey) {
		if (isset($passkey['label']) && is_string($passkey['label']) && $passkey['label'] !== '') {
			return $passkey['label'];
		}
		return 'Passkey';
	}

	/**
	 * Formats a Unix timestamp for CLI output.
	 *
	 * @param int|string $timestamp The timestamp.
	 * @return string
	 */
	private function format_time($timestamp) {
		$timestamp = (int) $timestamp;
		if ($timestamp <= 0) {
			return '';
		}
		return Controller_Time::format_local_time('Y-m-d H:i:s', $timestamp);
	}
}

/**
 * Manage role-based passkey availability and requirements.
 */
class Controller_CLI_Passkey_Roles extends Controller_CLI_Command {
	/**
	 * Lists passkey settings for roles.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security passkey-roles list
	 *     wp wordfence login-security passkey-roles list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function list_($args, $assoc_args) {
		$format = isset($assoc_args['format']) && is_string($assoc_args['format']) ? $assoc_args['format'] : 'table';
		\WP_CLI\Utils\format_items($format, $this->role_items(), array(
			'role',
			'name',
			'state',
			'required_since',
		));
	}

	/**
	 * Changes a role between passkey disabled, optional, and required.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Role slug. On multisite, only super-admin is supported.
	 *
	 * <state>
	 * : Passkey state. Accepted values: disabled, optional, required.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security passkey-roles set editor optional
	 *     wp wordfence login-security passkey-roles set administrator required
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function set($args, $assoc_args) {
		if (!isset($args[0])) {
			\WP_CLI::error('A role is required.');
		}
		if (!isset($args[1])) {
			\WP_CLI::error('A passkey role state is required.');
		}

		$role = (string) $args[0];
		$state = $this->parse_role_state($args[1]);
		$this->validate_role($role);
		if (is_multisite() && $role === 'super-admin' && $state === Controller_Settings::STATE_PASSKEY_DISABLED) {
			\WP_CLI::error('Super Administrator passkeys cannot be disabled on multisite. Use optional or required.');
		}

		$key = 'passkey-enabled-roles.' . $role;
		if (!Controller_Settings::shared()->set($key, $state)) {
			\WP_CLI::error(sprintf('Unable to set passkey state for role %s.', $role));
		}

		\WP_CLI::success(sprintf('Passkeys are now %s for role %s.', $this->state_label($state), $role));
	}

	/**
	 * Builds CLI output rows for passkey role settings.
	 *
	 * @return array
	 */
	private function role_items() {
		$wpRoles = new \WP_Roles();
		$items = array();
		if (is_multisite()) {
			$items[] = $this->role_item('super-admin', 'Super Administrator');
			return $items;
		}

		foreach ($wpRoles->role_objects as $name => $role) {
			$items[] = $this->role_item($name, isset($wpRoles->role_names[$name]) ? $wpRoles->role_names[$name] : $name, $role);
		}
		return $items;
	}

	/**
	 * Builds a CLI output row for a role's passkey setting.
	 *
	 * @param string $roleName The role slug.
	 * @param string $displayName The role display name.
	 * @param \WP_Role|null $role The role object.
	 * @return array
	 */
	private function role_item($roleName, $displayName, $role = null) {
		$requiredSince = Controller_Settings::shared()->get_required_passkey_role_activation_time($roleName);
		$state = Controller_Settings::STATE_PASSKEY_DISABLED;
		if ($requiredSince !== false) {
			$state = Controller_Settings::STATE_PASSKEY_REQUIRED;
		}
		else if ($roleName === 'super-admin' || ($role instanceof \WP_Role && $role->has_cap(Controller_Permissions::CAP_MANAGE_PASSKEY_SELF))) {
			$state = Controller_Settings::STATE_PASSKEY_OPTIONAL;
		}

		return array(
			'role' => $roleName,
			'name' => $displayName,
			'state' => $this->state_label($state),
			'required_since' => $requiredSince === false ? '' : Controller_Time::format_local_time('Y-m-d H:i:s', $requiredSince),
		);
	}

	/**
	 * Validates that a role can be managed by the passkey role command.
	 *
	 * @param string $role The role slug.
	 * @return void
	 */
	private function validate_role($role) {
		if (is_multisite()) {
			if ($role !== 'super-admin') {
				\WP_CLI::error('On multisite, passkey role settings are limited to super-admin.');
			}
			return;
		}

		$wpRoles = new \WP_Roles();
		if (!isset($wpRoles->role_objects[$role])) {
			\WP_CLI::error(sprintf('Role not found: %s', $role));
		}
	}

	/**
	 * Parses a CLI passkey role state value.
	 *
	 * @param string $state The state value.
	 * @return string
	 */
	private function parse_role_state($state) {
		$state = strtolower(str_replace('-', '_', trim((string) $state)));
		switch ($state) {
			case 'disabled':
			case 'passkey_disabled':
				return Controller_Settings::STATE_PASSKEY_DISABLED;
			case 'optional':
			case 'passkey_optional':
				return Controller_Settings::STATE_PASSKEY_OPTIONAL;
			case 'required':
			case 'passkey_required':
				return Controller_Settings::STATE_PASSKEY_REQUIRED;
		}

		\WP_CLI::error('State must be one of: disabled, optional, required.');
	}

	/**
	 * Formats a passkey role state for CLI output.
	 *
	 * @param string $state The stored passkey role state.
	 * @return string
	 */
	private function state_label($state) {
		switch ($state) {
			case Controller_Settings::STATE_PASSKEY_OPTIONAL:
				return 'optional';
			case Controller_Settings::STATE_PASSKEY_REQUIRED:
				return 'required';
			case Controller_Settings::STATE_PASSKEY_DISABLED:
			default:
				return 'disabled';
		}
	}
}

/**
 * Manage user grace periods for required login security.
 */
class Controller_CLI_Grace_Period extends Controller_CLI_Command {
	/**
	 * Resets the additional authentication grace period for a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, login, or email address.
	 *
	 * [--days=<days>]
	 * : Optional grace-period override in days. Must be between 0 and 99.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wordfence login-security grace-period reset admin
	 *     wp wordfence login-security grace-period reset 123 --days=14
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function reset($args, $assoc_args) {
		if (!isset($args[0])) {
			\WP_CLI::error('A user ID, login, or email address is required.');
		}

		$user = $this->get_user($args[0]);
		$override = null;
		if (array_key_exists('days', $assoc_args)) {
			$override = $this->parse_integer(
				$assoc_args['days'],
				0,
				Controller_Settings::MAX_REQUIRE_2FA_USER_GRACE_PERIOD,
				sprintf('Grace period override must be between 0 and %d days.', Controller_Settings::MAX_REQUIRE_2FA_USER_GRACE_PERIOD)
			);
		}

		Controller_Users::shared()->allow_grace_period($user->ID);
		if (!Controller_Users::shared()->reset_grace_period($user, $override)) {
			\WP_CLI::error('Failed to reset grace period. The user may not be missing required 2FA or passkey authentication.');
		}

		\WP_CLI::success(sprintf('Reset grace period for %s.', $this->user_label($user)));
	}
}
