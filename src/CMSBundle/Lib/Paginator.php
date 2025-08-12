<?php

namespace App\CMSBundle\Lib;

class Paginator {

    private $pageLimit = 10;
    private $limitStart;
    private $limitEnd;
    private $pageNumber;
    private $totalItems;
    private $numberOfpages = NULL;
    private $start = NULL;
    private $stop = NULL;

    /**
     * paginate results
     *
     * @param int $page
     * @param $limit
     * @return array
     */
    public function __construct($totalItems = 10, $page = 1, $pageLimit = 10) {
        $this->pageNumber = $page;
        $this->totalItems = $totalItems;
        $this->pageLimit = $pageLimit;
        $this->limitEnd = $this->pageLimit;
        $this->limitStart = ($page - 1) * $this->pageLimit;
    }

    /**
     * get limitStart
     *
     * @return int
     */
    public function getLimitStart() {
        return $this->limitStart;
    }

    /**
     * get limitEnd
     *
     * @return int
     */
    public function getLimitEnd() {
        return $this->limitEnd;
    }

    /**
     * get start
     *
     * @return int
     */
    private function getStart() {
        return $this->start;
    }

    /**
     * get start
     *
     * @return int
     */
    private function getStop() {
        return (int) $this->stop;
    }

    /**
     * get numberOfpages
     *
     * @return int
     */
    public function getNumberOfpages() {
        return (int) $this->numberOfpages;
    }

    /**
     * get $pageLimit
     *
     * @return int
     */
    public function getPageLimit() {
        return (int) $this->pageLimit;
    }

    public function calculate() {
        $radius = 2;
        $start = 1;
        $pageTotalNumber = ceil($this->totalItems / $this->pageLimit);

        $stop = ( $pageTotalNumber < ( ($radius * 2) + 1) ) ? $pageTotalNumber : ( ($radius * 2) + 1 );
        $pageNumber = $this->pageNumber;

        if ($pageNumber > $radius) {
            $start = $pageNumber - $radius;
            $stop = ( $pageTotalNumber <= ($pageNumber + $radius) ) ? $pageTotalNumber : $pageNumber + $radius;
        }
        $this->start = $start;
        $this->stop = $stop;
        $this->numberOfpages = $pageTotalNumber;
        return $this;
    }

    public function getPagination() {
        $this->calculate();

        if ($this->pageNumber != 1) {
            $return['fisrt'] = 1;
            $return['prev'] = $this->pageNumber - 1;
        }

        for ($i = $this->getStart(); $i <= $this->getStop(); $i++) {
            $return['items'][] = $i;
        }

        if ($this->pageNumber < $this->numberOfpages) {
            $return['next'] = ($this->pageNumber + 1);
            $return['last'] = $this->getNumberOfpages();
        }

        $return['show']['start'] = $this->limitStart + 1;
        $return['show']['end'] = (($this->limitStart + 1 + $this->pageLimit) > $this->totalItems ) ? $this->totalItems : ($this->limitStart + $this->pageLimit);
        $return['ItemsPerPage'] = $this->pageLimit;
        $return['totalItems'] = $this->totalItems;
        $return['currentPage'] = $this->pageNumber;
        return $return;
    }

}
