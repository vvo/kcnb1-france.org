<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
?>
<div id="wfls-settings" class="wfls-flex-row wfls-flex-row-wrappable wfls-flex-row-equal-heights">
	<!-- begin status content -->
	<div id="wfls-user-stats" class="wfls-flex-row wfls-flex-row-equal-heights wfls-flex-item-xs-100">
		<?php
			$usersController = \WordfenceLS\Controller_Users::shared();
			$userSummaryData = $usersController->get_user_summary_data();
			echo \WordfenceLS\Model_View::create('settings/user-stats', array(
				'counts' => $userSummaryData['counts'],
				'knownTotalUsers' => $userSummaryData['known_total_users'],
				'active2FAUsers' => $userSummaryData['active_2fa_users'],
				'activePasskeyUsers' => $userSummaryData['active_passkey_users'],
				'twoFactorEnabled' => $userSummaryData['2fa_enabled'],
				'passkeysEnabled' => $userSummaryData['passkeys_enabled'],
			))->render();
		?>
	</div>
	<!-- end status content -->
	<!-- begin options content -->
	<div id="wfls-options" class="wordfence-vue-wrapper" data-base-component="WFLSOptions"></div>
	<!-- end options content -->
</div>
