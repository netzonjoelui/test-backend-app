<tab name='General'>

    <column>
        <row editmodeonly='t'>
            <fieldset name='Details'>
                <row>
                    <field name='type_id'></field>
                </row>
                <row showif='type_id=2'>
                    <field name='name'></field>
                    <field ref_field='type_id' ref_value='1' name='primary_contact'></field>
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
            </fieldset>
        </row>
        <fieldset name='About'>
            <field name='notes' hidelabel='t' multiline='t'></field>
        </fieldset>
        <fieldset name='Additional'>
            <all_additional></all_additional>
        </fieldset>

        <row>
            <plugin name='customer_actions'></plugin>
        </row>

        <row>
            <field hidelabel='t' name='activity'></field>
        </row>
    </column>

    <column width='220px'>
        <fieldset>
            <row>
                <column width='50px'>
                    <field name='image_id' hidelabel='t' profile_image='t' path='/System/Customer Files'></field>
                </column>
                <column padding='0px'>
                    <header field='name' />
                    <text field='job_title' />
                    <text showif='primary_account=*'> at </text>
                    <text field='primary_account' />
                </column>
            </row>
            <row>
                <field class='compact' icon='/images/icons/phone_mobile_12.png' label='Mobile' name='phone_cell'></field>
                <field class='compact' icon='/images/icons/phone_home_12.png' label='Home' name='phone_home'></field>
                <field class='compact' icon='/images/icons/phone_work_12.png' label='Work' name='phone_work'></field>
                <field class='compact' icon='/images/icons/phone_ext_12.png' label='Ext' name='phone_ext'></field>
                <field class='compact' icon='/images/icons/phone_fax_12.png' name='phone_fax'></field>
                <field class='compact' icon='/images/icons/pager_12.png' name='phone_pager'></field>
                <field class='compact' icon='/images/icons/email-b_12.png' label='Home' name='email'></field>
                <field class='compact' icon='/images/icons/email-b_12.png' label='Work' name='email2'></field>
                <field class='compact' icon='/images/icons/email-b_12.png' label='Other' name='email3'></field>
                <field class='compact' icon='/images/icons/email-b_12.png' label='Spouse' name='email_spouse'></field>
                <field class='compact' editmodeonly='t' name='email_default'></field>
                <field class='compact' icon='/images/icons/link_12.png' name='website'></field>
                <field class='compact' icon='/images/icons/facebook_12.png' name='facebook'></field>
            </row>
        </fieldset>
        <fieldset name='Administration'>
            <field class='compact' name='status_id'></field>
            <field class='compact' name='stage_id'></field>
            <field class='compact' name='owner_id'></field>
            <field class='compact' name='f_nocall'></field>
            <field class='compact' name='f_noemailspam'></field>
            <field class='compact' name='f_nocontact'></field>
        </fieldset>
        <fieldset name='Billing Address'>
            <field class='compact' name='billing_street'></field>
            <field class='compact' name='billing_street2'></field>
            <field class='compact' name='billing_zip'></field>
            <field class='compact' name='billing_city'></field>
            <field class='compact' name='billing_state'></field>
        </fieldset>
        <fieldset name='Shipping Address'>
            <field class='compact' name='shipping_street'></field>
            <field class='compact' name='shipping_street2'></field>
            <field class='compact' name='shipping_zip'></field>
            <field class='compact' name='shipping_city'></field>
            <field class='compact' name='shipping_state'></field>
        </fieldset>
        <fieldset name='Business Address'>
            <field class='compact' name='business_street'></field>
            <field class='compact' name='business_street2'></field>
            <field class='compact' name='business_zip'></field>
            <field class='compact' name='business_city'></field>
            <field class='compact' name='business_state'></field>
        </fieldset>
        <fieldset name='Home Address'>
            <field class='compact' label='Street' name='street'></field>
            <field class='compact' label='Street 2' name='street2'></field>
            <field class='compact' label='Zip' name='zip'></field>
            <field class='compact' label='City' name='city'></field>
            <field class='compact' label='State' name='state'></field>
        </fieldset>
        <fieldset name='Groups'>
            <field name='groups' hidelabel='t'></field>
        </fieldset>
        <fieldset name='Important Dates'>
            <field class='compact' name='birthday'></field>
            <field class='compact' name='birthday_spouse'></field>
            <field class='compact' name='anniversary'></field>
            <field class='compact' name='last_contacted'></field>
        </fieldset>

    </column>
</tab>

<tab name='Tasks &amp; Events'>
    <fieldset name='Tasks'>
        <objectsref obj_type='task' ref_field='customer_id'></objectsref>
    </fieldset>
    <fieldset name='Events'>
        <objectsref obj_type='calendar_event' ref_field='customer_id'></objectsref>
    </fieldset>
</tab>

<tab name='Cases'>
    <objectsref obj_type='case' ref_field='customer_id'></objectsref>
</tab>

<tab name='Relationships'>
    <plugin name='relationships'></plugin>
</tab>

<tab name='Files'>
    <field name='folder_id' hidelabel='t'></field>
</tab>

<tab name='Opportunities'>
    <objectsref obj_type='opportunity' ref_field='customer_id'></objectsref>
</tab>

<tab name='Invoices'>
    <objectsref obj_type='invoice' ref_field='customer_id'></objectsref>
</tab>

<!--
<tab name='Publish'>
	<plugin name='publish'></plugin>
</tab>
-->

<plugin name='followup'></plugin>
