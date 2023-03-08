<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new JSONDB(__DIR__, JSON_UNESCAPED_UNICODE);
    }

    protected function tearDown(): void
    {
        unlink(__DIR__ . '/perf.json');
    }

    public function testInsert(): void
    {
        $i = 0;
        while ($i < 5000) {
            $sum = 0;
            for ($j = 0; $j < 1000; $j++) {
                $start = hrtime(true);
                $this->db->insert('perf', [
                    'test' => $i
                ]);
                $stop = hrtime(true);
                $sum += ($stop - $start) / 1000000;
            }
            $i += $j;
            fprintf(STDOUT, "\nTook average of %f ms to insert 1000 records - BATCH %d", $sum, $i / 1000);
            fflush(STDOUT);
        }

        $perfs = $this->db->select('test')->from('perf')->get();
        $this->assertCount(5000, $perfs);
    }
  }
