<?php
/**
 * Optional include
 *
 * Include this file to cause ADOConnection to throw exceptions
 */

class ADODB_Exception extends \PheasantAdodb\Exception {}

define('ADODB_ERROR_HANDLER','adodb_throw');
