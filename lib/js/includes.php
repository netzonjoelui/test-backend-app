<?php
	if (!defined("APPLICATION_PATH"))
		require_once("../AntConfig.php");

	$libs = array(
		"/lib/js/Ant.js", 

		"/lib/js/global.js",
		"/lib/js/CReport.js", 
		"/lib/js/Report.js", 
		"/customer/CCustActivity.js", 
		"/customer/CCustomerBrowser.js", 
		"/lib/js/CWidgetBox.js", 
		"/lib/js/CFlashObj.js", 
		"/lib/js/CVideoPlayer.js", 
		"/users/CUserBrowser.js",
		"/objects/CActivity.js", 
		"/contacts/contact_functions.js", 
		"/project/CProjectStart.js",
		"/customer/customer_functions.js", 
		"/calendar/calendar_functions.js", 
		"/project/project_functions.js", 
		"/email/email_functions.js",  
		"/lib/js/CRecurrencePattern.js", 

		// Base
		"/lib/js/AntUpdateStream.js", 
		"/lib/js/NewObjectTool.js", 
		"/lib/js/DaclEdit.js", 
		"/lib/js/Emailer.js", 

		// WorkFlow
		"/lib/js/WorkFlow.js", 
		"/lib/js/WorkFlow/Selector/User.js", 
		"/lib/js/WorkFlow/Selector/MergeField.js", 
		"/lib/js/WorkFlow/Action.js", 
        "/lib/js/WorkFlow/Action/Child.js", 
		"/lib/js/WorkFlow/Action/Invoice.js", 
		"/lib/js/WorkFlow/Action/Task.js", 
		"/lib/js/WorkFlow/Action/Notification.js", 
		"/lib/js/WorkFlow/Action/Email.js", 
        "/lib/js/WorkFlow/Action/Approval.js", 
        "/lib/js/WorkFlow/Action/CallPage.js", 
        "/lib/js/WorkFlow/Action/AssignRR.js", 
		"/lib/js/WorkFlow/Action/Update.js",
		"/lib/js/WorkFlow/Action/WaitCondition.js",
		"/lib/js/WorkFlow/Action/CheckCondition.js",
		"/lib/js/WorkFlow/ActionsGrid.js", 

		// AntView(s) and routers
		"/lib/js/AntViewsRouter.js",
		"/lib/js/AntViewManager.js",
		"/lib/js/AntView.js",

		// AntObject
		"/lib/js/AntObjectForms.js", 
		"/lib/js/CAntObject.js", 
		"/lib/js/CAntObjects.js", 
		"/lib/js/CAntObjectView.js", 
		"/lib/js/AntObjectInfobox.js", 
		"/lib/js/AntObjectGroupingSel.js", 
		"/lib/js/AntObjectTypeSel.js", 
		"/lib/js/CAntObjectCond.js",
		//"/lib/js/CAntObjectBrowser.js", 
		"/lib/js/EntityDefinitionEdit.js",
		"/lib/js/CAntObjectMergeWizard.js", 
		//"/lib/js/CAntObjectImpWizard.js",   // Replaced with EntityImport AntWizard
        //"/lib/js/CAntObjectFrmEditor.js", // Depricated
		"/lib/js/AntObjectFormEditor.js", 

		// Entity - Replacing AntObject due to reserved namespace of Object
		"/lib/js/EntityDefinition.js",
		"/lib/js/EntityDefinition/Field.js",
		"/lib/js/EntityDefinitionLoader.js",

		// AntObjectTemp
		"/lib/js/AntObjectTemp.js", 
		"/lib/js/AntObjectTempLoader.js", 

		// New browser and list
		"/lib/js/AntObjectList.js", 
		"/lib/js/AntObjectBrowser.js", 
		"/lib/js/ObjectBrowser/Item.js", 
		"/lib/js/ObjectBrowser/Item/Activity.js", 
		"/lib/js/ObjectBrowser/Item/Notification.js", 
		"/lib/js/ObjectBrowser/Item/StatusUpdate.js", 
		"/lib/js/ObjectBrowser/Item/Comment.js", 
		"/lib/js/ObjectBrowser/Toolbar.js", 
		"/lib/js/ObjectBrowser/Toolbar/EmailThread.js", 
        "/lib/js/AntObjectBrowserView.js", 
        "/lib/js/AntObjectViewEditor.js", 
		"/lib/js/AntCalendarBrowse.js", 

		// AntFs
		"/lib/js/AntFsBrowser.js", 
		"/lib/js/AntFsOpen.js", 
		"/lib/js/AntFsUpload.js", 
		"/lib/SWFUpload/swfupload.js", 
		"/lib/SWFUpload/plugins/swfupload.queue.js", 

		// Olap
		"/lib/js/COlapCube.js", 
		"/lib/js/OlapCube.js", 
        "/lib/js/OlapCube/Query.js", 
		"/lib/js/OlapCube/Graph.js", 
		"/lib/js/OlapCube/Table/Tabular.js", 
		"/lib/js/OlapCube/Table/Summary.js", 
		"/lib/js/OlapCube/Table/Matrix.js", 
        
        // Olap Report        
        "/lib/js/OlapReport/Dialog.js", 
        "/lib/js/OlapReport/Tabular.js", 
        "/lib/js/OlapReport/Summary.js", 
        "/lib/js/OlapReport/PivotMatrix.js", 

        // Class Objects
        "/lib/js/Object/User.js", 
        
		// ANT Application
		"/lib/js/AntApp.js", 
		"/lib/js/AntAppSettings.js", 
		"/lib/js/AntAppDash.js", 

		// Notifications
		"/lib/js/NotificationMan.js", 

		// Searcher
		"/lib/js/Searcher.js", 

		// ANT Chat
		"/lib/js/AntChatMessenger.js", 
		"/lib/js/AntChatClient.js",

		// Help notifications and tours
		"/lib/js/HelpTour.js", 

		// "/email/CEmailThreadViewer.js",
		// "/lib/js/CAntObjectLoader.js",
		// "/lib/js/CAntObjectForm.js",
		// "/lib/js/CAntObjectFormMem.js",
		// "/lib/js/CEmailViewer.js",

		// Object loaders
		"/lib/js/AntObjectLoader.js", // Base class
		"/lib/js/ObjectLoader/Form.js", // UIML default class
        "/lib/js/ObjectLoader/Plugin/global/Mem.js", // Global members plugin for forms
        "/lib/js/ObjectLoader/Plugin/global/Attachments.js", // Global attachment plugin for forms
		"/lib/js/ObjectLoader/Plugin/global/Uname.js", // Global Uname plugin for forms
		"/lib/js/ObjectLoader/Plugin/global/StatusUpdate.js", // Status update plugin - used for projects and big items
		"/lib/js/ObjectLoader/Plugin/global/Reminders.js", // Status update plugin - used for projects and big items
		"/objects/loaders/EmailThread.js",
		"/objects/loaders/EmailMessage.js",
        "/objects/loaders/EmailMessageCmp.js",
        //"/objects/loaders/User.js",
        "/objects/loaders/Report.js",
        "/objects/loaders/Dashboard.js",
		//"/objects/loaders/Calendar.js",

		// Object fileds fields - used mostly for forms
        "/lib/js/Object/FieldInput.js", 
        "/lib/js/Object/FieldInput/Alias.js", 
        "/lib/js/Object/FieldInput/Bool.js", 
        "/lib/js/Object/FieldInput/Date.js", 
        "/lib/js/Object/FieldInput/Grouping.js", 
        "/lib/js/Object/FieldInput/Number.js", 
        "/lib/js/Object/FieldInput/Object.js", 
        "/lib/js/Object/FieldInput/OptionalValues.js", 
        "/lib/js/Object/FieldInput/Text.js", 
        "/lib/js/Object/FieldInput/Timestamp.js", 

		// Validators
        "/lib/js/Object/FieldValidator.js", 
        
        // Email Includes
        "/lib/spell/spellcheck.js",
        "/email/CVideoWizard.js",

		// Wizards
		"/lib/js/AntWizard.js", 
		"/wizards/WorkflowWizard.js", 
	);
                  
	$dashboardWidgets = array(
							"/widgets/CWidWelcome.js", 
							"/widgets/CWidWeather.js", 
							"/widgets/CWidStocks.js", 
							"/widgets/CWidTasks.js", 
							"/widgets/CWidFriends.js",
							"/widgets/CWidSettings.js", 
							"/widgets/CWidBookmarks.js", 
							"/widgets/CWidRssManager.js", 
							"/widgets/CWidWebsearch.js",
							"/widgets/CWidCalendar.js", 
							"/widgets/CWidRss.js", 
							"/widgets/CWidReport.js", 
							"/widgets/CWidgetBrowser.js",
							"/widgets/CWidWebpage.js", 
							"/widgets/CWidActivity.js");    

	// Combine all scripts into a single file
	foreach ($libs as $lib)
	{
		if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == "build")
		{
			include(APPLICATION_PATH . $lib);
			echo "\n";
		}
		else
		{
			echo '<script type="text/javascript" src="'.$lib.'"></script>'."\n";
		}
	}
        
	// Widgets are handled differently because we will eventually be loading these dynamically
    foreach ($dashboardWidgets as $widget)
	{
		if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == "build")
		{
			include(APPLICATION_PATH . $widget);
			echo "\n";
		}
		else
		{
			echo '<script type="text/javascript" src="'.$widget.'"></script>'."\n";
		}
	}
?>
