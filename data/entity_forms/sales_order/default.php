<row>
<column>
	<fieldset name='General'>
		<row>
			<field name='name'></field>
			<field name='invoice_id'></field>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<fieldset name='Ship To'>
		<field name='ship_to_cship'></field>
		<field name='ship_to' multiline='t'></field>
	</fieldset>
	<fieldset name='Details'>
		<plugin name='order_details'></plugin>
	</fieldset>
	<fieldset name='Comments'>
		<field name='comments'></field>
	</fieldset>
	<fieldset name='Activity'>
		<field name='activity'></field>
	</fieldset>
</column>
<column width='200px'>
	<fieldset name='Status &amp; Ownership'>
		<field name='status_id'></field>
		<field name='customer_id'></field>
		<field name='owner_id'></field>
		<field name='ts_entered'></field>
		<field name='ts_updated'></field>
	</fieldset>
</column>
</row>
