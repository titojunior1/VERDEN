<?php

/**
 * Core_DataGrid_Renderer_HtmlTable
 *
 * @name Core_DataGrid_Renderer_HtmlTable
 *
 */
class Core_DataGrid_Renderer_HtmlTable {

	/**
	 *
	 * @var Core_DataGrid
	 */
	protected $_grid;

	/**
	 *
	 * @var Core_DataGrid_Paginator
	 */
	protected $_paginator;

	protected $_iconAsc = '&nbsp; &#9650';

	protected $_iconDesc = '&nbsp; &#9660';

	/**
	 */
	function __construct(Core_DataGrid $grid) {
		$this->_grid = $grid;
		$this->_paginator = $grid->getPaginator();
	}

	/**
	 *
	 * @return Core_DataGrid
	 */
	public function getDataGrid() {
		return $this->_grid;
	}

	/**
	 *
	 * @return Core_DataGrid_Paginator
	 */
	public function getPaginator() {
		return $this->_paginator;
	}

	/**
	 * Exibe a listagem com a páginação.
	 *
	 * @return string
	 */
	public function display() {
		$table = array();

		$table[] = '<table class="table">';

		$table[] = $this->_buildHeader();
		$table[] = $this->_buildBody();

		$table[] = '</table>';
		$table[] = $this->_buildPagination();

		return implode(PHP_EOL, $table);
	}

	/**
	 * Exibe informações referentes a quantidade de páginas e registros.
	 *
	 * @return string
	 */
	public function displayInfo() {
		return $this->_buildInfo();
	}

	/**
	 * Exibe o controle para selecionar a quantidade de registro por página.
	 *
	 * @return string
	 */
	public function displayChangePage() {
		return $this->_buildChangePage();
	}

	/**
	 *
	 * @return string
	 */
	protected function _buildHeader() {
		$header = array();
		$cols = $this->_grid->getColumns();

		foreach($cols as $col) {
			$params = array();

			$currentOrder = $this->getDataGrid()->getParam('order');
			$currentDir = $this->getDataGrid()->getParam('dir');

			if($col->isSortable()) {
				$identity = $col->getIdentity();

				$params['order'] = $identity;
				$icon = '';

				if($identity == $currentOrder) {
					if($currentDir == 'asc') {
						$params['dir'] = 'desc';
						$icon = $this->_iconAsc;
					} else {
						$params['dir'] = 'asc';
						$icon = $this->_iconDesc;
					}
				}

				$url = $this->_createHttpQuery($params);

				$header[] = "<th><a href='{$url}'>{$col->getLabel()}{$icon}</a></th>";

			} else {
				$header[] = "<th>{$col->getLabel()}</th>";
			}
		}

		return '<thead><tr>' . implode(PHP_EOL, $header) . '</tr></thead>';
	}

	/**
	 *
	 * @return string
	 */
	protected function _buildBody() {
		$records = $this->getDataGrid()->getItems();
		$columns = $this->getDataGrid()->getColumns();
		$countCols = $this->getDataGrid()->getTotalColumns();

		$rows = array();
		$count = 0;

		if(empty($records)) {
			$rows[] = "<td colspan='{$countCols}' style='text-align:center; font-size: 12px'>Nenhum registro encontrado.</td>";
		} else {
			foreach($records as $row) {
				$td = array();

				foreach($columns as $col) {
					$value = $col->render($row);

					$td[] = "<td>{$value}</td>";
				}

				$class = ($count % 2 == 0) ? '' : 'class="odd"';

				if (! empty($td)) {
					$rows[] = "<tr {$class}>" . implode(PHP_EOL, $td) . "</tr>";
				}

				$count ++;
			}
		}


		return '<tbody>' . implode(PHP_EOL, $rows) . '</tbody>';
	}

	/**
	 *
	 * @return string
	 */
	protected function _buildPagination() {
		$total = $this->getPaginator()->getTotalItemCount();

		if($total == 0) {
			return '';
		}

		$pages = $this->getPaginator()->getPages();
		$pagination = '<div class="pagination">';
		$params = array();

		$params['limit'] = $pages->itemCountPerPage;

		if (isset($pages->previous)) {
			$params['page'] = $pages->first;

			$pagination .= '<a href="'. $this->_createHttpQuery($params) . '"><img src="/images/icones/go-first.png" /></a>';
		} else {
			$pagination .= '<img src="/images/icones/go-first_off.png" />';
		}

		if (isset($pages->previous)) {
			$params['page'] = $pages->previous;
			$pagination .= '<a href="'. $this->_createHttpQuery($params) . '"><img src="/images/icones/go-previous.png" /></a>';
		} else {
			$pagination .= '<img src="/images/icones/go-previous_off.png" />';
		}

		$pagination .= '<div class="pagination-inner">';

		foreach($pages->pagesInRange as $page) {
			if ($page != $pages->current) {
				$params['page'] =$page;
				$pagination .= '<a href="'. $this->_createHttpQuery($params) . '">' . $page . '</a>';
			} else {
				$pagination .= '<strong>' . $page . '</strong>';
			}
		}

		$pagination .= '</div>';

		if (isset($pages->next)) {
			$params['page'] = $pages->next;
			$pagination .= '<a href="'. $this->_createHttpQuery($params) . '"><img src="/images/icones/go-next.png" /></a>';
		} else {
			$pagination .= '<img src="/images/icones/go-next_off.png" />';
		}

		if (isset($pages->next)) {
			$params['page'] = $pages->last;
			$pagination .= '<a href="'. $this->_createHttpQuery($params) . '"><img src="/images/icones/go-last.png" /></a>';
		} else {
			$pagination .= '<img src="/images/icones/go-last_off.png" />';
		}

		$pagination .= '</div>';

		return $pagination;
	}

	/**
	 *
	 * @return string
	 */
	protected function _buildInfo() {
		$pages = $this->getPaginator()->getPages();

		$total = $this->getPaginator()->getTotalItemCount();

		if($total == 0) {
			return '';
		}

		return sprintf('%s registros | página %s de %s', $pages->totalItemCount, $pages->current, $pages->pageCount);
	}

	/**
	 *
	 * @return string
	 */
	protected function _buildChangePage() {
		$html = 'Registros por página: ';
		$change = array();

		$total = $this->getPaginator()->getTotalItemCount();

		if($total == 0) {
			return '';
		}

		$pages = $this->getPaginator()->getPages();

		foreach(array(15, 30, 45, 60) as $value) {
			if($pages->itemCountPerPage == $value) {
				$change[] = "<strong>{$value}</strong>";
			} else {
				$params = array('limit' => $value);
				$change[] = '<a href="'. $this->_createHttpQuery($params) .'">'. $value .'</a>';
			}
		}

		return 'Registros por página: ' . implode(' | ', $change);
	}

	protected function _createHttpQuery($params) {
		$params+= $this->getDataGrid()->getParams();

		return '?' . http_build_query($params);
	}
}