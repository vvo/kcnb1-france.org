<?php

class wfAdminNoticeQueue {
	const USERS_ALL = 'all';
	const VERSION_UPGRADE_NOTICE_CATEGORY_STEM = 'versionUpgradeNotice';

	protected static function _notices() {
		return self::_purgeObsoleteNotices(wfConfig::get_ser('adminNoticeQueue', array()));
	}

	private static function _purgeObsoleteNotices($notices) {
		$altered = false;
		$now = time();
		foreach ($notices as $id => $notice) {
			if (!empty($notice['category']) && $notice['category'] === 'php8') {
				unset($notices[$id]);
				$altered = true;
				continue;
			}
			if (!empty($notice['category']) && $notice['category'] === self::VERSION_UPGRADE_NOTICE_CATEGORY_STEM . '900') {
				if (empty($notice['displayOn']) || !is_array($notice['displayOn'])) {
					$notices[$id]['displayOn'] = array('dashboard', 'plugins', 'wordfence');
					$altered = true;
				}
				if (empty($notice['enqueuedAt'])) {
					$notices[$id]['enqueuedAt'] = empty($notice['expires']) ? $now : ((int) $notice['expires'] - (14 * DAY_IN_SECONDS));
					$altered = true;
				}
				if (empty($notice['expires'])) {
					$notices[$id]['expires'] = (int) $notices[$id]['enqueuedAt'] + (14 * DAY_IN_SECONDS);
					$altered = true;
				}
			}
			if (!empty($notices[$id]['expires']) && is_numeric($notices[$id]['expires']) && (int) $notices[$id]['expires'] <= $now) {
				unset($notices[$id]);
				$altered = true;
			}
		}
		if ($altered)
			self::_setNotices($notices);
		return $notices;
	}
	
	protected static function _setNotices($notices) {
		wfConfig::set_ser('adminNoticeQueue', $notices);
	}

	/*
	 * Version upgrade notices.
	 */
	public static function queueVersionUpgradeNotice($previous_version) {
		$previous_version = trim((string) $previous_version);
		if ($previous_version === '' || version_compare($previous_version, '0.0.0', '<=')) {
			return;
		}

		//9.0.0
		if (version_compare($previous_version, '9.0.0', '<')) {
			$category = self::VERSION_UPGRADE_NOTICE_CATEGORY_STEM . '900';
			if (!self::hasNotice($category, false)) {
				$messageHTML = self::versionUpgradeNoticeMessageHTML($previous_version, '9.0.0');
				if (!empty($messageHTML)) {
					$enqueuedAt = time();
					self::addAdminNotice(wfAdminNotice::SEVERITY_UPDATE, $messageHTML, $category, false, array(
						'displayOn' => array('dashboard', 'plugins', 'wordfence'),
						'enqueuedAt' => $enqueuedAt,
						'expires' => $enqueuedAt + (14 * DAY_IN_SECONDS),
					));
				}
			}
		}
	}

	private static function versionUpgradeNoticeMessageHTML($previous_version, $target_version) {
		if ($target_version === '9.0.0') {
			$wflsLink = wfUtils::maybeNetworkAdminURL('admin.php?page=WFLS#top#settings');
			$addPasskeyOnClick = 'wordfenceExt.dismissAdminNoticeAndFollowLink(this); return false;';
			return '<strong>' . wfI18n::esc_html__('Introducing Passkeys with Wordfence', 'wordfence') . '</strong> ' .
				wfI18n::esc_html__('Wordfence 9 brings support for passkeys for all users. A passkey is a password replacement that validates your identity using touch, facial recognition, a device password, or a PIN. They can be used for sign-in as a simple and secure alternative to a password and two-factor credentials. Passkeys can be enabled for administrators or any other role on the Login Security settings page.', 'wordfence') .
				'<br>' . '<a class="wf-btn wf-btn-primary wf-btn-sm wf-no-left wf-add-top" href="' . esc_url($wflsLink) . '" onclick="' . esc_attr($addPasskeyOnClick) . '">' . wfI18n::esc_html__('Manage Login Security Settings', 'wordfence') . '</a>';
		}
		return '';
	}

	/**
	 * Adds an admin notice to the display queue.
	 * 
	 * @param string $severity
	 * @param string $messageHTML
	 * @param bool|string $category If not false, notices with the same category will be removed prior to adding this one.
	 * @param bool|array $users If not false, an array of user IDs the notice should show for.
	 * @param array $options Additional notice metadata.
	 */
	public static function addAdminNotice($severity, $messageHTML, $category = false, $users = false, $options = array()) {
		$notices = self::_notices();
		foreach ($notices as $id => $n) {
			$usersMatches = false;
			if (isset($n['users'])) {
				$usersMatches = wfUtils::sets_equal($n['users'], $users);
			}
			else if ($users === false) {
				$usersMatches = true;
			}
			
			$categoryMatches = false;
			if ($category !== false && isset($n['category']) && $n['category'] == $category) {
				$categoryMatches = true;
			}
			
			if ($usersMatches && $categoryMatches) {
				unset($notices[$id]);
			}
		}
		
		$id = wfUtils::uuid();
		$notices[$id] = array(
			'severity' => $severity,
			'messageHTML' => $messageHTML,
		);
		
		if ($category !== false) {
			$notices[$id]['category'] = $category;
		}
		
		if ($users !== false) {
			$notices[$id]['users'] = $users;
		}

		foreach (array('displayOn', 'enqueuedAt', 'expires') as $optionKey) {
			if (isset($options[$optionKey])) {
				$notices[$id][$optionKey] = $options[$optionKey];
			}
		}
		
		self::_setNotices($notices);
	}

	private static function _noticeShouldDisplayOnCurrentPage($notice) {
		if (empty($notice['displayOn']) || !is_array($notice['displayOn'])) {
			return true;
		}

		foreach ($notice['displayOn'] as $location) {
			if (self::_currentPageMatchesNoticeLocation($location)) {
				return true;
			}
		}

		return false;
	}

	private static function _currentPageMatchesNoticeLocation($location) {
		global $pagenow;

		if (!is_admin() || (is_multisite() && !is_network_admin())) {
			return false;
		}

		$currentPage = isset($pagenow) ? $pagenow : '';
		if ($currentPage === '' && isset($_SERVER['REQUEST_URI'])) {
			$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			$currentPage = $requestPath === false ? '' : basename($requestPath);
		}

		if ($location === 'dashboard') {
			return $currentPage === 'index.php';
		}

		if ($location === 'plugins') {
			return $currentPage === 'plugins.php';
		}

		if ($location === 'wordfence') {
			return wordfence::isWordfenceAdminPage();
		}

		return false;
	}
	
	/**
	 * Removes an admin notice by ID. An admin may remove any notice where lower privileged users can only
	 * remove themselves from the notice.
	 *
	 * @param string $id
	 */
	public static function removeAdminNoticeForID($id) {
		$user = wp_get_current_user();
		if (!$user->exists()) {
			return;
		}
		
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if ($id == $nid) { //ID match
				$currentUserInUsers = !empty($n['users']) && in_array($user->ID, $n['users']);
				if (wfUtils::isAdmin($user)) {
					unset($notices[$nid]);
					$found = true;
				}
				else if ($currentUserInUsers) {
					$notices[$nid]['users'] = array_diff($n['users'], array($user->ID));
					if (empty($notices[$nid]['users'])) {
						unset($notices[$nid]);
					}
					$found = true;
				}
				break;
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Removes any admin notices matching $category that are global (i.e. not specific to a user).
	 *
	 * @param string $category
	 * @return void
	 */
	public static function removeGlobalAdminNoticeForCategory($category) {
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['category']) && $category == $n['category']) {
				if (empty($n['users'])) {
					unset($notices[$nid]);
					$found = true;
				}
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Removes any admin notices matching $category that are specific to the user with ID $userID.
	 *
	 * @param string $category
	 * @param null|int|string $userID `null` means the current user, `all` means all users, and an integer means a specific user
	 * @return void
	 */
	public static function removeAdminNoticeForCategory($category, $userID = null) {
		if ($userID === null) {
			$user = wp_get_current_user();
			if (!$user->exists()) { return; }
			$userID = $user->ID;
		}
		
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['category']) && $category == $n['category']) {
				if ($userID === 'all') {
					unset($notices[$nid]);
					$found = true;
				}
				else {
					$currentUserInUsers = !empty($n['users']) && in_array($userID, $n['users']);
					if ($currentUserInUsers) {
						$notices[$nid]['users'] = array_diff($n['users'], array($userID));
						if (empty($notices[$nid]['users'])) {
							unset($notices[$nid]);
						}
						$found = true;
					}
				}
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Returns whether at least one queued admin notice matches the provided filters.
	 *
	 * Matching behavior:
	 * - `$category === null` matches notices with no `category` field.
	 * - `$category === false` matches notices with any `category` field.
	 * - `$category === {string}` matches notices whose `category` equals `$category`.
	 * - `$users === null` matches notices with no `users` field (global notices).
	 * - `$users === false` matches notices with any `users` field
	 * - `$users === {array}` matches notices with a `users` field where the notice's
	 *   user IDs contain the IDs in `$users` (`wfUtils::is_subset($noticeUsers, $users)`).
	 *
	 * A notice is considered a match only when both category and user checks pass.
	 *
	 * @param string|null|false $category Category to match, `false` for any category, or `null` for uncategorized notices.
	 * @param int[]|null|false $users User IDs to match against, `false` for any user, or `null` for global notices.
	 * @return bool True if a matching notice exists; otherwise false.
	 */
	public static function hasNotice($category = null, $users = null) {
		$notices = self::_notices();
		foreach ($notices as $nid => $n) {
			$categoryMatches = false;
			if ($category === false || ($category === null && !isset($n['category'])) || ($category !== false && $category !== null && isset($n['category']) && $category == $n['category'])) {
				$categoryMatches = true;
			}
			
			$usersMatches = null;
			if ($users === false || ($users === null && !isset($n['users'])) || ($users !== false && $users !== null && isset($n['users']) && wfUtils::is_subset($n['users'], $users))) {
				$usersMatches = true;
			}
			
			if ($categoryMatches && $usersMatches) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns whether the provided user has any admin notices that will show.
	 *
	 * @param WP_User $user
	 * @return bool
	 */
	public static function hasAnyNotice($user) {
		if (!$user->exists()) {
			return false;
		}
		
		$notices = self::_notices();
		foreach ($notices as $nid => $n) {
			if ((wfUtils::isAdmin($user) && !isset($n['users'])) || (isset($n['users']) && wfUtils::is_subset($n['users'], array($user->ID)))) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Enqueues any admin notices that are applicable to the current user.
	 *
	 * @param bool $userSpecificOnly If true, only notices that are specific to the current user will be enqueued.
	 */
	public static function enqueueAdminNotices($userSpecificOnly = false) {
		$user = wp_get_current_user();
		if ($user->ID == 0) {
			return false;
		}
		
		$networkAdmin = is_multisite() && is_network_admin();
		$notices = self::_notices();
		$added = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['users'])) {
				if (!in_array($user->ID, $n['users'])) { continue; }
			}
			else {
				if ($userSpecificOnly) { continue; }
			}

			if (!self::_noticeShouldDisplayOnCurrentPage($n)) { continue; }
			
			$notice = new wfAdminNotice($nid, $n['severity'], $n['messageHTML']);
			if ($networkAdmin) {
				add_action('network_admin_notices', array($notice, 'displayNotice'));
			}
			else {
				add_action('admin_notices', array($notice, 'displayNotice'));
			}
			
			$added = true;
		}
		
		return $added;
	}
}

class wfAdminNotice {
	const SEVERITY_CRITICAL = 'critical';
	const SEVERITY_WARNING = 'warning';
	const SEVERITY_INFO = 'info';
	const SEVERITY_UPDATE = 'update';
	
	private $_id;
	private $_severity;
	private $_messageHTML;
	
	public function __construct($id, $severity, $messageHTML) {
		$this->_id = $id;
		$this->_severity = $severity;
		$this->_messageHTML = $messageHTML;
	}
	
	public function displayNotice() {
		$severityClass = 'notice-info';
		if ($this->_severity == self::SEVERITY_CRITICAL) {
			$severityClass = 'notice-error';
		}
		else if ($this->_severity == self::SEVERITY_WARNING) {
			$severityClass = 'notice-warning';
		}
		else if ($this->_severity == self::SEVERITY_UPDATE) {
			$severityClass = 'notice-update';
		}
		
		$dismissAction = 'wordfenceExt.dismissAdminNotice(\'' . esc_js($this->_id) . '\'); return false;';
		if ($this->_severity == self::SEVERITY_UPDATE) {
			echo '<div class="wf-admin-notice notice ' . $severityClass . '" data-notice-id="' . esc_attr($this->_id) . '"><a class="wf-admin-notice-dismiss wf-dismiss-link" href="#" onclick="' . esc_attr($dismissAction) . '" role="button"><span aria-hidden="true">&times;</span><span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'wordfence') . '</span></a><p class="wf-admin-notice-content">' . $this->_messageHTML . '</p></div>';
			return;
		}

		echo '<div class="wf-admin-notice notice ' . $severityClass . '" data-notice-id="' . esc_attr($this->_id) . '"><p>' . $this->_messageHTML . '</p><p><a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="' . esc_attr($dismissAction) . '" role="button">' . esc_html__('Dismiss', 'wordfence') . '</a></p></div>';
	}
}
