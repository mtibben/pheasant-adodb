<?php

namespace PheasantAdodb\Tests;

use Pheasant\Types\Integer;
use Pheasant\Types\String;

class PheasantAdodbTestCase extends \PHPUnit_Framework_TestCase {

  public $pheasant;
  public $testdb_pha_dsn;
  public $testdb_ado_dsn;

  public $pha_connection;
  public $ado_connection;

  public function setUp()
  {
    $this->testdb_pha_dsn = new \Pheasant\Database\Dsn('mysql://root@localhost/pheasantadodb_test1?charset=utf8');
    $this->testdb_ado_dsn = new \Pheasant\Database\Dsn('mysql://root@localhost/pheasantadodb_test2?charset=utf8');

    // initialize a new pheasant
    $this->pheasant = \Pheasant::setup((string)$this->testdb_pha_dsn);

    $this->pha_connection = new \PheasantAdodb\Connection($this->pheasant->connection());

    $this->ado_connection = &NewADOConnection('mysqlt');
    $this->ado_connection->Connect(
      $this->testdb_ado_dsn->host,
      $this->testdb_ado_dsn->user,
      $this->testdb_ado_dsn->pass,
      $this->testdb_ado_dsn->database);
    $this->ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);


    // wipe sequence pool
    $this->pheasant->connection()
      ->sequencePool()
      ->initialize()
      ->clear()
      ;
  }

  public function assertTableExists($table)
  {
    $this->assertTrue($this->pheasant->connection()->table($table)->exists());
  }

  // Helper to drop and re-create a table
  public function table($connection, $name, $columns)
  {
    $table = $connection->table($name);

    if($table->exists()) $table->drop();

    $table->create($columns);

    $this->assertTableExists($name);

    return $table;
  }

  public function initUserTables()
  {
    $this->buildUserTable($this->pheasant->connection());

    $adodb = new \Pheasant\Database\Mysqli\Connection($this->testdb_ado_dsn);
    $this->buildUserTable($adodb);
    $adodb->close();
  }

  public function buildUserTable($connection)
  {
    $table = $this->table($connection, 'user', array(
      'userid'=>new Integer(8, 'primary auto_increment'),
      'firstname'=>new String(),
      'lastname'=>new String(),
      ));

    // create some users
    $table->insert(array('userid'=>null,'firstname'=>'Frank','lastname'=>'Castle'));
    $table->insert(array('userid'=>null,'firstname'=>'Cletus','lastname'=>'Kasady'));
    $table->insert(array('userid'=>null,'firstname'=>'Bob','lastname'=>'Smith'));
    $table->insert(array('userid'=>null,'firstname'=>'John','lastname'=>'Jones'));
    $table->insert(array('userid'=>null,'firstname'=>'George','lastname'=>'Harrison'));
    $table->insert(array('userid'=>null,'firstname'=>'Nancy','lastname'=>'Drew'));
  }

  public function fixBadAdodbAutoloading()
  {
    global $ADODB_INCLUDED_LIB;

    if (empty($ADODB_INCLUDED_LIB) && function_exists('_array_change_key_case'))
      $ADODB_INCLUDED_LIB=1;
  }
}
