<fieldset name='Details'>
	<field name='name'></field>
	<field name='amount'></field>
	<field name='probability_per'></field>
	<field name='expected_close_date'></field>
	<field name='objection_id'></field>
	<field name='stage_id'></field>
	<field name='lead_source_id'></field>
	<field name='type_id'></field>
	<field name='owner_id'></field>
	<field name='ts_entered'></field>
	<field name='ts_updated'></field>
	<field name='customer_id'></field>
	<field name='lead_id'></field>
	<field name='campaign_id'></field>
	<row>
		<all_additional></all_additional>
	</row>
</fieldset>

<fieldset name='Description'>
	<field name='notes' hidelabel='t' multiline='t'></field>
</fieldset>

<row>
	<objectsref obj_type='task'></objectsref>
</row>
<row>
	<objectsref obj_type='calendar_event'></objectsref>
</row>

<row>
	<fieldset name='Comments'>
		<field name='comments'></field>
	</fieldset>
</row>
