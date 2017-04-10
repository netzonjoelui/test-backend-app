<tab name='General'>
	<helptour id='objects/content_feed_post/1-introduction' type='inline' />
	<fieldset name=''>
		<row>
			<field name='title'></field>
		</row>
		<row>
			<field name='uname'></field>
		</row>
		<row>
			<column width='50%'>
				<field name='image' preview='t'></field>
				<field name='author' tooltip='If set will override the "User" field as the published author.'></field>
				<field name='feed_id' tooltip='Feeds are how posts are logically organized. Samples include "News" or "Blog Posts."'></field>
				<field name='site_id' tooltip='Optional, indicate this post belongs to a specific site if publishing to a site managed through netric.'></field>
				<field name='status_id'></field>
			</column>
			<column width='50%'>
				<field name='user_id'></field>
				<field name='categories' tooltip='Each feed has its own set of categories for posts. To edit, click the "Categories" tab in the parent feed of this post. This is commonly used to create blog post categories. May be left blank.'></field>
				<field name='time_entered'></field>
				<field name='ts_updated'></field>
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
		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
	</row>
</tab>
<tab name='Activity'>
	<field name='activity'></field>
</tab>
<plugin name='feed_post_publish'></plugin>
