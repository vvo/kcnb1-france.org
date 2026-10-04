<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WordfenceLS\Page\Model_Title $title The page title parameters.
 * @var bool $showIcon Whether or not to show the header icon. Optional, defaults to false.
 */
?>
<?php if ($title->featured): ?>
	<div class="wfls-section-title wfls-section-title-featured wfls-section-title-<?php echo esc_attr($title->icon); ?>">
		<?php if ($title->icon !== null): ?><span class="wfls-section-title-icon wfls-main-icon-<?php echo esc_attr($title->icon); ?>" aria-hidden="true"><?php echo \WordfenceLS\Model_View::create('common/semantic-icon', array('icon' => $title->icon))->render(); ?></span><?php endif; ?>
		<div class="wfls-section-title-copy">
			<h2 id="section-title-<?php echo esc_attr($title->id); ?>"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->title); ?></h2>
			<?php if ($title->context !== null): ?><div class="wfls-section-title-context"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->context); ?></div><?php endif; ?>
			<?php if ($title->subtitle !== null || $title->description !== null): ?>
				<div class="wfls-section-title-descriptions">
					<?php if ($title->subtitle !== null): ?><p class="wfls-section-title-subtitle"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->subtitle); ?></p><?php endif; ?>
					<?php if ($title->description !== null): ?><p class="wfls-section-title-description"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->description); ?></p><?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ($title->helpBelowSubtitle && $title->helpURL !== null && $title->helpLink !== null): ?>
				<a href="<?php echo esc_url($title->helpURL); ?>" target="_blank" rel="noopener noreferrer" class="wfls-help-link wfls-section-title-inline-help"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('info-circle')); ?>" aria-hidden="true"></i> <?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->helpLink); ?> <i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('external-link')); ?>" aria-hidden="true"></i></a>
			<?php endif; ?>
		</div>
		<?php if ($title->action !== null): ?>
			<div class="wfls-section-title-action"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->action); ?></div>
		<?php elseif (!$title->helpBelowSubtitle && $title->helpURL !== null && $title->helpLink !== null): ?>
			<a href="<?php echo esc_url($title->helpURL); ?>" target="_blank" rel="noopener noreferrer" class="wfls-help-link wfls-section-title-help"><i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('info-circle')); ?>" aria-hidden="true"></i> <?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->helpLink); ?> <i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('external-link')); ?>" aria-hidden="true"></i></a>
		<?php endif; ?>
	</div>
<?php else: ?>
	<div class="wfls-section-title">
		<?php if (isset($showIcon) && $showIcon): ?>
			<div class="wfls-header-icon wfls-hidden-xs"></div>
		<?php endif; ?>
		<h2 class="wfls-center-xs" id="section-title-<?php echo esc_attr($title->id); ?>"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->title); ?></h2>
		<?php if ($title->helpURL !== null && $title->helpLink !== null): ?>
			<span class="wfls-hidden-xs"><a href="<?php echo esc_url($title->helpURL); ?>" target="_blank" rel="noopener noreferrer" class="wfls-help-link"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->helpLink); ?> <i class="<?php echo esc_attr(\WordfenceLS\Utility_Style::font_awesome_classes('external-link')); ?>" aria-hidden="true"></i></a></span>
		<?php endif; ?>
	</div>
<?php endif; ?>
