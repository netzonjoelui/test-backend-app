<tab name='General'>
	<fieldset name='Status &amp; Admin'>
		<row>
			<column>
				<field name='title'></field>
				<field name='severity_id'></field>
				<field name='type_id'></field>
				<field name='owner_id'></field>
				<row>
					<plugin name='case_taskowner'></plugin>
				</row>
			</column>
			<column>
				<field name='project_id'></field>
				<field name='status_id'></field>
				<field name='customer_id'></field>
				<field name='ts_entered'></field>
			</column>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<row>
        <fieldset name='Attachments'>
            <attachments></attachments>
        </fieldset>
	</row>
	<row>
		<fieldset name='Description'>
			<field name='description' hidelabel='t' multiline='t'></field>
		</fieldset>
	</row>
	<row>
		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
	</row>
</tab>

<tab name='Activity'>
	<field name='activity'></field>
</tab>

<tab name='Tasks &amp; Events'>
	<fieldset name='Tasks'>
		<objectsref obj_type='task' ref_field='case_id'></objectsref>
	</fieldset>
	<fieldset name='Events'>
		<objectsref obj_type='calendar_event'></objectsref>
	</fieldset>
</tab>
