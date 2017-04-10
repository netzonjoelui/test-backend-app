<tab name='General'>
			<spacer></spacer>
			<row>
				<plugin name='customer_link'></plugin>
			</row>
			<row>
				<column>
					<fieldset name='Details'>
						<column>
							<field name='first_name'></field>
							<field name='last_name'></field>
							<field name='spouse_name'></field>
						</column>
						<column>
							<field name='company'></field>
							<field name='job_title'></field>
							<field name='salutation'></field>
						</column>
						<row>
							<field name='groups'></field>
						</row>
					</fieldset>
					<fieldset name='Notes'>
						<field name='notes' hidelabel='t' multiline='t'></field>
					</fieldset>
					<fieldset name='Internet Addresses'>
						<field name='email'></field>
						<field name='email2'></field>
						<field name='email_spouse'></field>
						<field name='website'></field>
						<field name='email_default'></field>
					</fieldset>
				</column>
				<column width='200px'>
					<fieldset name='Image'>
						<field name='image_id' hidelabel='t' profile_image='t' path='%userdir%/Contact Files/'></field>
					</fieldset>
					<fieldset name='Important Dates'>
						<column>
							<field name='birthday'></field>
							<field name='birthday_spouse'></field>
							<field name='anniversary'></field>
						</column>
					</fieldset>
				</column>
			</row>
			<fieldset name='Phone Numbers'>
				<column>
					<field name='phone_cell'></field>
					<field name='phone_home'></field>
				</column>
				<column>
					<field name='phone_work'></field>
					<field name='phone_fax'></field>
				</column>
				<column>
					<field name='ext'></field>
					<field name='phone_pager'></field>
				</column>
			</fieldset>
			<row>
				<column width='50%'>
					<fieldset name='Home Address'>
						<field name='street'></field>
						<field name='street2'></field>
						<field name='zip'></field>
						<field name='city'></field>
						<field name='state'></field>
					</fieldset>
				</column>
				<column width='50%'>
					<fieldset name='Business Address'>
						<field name='business_street'></field>
						<field name='business_street2'></field>
						<field name='business_zip'></field>
						<field name='business_city'></field>
						<field name='business_state'></field>
					</fieldset>
				</column>
			</row>
			<fieldset name='Additional'>
				<column>
					<all_additional></all_additional>
				</column>
			</fieldset>
		</tab>

		<tab name='Activity'>
			<field name='activity'></field>
		</tab>

		<tab name='Tasks &amp; Events'>
			<fieldset name='Tasks'>
				<objectsref obj_type='task' ref_field='contact_id'></objectsref>
			</fieldset>
			<fieldset name='Events'>
				<objectsref obj_type='calendar_event'></objectsref>
			</fieldset>
		</tab>
		
		<tab name='Files'>
			<field name='folder_id'></field>
		</tab>
