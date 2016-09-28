<?php

/**
 * Core_DataGrid_Column_Html_Link
 *
 * Exibe um link no datagrid.
 *
 */
class Core_DataGrid_Column_Html_Link extends Core_DataGrid_Column_ColumnAbstract {

	protected $_link;

	protected $_labelLink;

	protected $_attribs=array();

	protected function _init() {
		$this->_link = $this->getOption('link', '#');
		$this->_labelLink = $this->getOption('label');
		$this->_attribs = $this->getOption('attribs');
	}

	public function render(array $row) {
		$value = $this->getValueInRow($row);

		$label = empty($this->_labelLink)? $value: $this->_labelLink;
		$link = $this->_applyDecorator($row, $this->_link);

		return '<a href="'. $link .'">' . $label . '</a>';
	}

}