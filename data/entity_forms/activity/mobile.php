<fieldset name='Details'>
	<row>
		<field name='title'></field>
		<field name='severity_id'></field>
		<field name='type_id'></field>
		<field name='owner_id'></field>
		<field name='project_id'></field>
		<field name='status_id'></field>
		<field name='customer_id'></field>
		<field name='ts_entered'></field>
	</row>
	<row>
		<all_additional></all_additional>
	</row>
</fieldset>
<fieldset name='Description'>
	<field name='description' hidelabel='t' multiline='t'></field>
</fieldset>
<fieldset name='Comments'>
	<field name='comments'></field>
</fieldset>
<row>
	<objectsref name='View Tasks' obj_type='task' ref_field='case_id'></objectsref>
</row>
<row>
	<objectsref name='View Events' obj_type='calendar_event'></objectsref>
</row>
