<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PageNav
 *
 * @author hendra
 */
class PageNav {

    public $page;
    public $totalPage;
    public $totalRow;
    public $showPerPage;
    public $offset;

    public $pageParamName;

    //put your code here
    public function __construct($pageParamName, $data=array()) {
        $this->pageParamName=$pageParamName;
        
        if (isset($data['page'])) {
            $this->page = $data['page'];
        }
        if (isset($data['totalRow'])) {
            $this->totalRow = $data['totalRow'];
        }
        if (isset($data['totalPage'])) {
            $this->totalPage = $data['totalPage'];
        }
        if (isset($data['offset'])) {
            $this->offset = $data['offset'];
        }
        if (isset($data['showPerPage'])) {
            $this->showPerPage = $data['showPerPage'];
        }
    }

    public function show($separator=" ", $prevLabel="Prev", $nextLabel="Next", $firstLabel="First", $lastLabel="Last", $showPages=3) {
        $prev = "";
        $first = "";

        $pages = "";
        if ($showPages > 0) {
            $showNum = floor($showPages / 2);

            $q = preg_replace("/&".$this->pageParamName."=\d+/", '', $_SERVER["QUERY_STRING"]);
            $pageURL = $_SERVER["PHP_SELF"]."?".$q."&".$this->pageParamName."=";

            if ($this->page - $showNum <= 0) {
                // show from 1 to showPage
                for ($i=1;$i<=$showPages && $i <= $this->totalPage;$i++) {
                    if ($i == $this->page) {
                        $pages .= $i.$separator;
                    } else {
                        $pages .= '<a href="'.$pageURL.$i.'">'.$i.'</a>'.$separator;
                    }
                }
            } else if ($this->page + $showNum > $this->totalPage) {
                // show last x pages
                for ($i=$this->totalPage-$showPages+1;$i<=$this->totalPage;$i++) {
                    if ($i == $this->page) {
                        $pages .= $i.$separator;
                    } else {
                        $pages .= '<a href="'.$pageURL.$i.'">'.$i.'</a>'.$separator;
                    }
                }
            } else {
                // show page -+ showNum
                for ($i=$this->page-$showNum;$i<=$this->page+$showNum;$i++) {
                    if ($i == $this->page) {
                        $pages .= $i.$separator;
                    } else {
                        $pages .= '<a href="'.$pageURL.$i.'">'.$i.'</a>'.$separator;
                    }
                }
            }
            
        }

        if ($this->page > 1) {
            $q = preg_replace("/&".$this->pageParamName."=\d+/", '', $_SERVER["QUERY_STRING"]);
            $prevPageURL = $_SERVER["PHP_SELF"]."?".$q."&".$this->pageParamName."=".($this->page-1);
            $prev = '<a href="'.$prevPageURL.'">'.$prevLabel.'</a>';

            $firstPageURL = $_SERVER["PHP_SELF"]."?".$q."&".$this->pageParamName."=1";
            $first= '<a href="'.$firstPageURL.'">'.$firstLabel.'</a>';
        }
        $next = "";
        $last = "";
        if ($this->page < $this->totalPage) {
            $q = preg_replace("/&".$this->pageParamName."=\d+/", '', $_SERVER["QUERY_STRING"]);
            $nextPageURL = $_SERVER["PHP_SELF"]."?".$q."&".$this->pageParamName."=".($this->page + 1);
            $next = '<a href="'.$nextPageURL.'">'.$nextLabel.'</a>';

            $lastPageURL = $_SERVER["PHP_SELF"]."?".$q."&".$this->pageParamName."=".($this->totalPage);
            $last= '<a href="'.$lastPageURL.'">'.$lastLabel.'</a>';
        }
        ?>
        Displaying <?=$this->offset+1?>–<?=$this->offset+$this->showPerPage?> of <?=$this->totalRow?> <?=$first?><?=$separator?><?=$prev?><?=$separator?><?=$pages?><?=$separator?><?=$next?><?=$separator?><?=$last?>
        <?
    }
}
?>
