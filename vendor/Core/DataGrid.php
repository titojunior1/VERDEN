<?php

/**
 * Core_DataGrid
 *
 * @name Core_DataGrid
 *
 */
class Core_DataGrid {

	/**
	 *
	 * @var array
	 */
	protected $_columns=array();

	/**
	 *
	 * @var Core_DataGrid_Source_SourceInterface
	 */
	protected $_source;

	/**
	 *
	 * @var Core_DataGrid_Paginator
	 */
	protected $_paginator;

	/**
	 *
	 * @var array
	 */
	protected $_params=array();

	/**
	 *
	 * @param Core_DataGrid_Source_SourceInterface $datasource
	 */
	public function __construct(Core_DataGrid_Source_SourceInterface $datasource) {
		$this->_source = $datasource;
	}

	/**
	 *
	 * @param Core_DataGrid_Column_ColumnAbstract | array $column
	 * @return Core_DataGrid
	 */
	public function addColumn($column) {
		$this->_columns[$column->getIdentity()] = $column;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getColumns() {
		return $this->_columns;
	}

	/**
	 * Retorna o total de colunas do datagrid.
	 *
	 * @return integer
	 */
	public function getTotalColumns() {
		return count($this->_columns);
	}

	/**
	 *
	 * @param Core_DataGrid_Paginator $paginator
	 * @return Core_DataGrid
	 */
	public function setPaginator(Core_DataGrid_Paginator $paginator) {
		$this->_paginator = $paginator;

		return $this;
	}

	/**
	 *
	 * @return Core_DataGrid_Paginator
	 */
	public function getPaginator() {
		if(null == $this->_paginator) {
			$this->_paginator = new Core_DataGrid_Paginator($this->_source);
		}

		return $this->_paginator;
	}

	/**
	 *
	 * @return array
	 */
	public function getItems() {
		return $this->getPaginator()->getCurrentItems();
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return Core_DataGrid
	 */
	public function setParam($key, $value) {
		$this->_params[$key] = $value;

		return $this;
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParam($key, $default=null) {
		return array_key_exists($key, $this->_params)? $this->_params[$key]: $default;
	}

	/**
	 *
	 * @param array $params
	 * @return Core_DataGrid
	 */
	public function setParams(array $params) {
		$this->_params = $params;

		return $this;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getParams() {
		return $this->_params;
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasColumn($name) {
		return array_key_exists($name, $this->_columns);
	}

	/**
	 * Monta uma linha com todas as colunas configuradas no datagrid.
	 *
	 * @param array $row
	 * @return array
	 */
	private function _filterColumns($row) {
		$cols = array();

		foreach($row as $key => $col) {
			if($this->hasColumn($key)) {
				$cols[$key] = $col;
			}
		}

		return $cols;
	}

}