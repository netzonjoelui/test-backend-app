<?php
/**************************************************************************************
*
*	Object Definition: project_milestone 
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2011 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 21;

$obj_fields = array();
$obj_fields['name']			    = array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['notes']		    = array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['price']			= array('title'=>'Price', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>false);
$obj_fields['f_available']		= array('title'=>'Available', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, 'default'=>array("value"=>"t", "on"=>"null"));
$obj_fields['rating']			= array('title'=>'Rating', 'type'=>'number', 'subtype'=>'integer', 'readonly'=>false);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$obj_fields['family'] = array('title'=>'Product Family', 'type'=>'object', 'subtype'=>'product_family', 'readonly'=>false);

$obj_fields['categories'] = array('title'=>'Categories',
								  'type'=>'fkey_multi',
								  'subtype'=>'product_categories',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"product_categories_mem", 
																									"this"=>"category_id", 
																									"ref"=>"product_id"
																								   )));

$obj_fields['reviews'] = array('title'=>'Reviews', 'type'=>'object_multi', 'subtype'=>'review', 'readonly'=>false);

$obj_fields['image_id'] = array('title'=>'Image',
									  'type'=>'object',
									  'subtype'=>'file',
									  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));

$obj_fields['related_products'] = array('title'=>'Related Products', 'type'=>'object_multi', 'subtype'=>'product', 'readonly'=>false);


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Default View: All Products";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "price", "notes", "ts_updated", "ts_entered");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "desc");
$obj_views[] = $view;
unset($view);

?>
