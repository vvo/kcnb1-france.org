<?php

namespace WordfenceLS;

use RuntimeException;

class Controller_DB {
	const TABLE_2FA_SECRETS = 'wfls_2fa_secrets';
	const TABLE_PASSKEYS = 'wfls_passkeys';
	const TABLE_SETTINGS = 'wfls_settings';
	const TABLE_ROLE_COUNTS = 'wfls_role_counts';
	const TABLE_ROLE_COUNTS_TEMPORARY = 'wfls_role_counts_temporary';

	const SCHEMA_VERSION = 4;
	const PASSKEY_SCHEMA_VERSION = 3; //Schema version that introduced passkey support, not a separate schema version.
	
	/**
	 * Returns the singleton Controller_DB.
	 *
	 * @return Controller_DB
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_DB();
		}
		return $_shared;
	}
	
	/**
	 * Returns the table prefix for the main site on multisites and the site itself on single site installations.
	 *
	 * @return string
	 */
	public static function network_prefix() {
		global $wpdb;
		return $wpdb->base_prefix;
	}
	
	/**
	 * Returns the table with the site (single site installations) or network (multisite) prefix added.
	 *
	 * @param string $table
	 * @return string
	 */
	public static function network_table($table) {
		return self::network_prefix() . $table;
	}
	
	public function __get($key) {
		switch ($key) {
			case 'secrets':
				return self::network_table(self::TABLE_2FA_SECRETS);
			case 'passkeys':
				return self::network_table(self::TABLE_PASSKEYS);
			case 'settings':
				return self::network_table(self::TABLE_SETTINGS);
			case 'role_counts':
				return self::network_table(self::TABLE_ROLE_COUNTS);
			case 'role_counts_temporary':
				return self::network_table(self::TABLE_ROLE_COUNTS_TEMPORARY);
		}
		
		throw new \OutOfBoundsException('Unknown key: ' . $key);
	}
	
	/**
	 * Installs or upgrades the database schema.
	 *
	 * @return int Schema version found before migrations were applied, or zero for a new installation.
	 */
	public function install() {
		return $this->migrate_to_schema_version(self::SCHEMA_VERSION);
	}
	
	public function uninstall() {
		$tables = array(self::TABLE_2FA_SECRETS, self::TABLE_PASSKEYS, self::TABLE_SETTINGS, self::TABLE_ROLE_COUNTS);
		foreach ($tables as $table) {
			global $wpdb;
			$wpdb->query('DROP TABLE IF EXISTS `' . self::network_table($table) . '`');
		}
	}

	private function create_table($name, $definition, $temporary = false) {
		global $wpdb;
		if (is_array($definition)) {
			foreach ($definition as $attempt) {
				if ($this->create_table($name, $attempt, $temporary))
					return true;
			}
			return false;
		}
		else {
			return $wpdb->query('CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS `' . self::network_table($name) . '` ' . $definition);
		}
	}

	private function create_temporary_table($name, $definition) {
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES))
			return false;
		if ($this->create_table($name, $definition, true))
			return true;
		Controller_Settings::shared()->set(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES, true);
		return false;
	}
	
	/**
	 * Returns the schemas for the required tables.
	 *
	 * NOTE: It is important not to modify the schema of any existing table here. Instead, add a migration to the
	 * corresponding migration function for the schema version.
	 *
	 * @return array
	 */
	private function get_schema_table_definitions() {
		return array(
			self::TABLE_2FA_SECRETS => '(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `secret` tinyblob NOT NULL,
  `recovery` blob NOT NULL,
  `ctime` int(10) unsigned NOT NULL,
  `vtime` int(10) unsigned NOT NULL,
  `mode` enum(\'authenticator\') NOT NULL DEFAULT \'authenticator\',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
			self::TABLE_PASSKEYS => '(
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `credential_id` varbinary(1023) NOT NULL,
  `credential_id_hash` binary(32) NOT NULL,
  `public_key` blob NOT NULL,
  `sign_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `transports` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `user_handle` varbinary(255) DEFAULT NULL,
  `ctime` int(10) unsigned NOT NULL,
  `mtime` int(10) unsigned NOT NULL,
  `last_used_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id_hash` (`credential_id_hash`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
			self::TABLE_SETTINGS => '(
  `name` varchar(191) NOT NULL DEFAULT \'\',
  `value` longblob,
  `autoload` enum(\'no\',\'yes\') NOT NULL DEFAULT \'yes\',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
			self::TABLE_ROLE_COUNTS => $this->get_role_counts_table_definition_options()
		);
	}
	
	private function get_role_counts_table_definition($engine = null) {
		$engineClause = $engine === null ? '' : "ENGINE={$engine}";
		return <<<SQL
				(
				serialized_roles VARBINARY(255) NOT NULL,
				two_factor_inactive TINYINT(1) NOT NULL,
				passkey_inactive TINYINT(1) NOT NULL,
				user_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (serialized_roles, two_factor_inactive, passkey_inactive)
				) {$engineClause};
SQL;
	}
	
	private function get_role_counts_table_definition_options() {
		return array(
			$this->get_role_counts_table_definition('MEMORY'),
			$this->get_role_counts_table_definition('MyISAM'),
			$this->get_role_counts_table_definition()
		);
	}

	protected function _create_schema() {
		foreach ($this->get_schema_table_definitions() as $table => $def) {
			if (!$this->create_table($table, $def)) {
				throw new RuntimeException("Failed to create table schema for {$table}");
			}
		}
	}

	public function require_schema_version($version) {
		$target = min(max((int) $version, 0), self::SCHEMA_VERSION);
		if ($target > 0 && $this->get_schema_version() < $target) {
			$this->migrate_to_schema_version($target);
		}
	}

	private function migrate_to_schema_version($targetVersion) {
		$this->_create_schema();
		$currentVersion = $this->get_schema_version();
		if ($currentVersion >= $targetVersion) {
			if ($targetVersion > 0) {
				$this->normalize_schema_data();
			}
			return $currentVersion;
		}

		for ($nextVersion = $currentVersion + 1; $nextVersion <= $targetVersion; $nextVersion++) {
			$this->run_schema_migration($nextVersion);
			Controller_Settings::shared()->set(Controller_Settings::OPTION_SCHEMA_VERSION, $nextVersion);
		}

		$this->normalize_schema_data();
		return $currentVersion;
	}

	private function get_schema_version() {
		$currentVersion = Controller_Settings::shared()->get_int(Controller_Settings::OPTION_SCHEMA_VERSION);
		return min(max($currentVersion, 0), self::SCHEMA_VERSION);
	}

	private function run_schema_migration($targetVersion) {
		$method = 'migrate_schema_to_version_' . (int) $targetVersion;
		if (!method_exists($this, $method)) {
			throw new RuntimeException("No schema migration is defined for version {$targetVersion}");
		}
		$this->{$method}();
	}

	private function migrate_schema_to_version_1() {
		//Nothing
	}

	private function migrate_schema_to_version_2() {
		//Nothing
	}

	private function migrate_schema_to_version_3() {
		// This table is a derived cache of role counts, so it is safer to rebuild it than to mutate
		// arbitrary historical column types and index definitions in place.
		$table = self::TABLE_ROLE_COUNTS;
		$this->query('DROP TABLE IF EXISTS `' . self::network_table($table) . '`');
		if (!$this->create_table($table, $this->get_role_counts_table_definition_options())) {
			throw new RuntimeException("Failed to recreate table schema for {$table}");
		}
	}

	/**
	 * Initializes the global authentication feature switches from the existing role configuration.
	 *
	 * @return void
	 */
	private function migrate_schema_to_version_4() {
		$roles = function_exists('wp_roles') ? wp_roles() : (class_exists('WP_Roles') ? new \WP_Roles() : null);
		$enable2FA = is_multisite();
		$enablePasskeys = is_multisite();
		$roleObjects = is_object($roles) && isset($roles->role_objects) && is_array($roles->role_objects) ? $roles->role_objects : array();
		foreach ($roleObjects as $name => $role) {
			if ($role->has_cap(Controller_Permissions::CAP_ACTIVATE_2FA_SELF) || Controller_Settings::shared()->get_required_2fa_role_activation_time($name) !== false) {
				$enable2FA = true;
			}
			if (!is_multisite() && ($role->has_cap(Controller_Permissions::CAP_MANAGE_PASSKEY_SELF) || Controller_Settings::shared()->get_required_passkey_role_activation_time($name) !== false)) {
				$enablePasskeys = true;
			}
		}
		$changes = array();
		if (Controller_Settings::shared()->get(Controller_Settings::OPTION_ENABLE_2FA, null) === null) {
			$changes[Controller_Settings::OPTION_ENABLE_2FA] = $enable2FA;
		}
		if (Controller_Settings::shared()->get(Controller_Settings::OPTION_ENABLE_PASSKEYS, null) === null) {
			$changes[Controller_Settings::OPTION_ENABLE_PASSKEYS] = $enablePasskeys;
		}
		if (!empty($changes)) {
			Controller_Settings::shared()->set_multiple($changes, true);
		}
	}

	private function normalize_schema_data() {
		global $wpdb;
		$table = $this->secrets;
		$wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `vtime` = LEAST(`vtime`, %d)", Controller_Time::time()));
	}

	private function column_exists($table, $column) {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column)) === $column;
	}

	private function get_primary_key_columns($table) {
		global $wpdb;
		$rows = $wpdb->get_results("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'", ARRAY_A);
		if (!is_array($rows) || empty($rows)) {
			return array();
		}

		usort($rows, function($a, $b) {
			return (int) $a['Seq_in_index'] - (int) $b['Seq_in_index'];
		});

		return array_values(array_map(function($row) {
			return $row['Column_name'];
		}, $rows));
	}

	private function create_temporary_table_like($name, $sourceTable) {
		global $wpdb;
		$table = self::network_table($name);
		$wpdb->query("DROP TEMPORARY TABLE IF EXISTS `{$table}`");
		return $wpdb->query("CREATE TEMPORARY TABLE `{$table}` LIKE `{$sourceTable}`");
	}

	private function try_set_table_engine($table, $engine) {
		global $wpdb;
		return $wpdb->query("ALTER TABLE `{$table}` ENGINE={$engine}") !== false;
	}

	public function query($query) {
		global $wpdb;
		if ($wpdb->query($query) === false)
			throw new RuntimeException("Failed to execute query: {$query}");
	}

	public function get_wpdb() {
		global $wpdb;
		return $wpdb;
	}

	public function create_temporary_role_counts_table() {
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES)) {
			return false;
		}
		
		$table = self::network_table(self::TABLE_ROLE_COUNTS_TEMPORARY);
		if (!$this->create_temporary_table_like(self::TABLE_ROLE_COUNTS_TEMPORARY, $this->role_counts)) {
			Controller_Settings::shared()->set(Controller_Settings::OPTION_DISABLE_TEMPORARY_TABLES, true);
			return false;
		}
		
		if (!$this->try_set_table_engine($table, 'MEMORY')) {
			$this->try_set_table_engine($table, 'MyISAM');
		}
		
		return true;
	}

}
