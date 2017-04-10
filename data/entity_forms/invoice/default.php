	<column>
		<row>
			<field name='customer_id'></field>
		</row>
		<row>
			<field name='name'></field>
		</row>
		<row>
			<column><field name='date_due'></field></column>
			<column><field name='payment_terms'></field></column>
			<column><field name='tax_rate'></field></column>
			<column showif='type=p'><field name='reference'></field></column>
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
			<field class='compact' name='type'></field>
			<field class='compact' name='status_id'></field>
			<field class='compact' name='owner_id'></field>
			<field class='compact' name='sales_order_id'></field>
			<row showif='type=r'>
				<field class='compact' name='template_id'></field>
			</row>
			<field class='compact' name='ts_entered'></field>
			<field class='compact' name='ts_updated'></field>
		</fieldset>
	</column>
<plugin name='invoice_checkout'></plugin>
