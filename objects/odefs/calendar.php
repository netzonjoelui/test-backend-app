<?php
/**
 * calendar object definition
 */
$obj_revision = 14;

$obj_fields = array(
	"name" => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),
	
	'f_public' => array(
		'title'=>'Public', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=> array(
			"on"=>"null",
			"value"=>"f", 
		),
	),
);

$obj_fields['f_view']                  = array('title'=>'Visible', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['def_cal']                 = array('title'=>'Default', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']    = array('title'=>'Time Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']    = array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>-3, "on"=>"null");

$obj_fields['user_id'] = array('title'=>'User',
                                      'type'=>'object',
                                      'subtype'=>'user',
                                        'default'=>$default);

$obj_fields['owner_id'] = array('title'=>'Owner',
                                      'type'=>'object',
                                      'subtype'=>'user',
                                        'default'=>$default);
                                                                        
// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Calendars";
$view->description = "Viewing All Calendars";
$view->fDefault = true;
$view->view_fields = array("name", "description", "user_id");
$view->conditions[] = new CAntObjectCond("and", "f_public", "is_equal", "t");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
