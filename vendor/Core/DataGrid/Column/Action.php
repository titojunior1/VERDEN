<?php

/**
 * Core_DataGrid_Column_Action
 *
 *
 *
 */
class Core_DataGrid_Column_Action extends Core_DataGrid_Column_ColumnAbstract {

	/**
	 *
	 * @var array
	 */
	protected $_actions=array();

	/**
	 *
	 * @var string
	 */
	protected $_iconBasePath;

	/**
	 *
	 * @param string $url
	 * @param string $icon
	 * @param string $label
	 * @return Core_DataGrid_Column_Action
	 */
	public function addAction($url, $icon, $label=null) {
		$this->_actions[] = array(
			'url' => $url,
			'icon' => $icon,
			'label' => $label
		);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getIconBasePath() {
		return $this->_iconBasePath;
	}

	/**
	 * @param string $basePath
	 */
	public function setIconBasePath($basePath) {
		$this->_iconBasePath = $basePath;
	}

	/* (non-PHPdoc)
	 * @see Core_DataGrid_Column_ColumnAbstract::render()
	 */
	public function render(array $row) {
		$html= array();

		foreach($this->_actions as $action) {
			$icon = $this->_iconBasePath . '/' . $action['icon'];
			$url = $this->_applyDecorator($row, $action['url']);
			$label = $this->_applyDecorator($row, $action['label']);

			$html[] = "<a href='{$url}'><img src='{$icon}' /></a>";
		}

		return implode(' ', $html);
	}


}