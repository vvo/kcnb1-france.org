<?php

namespace WordfenceLS\View;

/**
 * Represents a tab in the UI.
 * 
 * @package Wordfence2FA\View
 * @property string $id
 * @property string $a
 * @property string $tabTitle
 * @property string $pageTitle
 * @property bool $active
 * @property string|null $mobileTabTitle
 * @property string|null $icon Presentation icon identifier.
 */
class Model_Tab {
	protected $_id;
	protected $_a;
	protected $_tabTitle;
	protected $_pageTitle;
	protected $_active;
	protected $_mobileTabTitle;
	protected $_icon;
	
	/**
	 * Creates a tab model.
	 *
	 * @param string $id The tab DOM ID.
	 * @param string $a The tab link target.
	 * @param string $tabTitle The visible tab title.
	 * @param string $pageTitle The browser page title.
	 * @param bool $active Whether the tab is initially active.
	 * @param string|null $mobileTabTitle Optional compact title.
	 * @param array $options Optional presentation values.
	 */
	public function __construct($id, $a, $tabTitle, $pageTitle, $active = false, $mobileTabTitle = null, $options = array()) {
		$options = is_array($options) ? $options : array();
		$this->_id = $id;
		$this->_a = $a;
		$this->_tabTitle = $tabTitle;
		$this->_pageTitle = $pageTitle;
		$this->_active = $active;
		$this->_mobileTabTitle = $mobileTabTitle;
		$this->_icon = isset($options['icon']) ? $options['icon'] : null;
	}
	
	public function __get($name) {
		switch ($name) {
			case 'id':
				return $this->_id;
			case 'a':
				return $this->_a;
			case 'tabTitle':
				return $this->_tabTitle;
			case 'pageTitle':
				return $this->_pageTitle;
			case 'active':
				return $this->_active;
			case 'mobileTabTitle':
				return $this->_mobileTabTitle;
			case 'icon':
				return $this->_icon;
		}
		
		throw new \OutOfBoundsException('Invalid key: ' . $name);
	}
}
