<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2015-2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Application\Schema;


/**
 * Class representing a property/column/field for a bucket
 */
class SchemaProperty
{
    /**
     * Property/column types supported
     */

    // Unique ID
    const TYPE_SERIAL = 'serial';
    const TYPE_BIGSERIAL = 'bigserial';

    // Numeric
    const TYPE_NUMERIC = 'numeric';
    const TYPE_INT = 'integer';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_BIGINT = 'bigint';
    const TYPE_DOUBLE = 'double precision';
    const TYPE_REAL = 'real';

    // Strings
    const TYPE_CHAR = 'character(1)';
    const TYPE_CHAR_2 = 'character varying(2)';
    const TYPE_CHAR_4 = 'character varying(4)';
    const TYPE_CHAR_6 = 'character varying(6)';
    const TYPE_CHAR_8 = 'character varying(8)';
    const TYPE_CHAR_16 = 'character varying(16)';
    const TYPE_CHAR_32 = 'character varying(32)';
    const TYPE_CHAR_64 = 'character varying(64)';
    const TYPE_CHAR_128 = 'character varying(128)';
    const TYPE_CHAR_256 = 'character varying(256)';
    const TYPE_CHAR_512 = 'character varying(512)';
    const TYPE_CHAR_TEXT = 'text';

    // Bool
    const TYPE_BOOL_ARRAY = 'boolean[]';
    const TYPE_BOOL = 'boolean';

    // Indexed tokens for searching text naturally
    const TYPE_TEXT_TOKENS = 'tsvector';

    // Raw binary data
    const TYPE_BINARY_STRING = 'bytea';
    const TYPE_BINARY_OID = 'oid'; // deprecated

    // Date and Time
    const TYPE_TIME_WITH_TIME_ZONE = 'time with time zone';
    const TYPE_TIMESTAMP = 'timestamp with time zone';
    const TYPE_DATE = 'date';
}