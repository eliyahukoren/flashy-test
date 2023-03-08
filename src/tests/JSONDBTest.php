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

  private function getRandomUserData(){
    $fnames = ['Adi', 'Anna', 'Rafael', 'Dikla', 'Eli'];
    $lnames = ['Mor', 'Cohen', 'Koren', 'Smith', 'Black'];
    $unames = ['un123', 'un456', 'un789', 'un010', 'un011'];

    shuffle($fnames);
    shuffle($lnames);
    shuffle($unames);

    $uname = current($unames);
    $fname = current($fnames);
    $lname = current($lnames);
    $id = mt_rand(1, 100);

    return [
      'id' => $id,
      'user_name' => $uname,
      'first_name' => $fname,
      'last_name' => $lname,
    ];
  }

  public function createUser($data)
  {
    $this->db->insert('users', $data);
  }

  private function insertDummyUsers(){
    $this->db->insert('users', [
      'user_name' => 'dummy0',
      'first_name' => null,
      'last_name' => 'DummyL',
    ]);

    $this->db->insert('users', [
      'user_name' => 'dummy1',
      'first_name' => 'Dummy1',
      'last_name' => 'Dummy1L',
    ]);

    $this->db->insert('users', [
      'user_name' => 'dummy2',
      'first_name' => 'Dummy2',
      'last_name' => 'Dummy2L',
    ]);
  }

  private function insertCountOfUsers($count = 1){
    for ($i = 0; $i < $count; $i++) {
      $this->createUser(
        $this->getRandomUserData()
      );
    }
  }

  public function testInsert(): void
  {
    $userData = $this->getRandomUserData();

    printf("Inserting: \n\nId\tUser Name\tFirst Name\tLast Name\n%d\t%s\t%s\t%s", $userData['id'], $userData['user_name'], $userData['first_name'], $userData['last_name']);

    $this->createUser($userData);

    $user = $this->db->select('*')
      ->from('users')
      ->where([
        'user_name' => $userData['user_name'],
        'first_name' => $userData['first_name'],
        'last_name' => $userData['last_name'],
      ], 'AND')
      ->get();

    // add another dummy user
    $this->createUser(['id' => 777,
      'user_name' => 'dummy',
      'first_name' => 'Dummy',
      'last_name' => 'Dummy',
    ]);

    $this->assertEquals($userData['user_name'], $user[0]['user_name']);
  }


  public function testGet(): void
  {
    // insert 5 random users
    $this->insertCountOfUsers(5);

    $users = $this->db->select('*')
    ->from('users')
    ->get();

    $this->assertNotEmpty($users);
  }

  public function testWhere(): void
  {
    // insert 2 random users
    $this->insertCountOfUsers(2);

    $user = $this->getRandomUserData();
    $this->createUser($user);

    $result = $this->db->select('first_name')
        ->from('users')
        ->where([
          'first_name' => $user['first_name'],
        ])
        ->get();


    $this->assertEquals($user['first_name'], $result[0]['first_name']);
  }

  public function testMultiWhereWithOr(): void
  {
    $this->insertDummyUsers();

    $result = $this->db->select('*')->from('users')->where([
      'first_name' => null,
      'user_name' => 'dummy0',
    ])->get();
    $this->assertEquals('DummyL', $result[0]['last_name']);
  }

  public function testWhereWithAnd(): void
  {
    $this->insertDummyUsers();

    $result = $this->db->select('*')->from('users')->where([
      'user_name' => 'dummy1',
      'first_name' => 'Dummy1',
      'last_name' => 'Dummy1L',
    ], JSONDB::AND)->get();


    $this->assertEquals(1, count($result));
    $this->assertEquals('Dummy1', $result[0]['first_name']);
  }

  public function testUpdate(): void
  {
    $this->insertDummyUsers();

    $this->db->update([
        'first_name' => 'Dummy0',
        'last_name' => 'Dummy0L',
      ])
      ->from('users')
      ->where([
        'user_name' => 'dummy1',
      ])
      ->flush();


    $result = $this->db->select('*')
      ->from('users')
      ->where([
        'user_name' => 'dummy1',
      ])
      ->get();

    $this->assertTrue($result[0]['first_name'] == 'Dummy0' && $result[0]['last_name'] == 'Dummy0L');
  }

  public function testDelete(): void
  {
    $this->insertDummyUsers();

    $this->db->delete()
      ->from('users')
      ->where([
        'user_name' => 'dummy1',
      ])
      ->flush();

    $result = $this->db->select('*')
    ->from('users')
    ->where([
      'user_name' => 'dummy1',
    ])
      ->get();

    $this->assertEmpty($result);
  }

}
