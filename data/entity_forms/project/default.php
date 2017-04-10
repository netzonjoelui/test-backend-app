<tab name='General'>
	<column>
		<row editmodeonly='t'>
			<fieldset name='Title'>
				<field name='name' hidelabel='t' class='formTitle'></field>
			</fieldset>
		</row>

		<fieldset name='Description'>
			<field name='notes' hidelabel='t' multiline='t'></field>
		</fieldset>	

		<fieldset name='Additional'>
			<all_additional></all_additional>
		</fieldset>	

		<row>
			<status_update />
		</row>
		<row>
			<field name='activity' view_id='sys_1' hidelabel='t'></field>
		</row>
	</column>
	<column width='220px'>
		<fieldset name='Details'>
			<row>
				<field name='priority' class='compact'></field>
			</row>
			<row>
				<field name='parent' class='compact' tooltip='A project can be a child of much larger projects which allows for smaller teams working on massive projects. This is not a commonly used feature, few projects are of that scale; but if you find a project has too much noise from all the people and activity it might be helpful to split out subprojects and make smaller teams.'></field>
			</row>
			<row>
				<field name='user_id' class='compact' tooltip='Each project must have one responisble owner even though many members may be working on the project.'></field>
			</row>
			<row>
				<field name='customer_id' class='compact'></field>
			</row>
			<row>
				<field name='date_started' class='compact'></field>
			</row>
			<row>
				<field name='date_deadline' class='compact' tooltip='If no deadline is set, this will be considered an ongoing project.'></field>
			</row>
			<row>
				<field name='date_completed' class='compact' tooltip='Once the project has been completed, enter the date here.'></field>
			</row>
			
		</fieldset>

		<fieldset name='Groups'>
			<field name='groups' hidelabel='t'></field>
		</fieldset>
	</column>
</tab>

<tab name='Tasks'>
	<objectsref obj_type='task' ref_field='project'></objectsref>
</tab>

<tab name='Cases'>
	<objectsref obj_type='case' ref_field='project_id'></objectsref>
</tab>

<tab name='Stories'>
	<objectsref obj_type='project_story' ref_field='project_id'></objectsref>
</tab>

<tab name='Discussions'>
	<objectsref obj_type='discussion'></objectsref>
</tab>

<tab name='Milestones'>
	<objectsref obj_type='project_milestone' ref_field='project_id'></objectsref>
</tab>

<tab name='Files'>
	<field hidelabel='t' name='folder_id'></field>
</tab>

<tab name='Members'>
	<plugin name='members'></plugin>
</tab>
	
<plugin name='onSaveHooks'></plugin>
