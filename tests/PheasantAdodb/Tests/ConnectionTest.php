<?php

namespace PheasantAdodb\Tests;

class ConnectionTest extends PheasantAdodbTestCase
{
  public function setUp()
  {
    parent::setUp();
    $this->initUserTables();

    $this->fixBadAdodbAutoloading();
  }

  public function testExecute()
  {
    $sql = "SELECT 1, 2, 3";
    $ado_result = $this->ado_connection->Execute($sql);
    $pha_result = $this->pha_connection->Execute($sql);

    $this->assertEquals('PheasantAdodb\RecordSet', get_class($pha_result));

    $ado_row = $ado_result->FetchRow();
    $pa_row = $pha_result->FetchRow();
    $this->assertEquals($ado_row, $pa_row);
  }

  public function testQuery()
  {
    $sql = "SELECT 1, 2, 3, 'test'";
    $ado_result = $this->ado_connection->Query($sql);
    $pha_result = $this->pha_connection->Query($sql);

    $this->assertEquals('PheasantAdodb\RecordSet', get_class($pha_result));

    $ado_row = $ado_result->FetchRow();
    $pa_row = $pha_result->FetchRow();
    $this->assertEquals($ado_row, $pa_row);
  }

  public function testError()
  {
    $sql = "SELECT badfield FROM nonexistant";

    $ado_result = $this->ado_connection->Execute($sql);
    $pha_result = $this->pha_connection->Execute($sql);
    $this->assertEquals($ado_result, $pha_result);

    $ado_errmsg = $this->ado_connection->ErrorMsg();
    $pa_errmsg = $this->pha_connection->ErrorMsg();
    // remove the database name from error message
    $ado_errmsg = str_replace($this->testdb_ado_dsn->database,'',$ado_errmsg);
    $pa_errmsg = str_replace($this->testdb_pha_dsn->database,'',$pa_errmsg);
    $this->assertEquals($ado_errmsg, $pa_errmsg);

    $ado_errno = $this->ado_connection->ErrorNo();
    $pa_errno = $this->pha_connection->ErrorNo();
    $this->assertEquals($ado_errno, $pa_errno);
  }

  public function testGetOne()
  {
    $sql = "SELECT 52";
    $ado_result = $this->ado_connection->GetOne($sql);
    $pha_result = $this->pha_connection->GetOne($sql);

    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetOneFromMultipleResults()
  {
    $sql = "SELECT * FROM user";
    $ado_result = $this->ado_connection->GetOne($sql);
    $pha_result = $this->pha_connection->GetOne($sql);

    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetOneFromNoResults()
  {
    $sql = "SELECT * FROM user WHERE 1=0";
    $ado_result = $this->ado_connection->GetOne($sql);
    $pha_result = $this->pha_connection->GetOne($sql);

    $this->assertEquals($ado_result, $pha_result);
  }

  public function testSelectLimit()
  {
    $sql = "SELECT * FROM user";
    $ado_result = $this->ado_connection->SelectLimit($sql, 2)->FetchRow();
    $pha_result = $this->pha_connection->SelectLimit($sql, 2)->FetchRow();

    $this->assertEquals($ado_result, $pha_result);
  }

  public function testSelectLimit2()
  {
    $sql = "SELECT * FROM user";
    $ado_result = $this->ado_connection->SelectLimit($sql, 2, 2)->FetchRow();
    $pha_result = $this->pha_connection->SelectLimit($sql, 2, 2)->FetchRow();

    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetAll()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->GetAll($sql);
    $pha_result = $this->pha_connection->GetAll($sql);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testGetRow()
  {
    $sql = "SELECT * FROM user";

    $ado_result = $this->ado_connection->GetRow($sql);
    $pha_result = $this->pha_connection->GetRow($sql);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testReplace()
  {

    $newfirstname = 'New First Name';

    // update
    $ado_result = $this->ado_connection->Replace('user', array('userid' => 1, 'firstname' => $newfirstname), 'userid', true);
    $pha_result = $this->pha_connection->Replace('user', array('userid' => 1, 'firstname' => $newfirstname), 'userid', true);
    $this->assertEquals($ado_result, $pha_result);
    $this->assertEquals(1, $pha_result);

    $ado_result = $this->ado_connection->Affected_Rows();
    $pha_result = $this->pha_connection->Affected_Rows();
    $this->assertEquals($ado_result, $pha_result);
    $this->assertEquals(1, $pha_result);

    $sql = "SELECT firstname FROM user WHERE userid = 1";
    $pha_result = $this->pha_connection->GetOne($sql);
    $this->assertEquals($newfirstname, $pha_result);

    $sql = "SELECT * FROM user WHERE userid = 1";
    $ado_result = $this->ado_connection->GetAll($sql);
    $pha_result = $this->pha_connection->GetAll($sql);
    $this->assertEquals($ado_result, $pha_result);

    // insert
    $ado_result = $this->ado_connection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
    $pha_result = $this->pha_connection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
    $this->assertEquals($ado_result, $pha_result);
    $this->assertEquals(2, $pha_result);

    $ado_result = $this->ado_connection->Affected_Rows();
    $pha_result = $this->pha_connection->Affected_Rows();
    $this->assertEquals($ado_result, $pha_result);

    $sql = "SELECT firstname FROM user WHERE userid = 101";
    $pha_result = $this->pha_connection->GetOne($sql);
    $this->assertEquals($newfirstname, $pha_result);

    $sql = "SELECT * FROM user WHERE userid = 101";
    $ado_result = $this->ado_connection->GetAll($sql);
    $pha_result = $this->pha_connection->GetAll($sql);
    $this->assertEquals($ado_result, $pha_result);

    // no changes
    $ado_result = $this->ado_connection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
    $pha_result = $this->pha_connection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testQuote()
  {
    $str = 'Q\'uoti"ng 12\3~!@#$%^`&*()';
    $ado_result = $this->ado_connection->Quote($str);
    $pha_result = $this->pha_connection->Quote($str);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testEscape()
  {
    $str = 'Q\'uoti"ng 12\3~!@#$%^`&*()';
    $ado_result = $this->ado_connection->escape($str);
    $pha_result = $this->pha_connection->escape($str);
    $this->assertEquals($ado_result, $pha_result);
  }

  public function testMetaColumns()
  {
    $ado_result = $this->ado_connection->MetaColumns('user');
    $pha_result = $this->pha_connection->MetaColumns('user');
    $this->assertEquals(json_encode($ado_result), json_encode($pha_result));
  }


  public function testAutoExecute()
  {
    $data = array('firstname'=>'testAutoExecuteInsert','lastname'=>'testAutoExecuteInsert');
    $ado_result = $this->ado_connection->AutoExecute('user', $data, 'INSERT');
    $pha_result = $this->pha_connection->AutoExecute('user', $data, 'INSERT');
    $this->assertEquals($ado_result, $pha_result);

    $ado_result = $this->ado_connection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteInsert'));
    $pha_result = $this->pha_connection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteInsert'));
    $this->assertEquals($ado_result, $pha_result);


    $data = array('firstname'=>'testAutoExecuteUpdate');
    $where = 'userid = '.$ado_result[0]['userid'];
    $ado_result = $this->ado_connection->AutoExecute('user', $data, 'UPDATE', $where);
    $pha_result = $this->pha_connection->AutoExecute('user', $data, 'UPDATE', $where);
    $this->assertEquals($ado_result, $pha_result);

    $ado_result = $this->ado_connection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteUpdate'));
    $pha_result = $this->pha_connection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteUpdate'));
    $this->assertEquals($ado_result, $pha_result);


    // test failure
    $data = array('firstname'=>'testAutoExecuteUpdate');
    $where = 'userid = 99999';
    $ado_result = $this->ado_connection->AutoExecute('user', $data, 'UPDATE', $where);
    $pha_result = $this->pha_connection->AutoExecute('user', $data, 'UPDATE', $where);
    $this->assertEquals($ado_result, $pha_result);

  }
}
