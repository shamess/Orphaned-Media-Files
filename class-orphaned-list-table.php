<?php

if ( ! class_exists ( 'WP_List_Table' ) ) {
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class OrphanedListTable extends WP_List_Table {
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
		$sortable = array();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$data = $this->getData ();
	}
	
	/**
	* Compares what files are in the upload directory, and finds any that aren't in the Media Library, and
	* returns them.
	*
	* @return array
	*/
	function getData () {
		global $wpdb;
	
		$orphaned_files = array();
	
		// where's the upload folder?
		$wp_upload_dir = wp_upload_dir ();
		$upload_folder = $wp_upload_dir['basedir'];
		
		// we'll start checking from the upload folder.
		// this variable will contain an array of the folders we need to look into.
		$to_check = array(NULL);
		
		// Whilst there's an element in $to_check, we'll keep checking it.
		while ( count ( $to_check ) ) {
			$to_check[0] .= "/";
			$full_path = $upload_folder.$to_check[0];
			
			$scanned_dir = scandir ( $full_path );
			
			foreach ( $scanned_dir as $path ) {
				if ( $path == '.' || $path == '..' ) continue;
				
				if ( is_dir ( $full_path.$path ) ) {
					// since it's a directory, we need to look through that too.
					$to_check[] = $to_check[0].$path;
				} else {
					$post_id = $wpdb->get_var ( 'SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key = "_wp_attached_file" AND meta_value = "'.trim ($to_check[0].$path, '/').'"' );
					if ( ! (bool) $post_id ) {
						$orphaned_files[] = trim ($to_check[0].$path, '/');
					}
				}
			}
			
			// remove [0]...
			array_shift ( $to_check );
		}
	}
}

?>