<?php
/**
 * Test the Entity Query Where condition
 */
namespace NetricTest\EntityQuery;

use Netric\EntityQuery\Where;
use PHPUnit_Framework_TestCase;

class WhereTest extends PHPUnit_Framework_TestCase
{
    /**
     * Make sure we can convert the condition to an array
     */
    public function testToArray()
    {
        $myFieldName = "my_field";
        $myFieldValue = "my_value";
        $where = new Where($myFieldName);
        $where->equals($myFieldValue);
        $arr = $where->toArray();
        $this->assertEquals(Where::COMBINED_BY_AND, $arr['blogic']);
        $this->assertEquals($myFieldName, $arr['field_name']);
        $this->assertEquals($myFieldValue, $arr['value']);
        $this->assertEquals(Where::OPERATOR_EQUAL_TO, $arr['operator']);
    }

    /**
     * Make sure we can load a condition from an array
     */
    public function testFromArray()
    {
        $arrCondtiion = array(
            "blogic" => Where::COMBINED_BY_OR,
            "field_name" => "my_field",
            "operator" => Where::OPERATOR_NOT_EQUAL_TO,
            "value" => "someval",
        );

        $where = new Where();
        $where->fromArray($arrCondtiion);
        $this->assertEquals($arrCondtiion['blogic'], $where->bLogic);
        $this->assertEquals($arrCondtiion['field_name'], $where->fieldName);
        $this->assertEquals($arrCondtiion['operator'], $where->operator);
        $this->assertEquals($arrCondtiion['value'], $where->value);
    }
}