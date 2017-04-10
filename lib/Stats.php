<?php
/**
 * Class used to track statistics
 *
 * Right now this class is simply used to send data to statsd
 *
 * @category  Ant
 * @package   Stats
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

if (!defined("STATS_ENABLE"))
	define("STATS_ENABLE", false);

class Stats 
{
    /**
     * Log timing information
     *
     * @param string $stats The metric to in log timing info for.
     * @param float $time The ellapsed time (ms) to log
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     **/
	public static function timing($stat, $time, $sampleRate=1) 
	{
        Stats::send(array($stat => "$time|ms"), $sampleRate);
    }

    /**
     * Increments one or more stats counters
     *
     * @param string|array $stats The metric(s) to increment.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
	public static function increment($stats, $sampleRate=1) 
	{
        Stats::updateStats($stats, 1, $sampleRate);
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string|array $stats The metric(s) to decrement.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
	public static function decrement($stats, $sampleRate=1) 
	{
        Stats::updateStats($stats, -1, $sampleRate);
    }

    /**
     * Updates one or more stats counters by arbitrary amounts.
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int|1 $delta The amount to increment/decrement each metric by.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
	public static function updateStats($stats, $delta=1, $sampleRate=1) 
	{
        if (!is_array($stats)) { $stats = array($stats); }
        $data = array();
        foreach($stats as $stat) {
            $data[$stat] = "$delta|c";
        }

        Stats::send($data, $sampleRate);
    }

    /**
     * Send the metrics over UDP
     */
	public static function send($data, $sampleRate=1) 
	{
		if (!STATS_ENABLE)
			return;

        // sampling
        $sampledData = array();

		if ($sampleRate < 1) 
		{
			foreach ($data as $stat => $value) 
			{
				if ((mt_rand() / mt_getrandmax()) <= $sampleRate) 
				{
                    $sampledData[$stat] = "$value|@$sampleRate";
                }
            }
		} 
		else 
		{
            $sampledData = $data;
        }

        if (empty($sampledData)) { return; }

        // Wrap this in a try/catch - failures in any of this should be silently ignored
		try 
		{
            $host = STATS_DHOST;
            $port = STATS_DPORT;
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (! $fp) { return; }
			foreach ($sampledData as $stat => $value) 
			{
				$key = "ant.";
				if (defined("STATS_PREFIX"))
					$key .= STATS_PREFIX.".";
				$key .= $stat;
                fwrite($fp, "$key:$value");
            }
            fclose($fp);
		} 
		catch (Exception $e) 
		{
        }
    }
}
