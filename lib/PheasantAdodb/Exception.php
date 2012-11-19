<?php

namespace PheasantAdodb;

class Exception extends \Exception {
  var $dbms;
  var $fn;
  var $sql = '';
  var $params = '';

  function __construct($dbms, $fn, $errno, $errmsg, $p1, $p2)
  {
    switch($fn) {
      case 'EXECUTE':
        $this->sql = $p1;
        $this->params = $p2;
        $s = "$dbms error: [$errno: $errmsg] in $fn(\"$p1\")\n";
        break;
      default:
        $s = "$dbms error: [$errno: $errmsg] in $fn($p1, $p2)\n";
        break;
    }

    $this->dbms = $dbms;
    $this->fn = $fn;
    $this->msg = $errmsg;

    if (!is_numeric($errno))
      $errno = -1;

    parent::__construct($s,$errno);
  }
}
