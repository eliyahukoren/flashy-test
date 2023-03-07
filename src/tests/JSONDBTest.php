<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JSONDBTest extends TestCase
{
  private $db;

  protected function setUp(): void
  {
    $this->db = new JSONDB(__DIR__);
  }

  public function tearDown(): void
  {
    @unlink(__DIR__ . '/users.json');
  }

  public function testSomething()
  {
    $this->assertEquals(true, true);
  }

}
