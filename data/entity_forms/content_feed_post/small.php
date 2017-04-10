<field name='title' class="headline"></field>
<field name='uname'></field>
<field name='image' preview='t'></field>
<field name='author'
       tooltip='If set will override the "User" field as the published author.'></field>
<field name='feed_id'
       tooltip='Feeds are how posts are logically organized. Samples include "News" or "Blog Posts."'></field>
<field name='site_id'
       tooltip='Optional, indicate this post belongs to a specific site if publishing to a site managed through netric.'></field>
<field name='status_id'></field>
<field name='user_id'></field>
<field name='categories'
       tooltip='Each feed has its own set of categories for posts. To edit, click the "Categories" tab in the parent feed of this post. This is commonly used to create blog post categories. May be left blank.'></field>
<field name='time_entered'></field>
<field name='ts_updated'></field>
<field name='time_publish'
       tooltip="If set then post will not be published until the selected date"></field>
<field name='time_expires'
       tooltip="If set then post will be automatically removed on the selected date"></field>
<all_additional ref_field="feed_id"></all_additional>
<field name='comments'></field>