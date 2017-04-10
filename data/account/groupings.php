<?php
/**
 * Default groupings for entities
 *
 * These values will be added if and only if there are no (0) groupings for the
 * field. This allows an administrator to completely change their groupings without
 * it being overwritten with each update.
 */
return array(
    'case' => array(
        'status_id' => array(
            array("name"=>"New", "sort_oder"=>1, "color"=>"2A4BD7"),
            array("name"=>"In-Progress", "sort_oder"=>3, "color"=>"FF9233"),
            array("name"=>"Closed: Resolved", "sort_oder"=>5, "color"=>"1D6914"),
            array("name"=>"Closed: Unresolved", "sort_oder"=>7, "color"=>"AD2323"),
        ),
        'severity_id' => array(
            array("name"=>"Low", "sort_oder"=>1, "color"=>"1D6914"),
            array("name"=>"Medium", "sort_oder"=>2, "color"=>"1D6914"),
            array("name"=>"High", "sort_oder"=>3, "color"=>"AD2323"),
        ),
        'type_id' => array(
            array("name"=>"Customer Support", "color"=>"1D6914"),
            array("name"=>"Technical Support", "color"=>"575757"),
        )
    ),
    'project_story' => array(
        'status_id' => array(
            array("name"=>"New", "sort_oder"=>1, "color"=>"2A4BD7"),
            array("name"=>"In-Progress", "sort_oder"=>2, "color"=>"FF9233"),
            array("name"=>"Ready for testing", "sort_oder"=>3, "color"=>"FFEE33"),
            array("name"=>"Test Passed", "sort_oder"=>4, "color"=>"575757"),
            array("name"=>"Test Failed", "sort_oder"=>5, "color"=>"29D0D0"),
            array("name"=>"Completed", "sort_oder"=>6, "color"=>"1D6914"),
            array("name"=>"Rejected", "sort_oder"=>7, "color"=>"AD2323"),
        ),
        'priority_id' => array(
            array("name"=>"Low", "sort_oder"=>1, "color"=>"1D6914"),
            array("name"=>"Medium", "sort_oder"=>2, "color"=>"1D6914"),
            array("name"=>"High", "sort_oder"=>3, "color"=>"AD2323"),
        ),
        'type_id' => array(
            array("name"=>"Enhancement", "color"=>"1D6914"),
            array("name"=>"Defect", "color"=>"AD2323"),
        )
    ),
    'marketing_campaign' => array(
        'type_id' => array(
            array("name"=>"Email", "color"=>"2A4BD7"),
            array("name"=>"Advertisement", "color"=>"575757"),
            array("name"=>"Telephone", "color"=>"FF9233"),
            array("name"=>"Banner Ads", "color"=>"FFEE33"),
            array("name"=>"Public Relations", "color"=>"1D6914"),
            array("name"=>"Partners", "color"=>"AD2323"),
            array("name"=>"Resellers", "color"=>"A0A0A0"),
            array("name"=>"Referral Program", "color"=>"814A19"),
            array("name"=>"Direct Mail", "color"=>"8126C0"),
            array("name"=>"Trade Show", "color"=>"9DAFFF"),
            array("name"=>"Conference", "color"=>"E9DEBB"),
            array("name"=>"Other", "color"=>"29D0D0"),

        ),
        'status_id' => array(
            array("name"=>"Planning", "sort_oder"=>1, "color"=>"2A4BD7"),
            array("name"=>"Active", "sort_oder"=>2, "color"=>"575757"),
            array("name"=>"Inactive", "sort_oder"=>3, "color"=>"FF9233"),
            array("name"=>"Complete", "sort_oder"=>4, "color"=>"FFEE33"),
        ),
    ),
    'content_feed_post' => array(
        'status_id' => array(
            array("name"=>"Draft", "color"=>"2A4BD7"),
            array("name"=>"Awaiting Review", "color"=>"575757"),
            array("name"=>"Rejected", "color"=>"FF9233"),
            array("name"=>"Published", "color"=>"FFEE33"),
        ),
    ),
    'cms_page' => array(
        'status_id' => array(
            array("name"=>"Draft", "color"=>"2A4BD7"),
            array("name"=>"Awaiting Review", "color"=>"575757"),
            array("name"=>"Rejected", "color"=>"FF9233"),
            array("name"=>"Published", "color"=>"FFEE33"),
        ),
    ),
    'activity' => array(
        'type_id' => array(
            array("name"=>"Phone Call", "color"=>"2A4BD7"),
            array("name"=>"Status Update", "color"=>"575757"),
        ),
    ),
    'phone_call' => array(
        'purpose_id' => array(
            array("name"=>"Prospecting", "color"=>"2A4BD7"),
            array("name"=>"Administrative", "color"=>"FF9233"),
            array("name"=>"Negotiation", "color"=>"1D6914"),
            array("name"=>"Demo", "color"=>"AD2323"),
            array("name"=>"Project", "color"=>"1D6914"),
            array("name"=>"Support", "color"=>"AD2323"),
        ),
    ),
    'lead' => array(
        'status_id' => array(
            array("name"=>"New: Not Contacted", "color"=>"2A4BD7"),
            array("name"=>"New: Pre Qualified", "color"=>"9DAFFF"),
            array("name"=>"Working: Attempted to Contact", "color"=>"575757"),
            array("name"=>"Working: Contacted", "color"=>"FF9233"),
            array("name"=>"Working: Contact Later", "color"=>"FFEE33"),
            array("name"=>"Closed: Converted", "color"=>"1D6914"),
            array("name"=>"Closed: Lost", "color"=>"AD2323"),
            array("name"=>"Closed: Junk", "color"=>"29D0D0"),
        ),
        'rating_id' => array(
            array("name"=>"Hot", "color"=>"2A4BD7"),
            array("name"=>"Medium", "color"=>"575757"),
            array("name"=>"Cold", "color"=>"FF9233"),
        ),
        'source_id' => array(
            array("name"=>"Advertisement", "color"=>"2A4BD7"),
            array("name"=>"Cold Call", "color"=>"575757"),
            array("name"=>"Employee Referral", "color"=>"FF9233"),
            array("name"=>"External Referral", "color"=>"FFEE33"),
            array("name"=>"Website", "color"=>"1D6914"),
            array("name"=>"Partner", "color"=>"AD2323"),
            array("name"=>"Email", "color"=>"A0A0A0"),
            array("name"=>"Web Research", "color"=>"814A19"),
            array("name"=>"Direct Mail", "color"=>"8126C0"),
            array("name"=>"Trade Show", "color"=>"9DAFFF"),
            array("name"=>"Conference", "color"=>"E9DEBB"),
            array("name"=>"Other", "color"=>"29D0D0"),
        ),
    ),
    /* Can't add private object types
    'calendar_event_proposal' => array(
        'status_id' => array(
            array("name"=>"Draft", "color"=>"2A4BD7"),
            array("name"=>"Sent: Awaiting Replies", "color"=>"FF9233"),
            array("name"=>"Completed", "color"=>"1D6914"),
            array("name"=>"Canceled", "color"=>"AD2323"),
        ),
    ),
    */
    'opportunity' => array(
        'stage_id' => array(
            array("name"=>"Qualification", "color"=>"2A4BD7"),
            array("name"=>"Needs Analysis", "color"=>"575757"),
            array("name"=>"Value Proposition", "color"=>"FF9233"),
            array("name"=>"Id. Decision Makers", "color"=>"FFEE33"),
            array("name"=>"Proposal/Price Quote", "color"=>"1D6914"),
            array("name"=>"Negotiation/Review", "color"=>"AD2323"),
            array("name"=>"Closed: Won", "color"=>"A0A0A0"),
            array("name"=>"Closed: Lost", "color"=>"814A19"),
        ),
        'type_id' => array(
            array("name"=>"New Business", "color"=>"2A4BD7"),
            array("name"=>"Existing Business", "color"=>"575757"),
        ),
        'objection_id' => array(
            array("name"=>"Not Interested / Don't need it", "color"=>"2A4BD7"),
            array("name"=>"Already Working with Someone", "color"=>"575757"),
            array("name"=>"Trouble Getting Approved", "color"=>"FF9233"),
            array("name"=>"Price Too High", "color"=>"FFEE33"),
            array("name"=>"Troubling Reputation", "color"=>"1D6914"),
            array("name"=>"Never Heard of Us", "color"=>"AD2323"),
            array("name"=>"Had Problems in the Past", "color"=>"A0A0A0"),
            array("name"=>"Too Confusing/Complex", "color"=>"814A19"),
            array("name"=>"Not a Good Fit", "color"=>"8126C0"),
        ),
        'selling_point_id' => array(
            array("name"=>"Price", "color"=>"2A4BD7"),

            array("name"=>"Features", "color"=>"575757"),
            array("name"=>"Good Reputation", "color"=>"FF9233"),
            array("name"=>"Support", "color"=>"FFEE33"),
            array("name"=>"Simplicity", "color"=>"1D6914"),
            array("name"=>"Good Experience", "color"=>"AD2323"),
        ),
    ),
);
