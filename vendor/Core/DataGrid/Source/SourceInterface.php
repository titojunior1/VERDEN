<?php

/**
 * Core_DataGrid_Source_SourceInterface
 *
 */
interface Core_DataGrid_Source_SourceInterface {

	/**
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */
	public function getItems($offset=false, $limit=false);

	/**
	 * @return integer
	 */
	public function getTotalItemCount();

	/**
	 *
	 * @param string $column
	 * @param string $direction
	 */
	public function addOrderBy($column, $direction = 'ASC');

}