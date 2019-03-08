<?php
/**
 * Setup wizard header template.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin\Wizard
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php echo '<title>' . $this->strings['title'] . '</title>'; ?>
	<?php wp_print_head_scripts(); ?>
	<?php wp_print_styles( 'rank-math-wizard' ); ?>
</head>
<body class="rank-math-wizard rank-math-wizard-body--<?php echo sanitize_html_class( $this->step_slug ); ?><?php echo is_rtl() ? ' rtl' : ''; ?>">
