<row>
    <column>
        <tabs>
            <tab name='General'>
                <row>
                    <column>
                        <field name='title' class="headline"></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='user_id'></field>
                        <field name='site_id'></field>
                    </column>
                    <column showif='all_day=f'>
                        <field name='groups'></field>
                        <field name='ts_created'></field>
                        <field name='ts_updated'></field>
                    </column>
                </row>
            </tab>
            <tab name='Posts'>
                <objectsref obj_type='content_feed_post' ref_field='feed_id'></objectsref>
            </tab>
            <tab name='Activity'>
                <field name='activity'></field>
            </tab>
            <tab name='Comments'>
                <field name='comments'></field>
            </tab>
            <tab name='Custom Fields'>
                <plugin name='CustomFields' objType='content_feed_post' ref_field='feed_id'></plugin>
            </tab>
            <tab name='Settings'>

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
        </tabs>
    </column>
</row>