The purpose of this file is to interface with StatsD (or in the future something similar).

See: https://github.com/etsy/statsd

## Usage
This is meant to be subclassed and the static `$host` and `$port` variables
overridden.

```php
use Netric\Stats;

StatsPublisher::increment("something");

StatsPublisher::timing("something", $time);

StatsPublisher::gauge("something", $value);

// Arbitrary valued counters (instead of inc / dec)
StatsPublisher::updateStat("something", 42, 0.1); // 0.1 sample rate

// Buffer UDP output packets
StatsPublisher::pauseStatsOutput();
// Bunch of StatsD::increment() or others

// Sends one UDP packet instead of one for each call
StatsPublisher::flushStatsOutput();
```