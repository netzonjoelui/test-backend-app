<?php
/**
 * Default users that should exist in every account
 */
namespace data\account;

use Netric\Entity\ObjType\UserEntity;
use Netric\EntityQuery\Where;

return array(
    array(
        "name" => "New Task Sendmail",
        "uname" => "new-task-sendmail",
        "notes" => "This will send a notification email to the task owner",
        "obj_type" => "task",
        "active" => true,
        "on_create" => true,
        "on_update" => true,
        "on_delete" => false,
        "on_daily" => false,
        "singleton" => false,
        "allow_manual" => false,
        "only_on_conditions_unmet" => true,
        "conditions" => array(
            // Only trigger if the task is assinged to someone other than the current user
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field_name" => "user_id",
                "operator" => Where::OPERATOR_NOT_EQUAL_TO,
                "value" => UserEntity::USER_CURRENT,
            )
        ),
        "actions" => array(
            array(
                "name" => "Send Email",
                "type" => "send_email",
                "params" => array(
                    "from" => "no-reply@netric.com",
                    "subject" => "New Task",
                    "body" => "You have been assigned a new task: <%object_link%>",
                    "to" => array(
                        "<%user_id.email%>"
                    )
                ),
            ),
        ),
    ),
    array(
        "name" => "Case Assigned Notification",
        "uname" => "case-assigned",
        "notes" => "This will send a notification email when a user is assigned to a case",
        "obj_type" => "case",
        "active" => true,
        "on_create" => true,
        "on_update" => true,
        "on_delete" => false,
        "on_daily" => false,
        "singleton" => false,
        "allow_manual" => false,
        "only_on_conditions_unmet" => true,
        "conditions" => array(
            // Only trigger if the case is assignged to someone other than the current user
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field_name" => "owner_id",
                "operator" => Where::OPERATOR_NOT_EQUAL_TO,
                "value" => UserEntity::USER_CURRENT,
            )
        ),
        "actions" => array(
            array(
                "name" => "Send Email",
                "type" => "send_email",
                "params" => array(
                    "from" => "no-reply@netric.com",
                    "subject" => "New Task",
                    "body" => "You have been assigned a case: <%object_link%>",
                    "to" => array(
                        "<%user_id.email%>"
                    )
                ),
            ),
        ),
    ),
);