<?php

namespace PheasantAdodb\Tests;

class RecordSetTest extends PheasantAdodbTestCase {

  public function setUp()
  {
    parent::setUp();
    $this->initUserTables();
  }

  public function testFieldCount()
  {
    $sql = "SELECT 1, 2, 3";
    $ado_result = $this->ado_connection->Execute($sql)->FieldCount();
    $pha_result = $this->pha_connection->Execute($sql)->FieldCount();
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testFetchField()
  {
    $sql = "SELECT 1 as one, 2 as two, 3 as three";
    $ado_result = $this->ado_connection->Execute($sql)->FetchField(2)->name;
    $pha_result = $this->pha_connection->Execute($sql)->FetchField(2)->name;
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testFetchRow()
  {
    $sql = "SELECT 1 as one, 2 as two, 3 as three";
    $ado_result = $this->ado_connection->Execute($sql)->FetchRow();
    $pha_result = $this->pha_connection->Execute($sql)->FetchRow();
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testFetchRowFromNoResults()
  {
    $sql = "SELECT 1 FROM user WHERE 1 = 0";

    $ado_result = $this->ado_connection->Execute($sql)->FetchRow();
    $pha_result = $this->pha_connection->Execute($sql)->FetchRow();
    $this->assertEquals($ado_result, $pha_result);

    $ado_errmsg = $this->ado_connection->ErrorMsg();
    $pa_errmsg = $this->pha_connection->ErrorMsg();
    $this->assertEquals($ado_errmsg, $pa_errmsg);

    $ado_errno = $this->ado_connection->ErrorNo();
    $pa_errno = $this->pha_connection->ErrorNo();
    $this->assertEquals($ado_errno, $pa_errno);
  }

  public function testIterator()
  {
    $sql = "SELECT * FROM user";

    $adodb = array();
    foreach($this->ado_connection->Execute($sql) as $row)
    {
      $adodb[] = $row;
    }

    $adoph = array();
    foreach($this->pha_connection->Execute($sql) as $row)
    {
      $adoph[] = $row;
    }

    $this->assertEquals($adodb, $adoph);
  }

  public function testGetAll()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetAll(2);
    $pha_result = $this->pha_connection->Execute($sql)->GetAll(2);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetArray()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetArray(2);
    $pha_result = $this->pha_connection->Execute($sql)->GetArray(2);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetArrayZero()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetArray(0);
    $pha_result = $this->pha_connection->Execute($sql)->GetArray(0);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetArrayMore()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetArray(20);
    $pha_result = $this->pha_connection->Execute($sql)->GetArray(20);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetAssoc()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetAssoc();
    $pha_result = $this->pha_connection->Execute($sql)->GetAssoc();
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetAssocForceArray()
  {
    $sql = "SELECT userid, firstname FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetAssoc(true);
    $pha_result = $this->pha_connection->Execute($sql)->GetAssoc(true);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetAssocFirst2Cols()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->Execute($sql)->GetAssoc(false, true);
    $pha_result = $this->pha_connection->Execute($sql)->GetAssoc(false, true);
    $this->assertEquals($ado_result, $pha_result);
  }

}
