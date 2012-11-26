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
        $adoResult = $this->adoConnection->Execute($sql);
        $phaResult = $this->phaConnection->Execute($sql);

        $this->assertEquals('PheasantAdodb\RecordSet', get_class($phaResult));

        $adoRow = $adoResult->FetchRow();
        $phaRow = $phaResult->FetchRow();
        $this->assertEquals($adoRow, $phaRow);
    }

    public function testExecuteBinding1()
    {
        $sql = "SELECT COUNT(*) FROM user WHERE lastname=?";
        $adoResult = $this->adoConnection->Execute($sql, array('Castle'));
        $phaResult = $this->phaConnection->Execute($sql, array('Castle'));

        $adoRow = $adoResult->FetchRow();
        $phaRow = $phaResult->FetchRow();
        $this->assertEquals($adoRow, $phaRow);
        $this->assertEquals(1, array_pop($phaRow));
    }

    public function testExecuteBinding2()
    {
        $sql = "SELECT COUNT(*) FROM user WHERE lastname=?";
        $adoResult = $this->adoConnection->Execute($sql, array('Castle', 'extra'));
        $phaResult = $this->phaConnection->Execute($sql, array('Castle', 'extra'));

        $this->assertEquals('PheasantAdodb\RecordSet', get_class($phaResult));

        $adoRow = $adoResult->FetchRow();
        $phaRow = $phaResult->FetchRow();
        //$this->assertEquals($adoRow, $phaRow); // adodb 4.81 appending 'extra' to the end of the sql
        $this->assertEquals(1, array_pop($phaRow));
    }

    public function testQuery()
    {
        $sql = "SELECT 1, 2, 3, 'test'";
        $adoResult = $this->adoConnection->Query($sql);
        $phaResult = $this->phaConnection->Query($sql);

        $this->assertEquals('PheasantAdodb\RecordSet', get_class($phaResult));

        $adoRow = $adoResult->FetchRow();
        $phaRow = $phaResult->FetchRow();
        $this->assertEquals($adoRow, $phaRow);
    }

    public function testError1()
    {
        $sql = "SELECT badfield FROM nonexistant";

        $this->setExpectedException('ADODB_Exception');
        $adoResult = $this->adoConnection->Execute($sql);
    }
    public function testError2()
    {
        $sql = "SELECT badfield FROM nonexistant";

        $this->setExpectedException('\PheasantAdodb\Exception');
        $phaResult = $this->phaConnection->Execute($sql);
    }
    public function testError3()
    {
        $ado_errmsg = $this->adoConnection->ErrorMsg();
        $pa_errmsg = $this->phaConnection->ErrorMsg();
        // remove the database name from error message
        $ado_errmsg = str_replace($this->testdbAdoDsn->database,'',$ado_errmsg);
        $pa_errmsg = str_replace($this->testdbPhaDsn->database,'',$pa_errmsg);
        $this->assertEquals($ado_errmsg, $pa_errmsg);

        $ado_errno = $this->adoConnection->ErrorNo();
        $pa_errno = $this->phaConnection->ErrorNo();
        $this->assertEquals($ado_errno, $pa_errno);
    }

    public function testGetOne()
    {
        $sql = "SELECT 52";
        $adoResult = $this->adoConnection->GetOne($sql);
        $phaResult = $this->phaConnection->GetOne($sql);

        $this->assertEquals($adoResult, $phaResult);
    }

    public function testGetOneFromMultipleResults()
    {
        $sql = "SELECT * FROM user";
        $adoResult = $this->adoConnection->GetOne($sql);
        $phaResult = $this->phaConnection->GetOne($sql);

        $this->assertEquals($adoResult, $phaResult);
    }

    public function testGetOneFromNoResults()
    {
        $sql = "SELECT * FROM user WHERE 1=0";
        $adoResult = $this->adoConnection->GetOne($sql);
        $phaResult = $this->phaConnection->GetOne($sql);

        $this->assertEquals($adoResult, $phaResult);
    }

    public function testSelectLimit()
    {
        $sql = "SELECT * FROM user";
        $adoResult = $this->adoConnection->SelectLimit($sql, 2)->FetchRow();
        $phaResult = $this->phaConnection->SelectLimit($sql, 2)->FetchRow();

        $this->assertEquals($adoResult, $phaResult);
    }

    public function testSelectLimit2()
    {
        $sql = "SELECT * FROM user";
        $adoResult = $this->adoConnection->SelectLimit($sql, 2, 2)->FetchRow();
        $phaResult = $this->phaConnection->SelectLimit($sql, 2, 2)->FetchRow();

        $this->assertEquals($adoResult, $phaResult);
    }

    public function testGetAll()
    {
        $sql = "SELECT * FROM user";

        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testGetRow()
    {
        $sql = "SELECT * FROM user";

        $adoResult = $this->adoConnection->GetRow($sql);
        $phaResult = $this->phaConnection->GetRow($sql);
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testReplace()
    {

        $newfirstname = 'New First Name';

        // update
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 1, 'firstname' => $newfirstname), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 1, 'firstname' => $newfirstname), 'userid', true);
        $this->assertEquals($adoResult, $phaResult);
        $this->assertEquals(1, $phaResult);

        $adoResult = $this->adoConnection->Affected_Rows();
        $phaResult = $this->phaConnection->Affected_Rows();
        $this->assertEquals($adoResult, $phaResult);
        $this->assertEquals(1, $phaResult);

        $sql = "SELECT firstname FROM user WHERE userid = 1";
        $phaResult = $this->phaConnection->GetOne($sql);
        $this->assertEquals($newfirstname, $phaResult);

        $sql = "SELECT * FROM user WHERE userid = 1";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // insert
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
        $this->assertEquals($adoResult, $phaResult);
        $this->assertEquals(2, $phaResult);

        $adoResult = $this->adoConnection->Affected_Rows();
        $phaResult = $this->phaConnection->Affected_Rows();
        $this->assertEquals($adoResult, $phaResult);

        $sql = "SELECT firstname FROM user WHERE userid = 101";
        $phaResult = $this->phaConnection->GetOne($sql);
        $this->assertEquals($newfirstname, $phaResult);

        $sql = "SELECT * FROM user WHERE userid = 101";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // no changes
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname), 'userid', true);
        $this->assertEquals($adoResult, $phaResult);

        // multiple keys
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname, 'lastname' => 'newlastname'), array('userid','firstname'), true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 101, 'firstname' => $newfirstname, 'lastname' => 'newlastname'), array('userid','firstname'), true);
        $this->assertEquals($adoResult, $phaResult);

        $sql = "SELECT * FROM user WHERE userid = 101";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // no key (insert)
        $adoResult = $this->adoConnection->Replace('user', array('firstname' => 'nokeytest'), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('firstname' => 'nokeytest'), 'userid', true);
        $this->assertEquals($adoResult, $phaResult);

        $sql = "SELECT * FROM user WHERE firstname = 'nokeytest'";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // autoInc
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 601, 'firstname' => 'test autoInc'), 'userid', true, true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 601, 'firstname' => 'test autoInc'), 'userid', true, true);
        $this->assertEquals($adoResult, $phaResult);

        $sql = "SELECT * FROM user WHERE firstname = 'test autoInc'";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // already quoted string
        $adoResult = $this->adoConnection->Replace('user', array('userid' => 300, 'firstname' => 'quoted\''), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 300, 'firstname' => 'quoted\''), 'userid', true);
        $this->assertEquals($adoResult, $phaResult);
        $sql = "SELECT * FROM user WHERE userid = 300";
        $adoResult = $this->adoConnection->GetAll($sql);
        $phaResult = $this->phaConnection->GetAll($sql);
        $this->assertEquals($adoResult, $phaResult);

        // already quoted string - adodb breaks
        //$adoResult = $this->adoConnection->Replace('user', array('userid' => 300, 'firstname' => '\'quoted\''), 'userid', true);
        $phaResult = $this->phaConnection->Replace('user', array('userid' => 301, 'firstname' => '\'quoted\''), 'userid', true);
        //$this->assertEquals($adoResult, $phaResult);
        $sql = "SELECT * FROM user WHERE userid = 301";
        //$adoResult = $this->adoConnection->GetRow($sql);
        $phaResult = $this->phaConnection->GetRow($sql);
        //$this->assertEquals($adoResult, $phaResult);  // this is clearly a bug in adodb
        $this->assertEquals('\'quoted\'', $phaResult['firstname']);
    }

    public function testQuote()
    {
        $str = 'Q\'uoti"ng 12\3~!@#$%^`&*()';
        $adoResult = $this->adoConnection->Quote($str);
        $phaResult = $this->phaConnection->Quote($str);
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testEscape()
    {
        $str = 'Q\'uoti"ng 12\3~!@#$%^`&*()';
        $adoResult = $this->adoConnection->escape($str);
        $phaResult = $this->phaConnection->escape($str);
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testMetaColumns()
    {
        $adoResult = $this->adoConnection->MetaColumns('user');
        $phaResult = $this->phaConnection->MetaColumns('user');
        $this->assertEquals(json_encode($adoResult), json_encode($phaResult));
    }

    public function testAutoExecute()
    {
        $data = array('firstname'=>'testAutoExecuteInsert','lastname'=>'testAutoExecuteInsert');
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'INSERT');
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'INSERT');
        $this->assertEquals($adoResult, $phaResult);

        $adoResult = $this->adoConnection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteInsert'));
        $phaResult = $this->phaConnection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteInsert'));
        $this->assertEquals($adoResult, $phaResult);

        $data = array('firstname'=>'testAutoExecuteUpdate');
        $where = 'userid = '.$adoResult[0]['userid'];
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'UPDATE', $where);
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'UPDATE', $where);
        $this->assertEquals($adoResult, $phaResult);

        $adoResult = $this->adoConnection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteUpdate'));
        $phaResult = $this->phaConnection->GetAll('SELECT * FROM user WHERE firstname = ?', array('testAutoExecuteUpdate'));
        $this->assertEquals($adoResult, $phaResult);

        // test failure
        $data = array('firstname'=>'testAutoExecuteUpdate');
        $where = 'userid = 99999';
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'UPDATE', $where);
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'UPDATE', $where);
        $this->assertEquals($adoResult, $phaResult);

        // non-existant column
        $data = array('userid' => 200, 'firstname'=>'testAutoExecuteWithNonExistant', 'nonexistant' => 2);
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'INSERT');
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'INSERT');
        $this->assertEquals($adoResult, $phaResult);
        $adoResult = $this->adoConnection->GetAll('SELECT * FROM user WHERE userid = ?', array(200));
        $phaResult = $this->phaConnection->GetAll('SELECT * FROM user WHERE userid = ?', array(200));
        $this->assertEquals($adoResult, $phaResult);

        // column name has different case
        $data = array('userid' => 201, 'FirstName'=>'testAutoExecuteWithCaseChange');
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'INSERT');
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'INSERT');
        $this->assertEquals($adoResult, $phaResult);
        $adoResult = $this->adoConnection->GetAll('SELECT * FROM user WHERE userid = ?', array(201));
        $phaResult = $this->phaConnection->GetAll('SELECT * FROM user WHERE userid = ?', array(201));
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testTransaction()
    {
        $this->adoConnection->StartTrans();
        $this->phaConnection->StartTrans();

        $data = array('firstname'=>'testTransaction','lastname'=>'testTransaction');
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'INSERT');
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'INSERT');
        $this->assertEquals($adoResult, $phaResult);

        $adoResult = $this->adoConnection->Replace('user', array('firstname' => 'testTransaction', 'lastname' => 'new last name'), 'firstname', true);
        $phaResult = $this->phaConnection->Replace('user', array('firstname' => 'testTransaction', 'lastname' => 'new last name'), 'firstname', true);
        $this->assertEquals($adoResult, $phaResult);

        $adoResult = $this->adoConnection->CompleteTrans();
        $phaResult = $this->phaConnection->CompleteTrans();
        $this->assertEquals($adoResult, $phaResult);
    }

    public function testTransactionRollback()
    {

        $this->adoConnection->StartTrans();
        $this->phaConnection->StartTrans();

        $data = array('firstname'=>'testTransactionRollback','lastname'=>'testTransactionRollback');
        $adoResult = $this->adoConnection->AutoExecute('user', $data, 'INSERT');
        $phaResult = $this->phaConnection->AutoExecute('user', $data, 'INSERT');
        $this->assertEquals($adoResult, $phaResult);

        $exceptionCaught = false;
        try {
            $phaResult = $this->phaConnection->AutoExecute('user', $data, 'UPDATE', 'nonexistant=12345');
        } catch (\PheasantAdodb\Exception $e) {
            $exceptionCaught = true;
        }
        try {
            $adoResult = $this->adoConnection->AutoExecute('user', $data, 'UPDATE', 'nonexistant=12345');
        }
        catch (\ADODB_Exception $e) {
            $adoExceptionCaught = true;
        }
        $this->assertEquals($adoResult, $phaResult);

        if (!$exceptionCaught)
            $this->fail("Exception not thrown");
        if (!$adoExceptionCaught)
            $this->fail("ADO Exception not thrown");

        $adoResult = $this->phaConnection->HasFailedTrans();
        $phaResult = $this->phaConnection->HasFailedTrans();
        $this->assertTrue($adoResult, $phaResult);

        $adoResult = $this->adoConnection->CompleteTrans();
        $phaResult = $this->phaConnection->CompleteTrans();
        $this->assertEquals($adoResult, $phaResult);
        $this->assertFalse($phaResult);
    }
}
