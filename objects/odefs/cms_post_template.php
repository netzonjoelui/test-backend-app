<?php
/**
 * cms_post_template
 *
 * TODO: it is undecided if we are going to use templates as of yet
 */
$obj_revision = 6;

$obj_fields = array(
	// Textual name
	'name' => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// Posts can be linked to sites
	"site_id" => array(
		'title'=>'Site', 
		'type'=>'object', 
		'subtype'=>'cms_site', 
		'readonly'=>false,
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Site Display Templates";
$view->description = "Display all templates";
$view->fDefault = true;
$view->view_fields = array("name", "url");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
