<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $icon Semantic icon identifier. Required.
 */
$fontAwesomeIcons = array(
	'2fa' => 'shield',
	'settings' => 'cog',
);
?>
<?php if ($icon !== 'passkey'): ?>
	<i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes(isset($fontAwesomeIcons[$icon]) ? $fontAwesomeIcons[$icon] : $icon)); ?>"></i>
<?php endif; ?>
