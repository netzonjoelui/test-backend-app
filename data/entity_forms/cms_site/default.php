<tab name='General'>
	<fieldset name=''>
		<row>
			<field name='name'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='url'></field>
				<field name='url_test'></field>
                <plugin name='edit_site'></plugin>
			</column>
			<column width='50%'>
				<field name='owner_id'></field>
				<field name='ts_created'></field>
				<field name='ts_updated'></field>
			</column>
		</row>
	</fieldset>
</tab>
<tab name='Feeds &amp; Blogs'>
	<objectsref obj_type='content_feed' ref_field='site_id' />
</tab>
<tab name='Pages'>
	<objectsref obj_type='cms_page' ref_field='site_id'></objectsref>
</tab>
<tab name='Page Templates'>
	<objectsref obj_type='cms_page_template' ref_field='site_id'></objectsref>
</tab>
<tab name='Snippets'>
	<objectsref obj_type='cms_snippet' ref_field='site_id'></objectsref>
</tab>
<tab name='Comments'>
	<field name='comments'></field>
</tab>
<tab name='Media'>
	<field hidelabel='t' name='folder_id'></field>
</tab>
