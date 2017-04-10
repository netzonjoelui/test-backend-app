<?php
class CAntObjectSort
{
    var $fieldName;
    var $order; // Deprecated
    var $direction; // replaced order

    function CAntObjectSort($fieldName, $direction)
    {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
        $this->order = $this->direction;
    }
}
