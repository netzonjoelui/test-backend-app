<?php
/**
 * cms_snippet object definition
 */
$obj_revision = 13;

$obj_fields = array(
	'name' => array(
		'title'=>'Title', 
		'type'=>'text', 
		'subtype'=>'128', 
		'readonly'=>false
	),	

	'data' => array(
		'title'=>'Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>false
	),

	// Posts can be linked to sites
	"site_id" => array(
		'title'=>'Site', 
		'type'=>'object', 
		'subtype'=>'cms_site', 
		'readonly'=>false,
	),

	// Snippets can also be linked to pages
	"page_id" => array(
		'title'=>'Page', 
		'type'=>'object', 
		'subtype'=>'cms_page', 
		'readonly'=>false,
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Snippets";
$view->description = "All Snippets";
$view->fDefault = true;
$view->view_fields = array("name");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "desc");
$obj_views[] = $view;
unset($view);
