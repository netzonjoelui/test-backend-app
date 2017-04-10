<?php
/**
 * cms_site object definition
 */
$obj_revision = 7;

$obj_fields = array(
	// Textual name
	'name' => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// The production URL
	'url' => array(
		'title'=>'URL', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// The testing URL
	'url_test' => array(
		'title'=>'TEST URL', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// Media folder
	'folder_id' => array(
		'title'=>'Media', 
		'type'=>'object', 
		'subtype'=>'folder', 
		'readonly'=>false,
	    'autocreate'=>true, // Create foreign object automatically
	    'autocreatebase'=>'/System/Objects/cms_site', // Where to create (for folders, the path with no trail slash)
	    'autocreatename'=>'id', // the field to pull the new object name from
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Sites";
$view->description = "Display all available sites";
$view->fDefault = true;
$view->view_fields = array("name", "url");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
