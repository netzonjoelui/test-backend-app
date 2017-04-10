-- update timestamps
update objects_contact_personal_act set ts_updated=date_changed where ts_updated is null;
update objects_contact_personal_act set ts_entered=date_entered where ts_entered is null;
update objects_email_message_act set ts_updated=message_date where ts_updated is null;
update objects_email_message_act set ts_entered=message_date where ts_entered is null;
update project_tasks set ts_updated=date_entered where ts_updated is null;
update project_tasks set ts_entered=date_entered where ts_entered is null;
update user_notes set ts_updated=date_added where ts_updated is null;
update user_notes set ts_entered=date_added where ts_entered is null;
