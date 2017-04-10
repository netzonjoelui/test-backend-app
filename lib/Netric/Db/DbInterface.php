<?php

/*
 * Short description for file
 * 
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  @author joe <sky.stebnicki@aereus.com>
 *  @copyright 2014 Aereus
 */
namespace Netric\Db;

/**
 * Description of DbInterface
 *
 * @author joe
 */
interface DbInterface 
{
    /**
     * Escape a string
     *
     * @param string $value
     * @return string Escaped string
     */
    public function escape($value);

    /**
     * Escape a number
     *
     * @param int $number
     * @return string Escaped string
     */
    public function escapeNumber($number);

    /**
     * Escape a date string
     *
     * @param string $date
     * @return string Escaped string
     */
    public function escapeDate($date);

    /**
     * Return number of rows for a given result
     *
     * @return int
     */
    public function getNumRows($result);

    /**
     * Execute an SQL query
     *
     * @param $sql The sql to run
     * @return result
     */
    public function query($sql);
}
