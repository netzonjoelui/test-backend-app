<fieldset name='General'>
	<row>
		<field name='name' hidelabel='t' class='formTitle'></field>
	</row>

	<row>
		<field name='notes' hidelabel='t' multiline='t'></field>
	</row>

	<row>
		<field name='priority' class='compact'></field>
	</row>
	<row>
		<field name='parent' class='compact'></field>
	</row>
	<row>
		<field name='user_id' class='compact'></field>
	</row>
	<row>
		<field name='customer_id' class='compact'></field>
	</row>
	<row>
		<field name='date_started' class='compact'></field>
	</row>
	<row>
		<field name='date_deadline' class='compact'></field>
	</row>
	<row>
		<field name='date_completed' class='compact'></field>
	</row>
</fieldset>

<fieldset name='Files'>
	<row>
		<field hidelabel='t' name='folder_id'></field>
	</row>
</fieldset>

<row>
	<objectsref obj_type='task' ref_field='project'></objectsref>
</row>
<row>
	<objectsref obj_type='case' ref_field='project_id'></objectsref>
</row>
<row>
	<objectsref obj_type='project_story' ref_field='project_id'></objectsref>
</row>
<row>
	<objectsref obj_type='discussion'></objectsref>
</row>
<row>
	<objectsref obj_type='project_milestone' ref_field='project_id'></objectsref>
</row>

	
<plugin name='onSaveHooks'></plugin>
