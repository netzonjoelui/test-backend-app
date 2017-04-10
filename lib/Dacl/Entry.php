<?php
class Dacl_Entry
{
	var $groups;
	var $users;
	var $id;

	function __construct($id=null, $parent=null)
	{
		$this->id = $id;
		$this->parent_id = $parent;
		$this->groups = array();
		$this->users = array();
	}
}
