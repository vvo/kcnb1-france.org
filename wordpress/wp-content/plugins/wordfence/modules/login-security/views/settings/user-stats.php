<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/** @var ?array|false $counts User counts, null when hidden, or false after a failed query. */
$countsAvailable = is_array($counts);
$knownTotalUsers = isset($knownTotalUsers) ? (int) $knownTotalUsers : 0;
$largeUserBase = $knownTotalUsers >= \WordfenceLS\Controller_Users::LARGE_USER_BASE_THRESHOLD;
$hasExactTotal = $countsAvailable || !$largeUserBase;
$totalUsers = $countsAvailable ? (int) $counts['total_users'] : ($hasExactTotal ? $knownTotalUsers : null);
$twoFactorEnabled = isset($twoFactorEnabled) ? (bool) $twoFactorEnabled : true;
$passkeysEnabled = isset($passkeysEnabled) ? (bool) $passkeysEnabled : true;
$active2FAUsers = $twoFactorEnabled ? ($countsAvailable ? (int) $counts['active_total_users'] : (isset($active2FAUsers) ? (int) $active2FAUsers : 0)) : 0;
$activePasskeyUsers = $passkeysEnabled ? ($countsAvailable ? (int) $counts['passkey_active_total_users'] : (isset($activePasskeyUsers) ? (int) $activePasskeyUsers : 0)) : 0;
$metrics = array(
	array(
		'label' => __('Total Users', 'wordfence'),
		'value' => $totalUsers === null ? number_format_i18n(\WordfenceLS\Controller_Users::LARGE_USER_BASE_THRESHOLD) . '+' : number_format_i18n($totalUsers),
		'icon' => 'users',
		'class' => 'users',
		'progress' => null,
	),
	array(
		'label' => __('2FA Active', 'wordfence'),
		'value' => $hasExactTotal ? '(' . number_format_i18n($active2FAUsers) . ' / ' . number_format_i18n($totalUsers) . ')' : number_format_i18n($active2FAUsers),
		'icon' => 'shield',
		'class' => 'active',
		'progress' => $hasExactTotal && $totalUsers > 0 ? min(100, max(0, (int) round(($active2FAUsers / $totalUsers) * 100))) : ($hasExactTotal ? 0 : null),
	),
	array(
		'label' => __('Passkey Active', 'wordfence'),
		'value' => $hasExactTotal ? '(' . number_format_i18n($activePasskeyUsers) . ' / ' . number_format_i18n($totalUsers) . ')' : number_format_i18n($activePasskeyUsers),
		'icon' => 'passkey',
		'class' => 'passkey-active',
		'progress' => $hasExactTotal && $totalUsers > 0 ? min(100, max(0, (int) round(($activePasskeyUsers / $totalUsers) * 100))) : ($hasExactTotal ? 0 : null),
	),
);
$manageUsersURL = is_multisite() ? network_admin_url('users.php') : admin_url('users.php');
?>
<section class="wfls-auth-summary wfls-flex-item-full-width">
	<header class="wfls-auth-summary-header wfls-settings-card-header">
		<div class="wfls-auth-summary-heading">
			<h3><?php esc_html_e('User Summary', 'wordfence'); ?></h3>
			<p><?php esc_html_e('Overview of authentication methods adoption across all users.', 'wordfence'); ?></p>
		</div>
		<a href="<?php echo esc_url($manageUsersURL); ?>" class="wfls-btn wfls-btn-sm wfls-btn-default wfls-auth-summary-manage"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('users')); ?>" aria-hidden="true"></i><span><?php esc_html_e('Manage Users', 'wordfence'); ?></span></a>
	</header>
	<div class="wfls-auth-summary-metrics">
		<?php foreach ($metrics as $metric): ?>
			<div class="wfls-auth-summary-metric wfls-auth-summary-metric-<?php echo esc_attr($metric['class']); ?>">
				<span class="wfls-auth-summary-icon" aria-hidden="true">
					<?php if ($metric['icon'] === 'passkey'): ?>
						<span class="wfls-main-icon-passkey"></span>
					<?php else: ?>
						<i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes($metric['icon'])); ?>"></i>
					<?php endif; ?>
				</span>
				<div class="wfls-auth-summary-metric-copy">
					<div class="wfls-auth-summary-metric-value">
						<strong><?php echo esc_html($metric['value']); ?></strong>
						<span><?php echo esc_html($metric['label']); ?></span>
					</div>
					<?php if ($metric['progress'] !== null): ?>
						<span class="wfls-auth-summary-progress" role="progressbar" aria-label="<?php echo esc_attr($metric['label']); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($metric['progress']); ?>">
							<span style="width: <?php echo esc_attr($metric['progress']); ?>%"></span>
						</span>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<div class="wordfence-vue-wrapper wfls-auth-summary-role-mount" data-base-component="WFLSUserSummaryRoleDisclosure" data-prop-title="<?php esc_attr_e('View by role', 'wordfence'); ?>"></div>
	</div>
</section>
