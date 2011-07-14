<?php

/*
Plugin Name: Orphaned Media Import
Plugin URI:
Description: For whatever reason, some times you end up with media in your uploaded content that isn't in the Media Library. This plugin hopes to fix that issue.
Version: 0.01
Author: Shamess
Author URI: http://shamess.info
License: GPL2
*/

require_once ( 'class-orphaned-list-table.php' );

class OrphanedMedia {
	function __construct () {
		add_action ( 'admin_menu', array ( &$this, 'add_menu' ) );
	}
	
	public function add_menu () {
		add_submenu_page ( 'upload.php', 'Orphaned Media', 'Orphaned Media', 'upload_files', 'orphaned', array ( &$this, 'draw_admin_page' ) );
	}
	
	public function draw_admin_page () {
		?>
		<style type="text/css">
			#preview {
				width: 90px;
			}
		</style>
		
		<div class="wrap">
			<div class="icon32" id="icon-upload"><br></div>
			<h2>Orphaned Media</h2>
			<p>For whatever reason, some times you end up with media in your uploaded content that isn't in the Media Library. This page will show you a list of files which are in your uploads folder, but not in your Media Library.</p>
		</div>
		<?php
		$Data = new OrphanedListTable ();
		$Data->prepare_items ();
		$Data->display ();
	}
}

$OrphanedMedia = new OrphanedMedia ();

?>