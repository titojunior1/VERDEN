<?php

/**
 * Core_DataGrid_DataSource_Db
 *
 * Implementação de datasource para o mysql.
 *
 * @name Core_DataGrid_Source_Db
 *
 */
class Core_DataGrid_Source_Db implements Core_DataGrid_Source_SourceInterface {

	/**
	 *
	 * @var Core_Db_AdapterInterface
	 */
	protected $_dbAdapter;

	/**
	 *
	 * @var string
	 */
	protected $_query;

	/**
	 *
	 * @var array
	 */
	protected $_params = array();

	/**
	 *
	 * @var array
	 */
	protected $_cols = array();

	/**
	 *
	 * @var string
	 */
	protected $_countSelect;

	/**
	 *
	 * @var array
	 */
	protected $_order = array();

	/**
	 *
	 * @param Core_Db_AdapterInterface $adapter
	 * @param string $query
	 * @param array $params
	 */
	public function __construct(Core_Db_AdapterInterface $adapter, $query = null, array $params = array()) {
		$this->_dbAdapter = $adapter;

		$this->_query = $query;
		$this->_params = $params;
	}

	/**
	 *
	 * @param string $query
	 * @param array $params
	 * @return Core_DataGrid_Source_Db
	 */
	public function setQuery($query, array $params = array()) {
		$this->_query = $query;
		$this->_params = $params;

		return $this;
	}

	public function mapColumn($column, $alias) {
		$this->_cols[$column] = $alias;

		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see Core_DataGrid_Source_SourceInterface::getItems()
	 */
	public function getItems($offset=false, $limit=false) {
		if (empty($this->_query)) {
			throw new RuntimeException("Query is empty");
		}

		$query = rtrim($this->_query, ';');
		$query = $this->_applyOrderBy($query);
		$query = $this->_applyLimitOffset($query, $offset, $limit);

		$rows = $this->_dbAdapter->fetchAll($query, $this->_params);

		if (! is_array($rows)) {
			throw new RuntimeException('The returned values should be array, but returned the "' . gettype($rows) . '".');
		}

		return empty($this->_cols) ? $rows : $this->_mapSource($rows);
	}


	/* (non-PHPdoc)
	 * @see Core_DataGrid_Source_SourceInterface::getTotalItemCount()
	*/
	public function getTotalItemCount() {
		$countPart = 'COUNT(1) AS TOTAL_ROWS';

		$fromPosition = stripos($this->_query, 'FROM');
		$ordeByPos = stripos($this->_query, 'ORDER BY');
		$groupByPos = stripos($this->_query, 'GROUP BY');

		if(false === $fromPosition) {
			throw new RuntimeException('Could not prepare query to get the total rows. Clause FROM not found.');
		}

		$fromPart = substr($this->_query, $fromPosition);

		$sql = "SELECT {$countPart} {$fromPart}";

		$result = $this->_dbAdapter->fetch($sql, $this->_params);

		return count($result) > 0? $result['TOTAL_ROWS'] : 0;
	}

	/**
	 * (non-PHPdoc)
	 * @see Core_DataGrid_Source_SourceInterface::addOrderBy()
	 */
	public function addOrderBy($column, $direction = 'ASC') {
		$this->_order[$column] = $direction;
	}

	/**
	 *
	 * @param array $row
	 * @return array
	 */
	protected function _mapSource($rows) {
		$data = array();

		foreach($rows as $row) {
			$inner = array();

			foreach($row as $col => $value) {
				if (array_key_exists($col, $this->_cols)) {
					$inner[$this->_cols[$col]] = $value;
				} else {
					$inner[$col] = $value;
				}
			}

			$data[] = $inner;
		}

		return $data;
	}

	/**
	 * Retorna $query com a clausula order by.
	 *
	 * @param string $query
	 * @return string
	 */
	protected function _applyOrderBy($query) {
		if(empty($this->_order)) {
			return $query;
		}

		$order = array();
		$colAndField = array_flip($this->_cols);

		foreach($this->_order as $col => $dir) {
			if(isset($colAndField[$col])) {
				$order[]= "{$colAndField[$col]} {$dir}";
			}
		}

		return empty($order)? $query: "{$query} ORDER BY " . implode(', ', $order);
	}

	/**
	 *
	 * @param string $query
	 * @return string
	 */
	protected function _applyLimitOffset($query, $offset, $limit) {
		if($offset === false || $limit === false) {
			return $query;
		}

		return "$query LIMIT $limit OFFSET $offset";
	}
}