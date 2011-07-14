<?php

if ( ! class_exists ( 'WP_List_Table' ) ) {
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class OrphanedListTable extends WP_List_Table {
	function __construct( $args = array() ) {
		parent::__construct( array(
			'singular' => 'file',
			'plural'   => 'files',
			'ajax'     => false
		) );
	}
	
	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}
	
	function column_preview( $item ) {
		$wp_upload_dir = wp_upload_dir ();
		$upload_folder = $wp_upload_dir['baseurl'];
		
		return '<img style="max-height: 60px;max-width: 80px;" src="'.$upload_folder.$item['file'].'" />';
	}
	
	function column_date( $item ) {
		if ( ( abs( time() - $item['date'] ) ) < 86400 ) {
			$time = human_time_diff( $item['date'] )." ago";
		} else {
			$time = date( 'Y/m/d', $item['date'] );
		}
	
		return $time;
	}
	
	function column_file( $item ) {
		$actions = array( 'add' => '<a href="?page=orphaned&amp;add=true&amp;file='.urlencode( $item['file'] ).'">Add to Media Library</a>' );
		
		return $item['file'].$this->row_actions($actions);
	}
	
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'add_to_media',
			/*$2%s*/ $item['file']
		);
	}
	
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />', //Render a checkbox instead of text
			'preview' => '',
			'file'    => 'File',
			'date'    => 'Date'
		);
		
		return $columns;
	}
	
	/**
	* Watches for requests to do something with the files, and then does it.
	*/
	function handle_actions () {
		if ($_GET['add'] == true && isset( $_GET['file'] )) {
			$path_parts = pathinfo( $_GET['file'] );
			
			$wp_upload_dir = wp_upload_dir ();
			$upload_folder = $wp_upload_dir['basedir'];
		
			// There's no "add this file to library" function that doesn't also try to upload it, so we'll have to take
			// parts of the code out ourselves to use here.
			// This is a bastardised version of media_handle_sideload(), from wp-admin/includes/media.php
			function add_to_media($file) {
				$url = $file['url'];
				$type = $file['type'];
				$file = $file['file'];
				$title = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
				$content = '';

				// use image exif/iptc data for title and caption defaults if possible
				if ( $image_meta = @wp_read_image_metadata($file) ) {
					if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
						$title = $image_meta['title'];
					if ( trim( $image_meta['caption'] ) )
						$content = $image_meta['caption'];
				}

				// Construct the attachment array
				$wp_filetype = wp_check_filetype( basename( $file ), null );
				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'guid' => $url,
					'post_parent' => 0,
					'post_title' => $title,
					'post_content' => $content,
					'post_status' => 'inherit'
				);
				
				// Save the attachment metadata
				$id = wp_insert_attachment( $attachment, $file );
				if ( !is_wp_error($id) )
					wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

				return $id;
			}
			
			$file_array = array (
				'file' => $upload_folder.$_GET['file'],
				'url' => $wp_upload_dir['url']."/".$_GET['file'],
				'type' => ''
			);
			
			add_to_media( $file_array );
		}
	}
	
	function prepare_items() {
		$this->handle_actions ();
	
		$per_page = 10;
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$data = $this->getData ();
		
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		
		$this->items = $data;
		
		$this->set_pagination_args ( array(
		            'total_items' => $total_items,
		            'per_page'    => $per_page,
		            'total_pages' => ceil($total_items/$per_page)
		) );
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
						$orphaned_files[] = $to_check[0].$path;
					}
				}
			}
			
			// remove [0]...
			array_shift ( $to_check );
		}
		
		// now we can format these files in a way that the list table object needs them
		$data = array();
		foreach ( $orphaned_files as $file_path ) {
			$data[] = array (
			                 'file' => $file_path,
			                 'date' => filemtime ( $upload_folder.$file_path )
			);
		}
		
		return $data;
	}
}

?>