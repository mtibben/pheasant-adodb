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
    $adoResult = $this->adoConnection->Execute($sql)->FieldCount();
    $phaResult = $this->phaConnection->Execute($sql)->FieldCount();
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testFetchField()
  {
    $sql = "SELECT 1 as one, 2 as two, 3 as three";
    $adoResult = $this->adoConnection->Execute($sql)->FetchField(2)->name;
    $phaResult = $this->phaConnection->Execute($sql)->FetchField(2)->name;
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testFetchRow()
  {
    $sql = "SELECT 1 as one, 2 as two, 3 as three";
    $adoResult = $this->adoConnection->Execute($sql)->FetchRow();
    $phaResult = $this->phaConnection->Execute($sql)->FetchRow();
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testFetchRowFromNoResults()
  {
    $sql = "SELECT 1 FROM user WHERE 1 = 0";

    $adoResult = $this->adoConnection->Execute($sql)->FetchRow();
    $phaResult = $this->phaConnection->Execute($sql)->FetchRow();
    $this->assertEquals($adoResult, $phaResult);

    $ado_errmsg = $this->adoConnection->ErrorMsg();
    $pa_errmsg = $this->phaConnection->ErrorMsg();
    $this->assertEquals($ado_errmsg, $pa_errmsg);

    $ado_errno = $this->adoConnection->ErrorNo();
    $pa_errno = $this->phaConnection->ErrorNo();
    $this->assertEquals($ado_errno, $pa_errno);
  }

  public function testIterator()
  {
    $sql = "SELECT * FROM user";

    $adodb = array();
    foreach($this->adoConnection->Execute($sql) as $row)
    {
      $adodb[] = $row;
    }

    $adoph = array();
    foreach($this->phaConnection->Execute($sql) as $row)
    {
      $adoph[] = $row;
    }

    $this->assertEquals($adodb, $adoph);
  }

  public function testGetAll()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetAll(2);
    $phaResult = $this->phaConnection->Execute($sql)->GetAll(2);
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetArray()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetArray(2);
    $phaResult = $this->phaConnection->Execute($sql)->GetArray(2);
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetArrayZero()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetArray(0);
    $phaResult = $this->phaConnection->Execute($sql)->GetArray(0);
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetArrayMore()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetArray(20);
    $phaResult = $this->phaConnection->Execute($sql)->GetArray(20);
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetAssoc()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetAssoc();
    $phaResult = $this->phaConnection->Execute($sql)->GetAssoc();
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetAssocForceArray()
  {
    $sql = "SELECT userid, firstname FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetAssoc(true);
    $phaResult = $this->phaConnection->Execute($sql)->GetAssoc(true);
    $this->assertEquals($adoResult, $phaResult);
  }

  public function testGetAssocFirst2Cols()
  {
    $sql = "SELECT * FROM user";

    $adoResult = $this->adoConnection->Execute($sql)->GetAssoc(false, true);
    $phaResult = $this->phaConnection->Execute($sql)->GetAssoc(false, true);
    $this->assertEquals($adoResult, $phaResult);
  }
}
