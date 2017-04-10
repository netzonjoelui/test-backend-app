<?php 
	$DACL_ADMIN = new Dacl($dbh, "administration"); 
	if (!$DACL_ADMIN->id)
	{
		$DACL_ADMIN->grantGroupAccess(GROUP_ADMINISTRATORS, "Edit System Settings");
		$DACL_ADMIN->save("administration");
	}
?>
<navigation default='Profile'>
	<section title='Settings'>
		<item type='plugin' name='Profile' class='Plugin_Settings_Profile' title='My Profile' icon="/images/icons/profile_16.png" />
		<?php if ($DACL_ADMIN->checkAccess($USER, "Edit System Settings")) { ?>
		<item type='plugin' name='General' class='Plugin_Settings_General' title='General Settings' icon="/images/icons/world_16.png" />
		<item type='plugin' name='Account' class='Plugin_Settings_Account' title='Account &amp; Billing' icon="/images/icons/dollar.png" />
		<item type='browse'	name='users' title='Users' obj_type='user' icon="/images/icons/user_16.png" />
		<item type='plugin' name='Groups' class='Plugin_Settings_Groups' title='Groups' icon="/images/icons/permissions_16.png" />
		<item type='plugin' name='Teams' class='Plugin_Settings_Teams' title='Teams' icon="/images/icons/users_16.png" />
        <item type='plugin' name='Applications' class='Plugin_Settings_Applications' title='Applications' icon="/images/icons/windows_16.png" />
		<item type='plugin' name='Workflow' class='Plugin_Settings_Workflow' title='Workflows' icon="/images/icons/gear_16.png" />
		<item type='plugin' name='Objects' class='Plugin_Settings_Objects' title='Manage Objects' icon="/images/icons/database_16.png" />
		<item type='plugin' name='System_Email' class='Plugin_Settings_Email' title='System Email Settings' icon="/images/icons/16-tool-b.png" />
		<?php } ?>
		<item type='plugin' name='My_Email' class='Plugin_Messages_Settings' title='My Email Settings' icon="/images/icons/email_16.png" />
	</section>
</navigation>
