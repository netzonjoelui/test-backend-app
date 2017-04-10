<?php
/**
 * cms_page_template
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

	// Pages can be linked to sites
	"site_id" => array(
		'title'=>'Site', 
		'type'=>'object', 
		'subtype'=>'cms_site', 
		'readonly'=>false,
	),

	// Type : blank page, module
	'type' => array(
		'title'=>'Type',
		'type'=>'text',
		'subtype'=>'32',
		'optional_values'=>array("blank"=>"Blank Page", "module"=>"Module"),
	),

	// Type : blank page, module
	'module' => array(
		'title'=>'Module Name',
		'type'=>'text',
		'subtype'=>'128',
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Templates";
$view->description = "Display all templates";
$view->fDefault = true;
$view->view_fields = array("name", "type");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
