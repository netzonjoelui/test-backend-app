<tab name='General'>
	<column>
		<fieldset name='Details'>
			<row>
				<field name='name'></field>
				<field name='customer_id'></field>
			</row>
			<row>
				<column>
					<field name='amount'></field>
					<field name='probability_per'></field>
				</column>
				<column>
					<field name='expected_close_date'></field>
					<field name='objection_id'></field>
				</column>
			</row>
			<row>
				<all_additional></all_additional>
			</row>
		</fieldset>
		<fieldset name='Description'>
			<field name='notes' hidelabel='t' multiline='t'></field>
		</fieldset>
		<row>
			<fieldset name='Comments'>
				<field name='comments'></field>
			</fieldset>
		</row>
	</column>
	<column width='260px'>
		<fieldset name='Administration'>
			<field class='compact' name='stage_id'></field>
			<field class='compact' name='lead_source_id'></field>
			<field class='compact' name='type_id'></field>
			<field class='compact' name='campaign_id'></field>
			<field class='compact' name='owner_id'></field>
			<field class='compact' name='ts_entered'></field>
			<field class='compact' name='ts_updated'></field>
		</fieldset>
	</column>
</tab>

<tab name='Activity'>
	<field name='activity'></field>
</tab>

<tab name='Tasks &amp; Events'>
	<fieldset name='Tasks'>
		<objectsref obj_type='task'></objectsref>
	</fieldset>
	<fieldset name='Events'>
		<objectsref obj_type='calendar_event'></objectsref>
	</fieldset>
</tab>

<tab name='Files'>
	<field name='folder_id'></field>
</tab>
	
<plugin name='followup'></plugin>
