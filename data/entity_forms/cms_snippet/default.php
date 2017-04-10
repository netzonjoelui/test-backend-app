<tab name='General'>
	<fieldset name=''>
		<row>
			<field name='name'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='uname'></field>
				<field name='owner_id'></field>
				<field name='page_id'></field>
			</column>
			<column width='50%'>
				<field name='time_entered'></field>
				<field name='ts_updated'></field>
			</column>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<fieldset name='Content'>
		<row>
			<field hidelabel='t' multiline='t' rich='t' name='data' plugins='cms'></field>
		</row>
	</fieldset>
	<row>
		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
	</row>
</tab>
<tab name='Activity'>
	<field name='activity'></field>
</tab>
