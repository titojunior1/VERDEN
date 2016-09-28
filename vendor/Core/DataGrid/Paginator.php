<?php

/**
 * Core_DataGrid_Paginator
 *
 * @name Core_DataGrid_Paginator
 */
class Core_DataGrid_Paginator {

	protected static $_defaultItemCountPerPage = 10;

	protected static $_defaultPageRange = 10;

	/**
	 *
	 * @var Core_DataGrid_Source_SourceInterface
	 */
	protected $_source;

	/**
	 *
	 * @var integer
	 */
	protected $_currentItemCount = null;

	/**
	 *
	 * @var array
	 */
	protected $_currentItems = null;

	/**
	 *
	 * @var integer
	 */
	protected $_currentPageNumber = 1;

	/**
	 *
	 * @var integer
	 */
	protected $_itemCountPerPage = null;

	/**
	 *
	 * @var integer
	 */
	protected $_pageCount = null;

	/**
	 *
	 * @var array
	 */
	protected $_pageRange = null;

	/**
	 *
	 * @var integer
	 */
	protected $_totalItemCount = null;

	/**
	 *
	 * @var array
	 */
	protected $_pages = null;

	/**
	 * Get the default item count per page
	 *
	 * @return int
	 */
	public static function getDefaultItemCountPerPage() {
		return self::$_defaultItemCountPerPage;
	}

	/**
	 * Set the default item count per page
	 *
	 * @param int $count
	 */
	public static function setDefaultItemCountPerPage($count) {
		self::$_defaultItemCountPerPage = (int) $count;
	}

	/**
	 * Get the default page range
	 *
	 * @return int
	 */
	public static function getDefaultPageRange() {
		return self::$_defaultPageRange;
	}

	/**
	 * Set the default page range
	 *
	 * @param int $count
	 */
	public static function setDefaultPageRange($count) {
		self::$_defaultPageRange = (int) $count;
	}

	/**
	 *
	 * @param Core_DataGrid_Source_SourceInterface $source
	 */
	public function __construct(Core_DataGrid_Source_SourceInterface $source) {
		$this->_source = $source;
	}

	/**
	 * Returns the number of pages.
	 *
	 * @return integer
	 */
	public function count() {
		if (! $this->_pageCount) {
			$this->_pageCount = $this->_calculatePageCount();
		}

		return $this->_pageCount;
	}

	/**
	 * Retorna o total de itens disponiveis.
	 *
	 * @return integer
	 */
	public function getTotalItemCount() {
		if (null == $this->_totalItemCount) {
			$this->_totalItemCount = $this->_source->getTotalItemCount();
		}

		return $this->_totalItemCount;
	}

	/**
	 *
	 * @return Core_DataGrid_Source_SourceInterface
	 */
	public function getSource() {
		return $this->_source;
	}

	/**
	 * Retorna o número de itens para a página corrente.
	 *
	 * @return integer
	 */
	public function getCurrentItemCount() {
		if (null == $this->_currentItemCount) {
			$this->_currentItemCount = count($this->getCurrentItems());
		}
	}

	/**
	 * Retorna os itens da página corrente.
	 *
	 * @return array
	 */
	public function getCurrentItems() {
		if ($this->_currentItems === null) {
			$this->_currentItems = $this->getItemsByPage($this->getCurrentPageNumber());
		}

		return $this->_currentItems;
	}

	/**
	 * Retorna número da página corrente.
	 *
	 * return integer
	 */
	public function getCurrentPageNumber() {
		return $this->normalizePageNumber($this->_currentPageNumber);
	}

	/**
	 * Define o número da página corrente.
	 *
	 * @param integer $pageNumber Page number
	 * @return Core_DataGrid_Paginator $this
	 */
	public function setCurrentPageNumber($pageNumber) {
		$this->_currentPageNumber = (integer) $pageNumber;
		$this->_currentItems = null;
		$this->_currentItemCount = null;

		return $this;
	}

	/**
	 * Retorna o número de item por página.
	 *
	 * @return integer
	 */
	public function getItemCountPerPage() {
		if (empty($this->_itemCountPerPage)) {
			$this->_itemCountPerPage = self::getDefaultItemCountPerPage();
		}

		return $this->_itemCountPerPage;
	}

	/**
	 * Define o número de itens por página.
	 *
	 * @param integer $itemCountPerPage
	 * @return Core_DataGrid_Paginator $this
	 */
	public function setItemCountPerPage($itemCountPerPage = -1) {
		$this->_itemCountPerPage = (integer) $itemCountPerPage;
		if ($this->_itemCountPerPage < 1) {
			$this->_itemCountPerPage = $this->getTotalItemCount();
		}
		$this->_pageCount = $this->_calculatePageCount();
		$this->_currentItems = null;
		$this->_currentItemCount = null;

		return $this;
	}

	/**
	 * Returns the items for a given page.
	 *
	 * @return array
	 */
	public function getItemsByPage($pageNumber) {
		$pageNumber = $this->normalizePageNumber($pageNumber);

		$offset = ($pageNumber - 1) * $this->getItemCountPerPage();
		$items = $this->getSource()->getItems($offset, $this->getItemCountPerPage());

		return $items;
	}

	/**
	 * Returns the page range (see property declaration above).
	 *
	 * @return integer
	 */
	public function getPageRange() {
		if (null === $this->_pageRange) {
			$this->_pageRange = self::getDefaultPageRange();
		}

		return $this->_pageRange;
	}

	/**
	 * Sets the page range (see property declaration above).
	 *
	 * @param integer $pageRange
	 * @return Zend_Paginator $this
	 */
	public function setPageRange($pageRange) {
		$this->_pageRange = (integer) $pageRange;

		return $this;
	}

	/**
	 * Returns the page collection.
	 *
	 * @param string $scrollingStyle Scrolling style
	 * @return array
	 */
	public function getPages($scrollingStyle = null) {
		if ($this->_pages === null) {
			$this->_pages = $this->_createPages($scrollingStyle);
		}

		return $this->_pages;
	}

	/**
	 * Returns a subset of pages within a given range.
	 *
	 * @param integer $lowerBound Lower bound of the range
	 * @param integer $upperBound Upper bound of the range
	 * @return array
	 */
	public function getPagesInRange($lowerBound, $upperBound) {
		$lowerBound = $this->normalizePageNumber($lowerBound);
		$upperBound = $this->normalizePageNumber($upperBound);

		$pages = array();

		for($pageNumber = $lowerBound; $pageNumber <= $upperBound; $pageNumber ++) {
			$pages[$pageNumber] = $pageNumber;
		}

		return $pages;
	}

	/**
	 * Brings the item number in range of the page.
	 *
	 * @param integer $itemNumber
	 * @return integer
	 */
	public function normalizeItemNumber($itemNumber) {
		$itemNumber = (integer) $itemNumber;

		if ($itemNumber < 1) {
			$itemNumber = 1;
		}

		if ($itemNumber > $this->getItemCountPerPage()) {
			$itemNumber = $this->getItemCountPerPage();
		}

		return $itemNumber;
	}

	/**
	 * Brings the page number in range of the paginator.
	 *
	 * @param integer $pageNumber
	 * @return integer
	 */
	public function normalizePageNumber($pageNumber) {
		$pageNumber = (integer) $pageNumber;

		if ($pageNumber < 1) {
			$pageNumber = 1;
		}

		$pageCount = $this->count();

		if ($pageCount > 0 && $pageNumber > $pageCount) {
			$pageNumber = $pageCount;
		}

		return $pageNumber;
	}

	/**
	 * Calculates the page count.
	 *
	 * @return integer
	 */
	protected function _calculatePageCount() {
		return (integer) ceil($this->getTotalItemCount() / $this->getItemCountPerPage());
	}

	/**
	 * Creates the page collection.
	 *
	 * @param string $scrollingStyle Scrolling style
	 * @return stdClass
	 */
	protected function _createPages($scrollingStyle = null) {
		$pageCount = $this->count();
		$currentPageNumber = $this->getCurrentPageNumber();

		$pages = new stdClass();
		$pages->pageCount = $pageCount;
		$pages->itemCountPerPage = $this->getItemCountPerPage();
		$pages->first = 1;
		$pages->current = $currentPageNumber;
		$pages->last = $pageCount;

		// Previous and next
		if ($currentPageNumber - 1 > 0) {
			$pages->previous = $currentPageNumber - 1;
		}

		if ($currentPageNumber + 1 <= $pageCount) {
			$pages->next = $currentPageNumber + 1;
		}

		// Pages in range
// 		$scrollingStyle = $this->_loadScrollingStyle();
// 		$pages->pagesInRange = $scrollingStyle->getPages($this);
		$pages->pagesInRange = $this->_loadScrollingStyle();
		$pages->firstPageInRange = min($pages->pagesInRange);
		$pages->lastPageInRange = max($pages->pagesInRange);

		// Item numbers
		if ($this->getCurrentItems() !== null) {
			$pages->currentItemCount = $this->getCurrentItemCount();
			$pages->itemCountPerPage = $this->getItemCountPerPage();
			$pages->totalItemCount = $this->getTotalItemCount();
			$pages->firstItemNumber = (($currentPageNumber - 1) * $this->getItemCountPerPage()) + 1;
			$pages->lastItemNumber = $pages->firstItemNumber + $pages->currentItemCount - 1;
		}

		return $pages;
	}

	/**
	 * @todo Utilizar uma estratégia semelhante a do zend,
	 * uma classe para gerar o formato de paginação.
	 *
	 */
	protected function _loadScrollingStyle() {

		$pageRange = $this->getPageRange();

		$pageNumber = $this->getCurrentPageNumber();
		$pageCount = $this->count();

		if ($pageRange > $pageCount) {
			$pageRange = $pageCount;
		}

		$delta = ceil($pageRange / 2);

		if ($pageNumber - $delta > $pageCount - $pageRange) {
			$lowerBound = $pageCount - $pageRange + 1;
			$upperBound = $pageCount;
		} else {
			if ($pageNumber - $delta < 0) {
				$delta = $pageNumber;
			}

			$offset = $pageNumber - $delta;
			$lowerBound = $offset + 1;
			$upperBound = $offset + $pageRange;
		}

		return $this->getPagesInRange($lowerBound, $upperBound);
	}

}