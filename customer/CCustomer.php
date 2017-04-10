<?php
class CCustomer extends CAntObject
{
	var $userid;

	function CCustomer($dbh, $id=null, $userid=null)
	{
		$this->userid = $userid;

		parent::CAntObject($dbh, "customer", $id);
	}

	function save($logact = true)
	{
		parent::save();

		// The below is now handled natively in the CAntObject class
		/*
		if ($this->id && $this->userid)
		{
			// Sync with linked contacts
			$cid = CustGetContactId($this->dbh, $this->userid, $this->id);
			if ($cid)
			{
				CustSyncContact($this->dbh, $this->userid, $this->id, $cid, "cust_to_contact");
			}


			// Sync child objects if set to inherit fields
			$result = $this->dbh->Query("select customer_association_types.inherit_fields, customer_associations.customer_id 
										 from customer_associations, customer_association_types where f_child='t' and inherit_fields is not null 
										 and inherit_fields!='' and customer_associations.type_id=customer_association_types.id 
										 and customer_associations.parent_id='".$this->id."'");
			for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
			{
				$inherit_fields = explode(":", $this->dbh->GetValue($result, $i, "inherit_fields"));
				$cust_id = $this->dbh->GetValue($result, $i, "customer_id");

				$child_cust = new CCustomer($this->dbh, $cust_id, $this->userid);

				foreach ($inherit_fields as $fname)
				{
					if ($fname)
					{
						$child_cust->setValue($fname, $this->getValue($fname));
					}
				}
				
				$child_cust->save();
			}
		}
		 */

		return $this->id;
	}
}
?>
