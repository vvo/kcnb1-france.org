<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $siteName The site name. Required.
 * @var string $passkeyLabel The registered passkey label. Required.
 * @var int $registeredAt The registration timestamp. Required.
 * @var string $ip The requesting IP. Required.
 * @var string $manageURL The passkey management URL. Required.
 */
?>
<strong><?php echo esc_html(sprintf(/* translators: Site name. */ __('A passkey was added to your account on %s.', 'wordfence'), $siteName)); ?></strong>
<br><br>
<?php echo '<strong>' . esc_html__('Passkey:', 'wordfence') . '</strong> ' . esc_html($passkeyLabel); ?><br>
<?php echo '<strong>' . esc_html__('Added:', 'wordfence') . '</strong> ' . esc_html(\WordfenceLS\Controller_Time::format_local_time('F j, Y h:i:s A', $registeredAt)); ?><br>
<?php echo '<strong>' . esc_html__('IP:', 'wordfence') . '</strong> ' . esc_html($ip); ?>
<br><br>
<?php echo esc_html__('If you added this passkey, no further action is needed.', 'wordfence'); ?>
<br><br>
<?php echo esc_html__('If you did not add this passkey, remove it immediately, change your password, and contact the site administrator.', 'wordfence'); ?>
<br><br>
<?php echo wp_kses(sprintf(/* translators: Passkey management URL. */ __('<a href="%s"><strong>Review Passkeys</strong></a>', 'wordfence'), esc_url($manageURL)), array('a' => array('href' => array()), 'strong' => array())); ?>
