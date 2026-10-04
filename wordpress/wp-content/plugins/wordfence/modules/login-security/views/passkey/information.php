<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var bool $adminLayout Whether the panel participates in the full-page card layout. Optional.
 */
$adminLayout = isset($adminLayout) ? (bool) $adminLayout : false;
?>
<div class="wfls-passkey-information<?php if ($adminLayout): ?> wfls-passkey-information-admin<?php endif; ?>" role="note">
	<div class="wfls-passkey-information-intro">
		<span class="wfls-passkey-information-illustration" aria-hidden="true">
			<span class="wfls-passkey-information-stencil"></span>
		</span>
		<div class="wfls-passkey-information-copy">
			<h3 class="wfls-passkey-information-title"><?php esc_html_e('Passwordless Sign-in with Passkeys', 'wordfence'); ?></h3>
			<p><?php esc_html_e('Replace passwords with a faster, safer way to sign in.', 'wordfence'); ?></p>
			<a class="wfls-passkey-information-learn-more" href="<?php echo \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEYS); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn more about passkeys', 'wordfence'); ?> &rarr;</a>
		</div>
	</div>
	<ul class="wfls-passkey-information-list">
		<li>
			<i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('check')); ?> wfls-passkey-information-list-icon" aria-hidden="true"></i>
			<span><?php echo wp_kses(__('<strong>Phishing-resistant</strong> — passkeys only work with the site they were created for.', 'wordfence'), array('strong' => array())); ?></span>
		</li>
		<li>
			<i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('check')); ?> wfls-passkey-information-list-icon" aria-hidden="true"></i>
			<span><?php echo wp_kses(__('<strong>Fast and easy</strong> — sign in with your fingerprint, face, PIN, or device unlock.', 'wordfence'), array('strong' => array())); ?></span>
		</li>
		<li>
			<i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('check')); ?> wfls-passkey-information-list-icon" aria-hidden="true"></i>
			<span><?php echo wp_kses(__('<strong>Safer by design</strong> — each passkey is unique to the site, and no reusable secret is sent during sign-in.', 'wordfence'), array('strong' => array())); ?></span>
		</li>
	</ul>
</div>
