<row>
	<column width='50px' padding='0px'>
		<field name='image_id' hidelabel='t' profile_image='t' />
	</column>
	<column padding='0px'>
		<row>
			<header field='full_name' class='formTitle' />
			<text field='job_title' />
			<text  showif='city=*'> | </text>
			<text field='city' />
			<text field='state' />
		</row>
		<row>
			<field name='email' hidelabel='t' />
		</row>
	</column>
</row>
