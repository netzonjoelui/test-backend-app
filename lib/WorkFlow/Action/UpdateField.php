<?php
/**
 * Action to handle approval requests
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	Approval
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");

/**
 * Class for approval workflow actions
 */
class WorkFlow_Action_UpdateField extends WorkFlow_Action_Abstract
{
	/**
	 * Execute action that will update a field of object
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	public function execute($obj, $act)
	{
		$updateField = $act->update_field;
		$updateTo = $act->update_to;

		// First check if we are updating an associated field
		if (strpos($updateField, '.'))
		{
			// 0 = field_name, 1 = ref_obj_type, 2 = ref_obj_field
			$parts = explode(".", $updateField);
			if (count($parts) == 3)
			{
				$fld = $obj->fields->getField($parts[0]);

				if ($fld["type"] == "object" && !$fld['subtype'])
				{
					$val = $obj->getValue($parts[0]);

					if ($val)
					{
						$ref_parts = explode(":", $val);

						if (count($ref_parts) > 1)
						{
							if ($ref_parts[0] == $parts[1]) // Make sure we are working with the same type of object
							{
								$ref_obj = new CAntObject($this->dbh, $ref_parts[0], $ref_parts[1], $this->user);
								$ref_obj->setValue($parts[2], $updateTo);
								$ref_obj->save();
							}
						}
					}
				}
				else if ($fld["type"] == "object" && $fld['subtype'] && $fld['subtype'] == $parts[1])
				{
					$val = $obj->getValue($parts[0]);

					if ($val)
					{
						$ref_obj = new CAntObject($this->dbh, $fld['subtype'], $val, $this->user);
						$ref_obj->setValue($parts[2], $updateTo);
						$ref_obj->save();
					}
				}
			}
		}
		else
		{
			$field = $obj->fields->getField($updateField);
			if ($field['type'] == "fkey_multi" || $field['type'] == "object_multi")
			{
				$obj->setMValue($updateField, $updateTo);
			}
			else
			{
				$update_to = $updateTo;

				$all_fields = $obj->fields->getFields();
				foreach ($all_fields as $fname=>$fdef)
				{
					if ($fdef['type'] != "object_multi" && $fdef['type'] != "fkey_multi")
					{
						if ($update_to == "<%".$fname."%>")
							$update_to = $obj->getValue($fname);
					}
				}

				$obj->setValue($updateField, $update_to);
			}
			$obj->save();
		}
	}
}
