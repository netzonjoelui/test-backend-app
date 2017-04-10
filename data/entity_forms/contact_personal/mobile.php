<row>
	<plugin name='customer_link'></plugin>
</row>
<fieldset name='Details'>
	<field name='first_name'></field>
	<field name='last_name'></field>
	<field name='spouse_name'></field>
	<field name='company'></field>
	<field name='job_title'></field>
	<field name='salutation'></field>
	<field name='groups'></field>
</fieldset>
<fieldset name='Notes'>
	<field name='notes' hidelabel='t' multiline='t'></field>
</fieldset>
<fieldset name='Internet Addresses'>
	<field name='email'></field>
	<field name='email2'></field>
	<field name='email_spouse'></field>
	<field name='website'></field>
	<field name='email_default'></field>
</fieldset>
<fieldset name='Image'>
	<field name='image_id' hidelabel='t' profile_image='t' path='%userdir%/Contact Files/'></field>
</fieldset>
<fieldset name='Important Dates'>
	<field name='birthday'></field>
	<field name='birthday_spouse'></field>
	<field name='anniversary'></field>
</fieldset>
<fieldset name='Phone Numbers'>
	<field name='phone_cell'></field>
	<field name='phone_home'></field>
	<field name='phone_work'></field>
	<field name='phone_fax'></field>
	<field name='ext'></field>
	<field name='phone_pager'></field>
</fieldset>
<fieldset name='Home Address'>
	<field name='street'></field>
	<field name='street2'></field>
	<field name='zip'></field>
	<field name='city'></field>
	<field name='state'></field>
</fieldset>
<fieldset name='Business Address'>
	<field name='business_street'></field>
	<field name='business_street2'></field>
	<field name='business_zip'></field>
	<field name='business_city'></field>
	<field name='business_state'></field>
</fieldset>
<fieldset name='Additional'>
	<all_additional></all_additional>
</fieldset>
<row>
	<objectsref name='View Tasks' obj_type='task' ref_field='contact_id'></objectsref>
</row>
<row>
	<objectsref name='View Events' obj_type='calendar_event'></objectsref>
</row>
