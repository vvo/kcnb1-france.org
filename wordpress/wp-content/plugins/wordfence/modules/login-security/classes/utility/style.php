<?php

namespace WordfenceLS;

/**
 * Provides shared helpers for styling Login Security interfaces.
 */
class Utility_Style {

	/**
	 * Returns the stylesheet-aware Font Awesome classes for an icon.
	 *
	 * @param string $icon Font Awesome icon name without a prefix.
	 * @param string|null $context Optional explicit UI style context.
	 * @return string
	 */
	public static function font_awesome_classes($icon, $context = null) {
		$context = $context === null ? Controller_WordfenceLS::shared()->ui_style_context() : Controller_WordfenceLS::normalize_ui_style_context($context);
		$prefix = $context === Controller_WordfenceLS::UI_STYLE_CONTEXT_CORE ? 'wf-fa' : 'wfls-fa';
		return $prefix . ' ' . $prefix . '-' . $icon;
	}
}
