<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfLoginSecPresent',
	'displayType' => __('Wordfence Login Security Present', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => [
		__("Plugin Name", "wordfence") => "Wordfence Login Security",
		null,
		__("Details", "wordfence") => '$longMsg'
	]
))->render();
