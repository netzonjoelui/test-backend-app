<?php
/**
 * Return navigation for entity of object type 'crm'
 */
namespace modules\navigation;

return array(
    "title" => "CRM",
    "icon" => "child",
    "default_route" => "all-customers",
    "navigation" => array(
        array(
            "title" => "All Customers",
            "type" => "browse",
            "route" => "all-customers",
            "objType" => "customer",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        ),
        array(
            "title" => "All Leads",
            "type" => "browse",
            "route" => "all-leads",
            "objType" => "lead",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        ),
        array(
            "title" => "All Opportunities",
            "type" => "browse",
            "route" => "all-opportunity",
            "objType" => "opportunity",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        ),
        array(
            "title" => "All Campaigns",
            "type" => "browse",
            "route" => "all-campaigns",
            "objType" => "marketing_campaign",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        ),
        array(
            "title" => "All Cases",
            "type" => "browse",
            "route" => "all-cases",
            "objType" => "case",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        )
    )
);