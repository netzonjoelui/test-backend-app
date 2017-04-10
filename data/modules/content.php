<?php
/**
 * Return navigation for entity of object type 'content'
 */
namespace modules\navigation;

return array(
    "title" => "Content",
    "icon" => "newspaper-o",
    "default_route" => "all-contents",
    "navigation" => array(
        array(
            "title" => "All Contents",
            "type" => "browse",
            "route" => "all-contents",
            "objType" => "content_feed",
            "icon" => "ViewListIcon",
            "browseby" => "groups",
        )
    )
);