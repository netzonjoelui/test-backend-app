<tabs>
    <tab name='General'>
        <row showif='id=null'>
            <column>
                <field name='type_id'></field>
            </column>
        </row>
        <row showif='type_id=1'>
            <column>
                <field name='first_name'></field>
                <field name='last_name'></field>
                <field name='spouse_name'></field>
            </column>
            <column>
                <field ref_field='type_id' ref_value='2' name='primary_account'></field>
                <field name='job_title'></field>
                <field name='salutation'></field>
            </column>
        </row>
        <row showif='type_id=2'>
            <column>
                <field name='name'></field>
                <field ref_field='type_id' ref_value='1' name='primary_contact'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='notes' hidelabel='t' multiline='t'></field>
            </column>
        </row>
        <row>
            <column>
                <field hidelabel='t' name='activity'></field>
            </column>
        </row>
    </tab>

    <tab name='Files'>
        <attachments></attachments>
    </tab>
</tabs>