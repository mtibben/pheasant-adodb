<?php

namespace PheasantAdodb\Tests;

class FieldObjectTest extends PheasantAdodbTestCase {

  public function setUp()
  {
    parent::setUp();
  }

  public function testFetchFieldName()
  {
    $sql = "SELECT 1 as one, 2 as two, 3 as three";
    $ado_result = $this->ado_connection->Execute($sql)->FetchField(1)->name;
    $pa_result = $this->pha_connection->Execute($sql)->FetchField(1)->name;
    $this->assertEquals($ado_result, $pa_result);
  }
}
