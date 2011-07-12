<?php

class OrphanedListTable extends WP_List_Table () {
	function __construct ( $args = array() ) {
		parent::__construct( array(
			'singular' => 'file',
			'plural'   => 'files',
			'ajax'     => false
		) );
	}
	
	function column_cb ( $item ){
		return sprintf (
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'add_to_media',
			/*$2%s*/ $item['file_uri']
		);
	}
	
	function get_columns () {
		$columns = array(
			'cb'      => '<input type="checkbox" />', //Render a checkbox instead of text
			'preview' => '',
			'file'    => 'File',
			'date'    => 'Date'
		);
		
		return $columns;
	}
	
	function prepare_items () {
		$per_page = 5;
		
		$columns = $this->get_columns ();
		$hidden = array();
		$sortable = array ();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$data = $this->getData ();
	}
	
	/**
	* Compares what files are in the upload directory, and finds any that aren't in the Media Library, and
	* returns them.
	*
	* @return array
	*/
	private function getData () {
		
	}
}

?>