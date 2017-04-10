<?php
/**
 * email_campaign object definition
 */
$obj_revision = 21;
$defaultActivityLevel = 5;

$obj_fields = array(
	// Textual name
	'name' => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// The email subject
	'subject' => array(
		'title'=>'Subject', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// Flag to indicate if we are tracking this campaign
	'f_trackcamp' => array(
		'title'=>'Track Campaign', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array(
			"value"=>"t", "on"=>"null"
		),
	),

	// From text name like "First Last" to be sent in the name portion of the From header in the email
	'from_name' => array(
		'title'=>'From Name', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// The email address to be sent from
	'from_email' => array(
		'title'=>'From Email', 
		'type'=>'text', 
		'subtype'=>'512', 
		'readonly'=>false,
	),

	// The html version of the message to send
	'body_html' => array(
		'title'=>'Html Body', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false
	),

	// The plain text version of the message to send
	'body_plain' => array(
		'title'=>'Plain Body', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false
	),

	// Select the type of 'to' list we are using
	'to_type' => array(
		'title'=>'List',
		'type'=>'text',
		'subtype'=>'64',
		'readonly'=>true,
		'optional_values' => array(
			"manual"=>"Manual", "view"=>"View", "condition"=>"Query"
		),
	),

	// Manually defined recipients email address separated by a ','
	'to_manual' => array(
		'title'=>'To', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false
	),

	// View to use when sending this 
	'to_view' => array(
		'title'=>'To View', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false
	),

	// Json encoded list of conditions to use
	'to_conditions' => array(
		'title'=>'To Condition', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>true,
	),

	// Select the design type
	'design_type' => array(
		'title'=>'Design',
		'type'=>'text',
		'subtype'=>'64',
		'readonly'=>true,
		'optional_values' => array(
			"blank"=>"Blank", "template"=>"Use Template"
		),
	),

	// The status of this campaign
	'status' => array(
		'title'=>'Status', 
		'type'=>'integer', 
		'subtype'=>'', 
		'readonly'=>true,
		'default'=>array(
			"value"=>"1", "on"=>"null"
		),
		'optional_values'=>array(
			"1"=>"Draft", "2"=>"Awaiting Approval", "3"=>"Pending", "4"=>"In-Progress", "5"=>"Sent",
		),
	),

	// Timestamp when this campaign should start in the future - if blank then we start immedaitely
	'ts_start' => array(
		'title'=>'Scheduled Send', 
		'type'=>'timestamp',
		'subtype'=>'',
		'readonly'=>false,
	),
    
    // Flag to indicate if we send notification
    'f_confirmation' => array(
        'title'=>'Send Confirmation', 
        'type'=>'bool', 
        'subtype'=>'', 
        'readonly'=>false,
    ),
    
    // Email where we send the confirmation
    'confirmation_email' => array(
        'title'=>'Confirmation Email',
        'type'=>'text',
        'subtype'=>'64',
        'readonly'=>false,
    ),
    

	// Marketing campaign references
	'campaign_id' => array(
		'title'=>'Campaign',
		'type'=>'object',
		'subtype'=>'marketing_campaign'
	),

	// html_template references
	'template_id' => array(
		'title'=>'Template',
		'type'=>'object',
		'subtype'=>'html_template'
	),

	// Id of background job for checking status
	'job_id' => array(
		'title'=>'Job Id', 
		'type'=>'text',
		'subtype'=>'256',
		'readonly'=>true,
	),
    
    // Throttle number of seconds between batch sends
	'throttle_interval' => array(
		'title'=>'Throttle Interval', 
		'type'=>'integer', 
		'subtype'=>'', 
	),
    
    // Throttle number of emails to send per session
	'throttle_number' => array(
		'title'=>'Throttle Num', 
		'type'=>'integer', 
		'subtype'=>'', 
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Email Campaigns";
$view->description = "Display all available HTML templates for all object types";
$view->fDefault = true;
$view->view_fields = array("name", "description");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
