<?php
/**
 * Database DataMapper for recurrence pattern
 *
 * This DataMapper is typically loaded from the service manager
 * with $serviceLocation->get("Entity_RecurrenceDataMapper")
 * which will setup all the necessary dependencies.
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\Recurrence;

use Netric\Db;
use Netric\EntityDefinitionLoader;
use Netric\Error;

class RecurrenceDataMapper extends \Netric\DataMapperAbstract
{
	/**
	 * Database handle
	 *
	 * @var \Netric\Db\DbInterface
	 */
	private $dbh = null;

    /**
     * Entity definition loader
     *
     * This is mostly used to get the id of a textual objType
     *
     * @var EntityDefinitionLoader
     */
    private $entityDefinitionLoader = null;

    /**
     * Last error message
     *
     * @var string
     */
    private $lastError = "";

	/**
	 * Class constructor to set up dependencies
	 *
	 * @param \Netric\Account\Account $account The required account/tennant
	 * @param \Netric\Db\DbInterface $dbh Handle to account database
     * @param EntityDefinitionLoader $entDefLoader Used to get the id of objType
	 */
	public function __construct(\Netric\Account\Account $account, Db\DbInterface $dbh, EntityDefinitionLoader $entDefLoader)
	{
		// The base DataMapper always has a reference to account
		$this->account = $account;
		$this->dbh = $dbh;
        $this->entityDefinitionLoader = $entDefLoader;
	}
	
	/**
	 * Save a recurrence pattern to the database
     *
     * When the pattern is saved for the first time, it can use the $useId field
     * to see if it should be using a reserved ID or request a new one. This is
     * sometimes used when we need to save a reference to a recurrence in an entity
     * before saving the details of said recurrence.
	 *
	 * @param \Netric\Entity\Recurrence\RecurrencePattern $recurPattern
     * @param int $useId We can reserve an ID to use when creating a new instace via getNextId()
	 * @return int Unique id of the pattern on success or null on failure this $this->lastError set
	 * @throws \InvalidArgumentException in the instance that the pattern is not valid
     * @throws \RuntimeException if saving failed for some reason
     */
	public function save(RecurrencePattern $recurPattern, $useId = null)
	{
		if (!$recurPattern->validatePattern())
            throw new \InvalidArgumentException($recurPattern->getLastError()->getMessage());

        $data = $recurPattern->toArray();
        $dayOfWeekMask = $recurPattern->getDayOfWeekMask();

        if (!$data['obj_type'])
            throw new \InvalidArgumentException("No object type set for recurring pattern");

        // Get object type id
        $def = $this->entityDefinitionLoader->get($data['obj_type']);

		$dbh = $this->dbh;
        $toUpdate = array(
            'object_type_id' => $dbh->escapeNumber($def->getId()),
            'object_type' => "'" . $dbh->escape($data['obj_type']) . "'",
            'date_processed_to' => $dbh->escapeDate($data['date_processed_to']),
            'parent_object_id' => $dbh->escapeNumber($data['first_entity_id']),
            'type' => $dbh->escapeNumber($data['recur_type']),
            'interval' => $dbh->escapeNumber($data['interval']),
            'date_start' => $dbh->escapeDate($data['date_start']),
            'date_end' => $dbh->escapeDate($data['date_end']),
            'dayofmonth' => $dbh->escapeNumber($data['day_of_month']),
            'instance' => $dbh->escapeNumber($data['instance']),
            'monthofyear' => $dbh->escapeNumber($data['month_of_year']),
            'f_active' => (($data['f_active']) ? "'t'" : "'f'"),
            'ep_locked' => $dbh->escapeNumber($data['ep_locked']),
        );

        /*
         * It is possible that the id of the recurrence pattern was pre-set with the
         * next unique id prior to it having been saved in the database. If this is the
         * case we will need to make sure we include the id in the insert statement
         * because we cannot ever assume that if it already has an id that it was previously saved.
         */
        if ($recurPattern->getId())
        {
            $toUpdate['id'] = $recurPattern->getId();
        }

        $sql = "select id from object_recurrence WHERE id=" . $dbh->escapeNumber($recurPattern->getId());
		if ($recurPattern->getId() && $dbh->getNumRows($dbh->query($sql)))
        {
			$upd = "";
			foreach ($toUpdate as $fname => $fval)
            {
				if ($upd) $upd .= ", ";
				$upd .= $fname . "=" . $fval;
			}

			if ($upd) $upd .= ", ";
			$upd .= "dayofweekmask[1]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_SUNDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[2]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_MONDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[3]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_TUESDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[4]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_WEDNESDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[5]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_THURSDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[6]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_FRIDAY) ? 't' : 'f') . "', ";
			$upd .= "dayofweekmask[7]='" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_SATURDAY) ? 't' : 'f') . "'";

			$query = "UPDATE object_recurrence SET $upd WHERE id='" . $recurPattern->getId() . "';";
            $query .= "select '" . $recurPattern->getId() . "' as id;";
		}
		else
		{
			if ($useId)
				$toUpdate['id'] = $useId;

			$flds = "";
			$vls = "";
			foreach ($toUpdate as $fname => $fval)
            {
				if ($flds) {
					$flds .= ", ";
					$vls .= ", ";
				}

				$flds .= $fname;
				$vls .= $fval;
			}

			if ($flds) {
				$flds .= ", ";
				$vls .= ", ";
			}

			$flds .= "dayofweekmask[1],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_SUNDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[2],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_MONDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[3],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_TUESDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[4],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_WEDNESDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[5],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_THURSDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[6],";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_FRIDAY) ? 't' : 'f') . "', ";
			$flds .= "dayofweekmask[7]";
			$vls .= "'" . (($dayOfWeekMask & RecurrencePattern::WEEKDAY_SATURDAY) ? 't' : 'f') . "'";

			$query = "INSERT INTO object_recurrence($flds) VALUES($vls); ";

			if ($useId)
				$query .= "select '" . $useId . "' as id;";
			else
				$query .= "select currval('object_recurrence_id_seq') as id;";
		}

		//echo "\n-----\nSAVE: " . $query . "\n-----\n";
		$result = $dbh->query($query);
        if (!$result)
            throw new \RuntimeException("Error saving recurrence: " . $dbh->getLastError());

		if (!$recurPattern->getId())
        {
			if ($dbh->getNumRows($result))
            {
				$recurPattern->setId($dbh->getValue($result, 0, "id"));
			}
		}

		return $recurPattern->getId();
	}

	/**
	 * Secure a unique id to use before it is saved
     *
	 * @return int|bool false if fails
	 */
	public function getNextId()
	{
		$dbh = $this->dbh;
		$ret = false;

		$query = "select nextval('object_recurrence_id_seq') as id;";
		$result = $dbh->query($query);
		if ($dbh->getNumRows($result))
        {
			$ret = $dbh->getValue($result, 0, "id");
		}

		return $ret;
	}

	/**
	 * Load up an entity recurrence pattern by id
	 *
	 * @param id $id The unique id of the pattern to load
	 * @return RecurrencePattern
     * @throws \InvalidArgumentException if the id passed is not a valid number
	 */
	public function load($id)
	{
        if (!is_numeric($id))
            throw new \InvalidArgumentException("First param must be a number");

		$dbh = $this->dbh;

		$query = "SELECT id, object_type_id, object_type, date_processed_to, parent_object_id,
                    type, interval, date_start,
					date_end, dayofmonth, instance, monthofyear, ep_locked,
					dayofweekmask[1] as day1, dayofweekmask[2] as day2, dayofweekmask[3] as day3,
					dayofweekmask[4] as day4, dayofweekmask[5] as day5, dayofweekmask[6] as day6,
					dayofweekmask[7] as day7
				  FROM object_recurrence WHERE id=" . $dbh->escapeNumber($id);
		$result = $dbh->query($query);
        //echo "\n-----\nLOAD: " . $query . "\n-----\n";
		if ($dbh->getNumRows($result))
        {
			$row = $dbh->GetRow($result, 0);

            $data = array(
                "id" => $row['id'],
                "recur_type" => $row['type'],
                "obj_type" => $row['object_type'],
                "date_processed_to" => $row['date_processed_to'],
                "first_entity_id" => $row['parent_object_id'],
                "interval" => $row['interval'],
                "date_start" => $row['date_start'],
                "date_end" => $row['date_end'],
                "day_of_month" => $row['dayofmonth'],
                "month_of_year" => $row['monthofyear'],
                "instance" => $row['instance'],
                "ep_locked" => $row['ep_locked'],
            );

			// Load recurrence rules
			if ($row['object_type']) {
				$def = $this->entityDefinitionLoader->get($row['object_type']);
				$data['field_date_start'] = $def->recurRules['field_date_start'];
                $data['field_time_start'] = $def->recurRules['field_time_start'];
                $data['field_date_end'] = $def->recurRules['field_date_end'];
                $data['field_time_end'] = $def->recurRules['field_time_end'];
			}

            // Create recurrence pattern to return
            $recurPattern = new RecurrencePattern();
            $recurPattern->fromArray($data);

            // Now set weekday bits
            if ($row['day1'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
            if ($row['day2'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_MONDAY, true);
            if ($row['day3'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_TUESDAY, true);
            if ($row['day4'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_WEDNESDAY, true);
            if ($row['day5'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_THURSDAY, true);
            if ($row['day6'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_FRIDAY, true);
            if ($row['day7'] == 't')
                $recurPattern->setDayOfWeek(RecurrencePattern::WEEKDAY_SATURDAY, true);


            // Make sure that we start tracking changes from now on
            $recurPattern->resetIsChanged();

            // Cleanup
            $dbh->freeResults($result);

			return $recurPattern;
		}

		return null;
	}

    /**
     * Delete a recurrence pattern
     *
     * @param RecurrencePattern $recurrencePattern
     * @return bool
     */
    public function delete(RecurrencePattern $recurrencePattern)
    {
        if (!$recurrencePattern->getId())
            throw new \InvalidArgumentException("You cannot delete a pattern that has not been saved");
        return $this->deleteById($recurrencePattern->getId());
    }

	/**
	 * Delete recurrence pattern by id
	 *
	 * @param int $id The unique id of the recurring pattern to delete
     * @return bool true on success, false on failure
	 */
	public function deleteById($id)
	{
		if (!is_numeric($id))
			return false;

		if ($this->dbh->query("delete from object_recurrence where id='" . $id . "'"))
        {
			return true;
		}
        else
        {
			$this->lastError = $this->dbh->getLastError();
			return false;
		}
	}

    /**
     * Return the last error that occurred
     *
     * @return Error\Error
     */
    public function getLastError()
    {
        if ($this->lastError)
            return new Error\Error($this->lastError);
        else
            return null;
    }

	/**
	 * Select patterns that have not been processed to a specified date
	 *
	 * This gets a list of pattern IDs that have not been processed to
	 * the date specified and date end is after the date specified.
	 *
	 * @param string $objType The object type to select patterns for
	 * @param \DateTime $dateTo The date to indicate if a pattern is stale
	 * @return array of IDs of stale patterns
	 */
	public function getStalePatternIds($objType, \DateTime $dateTo)
	{
		$ret = array();

		$def = $this->entityDefinitionLoader->get($objType);
		$dateToString = $dateTo->format("Y-m-d");
		$query = "SELECT id FROM object_recurrence
				  WHERE f_active is true AND
				  date_processed_to<'" . $dateToString . "'
				  AND (date_end is null or date_end>='" . $dateToString . "')
				  AND object_type_id='" . $def->getId(). "'";
		$result = $this->dbh->query($query);
		$num = $this->dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$ret[] = $this->dbh->getValue($result, $i, "id");
		}
		$this->dbh->freeResults($result);

		return $ret;
	}
}
