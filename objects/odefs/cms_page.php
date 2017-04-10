<?php
/**
 * cms_page
 */
$obj_revision = 16;
$parentField = "parent_id";
$unameSettings = "site_id:parent_id:name";

$obj_fields = array(
	// Textual name
	'name' => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	'title' => array(
		'title'=>'Title', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	'f_navmain' => array(
		'title'=>'Show in Main Nav', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array("value"=>"f", "on"=>"null"),
	),

	'f_publish' => array(
		'title'=>'Published', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array("value"=>"f", "on"=>"null"),
	),

	'meta_description' => array(
		'title'=>'Meta Description', 
		'type'=>'text', 
		'subtype'=>'150', 
		'readonly'=>false
	),

	'meta_keywords' => array(
		'title'=>'Meta Keywords', 
		'type'=>'text', 
		'subtype'=>'150', 
		'readonly'=>false
	),

	"time_publish" => array(
		'title'=>'Publish After', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false
	),

	"time_expires" => array(
		'title'=>'Expires', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false
	),

	// Pages can be linked to sites
	"site_id" => array(
		'title'=>'Site', 
		'type'=>'object', 
		'subtype'=>'cms_site', 
		'readonly'=>false,
	),

	// Pages can use a template
	"template_id" => array(
		'title'=>'Template', 
		'type'=>'object', 
		'subtype'=>'cms_page_template', 
		'readonly'=>false,
	),

	// Body
	'data' => array(
		'title'=>'Body', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// Menu Order
	'sort_order' => array(
		'title'=>'Menu Order', 
		'type'=>'number', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The parent page
	"parent_id" => array(
		'title'=>'Parent', 
		'type'=>'object', 
		'subtype'=>'cms_page', 
		'readonly'=>false,
	),

	// Status flag
	'status_id' => array(
		'title'=>'Status',
		'type'=>'fkey',
		'subtype'=>'object_groupings',
		'fkey_table'=>array(
			"key"=>"id", 
			"title"=>"name", 
			"ref_table"=>array(
				"table"=>"object_grouping_mem", 
				"this"=>"object_id", 
				"ref"=>"grouping_id"
			)
		)
	),
);


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Pages";
$view->description = "Display all pages";
$view->fDefault = true;
$view->view_fields = array("name", "uname", "title", "parent_id");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
