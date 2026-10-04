<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$assets = isset($assets) ? $assets : array();
$scriptData = isset($scriptData) ? $scriptData : array();
?>
<?php if (!empty($scriptData)): ?>
	<script type="text/javascript">
	<?php foreach ($scriptData as $key => $data): ?>
		var <?php echo $key ?> = <?php echo wp_json_encode($data); ?>;
	<?php endforeach ?>
	</script>
<?php endif ?>
<?php foreach ($assets as $asset): ?>
	<?php $asset->renderInlineIfNotEnqueued(); ?>
<?php endforeach ?>
<?php
echo \WordfenceLS\Model_View::create('passkey/manage', array(
	'user' => $user,
	'stacked' => $stacked,
	'passkeys' => $passkeys,
	'canRegisterPasskeys' => isset($canRegisterPasskeys) ? $canRegisterPasskeys : true,
	'passkeysEnabledForUser' => isset($passkeysEnabledForUser) ? $passkeysEnabledForUser : true,
	'settingsURL' => isset($settingsURL) ? $settingsURL : (is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings')),
	'showSettingsButton' => isset($showSettingsButton) ? $showSettingsButton : true,
	'initialAllowedHostnames' => isset($initialAllowedHostnames) ? $initialAllowedHostnames : array(),
))->render();
?>
