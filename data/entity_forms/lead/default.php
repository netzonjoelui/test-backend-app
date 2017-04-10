<tab name='General'>
	<fieldset name='Status &amp; Admin'>
		<row>
			<column>
				<field name='status_id'></field>
				<field name='source_id'></field>
				<field name='owner_id'></field>
			</column>
			<column>
				<field name='f_converted'></field>
				<field name='rating_id'></field>
				<field name='campaign_id'></field>
			</column>
			<column>
				<field name='converted_opportunity_id'></field>
				<field name='converted_customer_id'></field>
			</column>
		</row>
	</fieldset>
	<fieldset name='Contact Information'>
		<row>
			<column>
				<field name='first_name'></field>
				<field name='last_name'></field>
				<field name='company'></field>
				<field name='title'></field>
				<field name='website'></field>
			</column>
			<column>
				<field name='email'></field>
				<field name='phone'></field>
				<field name='phone2'></field>
				<field name='phone3'></field>
				<field name='fax'></field>
			</column>
		</row>
	</fieldset>
	<fieldset name='Physical Address'>
		<row>
			<column>
				<field name='street'></field>
				<field name='zip'></field>
				<field name='city'></field>
			</column>
			<column>
				<field name='street2'></field>
				<field name='state'></field>
				<field name='country'></field>
			</column>
		</row>
	</fieldset>
	<fieldset name='Additional'>
		<column>
			<all_additional></all_additional>
		</column>
	</fieldset>
	<row>
		<fieldset name='Description'>
			<field name='notes' hidelabel='t' multiline='t'></field>
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
		<objectsref obj_type='task'></objectsref>
	</fieldset>
	<fieldset name='Events'>
		<objectsref obj_type='calendar_event'></objectsref>
	</fieldset>
</tab>
	
<plugin name='followup'></plugin>

<plugin name='convert'></plugin>
