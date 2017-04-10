<row>
    <column>
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
                        <field name='name' hidelabel="t" class='headline'></field>
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

            <tab name='Reminders' showif='type_id=1'>
                <objectsref obj_type='reminder' ref_field='obj_reference'></objectsref>
            </tab>

            <tab name='Phone Call' showif='type_id=1'>
                <objectsref obj_type='phone_call' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Task'>
                <objectsref obj_type='task' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Events'>
                <objectsref obj_type='calendar_event' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Cases'>
                <objectsref obj_type='case' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Opportunities'>
                <objectsref obj_type='opportunity' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Files'>
                <attachments></attachments>
            </tab>
        </tabs>
    </column>
    <column type="sidebar">
        <row>
            <column>
                <field name='image_id' hidelabel='t' profile_image='t'></field>
                <text field='job_title'/>
                <text showif='primary_account=*'> at</text>
                <text field='primary_account'/>
            </column>
        </row>
        <row>
            <column>

            </column>
        </row>
        <row>
            <column>
                <field label='Mobile' name='phone_cell'></field>
                <field label='Home' name='phone_home'></field>
                <field label='Work' name='phone_work'></field>
                <field label='Ext' name='phone_ext'></field>
                <field name='phone_fax'></field>
                <field name='phone_pager'></field>
                <field label='Home' name='email'></field>
                <field label='Work' name='email2'></field>
                <field label='Other' name='email3'></field>
                <field label='Spouse' name='email_spouse'></field>
                <field editmodeonly='t' name='email_default'></field>
                <field name='website'></field>
                <field name='facebook'></field>
            </column>
        </row>
        <header>Administration</header>
        <row>
            <column>
                <field name='status_id'></field>
                <field name='stage_id'></field>
                <field name='owner_id'></field>
                <field name='f_nocall'></field>
                <field name='f_noemailspam'></field>
                <field name='f_nocontact'></field>
            </column>
        </row>
        <header>Billing Address</header>
        <row>
            <column>
                <field name='billing_street'></field>
                <field name='billing_street2'></field>
                <field name='billing_zip'></field>
                <field name='billing_city'></field>
                <field name='billing_state'></field>
            </column>
        </row>
        <header>Shipping Address</header>
        <row>
            <column>
                <field name='shipping_street'></field>
                <field name='shipping_street2'></field>
                <field name='shipping_zip'></field>
                <field name='shipping_city'></field>
                <field name='shipping_state'></field>
            </column>
        </row>
        <header>Business Address</header>
        <row>
            <column>
                <field name='business_street'></field>
                <field name='business_street2'></field>
                <field name='business_zip'></field>
                <field name='business_city'></field>
                <field name='business_state'></field>
            </column>
        </row>
        <header>Home Address</header>
        <row>
            <column>
                <field label='Street' name='street'></field>
                <field label='Street 2' name='street2'></field>
                <field label='Zip' name='zip'></field>
                <field label='City' name='city'></field>
                <field label='State' name='state'></field>
            </column>
        </row>
        <header>Groups</header>
        <row>
            <field name='groups' hidelabel='t'></field>
        </row>
        <header>Important Dates</header>
        <row>
            <column>
                <field name='birthday'></field>
                <field name='birthday_spouse'></field>
                <field name='anniversary'></field>
                <field name='last_contacted'></field>
            </column>
        </row>

    </column>
</row>