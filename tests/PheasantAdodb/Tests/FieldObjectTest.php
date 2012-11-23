<?php

namespace PheasantAdodb\Tests;

class FieldObjectTest extends PheasantAdodbTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testFetchFieldName()
    {
        $sql = "SELECT 1 as one, 2 as two, 3 as three";
        $adoResult = $this->adoConnection->Execute($sql)->FetchField(1)->name;
        $phaResult = $this->phaConnection->Execute($sql)->FetchField(1)->name;
        $this->assertEquals($adoResult, $phaResult);
    }
}
