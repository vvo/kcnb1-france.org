<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="keep_active_notice" class="notice notice-info">
  <p>
	<?php esc_html_e(
		'You should keep this plugin active. As soon as you deactive it, your favicon is disabled, too.',
		'favicon-by-realfavicongenerator'
	) ?>
	<?php
	echo wp_kses(
		sprintf(
			// translators: %s is the HTML attributes (target and href) injected into the anchor tag
			__(
				'You may wonder <a %s>why it works that way</a>.',
				'favicon-by-realfavicongenerator'
			),
			'target="_blank" href="https://realfavicongenerator.net/blog/why-does-the-favicon-by-realfavicongenerator-wordpress-plugin-need-to-be-activated-all-the-time/"'
		),
		array( 'a' => array( 'href' => array(), 'target' => array() ) )
	);
	?>
	<?php
	echo wp_kses(
		sprintf(
			// translators: %s is the HTML attributes (target and href) injected into the anchor tag
			__(
				'In any case, <a %s>the plugin is super lightweight</a> and will not affect the performance of your site.',
				'favicon-by-realfavicongenerator'
			),
			'target="_blank" href="https://realfavicongenerator.net/blog/rfgs-wordpress-plugin-and-performance/"'
		),
		array( 'a' => array( 'href' => array(), 'target' => array() ) )
	);
	?>
  </p>
</div>
