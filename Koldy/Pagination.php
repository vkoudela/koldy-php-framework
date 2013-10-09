<?php namespace Koldy;

class Pagination {

	protected $numberOfPageLinks = 5;

	protected $currentPage = null;

	protected $total = null;

	protected $showFirstAndLast = true;

	protected $showPrevAndNext = true;

	protected $itemsPerPage = 20;

	protected $linkCss = null;

	protected $firstCss = 'first';

	protected $prevCss = 'prev';

	protected $nextCss = 'next';

	protected $lastCss = 'last';

	protected $pageCss = 'page';

	protected $selectedCss = 'selected';

	protected $firstText = '&lArr;';

	protected $previousText = '&larr;';

	protected $nextText = '&rarr;';

	protected $lastText = '&rArr;';

	protected $link = '#/page/%s';

	/**
	 * Create the object
	 * @param int $currentPage
	 * @param int $total
	 */
	public function __construct($currentPage, $total) {
		$this->currentPage = $currentPage;
		$this->total = $total;
	}

	/**
	 * Set how many link will be displayed as number
	 * @param int $numberOfLinksPerPage
	 * @return Pagination
	 */
	public function setLinksPerPage($numberOfLinksPerPage) {
		$this->numberOfPageLinks = $numberOfLinksPerPage;
		return $this;
	}

	/**
	 * Set the URL pattern
	 * @param string $pattern
	 * @return Pagination
	 * @example param #/page/%s
	 */
	public function setUrl($pattern) {
		$this->link = $pattern;
		return $this;
	}

	/**
	 * Set how many records will be displayed per page
	 * @param int $numberOfRecordsPerPage
	 * @return Pagination
	 */
	public function setItemsPerPage($numberOfRecordsPerPage) {
		$this->itemsPerPage = $numberOfRecordsPerPage;
		return $this;
	}

	/**
	 * Set the visible text for first link
	 * @param string $first
	 * @return Pagination
	 */
	public function setTextFirst($first) {
		$this->firstText = $first;
		return $this;
	}

	/**
	 * Set the visible text for previous link
	 * @param string $prev
	 * @return Pagination
	 */
	public function setTextPrevious($prev) {
		$this->previousText = $prev;
		return $this;
	}

	/**
	 * Set the visible text for next link
	 * @param string $next
	 * @return Pagination
	 */
	public function setTextNext($next) {
		$this->nextText = $next;
		return $this;
	}

	/**
	 * Set the visible text for last link
	 * @param string $last
	 * @return Pagination
	 */
	public function setTextLast($last) {
		$this->lastText = $last;
		return $this;
	}

	/**
	 * Set the css class that will be added on the first place to all the <a> links
	 * @param string $defaultCssClass
	 * @return Pagination
	 */
	public function setCssDefault($defaultCssClass) {
		$this->linkCss = $defaultCssClass;
		return $this;
	}

	/**
	 * Set the CSS attribute for first link
	 * @param string $css
	 * @return Pagination
	 */
	public function setCssFirst($css) {
		$this->firstCss = $css;
		return $this;
	}

	/**
	 * Set the CSS attribute for prev link
	 * @param string $css
	 * @return Pagination
	 */
	public function setCssPrev($css) {
		$this->prevCss = $css;
		return $this;
	}

	/**
	 * Set the CSS attribute for next link
	 * @param string $css
	 * @return Pagination
	 */
	public function setCssNext($css) {
		$this->nextCss = $css;
		return $this;
	}

	/**
	 * Set the CSS attribute for last link
	 * @param string $css
	 * @return Pagination
	 */
	public function setCssLast($css) {
		$this->lastCss = $css;
		return $this;
	}

	/**
	 * Set the CSS attribute for each link
	 * @param string $css
	 * @return Pagination
	 */
	public function setCssPage($css) {
		$this->pageCss = $css;
		return $this;
	}

	/**
	 * Set the CSS class for selected page
	 * @param string $selected
	 * @return Pagination
	 */
	public function setCssSelected($selected) {
		$this->selectedCss = $selected;
		return $this;
	}

	/**
	 * Get the CSS for the link
	 * @param string $add
	 * @return string
	 */
	private function getLinkCss($add = null) {
		$css = '';

		if ($this->linkCss !== null) {
			$css .= $this->linkCss . ' ';
		}

		if ($add !== null) {
			$css .= $add;
		}

		return rtrim($css);
	}

	/**
	 * Generate the HTML
	 * @return string
	 */
	public function generate() {
		$currentPage = $this->currentPage;
		$total = $this->total;

		$html = '';
		$totalPages = ceil($total / $this->itemsPerPage);

		$half = floor($this->numberOfPageLinks / 2);
		$startPage = $currentPage - $half;

		if ($startPage < 1) {
			$startPage = 1;
		}

		$endPage = $startPage + $this->numberOfPageLinks -1;

		if ($endPage > $totalPages) {
			$endPage = $totalPages;
		}

		if ($this->showFirstAndLast) {
			if ($currentPage > 2) {
				$html .= sprintf('<a href="%s" class="%s" data-page="1">%s</a>', sprintf($this->link, 1), $this->getLinkCss($this->firstCss), $this->firstText);
			}
		}

		if ($this->showPrevAndNext) {
			if ($currentPage > 1) {
				$html .= sprintf('<a href="%s" class="%s" data-page="%d">%s</a>', sprintf($this->link, $currentPage -1), $this->getLinkCss($this->prevCss), ($currentPage -1), $this->previousText);
			}
		}

		for ($i = $startPage; $i <= $endPage; $i++) {
			$css = $this->pageCss;

			if ($i == $currentPage) {
				$css .= ' ' . $this->selectedCss;
			}
			$html .= sprintf('<a href="%s" class="%s" data-page="%d">%s</a>', sprintf($this->link, $i), $this->getLinkCss($css), $i, $i);
		}

		if ($this->showPrevAndNext) {
			if ($currentPage < $totalPages) {
				$html .= sprintf('<a href="%s" class="%s" data-page="%d">%s</a>', sprintf($this->link, $currentPage +1), $this->getLinkCss($this->nextCss), ($currentPage +1), $this->nextText);
			}
		}

		if ($this->showFirstAndLast) {
			if ($currentPage < $totalPages -1) {
				$html .= sprintf('<a href="%s" class="%s" data-page="%d">%s</a>', sprintf($this->link, $totalPages), $this->getLinkCss($this->lastCss), $totalPages, $this->lastText);
			}
		}

		return $html;
	}

	public function __toString() {
		return $this->generate();
	}
}