<?php
/**
 * Optional include
 *
 * Compatibility classes and functions for adodb
 *
 * Instantiating with ADONewConnection will mean
 * that a fetchmode will need to be set explicitly
 */

define('ADODB_FETCH_DEFAULT',0);
define('ADODB_FETCH_NUM',1);
define('ADODB_FETCH_ASSOC',2);
define('ADODB_FETCH_BOTH',3);


class ADOConnection extends \PheasantAdodb\Connection {}
class ADORecordSet extends \PheasantAdodb\RecordSet {}
class ADOFieldObject extends \PheasantAdodb\FieldObject {}

function adodb_err()
{
  return false;
}

function &NewADOConnection($dsn)
{
  $conn =& ADONewConnection($dsn);

  return $conn;
}

function &ADONewConnection($dsn)
{
  $errHandler = defined('ADODB_ERROR_HANDLER')
                  ? ADODB_ERROR_HANDLER
                  : 'adodb_err';
  $conn = new ADOConnection(
    new \Pheasant\Database\Mysqli\Connection(
      new \Pheasant\Database\Dsn($dsn)
    ),
    ADODB_FETCH_DEFAULT,
    $errHandler
  );

  return $conn;
}
