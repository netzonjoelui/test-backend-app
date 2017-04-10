<row>
    <column>

        <row>
            <column>
                <field name='name' hidelabel="t" class='headline'
                       tooltip="Enter the subject of this call"></field>
            </column>
        </row>
        <row>
            <column>
                <field name='direction'></field>
            </column>
            <column>
                <field name='purpose_id'></field>
            </column>
            <column>
                <field name='ts_start' part='date'></field>
            </column>
            <column>
                <field name='ts_start' part='time' hidelabel='t'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='duration'></field>
            </column>
            <column>
                <field name='owner_id'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='result'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='customer_id'></field>
                <field name='lead_id'></field>
            </column>
            <column>
                <field name='project_id'></field>
                <field ref_field='customer_id' ref_this='customer_id' name='case_id'></field>
                <field ref_field='customer_id' ref_this='customer_id' name='opportunity_id'></field>
                <field name='campaign_id'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='notes' hidelabel='t' multiline='t'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='comments' hidelabel='t'></field>
            </column>
        </row>
    </column>
</row>