<fieldset name='Details'>
	<row>
		<field name='type_id'></field>
	</row>
	<row showif='type_id=2'>
		<field name='name'></field>
		<field name='primary_contact'></field>
	</row>
	<row showif='type_id=1'>
		<field name='first_name'></field>
		<field name='last_name'></field>
		<field name='spouse_name'></field>
	</row>
	<row showif='type_id=1'>
		<field name='company'></field>
		<field name='job_title'></field>
		<field name='salutation'></field>
	</row>
	<row>
		<field name='status_id'></field>
	</row>
	<row>
		<field name='stage_id'></field>
	</row>
	<row>
		<field name='owner_id'></field>
	</row>
</fieldset>
<fieldset name='Groups'>
	<field name='groups' hidelabel='t'></field>
</fieldset>
<fieldset name='Internet Addresses'>
	<field name='email'></field>
	<field name='email2'></field>
	<field name='email3'></field>
	<field name='email_spouse'></field>
	<field name='website'></field>
	<field name='email_default'></field>
</fieldset>
<fieldset name='Phone Numbers'>
	<field name='phone_cell'></field>
	<field name='phone_home'></field>
	<field name='phone_work'></field>
	<field name='phone_fax'></field>
	<field name='phone_ext'></field>
	<field name='phone_pager'></field>
</fieldset>
<fieldset name='Additional'>
	<column>
		<all_additional></all_additional>
	</column>
</fieldset>
<fieldset name='Important Dates'>
		<field name='birthday'></field>
		<field name='birthday_spouse'></field>
		<field name='anniversary'></field>
		<field name='last_contacted'></field>
</fieldset>
<fieldset name='Billing Address'>
	<field name='billing_street'></field>
	<field name='billing_street2'></field>
	<field name='billing_zip'></field>
	<field name='billing_city'></field>
	<field name='billing_state'></field>
</fieldset>
<fieldset name='Shipping Address'>
	<field name='shipping_street'></field>
	<field name='shipping_street2'></field>
	<field name='shipping_zip'></field>
	<field name='shipping_city'></field>
	<field name='shipping_state'></field>
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
<fieldset name='Contact Rules'>
	<field name='f_nocall'></field>
	<field name='f_noemailspam'></field>
	<field name='f_nocontact'></field>
</fieldset>
<fieldset name='About'>
	<field name='notes' hidelabel='t' multiline='t'></field>
</fieldset>

<row>
	<objectsref name='View Tasks' obj_type='task' ref_field='customer_id'></objectsref>
</row>
<row>
	<objectsref name='View Calendar Events' obj_type='calendar_event' ref_field='customer_id'></objectsref>
</row>
<row>
	<objectsref name='View Projects' obj_type='project' ref_field='customer_id'></objectsref>
</row>
<row>
	<objectsref name='View Cases' obj_type='case' ref_field='customer_id'></objectsref>
</row>
<row>
	<objectsref name='View Discussions' obj_type='discussion'></objectsref>
</row>

<fieldset name='Comments'>
	<field name='comments'></field>
</fieldset>
