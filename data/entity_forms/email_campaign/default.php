<tab name='General'>
	<fieldset name=''>	
		<row>
			<field name='name' tooltip="Give this a campaign a unique name to make it easier to remember"></field>
		</row>
		<row>
			<field name='subject' />
		</row>
		<row>
			<field name='from_name' />
		</row>
		<row>
			<field name='from_email' />
		</row>
		<row>
			<field name='status' />
		</row>
		<row>
			<field name='f_confirmation' />
		</row>
		<row showif="f_confirmation=t">
			<field name='confirmation_email' />
		</row>
		<row>
			<field name='campaign_id' />
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<fieldset name='Recipients'>
		<row>
			<field name='to_type' />
		</row>
		<row showif="to_type=manual">
			<field name='to_manual' />
		</row>
		<row showif="to_type=view">
			<field name='to_view' />
		</row>
		<row showif="to_type=condition">
			<field name='to_conditions' />
		</row>
	</fieldset>
	<fieldset name='Content (HTML)'>
		<row>
			<field hidelabel='t' multiline='t' rich='t' name='body_html'></field>
		</row>
	</fieldset>
	<fieldset name='Content (TEXT)'>
		<row>
			<field hidelabel='t' multiline='t' name='body_plain'></field>
		</row>
	</fieldset>

</tab>
<tab name='Activity'>
	<field name='activity'></field>
</tab>
