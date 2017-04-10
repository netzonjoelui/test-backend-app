<?php
class CAntObjectCond
{
	var $blogic;
	var $fieldName;
	var $operator;
	var $value;

	function CAntObjectCond($blogic=null, $fieldName, $operator, $value)
	{
		$this->fieldName = $fieldName;
		$this->operator = $operator;
		$this->value = $value;
		$this->blogic = $blogic;
	}
}
