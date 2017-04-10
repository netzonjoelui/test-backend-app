<?php
/**
 * Move the old calendar_events_recurring table to the new object_recurrence table
 */
use Netric\Entity\Recurrence\RecurrencePattern;

$account = $this->getAccount();
$serviceManager = $account->getServiceManager();
$db = $serviceManager->get("Netric/Db/Db");
$loader = $serviceManager->get("Netric/EntityLoader");

// We need to check first if f_imported_to_entity column is existing, if not then add the column
if (!$db->columnExists("calendar_events_recurring", "f_imported_to_entity")) {

    // Add the f_imported_to_entity column with type boolean. This will be used to check if the recur event has been imported already.
    $db->query("ALTER TABLE calendar_events_recurring ADD COLUMN f_imported_to_entity boolean;");
}

// Specify what object type we are going to move
$objType = "calendar_event";

// Query the calendar event recurrences
$result = $db->query("SELECT calendar_events.id AS event_id,
              calendar_events_recurring.*,
              calendar_events_recurring.type AS recur_type,
              calendar_events_recurring.day AS day_of_month,
              calendar_events_recurring.month AS month_of_year,

              week_days[1] as day1, week_days[2] as day2, week_days[3] as day3,
			  week_days[4] as day4, week_days[5] as day5, week_days[6] as day6,
			  week_days[7] as day7
            FROM calendar_events_recurring
            INNER JOIN calendar_events ON (calendar_events_recurring.id = calendar_events.recur_id)
            WHERE f_imported_to_entity IS NULL OR f_imported_to_entity = FALSE;");


for ($i = 0; $i < $db->getNumRows($result); $i++) {
    
    // Get the result row
    $row = $db->getRow($result, $i);

    // Setup the object type
    $row['obj_type'] = $objType;

    // Unset the $row['id'] so it will create a new recurrence pattern entity
    $calendarEventRecurringId = null;
    if(isset($row['id'])) {
        $calendarEventRecurringId = $row['id'];
        unset($row['id']);
    }

    // Create a new instance of recurrence pattern
    $recurrencePattern = new RecurrencePattern();
    
    // Import the recurrence data
    $recurrencePattern->fromArray($row);

    if ($row['day1'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
    if ($row['day2'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_MONDAY, true);
    if ($row['day3'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_TUESDAY, true);
    if ($row['day4'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_WEDNESDAY, true);
    if ($row['day5'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_THURSDAY, true);
    if ($row['day6'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_FRIDAY, true);
    if ($row['day7'] === 't')
        $recurrencePattern->setDayOfWeek(RecurrencePattern::WEEKDAY_SATURDAY, true);

    // We need to check if we are dealing with MonthNth or YearNth and make sure we pass instance value
    if ($recurrencePattern->getRecurType() == RecurrencePattern::RECUR_MONTHNTH || $recurrencePattern->getRecurType() == RecurrencePattern::RECUR_YEARNTH) {

        // If $row['instance'] is not set or is empty, then we need to set the instance value to 1 (default)
        if (!isset($row['instance']) || is_empty($row['instance'])) {

            $recurrencePattern->setInstance(1);
        }
    }

    // Load the calendar event that was associated to this recurrence and update the new recurrence id
    $event = $loader->get($objType, $row['event_id']);

    // Set the new recurrence to this calendar event
    $event->setRecurrencePattern($recurrencePattern);

    // Save the calendar event
    if ($loader->save($event)) {

        // Update the calendar_events_recurring that it has been imported
        $db->query("UPDATE calendar_events_recurring SET f_imported_to_entity = TRUE WHERE id = $calendarEventRecurringId");
    }
}