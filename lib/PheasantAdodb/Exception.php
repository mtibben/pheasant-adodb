<?php

namespace PheasantAdodb;

class Exception extends \Exception
{
    public $dbms;
    public $fn;
    public $sql = '';
    public $params = '';

    public function __construct($dbms, $fn, $errno=-1, $errmsg='', $p1='', $p2='')
    {
        switch ($fn) {
            case 'EXECUTE':
                $this->sql = $p1;
                $this->params = $p2;
                $s = sprintf('%s error: [%s: %s] in %s(%s)', $dbms, $errno, $errmsg, $fn, var_export($p1, true));
                break;
            default:
                $s = sprintf('%s error: [%s: %s] in %s(%s, %s)', $dbms, $errno, $errmsg, $fn, var_export($p1, true), var_export($p2, true));
                break;
        }

        $this->dbms = $dbms;
        $this->fn = $fn;
        $this->msg = $errmsg;

        if (!is_numeric($errno))
            $errno = -1;

        parent::__construct($s, $errno);
    }
}
