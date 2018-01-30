<?php
class Paginator {
	private $_db;
	private $_limit;
	private $_page;
	private $_query;
	private $_total;
	public function __construct($db, $query ) {
		$this->_db = $db;
		$this->_query = $query;
		$rs= $this->_db->table($this->_query);
		$sqlCount = "SELECT Count(*) c ".substr($this->_query, strpos($this->_query, "FROM"));
		$this->_total = $this->_db->cell($sqlCount);
	}
	public function getData( $limit = 10, $page = 1 ) {
		$this->_limit = $limit;
		$this->_page = $page;
		if ( $this->_limit == 'all' ) {
			$query = $this->_query;
		} else {
			$query= $this->_query . " LIMIT " . ( ( $this->_page - 1 ) * $this->_limit ) . ", $this->_limit";
		}
		$rs = $this->_db->table( $query );
		
		$result = new stdClass();
		$result->page = $this->_page;
		$result->limit = $this->_limit;
		$result->total = $this->_total;
		$result->data  = $rs;
		
		return $result;
	}
	public function createLinks( $links, $list_class ) {
		if ( $this->_limit == 'all' ) {
			return '';
		}
		
		$last= ceil( $this->_total / $this->_limit );
		
		$start = ( ( $this->_page - $links ) > 0 ) ? $this->_page - $links : 1;
		$end        = ( ( $this->_page + $links ) < $last ) ? $this->_page + $links : $last;
		
		$html= '<ul class="' . $list_class . '">';
		
		$class = 'paginate_button '.( $this->_page == 1 ) ? "disabled" : "";
		$html .= '<li class="' . $class . '"><a style="cursor:pointer;" href="?limit=' . $this->_limit . '&page=' . ( $this->_page - 1 ) . '">&laquo;</a></li>';
		
		if ( $start > 1 ) {
			$html   .= '<li><a href="?limit=' . $this->_limit . '&page=1">1</a></li>';
			$html   .= '<li  class="paginate_button disabled"><span>...</span></li>';
		}
		
		for ( $i = $start ; $i <= $end; $i++ ) {
			$class  = 'paginate_button '.( $this->_page == $i ) ? "active" : "";
			$html   .= '<li  class="' . $class . '"><a style="cursor:pointer;" href="?limit=' . $this->_limit . '&page=' . $i . '">' . $i . '</a></li>';
		}
		
		if ( $end < $last ) {
			$html   .= '<li class="paginate_button disabled"><span>...</span></li>';
			$html   .= '<li><a style="cursor:pointer;" href="?limit=' . $this->_limit . '&page=' . $last . '">' . $last . '</a></li>';
		}
		
		$class      = 'paginate_button '.( $this->_page == $last ) ? "disabled" : "";
		$html       .= '<li class="' . $class . '"><a style="cursor:pointer;" href="?limit=' . $this->_limit . '&page=' . ( $this->_page + 1 ) . '">&raquo;</a></li>';
		
		$html       .= '</ul>';
		
		return $html;
}
}