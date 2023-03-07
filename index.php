
<?php
$autoLoader = __DIR__ . "/src/autoload.php";

include_once $autoLoader;

function getUsersArray(){
  return [
    ['id' => 1, 'user_name' => 'un121', 'first_name' => 'Eli', 'last_name' => 'Koren'],
    ['id' => 2, 'user_name' => 'un122', 'first_name' => 'Dikla', 'last_name' => 'Cohen'],
    ['id' => 3, 'user_name' => 'un123', 'first_name' => 'Rafael', 'last_name' => 'Mor'],
    ['id' => 4, 'user_name' => 'un123', 'first_name' => 'Michael', 'last_name' => 'Mulik'],
    ['id' => 5, 'user_name' => 'un124', 'first_name' => 'Shahaf', 'last_name' => 'Karp'],
    ['id' => 6, 'user_name' => 'un125', 'first_name' => 'Kobi', 'last_name' => 'Biton'],
    ['id' => 7, 'user_name' => 'un126', 'first_name' => 'Bar', 'last_name' => 'Nashalsky'],
    ['id' => 8, 'user_name' => 'un127', 'first_name' => 'Or', 'last_name' => 'Malka'],
    ['id' => 9, 'user_name' => 'un128', 'first_name' => 'Danit', 'last_name' => 'Keren'],
    ['id' => 10, 'user_name' => 'un129', 'first_name' => 'Karin', 'last_name' => 'Yarhi'],
  ];
}

function printResult($data){
  $header = [];
  $body = [];
  $row = [];

  foreach($data as $entity){
    foreach( $entity as $k => $v){
      if( !in_array($k, $header) ){
        array_push($header, $k);
      }

      array_push($row, $v);
    }
    array_push($body, implode("\t", $row));
    $row = [];

  }

  echo implode("\t", $header) . "\n";
  echo implode("\n", $body) . "\n";
}

unlink(__DIR__ . "/db/users.json");

$jsonDb = new JSONDB(__DIR__ . "/db");

$users = getUsersArray();


// insert
foreach($users as $user){
  $jsonDb->insert(
    'users',
    $user
  );
}

echo "INSERT DONE.\n\n";

// select all
$allFields = $jsonDb->select()
  ->from('users')
  ->get();

echo "SHOW ALL FIELDS:\n";
// print_r($allFields[0]);
printResult($allFields);

// select specific fields
$selectedFields = $jsonDb->select('id, user_name')
  ->from('users')
  ->get();

echo "\nSHOW SELECTED FIELDS:\n";
printResult($selectedFields);
// print_r($selectedFields[0]);

// $json = json_encode($users);
//print_r($json);

// update
$jsonDb->update( ['first_name' => 'Eliyahu'] )
  ->from('users')
  ->where( ['id' => 1] )
  ->flush();

echo "\nUPDATE DONE.\n\n";

$updatedFields = $jsonDb->select('id, user_name, first_name')
  ->from('users')
  ->get();

echo "SHOW SELECTED AFTER UPDATE:\n";
printResult($updatedFields);
// print_r($updatedFields);

// select sorted
$sortedUsers = $jsonDb->select('id, user_name, first_name')
  ->from('users')
  ->orderBy('first_name', JSONDB::ASC)
  ->get();

echo "\nSHOW SORTED RESULT:\n";
printResult($sortedUsers);

$jsonDb->delete()
  ->from('users.json')
  ->where(['id' => 1])
  ->flush();

echo "\nDELETE DONE.\n\n";

$selectedFields = $jsonDb->select('id, user_name, first_name')
  ->from('users')
  ->get();

echo "SHOW SELECTED AFTER DELETE:\n";
printResult($selectedFields);



