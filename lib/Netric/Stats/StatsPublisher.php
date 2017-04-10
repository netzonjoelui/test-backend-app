<?php
/**
 * Sends statistics to an instance of the statsd daemon over UDP
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 **/
namespace Netric\Stats;

use Netric\Config\ConfigLoader;
use Netric\ServiceManager\ServiceLocatorInterface;

/**
 * Stats publisher
 * @package Netric\Stats
 */
class StatsPublisher
{
    /**
     * Hostname of the StatsD server
     *
     * @var string
     */
    protected static $host = null;

    /**
     * UDP-port of the statsd-server
     *
     * @var string
     */
    protected static $port = '8125';

    /**
     * Maximum payload we may cramp into a UDP packet
     */
    const MAX_PACKET_SIZE = 512;

    /**
     * If true, stats are added to a queue until a flush is triggered
     * If false, stats are sent immediately, one UDP packet per call
     *
     * @var bool
     */
    protected static $addStatsToQueue = false;

    /**
     * Internal queue of stats to be sent
     *
     * @var array
     */
    protected static $queuedStats = array();

    /**
     * Internal representation of queued counters to be sent.
     * This is used to aggregate increment/decrements before sending them.
     *
     * @var array
     */
    protected static $queuedCounters = array();

    /**
     * Namespace for measure - prefix to string
     *
     * @var string
     */
    protected static $prefixNamespace = '';

    /**
     * Setup the parameters based on the dev environment if no params are passed
     *
     * @param string $host The host of the StatsD service
     * @param string $port The port that StatsD is listening on
     */
    public static function setup($host=null, $port=null)
    {
        // If manually passed into the setup function then just set local variables
        if ($host)
        {
            static::$host = $host;
            if ($port)
            {
                static::$port = $port;
            }
        }
        else
        {
            // Pull from global config
            //$config = Config::getInstance();

            $configLoader = new \Netric\Config\ConfigLoader();
            $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

            // Setup the new config
            $config = $configLoader->fromFolder(__DIR__ . "/../../../config", $applicationEnvironment);

            // If config failed to load then there is something wrong with our installation
            if ($config === null) {
                throw new \RuntimeException("Could not load config files from: " . __DIR__ . "/../../../config");
            }

            if ($config->stats['enabled'])
            {
                static::$host = $config->stats['host'];

                if ($config->stats['port'])
                {
                    static::$port = $config->stats['port'];
                }

                if ($config->stats['prefix'])
                {
                    static::$prefixNamespace = $config->stats['prefix'];
                }

            }
            else
            {
                // Set hostname to null just in case
                static::$host = null;
            }
        }

    }

    /**
     * Log timing information
     *
     * @param string $stat The metric to in log timing info for.
     * @param float $time The ellapsed time (ms) to log
     * @param float $sampleRate the rate (0-1) for sampling.
     **/
    public static function timing($stat, $time, $sampleRate=1.0)
    {
        static::queueStats(array($stat => self::num($time) . "|ms"), $sampleRate);
    }

    /**
     * Report the current value of some gauged value.
     *
     * @param string|array $stat The metric to report on
     * @param float $value The value for this gauge
     */
    public static function gauge($stat, $value)
    {
        static::queueStats(array($stat => self::num($value) . "|g"));
    }

    /**
     * Increments one stats counter
     *
     * @param string $stat The metric to increment.
     * @param float $sampleRate the rate (0-1) for sampling.
     **/
    public static function increment($stat, $sampleRate=1.0)
    {
        static::updateStat($stat, 1, $sampleRate);
    }

    /**
     * Decrements one counter.
     *
     * @param string $stat The metric to decrement.
     * @param float $sampleRate the rate (0-1) for sampling.
     **/
    public static function decrement($stat, $sampleRate=1.0)
    {
        static::updateStat($stat, -1, $sampleRate);
    }

    /**
     * Pause and collect all reported stats until flushStatsOutput() is called.
     */
    public static function pauseStatsOutput()
    {
        static::$addStatsToQueue = true;
    }

    /**
     * Send all stats generated AFTER a call to pauseStatsOutput()
     * and resume immediate sending again.
     */
    public static function flushStatsOutput()
    {
        static::$addStatsToQueue = false;
        static::sendAllStats();
    }

    /**
     * Updates a counter by an arbitrary amount.
     *
     * @param string $stat The metric to update.
     * @param float $delta The amount to increment/decrement the metric by.
     * @param float $sampleRate the rate (0-1) for sampling.
     **/
    public static function updateStat($stat, $delta=1, $sampleRate=1.0)
    {
        $deltaStr = self::num($delta);

        // Check if we need to down-sample
        if ($sampleRate < 1)
        {
            if ((mt_rand() / mt_getrandmax()) <= $sampleRate)
            {
                static::$queuedStats[] = "$stat:$deltaStr|c|@". self::num($sampleRate);
            }
        }
        else
        {
            if (!isset(static::$queuedCounters[$stat]))
            {
                static::$queuedCounters[$stat] = 0;
            }
            static::$queuedCounters[$stat] += $delta;
        }

        // Send immediately if we are not caching stats for a batch send
        if (!static::$addStatsToQueue)
        {
            static::sendAllStats();
        }
    }


    /**
     * Add stats to the queue or send them immediately depending on
     * self::$addStatsToQueue
     *
     * @param array $data The data to be queued.
     * @param float $sampleRate the rate (0-1) for sampling
     */
    protected static function queueStats($data, $sampleRate=1.0)
    {
        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    static::$queuedStats[] = "$stat:$value|@". self::num($sampleRate);
                }
            }
        } else {
            foreach($data as $stat => $value) {
                static::$queuedStats[] = "$stat:$value";
            }
        }
        if (!static::$addStatsToQueue) {
            static::sendAllStats();
        }
    }

    /**
     * Flush the queue and send all the stats we have.
     */
    protected static function sendAllStats()
    {
        if (empty(static::$queuedStats) && empty(static::$queuedCounters))
            return;

        // Setup before sending
        if (null === static::$host)
        {
            static::setup();
        }

        foreach(static::$queuedCounters as $stat => $value) {
            $line = "$stat:$value|c";
            static::$queuedStats[] = $line;
        }

        // Prepend namespace if set
        if (static::$prefixNamespace)
        {
            foreach (static::$queuedStats as $key=>$line)
            {
                $buf = static::$queuedStats[$key];
                static::$queuedStats[$key] = static::$prefixNamespace . "." . $buf;
            }
        }

        self::sendLines(static::$queuedStats);
        static::$queuedStats = array();
        static::$queuedCounters = array();
    }

    /**
     * Squirt the metrics over UDP
     *
     * @param array $data the data to be sent.
     */
    protected static function sendAsUDP($data)
    {
        // Wrap this in a try/catch -
        // failures in any of this should be silently ignored
        try {
            $host = static::$host;
            $port = static::$port;
            $fp = @fsockopen("udp://$host", $port, $errno, $errstr);
            if (! $fp) { return; }
            // Non-blocking I/O, please.
            stream_set_blocking($fp, 0);
            fwrite($fp, $data);
            fclose($fp);
        } catch (Exception $e) {
        }

    }

    /**
     * Send these lines via UDP in groups of self::MAX_PACKET_SIZE bytes
     * Sending UDP packets bigger than ~500-1000 bytes will mean the packets
     * get fragmented, and if ONE fragment doesn't make it, the whole datagram
     * is thrown out.
     *
     * @param array $lines The lines to be sent to the stats-Server
     */
    protected static function sendLines($lines)
    {
        $out = array();
        $chunkSize = 0;
        $i = 0; $lineCount = count($lines);
        while ($i < $lineCount) {
            $line = $lines[$i];
            $len = strlen($line) + 1;
            $chunkSize += $len;
            if ($chunkSize > self::MAX_PACKET_SIZE) {
                static::sendAsUDP(implode("\n", $out));
                $out = array($line);
                $chunkSize = $len;
            } else {
                $out[] = $line;
            }
            $i++;
        }
        static::sendAsUDP(implode("\n", $out));
    }

    /**
     * This is the fastest way to ensure locale settings don't affect the
     * decimal separator. Really, this is the only way (besides temporarily
     * changing the locale) to really get what we want.
     *
     * @param string $value the value to be "translated" to the needed locale
     * @return string the "translated" value
     */
    protected static function num($value)
    {
        return strtr($value, ',', '.');
    }
}

