<?php

/**
 * Core_DataGrid_Column_Text
 *
 */
class Core_DataGrid_Column_Text extends Core_DataGrid_Column_ColumnAbstract {

	/* (non-PHPdoc)
	 * @see Core_DataGrid_Column_ColumnAbstract::render()
	 */
	public function render(array $row) {
		$value = $this->getValueInRow($row, '');

		return $this->hasDecorator()? $this->_applyDecorator($row): $value;
	}
}