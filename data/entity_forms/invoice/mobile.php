<row>
<column>
	<field name='customer_id'></field>
	<row>
		<field name='name'></field>
	</row>
	<row>
		<column><field name='date_due'></field></column>
		<column><field name='payment_terms'></field></column>
		<column><field name='tax_rate'></field></column>
	</row>
	<row>
		<all_additional></all_additional>
	</row>

	<row>
		<plugin name='invoice_details'></plugin>
	</row>

	<fieldset name='Comments'>
		<field name='comments'></field>
	</fieldset>
	
	<fieldset name='Activity'>
		<field name='activity'></field>
	</fieldset>
</column>
<column width='220px'>
	<fieldset name='Status &amp; Ownership'>
		<field name='type'></field>
		<field name='status_id'></field>
		<field name='owner_id'></field>
		<row showif='type=r'>
			<field name='template_id'></field>
		</row>
		<field name='ts_entered'></field>
		<field name='ts_updated'></field>
	</fieldset>
</column>
</row>
<plugin name='invoice_checkout'></plugin>
