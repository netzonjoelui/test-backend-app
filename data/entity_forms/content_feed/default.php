<tab name='General'>
	<helptour id='objects/content_feed/1-introduction' type='inline' />
	<fieldset name=''>
		<row>
			<field name='title'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='user_id'></field>
				<field name='site_id'></field>
			</column>
			<column width='50%'>
				<field name='groups'></field>
				<field name='ts_created'></field>
				<field name='ts_updated'></field>
			</column>
		</row>
	</fieldset>
	<fieldset name='Posts'>
		<row>
			<objectsref obj_type='content_feed_post' ref_field='feed_id'></objectsref>
		</row>
	</fieldset>
</tab>
<tab name='Activity'>
	<field name='activity'></field>
</tab>
<tab name='Comments'>
	<field name='comments'></field>
</tab>
<tab name='Custom Fields'>
	<plugin name='feed_fields'></plugin>
</tab>
<tab name='Post Categories'>
	<plugin name='feed_categories'></plugin>
</tab>
<tab name='Settings'>
	<row>
		<plugin name='publish_link'></plugin>
	</row>
	<row>
		<field name='sort_by'></field>
	</row>
	<row>
		<field name='limit_num'></field>
	</row>
	<row>
		<field name='subs_title'></field>
	</row>
	<row>
		<field name='subs_body' multiline='t' rich='t'></field>
	</row>
</tab>
