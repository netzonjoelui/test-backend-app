<?php
/*
 * Order a query by entity fields
 */
namespace Netric\EntityQuery;

/**
 * Condition for sorting a query
 *
 * @author joe
 */
class OrderBy
{
    /**
     * Field name
     *
     * @var string
     */
    public $fieldName = "";

    /**
     * Direction constants
     */
    const DESCENDING = "DESC";
    const ASCENDING = "ASC";

    /**
     * Field name
     *
     * @var string
     */
    public $direction = self::DESCENDING;

    /**
     * @param string $fieldName The name of the entity field to query
     * @param string $direction Optional sort direction self::ASCENDING or DESCENDING
     */
    public function __construct($fieldName = "", $direction = self::ASCENDING)
    {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
    }

    /**
     * Convert to an associative array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            "field_name" => $this->fieldName,
            "direction" => $this->direction,
        );
    }

    /**
     * Convert to an associative array
     *
     * @param array $data Associative array of order by to load
     */
    public function fromArray($data)
    {
        if (isset($data['field_name']))
            $this->fieldName = $data['field_name'];

        if (isset($data['direction']))
            $this->direction = $data['direction'];
    }

}