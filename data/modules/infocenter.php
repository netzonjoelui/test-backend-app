<?php
/**
 * Return navigation for entity of object type 'infocenter'
 */
namespace modules\navigation;

return array(
    "title" => "Infocenter",
    "icon" => "clipboard",
    "default_route" => "all-documents",
    "navigation" => array(
        array(
            "title" => "All Documents",
            "type" => "browse",
            "route" => "all-documents",
            "objType" => "infocenter_document",
            "icon" => "tags",
            "browseby" => "groups",
        )
    )
);