<?php
/**
 * Optional include
 *
 * Include this file to cause ADOConnection to throw exceptions
 */

define('ADODB_ERROR_HANDLER','adodb_throw');


class ADODB_Exception extends \PheasantAdodb\Exception {}


function adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2)
{
    throw new ADODB_Exception($dbms, $fn, $errno, $errmsg, $p1, $p2);
}
