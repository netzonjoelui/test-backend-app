<tab name='General'>
	<fieldset name=''>
		<row>
			<field name='name' tooltip="Name this page. This is different from the title in that is should be unique to each level. For instance, 'index' or 'blog' would be good names"></field>
		</row>
		<row>
			<field name='uname'></field>
		</row>
		<row>
			<field name='title' tooltip='The displayed title of this page.'></field>
		</row>
		<row>
			<field name='meta_keywords'></field>
		</row>
		<row>
			<field name='meta_description'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='parent_id'></field>
				<field name='template_id'></field>
				<field name='site_id'></field>
				<field name='status_id'></field>
			</column>
			<column width='50%'>
				<field name='sort_order'></field>
				<field name='f_navmain'></field>
				<field name='time_publish' tooltip="If set then post will not be published until the selected date"></field>
				<field name='time_expires' tooltip="If set then post will be automatically removed on the selected date"></field>
			</column>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
	</fieldset>
	<fieldset name='Body'>
		<row>
			<field hidelabel='t' multiline='t' rich='t' name='data' plugins='cms'></field>
		</row>
	</fieldset>

	<row>
		<fieldset name='Snippets'>
			<objectsref obj_type='cms_snippet' ref_field='page_id'></objectsref>
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
