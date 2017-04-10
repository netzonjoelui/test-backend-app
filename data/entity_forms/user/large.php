<row>
    <column type="half">
        <row>
            <column>
                <row>
                    <field name='image_id' hidelabel='t' profile_image='t'></field>
                </row>
                <row>
                    <header field='full_name'/>
                </row>
                <row>
                    <text field='job_title' class="profile-label"/>
                </row>
                <row>
                    <text field='city' class="profile-label"/>
                </row>
                <row>
                    <text field='state' class="profile-label"/>
                </row>
            </column>
        </row>
        <header>Admin</header>
        <row>
            <column>
                <field name='team_id'/>
                <field name='active'/>
                <field name='groups' hidelabel='t'/>
                <field name='manager_id'/>
            </column>
        </row>
        <header>Contact</header>
        <row>
            <column>
                <field label='Carier' name='phone_mobile_carrier'/>
                <field label='Mobile' name='phone_mobile'/>
                <field label='Office' name='phone_office'/>
                <field label='Ext' name='phone_ext'/>
            </column>
        </row>
    </column>
    <column>
        <row>
            <column>
                <field name='name' validator='username'/>
                <field name='full_name'/>
                <field label='Email' name='email'/>
            </column>
        </row>
        <row showif="editMode=1">
            <column>
                <field name='job_title'/>
            </column>
        </row>
        <row showif="editMode=1">
            <column>
                <field name='city'/>
            </column>
            <column>
                <field name='state'/>
            </column>
        </row>
        <row>
            <column>
                <field name='notes' hidelabel='t' multiline='t'/>
            </column>
        </row>
    </column>
    <column type="sidebar">
        <row>
            <field hidelabel='t' name='activity'/>
        </row>
    </column>
</row>