<?php
namespace NetricTest\Stats;

use Netric\Stats\StatsPublisher;

/**
 * Class StatsDMocker
 * @package NetricTest\Stats
 */
class StatsPublisherMocker extends StatsPublisher {
    protected static $writtenData;
    protected static $writtenChunks = array();

    protected static function sendAsUDP($data) {
        self::$writtenData .= $data;
        self::$writtenChunks[] = $data;
    }
    public static function getWrittenData() {
        $data = self::$writtenData;
        self::$writtenData = "";
        return $data;
    }
    public static function getWrittenChunks() {
        $data = self::$writtenChunks;
        self::$writtenChunks = array();
        return $data;
    }
}
