<?php

namespace GitGis;

/**
 * Pagination class
 *
 * Usage:
 * $pager = new Pager(MAINURL.'/messages/', 25);
 * $pager->setPage($page); // set current page
 * $query = $pager->getQueryArray($query); 
 * $queryResult = $dao->getList($query); // fetches list of rows
 * $pager->setCount(count($queryResult['list'])); // set number of rows fetched
 * if (isset($list['total'])) $pager->setTotal($queryResult['total']); // Optionally set total number of rows
 *
 */
class Pager {
	
	/**
	 * Margin size of pager
	 * 
	 * If the current page is 10 and MARGIN is set to 3 the following pages will be displayed:
	 * 7 8 9 _10_ 11 12 13
	 * 
	 * @var number
	 */
	const MARGIN = 3;
	
	/**
	 * Page size limit in rows
	 * 
	 * @var number
	 */
	private $pageSize = 50;
	
	/**
	 * Current page size in rows
	 * 
	 * Usually same as pageSize except last page when can be fewer rows
	 * 
	 * @var number
	 */
	private $count = 0;
	
	/**
	 * Total number of rows in DB
	 * 
	 * If set to -1 total is unknown (no link to last page in pager) 
	 * 
	 * @var number
	 */
	private $total = -1;
	
	/**
	 * Current page number (begins from 0) 
	 * 
	 * @var number
	 */
	private $page = 0;
	
	/**
	 * Base URL for page links
	 * 
	 * @var string
	 */
	private $url = '';

    private $queryMode = false;

	/**
	 * Constructor
	 * 
	 * Initialize base URL for links and pageSize
	 * 
	 * @param string $url
	 * @param number $pageSize
	 */
	public function __construct($url, $pageSize = 50) {
		$this->url = $url;
		$this->pageSize = $pageSize;
	}

	/**
	 * Set base URL for page links
	 * 
	 * @param unknown $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * Returns page size (number of rows, 50 by default)
	 * 
	 * @return number
	 */
	public function getPageSize() {
		return $this->pageSize;
	}

	/**
	 * Overrides page size (50 by default)
	 * 
	 * @param number $pageSize
	 */
	public function  setPageSize($pageSize) {
		$this->pageSize = $pageSize;
	}

	/**
	 * Get current page
	 * 
	 * @return number
	 */
	public function getPage() {
		return $this->page;
	}
	
	/**
	 * Set current page
	 * 
	 * @param unknown $page
	 */
	public function setPage($page) {
		$this->page = $page;
	}
	
	/**
	 * Get number of rows currently displayed on the list
	 * 
	 * Usually same as pageSize except last page when can be fewer rows
	 * 
	 * @return number
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * Set number of rows currently displayed on the list
	 * 
	 * Usually same as pageSize except last page when can be fewer rows
	 * 
	 * @param number $count
	 */
	public function setCount($count) {
		$this->count = $count;
	}
	
	/**
	 * Get total number of rows
	 * 
	 * @return number
	 */
	public function getTotal() {
		return $this->total;
	}
	
	/**
	 * Set total number of rows
	 * 
	 * If set to -1 total is unknown (no link to last page in pager) 
	 * 
	 * @return number
	 */
	public function setTotal($total) {
		$this->total = $total;
	}
	
	/**
	 * Creates link to specified page relative to url
	 * 
	 * @param number $page
	 * @return string
	 */
	public function createHref($page) {
		if ($page <= 0) {
			return $this->url;
		}
	
		$retVal = parse_url($this->url);
        if ($this->queryMode) {
            $retVal['query'] .= '&'.$this->queryMode.'='.$page;
        } else {
            $retVal['path'] .= $page."/";
        }

		return Pager::unparse_url($retVal);
	}
	
	/**
	 * Returns number of last page
	 * 
	 * @return number
	 */
	public function getLastPage() {
		if ($this->total == -1) return 0;
		
		$lastPage = floor(($this->total-1) / $this->pageSize);
		if ($lastPage < 0) {
			$lastPage = 0;
		}
		return $lastPage;
	}
	
	/**
	 * Returns first page for pager: usually currentPage - MARGIN
	 * 
	 * @return number
	 */
	public function getStartPage() {
		$startPage = 0;
		if ($this->getPage() > (Pager::MARGIN+1)) {
			$startPage = $this->getPage() - Pager::MARGIN;
		}
		return startPage;
	}
	
	/**
	 * Returns last page for pager: usually currentPage + MARGIN
	 * 
	 * @return number
	 */
	public function getEndPage() {
		$endPage = $this->getPage() + Pager::MARGIN;
		if ($endPage > $this->getLastPage()) {
			$endPage = $this->getLastPage();
		}
		return $endPage;
	}

	/**
	 * Query is an array of parameters for SQL.
	 * This function adds start and limit variables to the array based on current page and pagesize
	 *  
	 * @param array $query
	 * @return number
	 */
	public function getQueryArray($query) {
		$query['limit'] = $this->pageSize;
		$query['start'] = $this->pageSize * $this->page;
		
		return $query;
	}

	/**
	 * Reverse to <a href="http://php.net/manual/en/function.parse-url.php">parse_url</a>
	 * 
	 * @param array $parsed_url
	 * @return string
	 */
	public static function unparse_url($parsed_url) {
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";
	}

    /**
     * @param boolean $queryMode
     */
    public function setQueryMode($queryMode)
    {
        $this->queryMode = $queryMode;
    }

    /**
     * @return boolean
     */
    public function getQueryMode()
    {
        return $this->queryMode;
    }
}

