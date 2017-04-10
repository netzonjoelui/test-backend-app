<tab name='General'>
	<fieldset name=''>
		<row>
			<field name='name'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='type'></field>
				<field name='owner_id'></field>
			</column>
			<column width='50%'>
				<row  showif="type=module">
					<field name='module'></field>
				</row>
			</column>
		</row>
		<row>
			<all_additional></all_additional>
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
