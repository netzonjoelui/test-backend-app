<fieldset name='Status &amp; Admin'>
	<field name='status_id'></field>
	<field name='source_id'></field>
	<field name='rating_id'></field>
	<field name='class_id'></field>
	<field name='owner_id'></field>
	<field name='queue_id'></field>
</fieldset>
<fieldset name='Contact Information'>
	<field name='first_name'></field>
	<field name='last_name'></field>
	<field name='company'></field>
	<field name='title'></field>
	<field name='website'></field>
	<field name='email'></field>
	<field name='phone'></field>
	<field name='phone2'></field>
	<field name='phone3'></field>
	<field name='fax'></field>
</fieldset>
<fieldset name='Physical Address'>
	<field name='street'></field>
	<field name='zip'></field>
	<field name='city'></field>
	<field name='street2'></field>
	<field name='state'></field>
	<field name='country'></field>
</fieldset>
<fieldset name='Additional'>
	<all_additional></all_additional>
</fieldset>
<fieldset name='Description'>
	<field name='notes' hidelabel='t' multiline='t'></field>
</fieldset>
<fieldset name='Comments'>
	<field name='comments'></field>
</fieldset>

<row>
	<objectsref name='View Tasks' obj_type='task'></objectsref>
</row>
<row>
	<objectsref name='View Calendar Events' obj_type='calendar_event'></objectsref>
</row>
<plugin name='convert'></plugin>
