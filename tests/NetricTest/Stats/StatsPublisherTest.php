<?php
namespace NetricTest\Stats;

use Netric\Stats\StatsPublisher;

use PHPUnit_Framework_TestCase;


class StatsPublisherTest extends PHPUnit_Framework_TestCase {
    public function testIncrement() {
        StatsPublisherMocker::increment("test-inc");
        $this->assertSame("test-inc:1|c", StatsPublisherMocker::getWrittenData());
    }
    public function testDecrement() {
        StatsPublisherMocker::decrement("test-dec");
        $this->assertSame("test-dec:-1|c", StatsPublisherMocker::getWrittenData());
    }
    public function testTiming() {
        StatsPublisherMocker::timing("test-tim", 100);
        $this->assertSame("test-tim:100|ms", StatsPublisherMocker::getWrittenData());
    }
    public function testGauges() {
        StatsPublisherMocker::gauge("test-gag", 345);
        $this->assertSame("test-gag:345|g", StatsPublisherMocker::getWrittenData());
    }
    public function testUpdateStats() {
        StatsPublisherMocker::updateStat("test-dec", -9);
        $this->assertSame("test-dec:-9|c", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::updateStat("test-inc", 9);
        $this->assertSame("test-inc:9|c", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::updateStat("test-inc", 1.01);
        $this->assertSame("test-inc:1.01|c", StatsPublisherMocker::getWrittenData());
    }
    public function testInternationalStats() {
        $old = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'German');
        StatsPublisherMocker::timing("test", 9.01);
        $this->assertSame("test:9.01|ms", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::gauge("test", 9.01);
        $this->assertSame("test:9.01|g", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::updateStat("test", 1.0001, 0.99999);
        $this->assertSame("test:1.0001|c|@0.99999", StatsPublisherMocker::getWrittenData());
        setlocale(LC_NUMERIC, $old);
    }
    public function testSampleRate() {
        StatsPublisherMocker::increment("test-inc", 0);
        StatsPublisherMocker::decrement("test-dec", 0);
        StatsPublisherMocker::updateStat("test-dec", -9, 0);
        StatsPublisherMocker::updateStat("test-inc", 9, 0);
        $this->assertSame("", StatsPublisherMocker::getWrittenData());
    }
    public function testPauseAndFlushCounts() {
        StatsPublisherMocker::pauseStatsOutput();
        StatsPublisherMocker::increment("test-a");
        StatsPublisherMocker::increment("test-b");
        $this->assertSame("", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::flushStatsOutput();
        $this->assertSame("test-a:1|c\ntest-b:1|c",
            StatsPublisherMocker::getWrittenData());
    }
    public function testPauseAndFlushSameName() {
        StatsPublisherMocker::pauseStatsOutput();
        StatsPublisherMocker::increment("test-inc");
        StatsPublisherMocker::updateStat("test-inc", 3);
        $this->assertSame("", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::flushStatsOutput();
        $this->assertSame("test-inc:4|c",
            StatsPublisherMocker::getWrittenData());
    }
    public function testmaxPacketSize() {
        StatsPublisherMocker::pauseStatsOutput();
        for ($i=0; $i< 100; $i++) {
            StatsPublisherMocker::increment("test-stat-$i");
        }
        StatsPublisherMocker::flushStatsOutput();
        $dummy = StatsPublisherMocker::getWrittenData();
        $chunks = StatsPublisherMocker::getWrittenChunks();
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(512, strlen($chunk));
        }
    }
    public function testPauseAndFlushSameNameTiming() {
        StatsPublisherMocker::pauseStatsOutput();
        StatsPublisherMocker::timing("test-tim", 3);
        StatsPublisherMocker::timing("test-tim", 4);
        $this->assertSame("", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::flushStatsOutput();
        $this->assertSame("test-tim:3|ms\ntest-tim:4|ms",
            StatsPublisherMocker::getWrittenData());
    }
    public function testFlushResumesImmediateSend() {
        StatsPublisherMocker::pauseStatsOutput();
        StatsPublisherMocker::flushStatsOutput();
        $this->assertSame("", StatsPublisherMocker::getWrittenData());
        StatsPublisherMocker::increment("test-a");
        $this->assertSame("test-a:1|c",
            StatsPublisherMocker::getWrittenData());
    }
}
