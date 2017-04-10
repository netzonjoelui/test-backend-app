<tab name='General'>
	<row>
		<plugin name="email_campaign" />
	</row>
	<fieldset name='Campaign'>
		<row>
			<field name='name'></field>
			<field name='parent_id'></field>
		</row>
		<row>
			<column>
				<field name='type_id'></field>
				<field name='status_id'></field>
			</column>
			<column>
				<field name='date_start'></field>
				<field name='date_end'></field>
				<field name='date_completed'></field>
			</column>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<fieldset name='Stats'>
		<row>
			<column>
				<field name='cost_estimated'></field>
				<field name='cost_actual'></field>
				<field name='rev_estimated'></field>
				<field name='rev_actual'></field>
			</column>
			<column>
				<field name='num_sent'></field>
				<field name='resp_estimated'></field>
				<field name='resp_actual'></field>
				<!--
				<field name='email_opens'></field>
				<field name='email_unsubscribers'></field>
				<field name='email_bounced'></field>
				-->
			</column>
		</row>
	</fieldset>
	<row>
		<fieldset name='Attachments'>
			<attachments></attachments>
		</fieldset>
	</row>
	<row>
		<fieldset name='Description'>
			<field name='description' hidelabel='t' multiline='t'></field>
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
