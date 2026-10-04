<?php

namespace WordfenceLS\View;

/**
 * Class Model_Title
 * @package Wordfence2FA\Page
 * @var string $id A valid DOM ID for the title.
 * @var string|Model_HTML $title The title text or HTML.
 * @var string $helpURL The help URL.
 * @var string|Model_HTML $helpLink The text/HTML of the help link.
 * @var string|Model_HTML|null $subtitle Optional descriptive subtitle.
 * @var string|Model_HTML|null $description Optional secondary description.
 * @var string|null $icon Optional presentation icon identifier.
 * @var string|Model_HTML|null $context Optional contextual content rendered below the title.
 * @var string|Model_HTML|null $action Optional right-aligned title action.
 * @var bool $helpBelowSubtitle Whether the help link is rendered below the subtitle.
 * @var bool $featured Whether to use the featured title presentation.
 */
class Model_Title {
	private $_id;
	private $_title;
	private $_helpURL;
	private $_helpLink;
	private $_subtitle;
	private $_description;
	private $_icon;
	private $_context;
	private $_action;
	private $_helpBelowSubtitle;
	private $_featured;
	
	/**
	 * Creates a page title model.
	 *
	 * @param string $id A valid DOM ID for the title.
	 * @param string|Model_HTML $title The title text or HTML.
	 * @param string|null $helpURL Optional help URL.
	 * @param string|Model_HTML|null $helpLink Optional help-link text or HTML.
	 * @param array $options Optional presentation values.
	 */
	public function __construct($id, $title, $helpURL = null, $helpLink = null, $options = array()) {
		$options = is_array($options) ? $options : array();
		$this->_id = $id;
		$this->_title = $title;
		$this->_helpURL = $helpURL;
		$this->_helpLink = $helpLink;
		$this->_subtitle = isset($options['subtitle']) ? $options['subtitle'] : null;
		$this->_description = isset($options['description']) ? $options['description'] : null;
		$this->_icon = isset($options['icon']) ? $options['icon'] : null;
		$this->_context = isset($options['context']) ? $options['context'] : null;
		$this->_action = isset($options['action']) ? $options['action'] : null;
		$this->_helpBelowSubtitle = !empty($options['helpBelowSubtitle']);
		$this->_featured = !empty($options['featured']);
	}
	
	public function __get($name) {
		switch ($name) {
			case 'id':
				return $this->_id;
			case 'title':
				return $this->_title;
			case 'helpURL':
				return $this->_helpURL;
			case 'helpLink':
				return $this->_helpLink;
			case 'subtitle':
				return $this->_subtitle;
			case 'description':
				return $this->_description;
			case 'icon':
				return $this->_icon;
			case 'context':
				return $this->_context;
			case 'action':
				return $this->_action;
			case 'helpBelowSubtitle':
				return $this->_helpBelowSubtitle;
			case 'featured':
				return $this->_featured;
		}
		
		throw new \OutOfBoundsException('Invalid key: ' . $name);
	}
}
