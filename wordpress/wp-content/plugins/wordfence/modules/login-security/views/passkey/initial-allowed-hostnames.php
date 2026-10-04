<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string[] $initialAllowedHostnames The hostnames that will be saved as Allowed Passkey Hostnames. Optional.
 */

$initialAllowedHostnames = isset($initialAllowedHostnames) && is_array($initialAllowedHostnames) ? $initialAllowedHostnames : array();
if (empty($initialAllowedHostnames)) {
	return;
}
?>
<div class="wfls-notice wfls-add-top">
	<p><strong><?php esc_html_e('Allowed Passkey Hostnames has not been saved yet.', 'wordfence'); ?></strong></p>
	<p><?php esc_html_e('You may manually configure this setting on the Settings tab or continue adding a passkey to use the default values. Wordfence will save the following hostnames and any required ports for passkey login and registration:', 'wordfence'); ?></p>
	<ul>
		<?php foreach ($initialAllowedHostnames as $hostname): ?>
			<li><code class="wfls-passkey-hostname"><?php echo esc_html($hostname); ?></code></li>
		<?php endforeach; ?>
	</ul>
</div>
