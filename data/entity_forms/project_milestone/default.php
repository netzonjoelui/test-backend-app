<tab name='General'>
		<fieldset name='Details'>
			<field name='name'></field>
			<field name='date_start'></field>
			<field name='deadline'></field>
			<field name='project_id'></field>
			<field name='user_id'></field>
			<field name='f_completed'></field>
		</fieldset>

		<fieldset name='Description'>
			<field name='notes' hidelabel='t' multiline='t'></field>
		</fieldset>

		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
</tab>

<tab name='Activity'>
	<field name='activity'></field>
</tab>

<tab name='Stories'>
	<objectsref name='Sprint Stories' obj_type='project_story' ref_field='milestone_id'></objectsref>
</tab>

<tab name='Tasks'>
	<objectsref name='Tasks' obj_type='task' ref_field='milestone_id'></objectsref>
</tab>

<tab name='Discussions'>
	<objectsref obj_type='discussion'></objectsref>
</tab>
