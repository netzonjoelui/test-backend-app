<fieldset name='Details'>
	<row>
		<field name='name' tooltip="Enter the subject of this call"></field>
	</row>
	<row>
		<field name='direction'></field>
	</row>
	<row>
		<field name='purpose_id'></field>
	</row>
	<row>
		<column width='170px'>
			<field name='ts_start' part='date'></field>
		</column>
		<column  style='padding-left:0px' width='50px'>
			<field name='ts_start' part='time' hidelabel='t'></field>
		</column>
	</row>
	<row>
		<field name='duration'></field>
	</row>
	<row>
		<field name='result'></field>
	</row>
	<row>
		<field name='owner_id'></field>
	</row>
</fieldset>
<row>
	<column width='50%'>
		<fieldset name='Contact'>
			<field name='customer_id'></field>
			<field name='lead_id'></field>
		</fieldset>
	</column>
	<column>
		<fieldset name='Concerning'>
			<field name='project_id'></field>
			<field ref_field='customer_id' ref_this='customer_id' name='case_id'></field>
			<field ref_field='customer_id' ref_this='customer_id' name='opportunity_id'></field>
			<field name='campaign_id'></field>
		</fieldset>
	</column>
</row>
<row>
	<all_additional></all_additional>
</row>
<fieldset name='Notes'>
	<field hidelabel='t' multiline='t' name='notes'></field>
</fieldset>
<fieldset name='Comments'>
	<field hidelabel='t' name='comments'></field>
</fieldset>
