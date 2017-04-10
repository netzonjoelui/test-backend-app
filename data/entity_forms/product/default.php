<tab name='General'>
		<fieldset name='Details'>
			<row>
				<field name='name'></field>
				<field name='price'></field>
				<field name='ts_updated'></field>
				<field name='ts_entered'></field>
			</row>
			<row>
				<all_additional></all_additional>
			</row>
		</fieldset>

		<fieldset name='Description'>
			<field name='notes' hidelabel='t' multiline='t'></field>
		</fieldset>

		<fieldset name='Related Products'>
			<field name='related_products' hidelabel='t'></field>
		</fieldset>

		<fieldset name='Reviews'>
			<field name='reviews'></field>
		</fieldset>

		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
</tab>

<tab name='Activity'>
	<field name='activity'></field>
</tab>

<tab name='Discussions'>
	<objectsref obj_type='discussion'></objectsref>
</tab>
