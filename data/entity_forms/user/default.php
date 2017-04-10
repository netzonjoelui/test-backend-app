<column width='220px'>
	<fieldset>
		<row>
			<column width='50px'>
				<field name='image_id' hidelabel='t' profile_image='t'></field>
			</column>
			<column padding='0px'>
				<row>
					<header field='full_name' />
				</row>
				<row>
					<text field='name' />
				</row>
				<row>
					<text field='job_title' />
					<text showif='city=*'> | </text>
					<text field='city' />
					<text showif='state=*'> </text>
					<text field='state' />
				</row>
			</column>
		</row>
	</fieldset>

	<fieldset name='Admin'>
		<field class='compact' name='active'></field>
		<field class='compact' name='manager_id'></field>
		<field class='compact' name='team_id'></field>
	</fieldset>

	<fieldset name='Contact'>
		<field class='compact' icon='/images/icons/phone_mobile_12.png' label='Mobile' name='phone_mobile'></field>
		<field class='compact' icon='/images/icons/phone_mobile_12.png' label='Carier' name='phone_mobile_carrier'></field>
		<field class='compact' icon='/images/icons/phone_work_12.png' label='Office' name='phone_office'></field>
		<field class='compact' icon='/images/icons/phone_ext_12.png' label='Ext' name='phone_ext'></field>
		<field class='compact' icon='/images/icons/email-b_12.png' label='Email' name='email'></field>
	</fieldset>

	<fieldset name='Groups'>
		<field name='groups' hidelabel='t'></field>
	</fieldset>
	
</column>
<column>
	<row editmodeonly='t'>
		<fieldset name='Details'>
			<field name='name' validator='username' />
			<field name='full_name' />
			<field name='job_title' />
			<field name='city' />
			<field name='state' />
		</fieldset>
	</row>

	<row editmodeonly='t'>
		<fieldset name='Set Password'>
			<plugin name='set_password'></plugin>
		</fieldset>
	</row>

	<fieldset name='About'>
		<field name='notes' hidelabel='t' multiline='t'></field>
	</fieldset>

	<row>
		<field hidelabel='t' name='activity'></field>
	</row>

</column>
