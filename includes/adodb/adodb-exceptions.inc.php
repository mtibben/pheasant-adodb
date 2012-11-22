<?php
/**
 * Optional include
 *
 * Include this file to cause ADOConnection to throw exceptions
 */

define('ADODB_ERROR_HANDLER','adodb_throw');
define('ADODB_EXCEPTION','ADODB_Exception');

class ADODB_Exception extends \PheasantAdodb\Exception {}
