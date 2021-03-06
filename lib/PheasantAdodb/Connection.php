<?php

namespace PheasantAdodb;

class Connection
{
    public $databaseType = 'pheasant-adodb';
    public $dataProvider = 'mysql';
    public $replaceQuote = "\\'";
    public $raiseErrorFn;

    public $transOff = 0;
    public $transCnt = 0;
    public $_oldRaiseFn =  false;
    public $_transOK = null;

    protected $_connection;

    private $_lastResult;
    private $_lastSql;
    private $_errorMsg;
    private $_errorNo;

    public $fetchmode;
    const ADODB_FETCH_ASSOC = 2;

    /**
     * @param \Pheasant\Database\Mysqli\Connection $connection
     * @param int $fetchMode
     * @param string $raiseErrorFn
     */
    public function __construct(\Pheasant\Database\Mysqli\Connection $connection, $fetchMode = self::ADODB_FETCH_ASSOC, $raiseErrorFn = '_adodb_throw')
    {
        $this->_connection = $connection;
        $this->fetchmode = $fetchMode;
        $this->raiseErrorFn = $raiseErrorFn;
    }

    /**
     * @param mode The fetchmode ADODB_FETCH_ASSOC or ADODB_FETCH_NUM
     */
    public function SetFetchMode($mode)
    {
        $oldFetchmode = $this->fetchmode;
        $this->fetchmode = $mode;

        return $oldFetchmode;
    }

    /**
     * Reset the query params before anything is executed
     */
    private function _resetQuery()
    {
        if ($this->fetchmode != self::ADODB_FETCH_ASSOC)
            throw new Exception("Fetchmodes other than ADODB_FETCH_ASSOC have not been implemented");

        unset($this->_lastResult);
        unset($this->_lastSql);
        unset($this->_errorMsg);
        unset($this->_errorNo);
    }

    public function Affected_Rows()
    {
        return $this->_lastResult->affectedRows();
    }

    public function ErrorMsg()
    {
        return isset($this->_errorMsg) ? $this->_errorMsg : '';
    }

    public function ErrorNo()
    {
        return isset($this->_errorNo) ? $this->_errorNo : 0;
    }

    public function Quote($s)
    {
        return $this->qstr($s);
    }

    public function &Query($sql, $inputarr=false)
    {
        $rs = &$this->Execute($sql, $inputarr);

        return $rs;
    }

    public function &SelectLimit($sql, $nrows=-1, $offset=-1, $inputarr=false, $secs=0)
    {
        $offsetStr = ($offset>=0) ? ((integer) $offset)."," : '';
        $result = $this->Execute($sql." LIMIT $offsetStr".((integer) $nrows), $inputarr);

        return $result;
    }

    public function &GetAll($sql, $inputarr=false)
    {
        $arr =& $this->GetArray($sql, $inputarr);

        return $arr;
    }

    public function &GetAssoc($sql, $inputarr=false, $forceArray=false, $first2cols=false)
    {
        $rs =& $this->Execute($sql, $inputarr);
        if (!$rs)
            return false;
        $arr =& $rs->GetAssoc($forceArray, $first2cols);

        return $arr;
    }

    public function GetOne($sql, $inputarr=false)
    {
        if (strncasecmp($sql,'sele',4) == 0) {
            $rs =& $this->SelectLimit($sql, 1, -1, $inputarr);
            if ($rs) {
                $rs->Close();
                if ($rs->EOF)
                    return false;
                else
                    return reset($rs->fields);
            }
        } else {
            $ret = false;
            $rs =& $this->Execute($sql, $inputarr);
            if ($rs) {
                if (!$rs->EOF)
                    $ret = reset($rs->fields);
                $rs->Close();
            }

            return $ret;
        }

        return false;
    }

    public function GetCol($sql, $inputarr=false, $trim=false)
    {
        $rv = false;
        $rs =& $this->Execute($sql, $inputarr);
        if ($rs) {
            $rv = array();
            if ($trim) {
                while (!$rs->EOF) {
                    $rv[] = trim(reset($rs->fields));
                    $rs->MoveNext();
                }
            } else {
                while (!$rs->EOF) {
                    $rv[] = reset($rs->fields);
                    $rs->MoveNext();
                }
            }
            $rs->Close();
        }

        return $rv;
    }

    /**
     *
     * @param sql      SQL statement
     * @param [inputarr]   input bind array
     */
    public function &GetArray($sql, $inputarr=false)
    {
        $rs =& $this->Execute($sql,$inputarr);
        if (!$rs)
            return false;

        $arr =& $rs->GetArray();
        $rs->Close();

        return $arr;
    }

    /**
     * Return one row of sql statement. Recordset is disposed for you.
     *
     * @param sql      SQL statement
     * @param [inputarr]   input bind array
     */
    public function &GetRow($sql, $inputarr=false)
    {
        $rs =& $this->Execute($sql, $inputarr);

        if (!$rs)
            return false;

        if (!$rs->EOF)
            $arr = $rs->fields;
        else
            $arr = array();
        $rs->Close();

        return $arr;
    }

    /**
     * Insert or replace a single record. Note: this is not the same as MySQL's replace.
     * ADOdb's Replace() uses update-insert semantics, not insert-delete-duplicates of MySQL.
     * Also note that no table locking is done currently, so it is possible that the
     * record be inserted twice by two programs...
     *
     * $this->Replace('products', array('prodname' =>"'Nails'","price" => 3.99), 'prodname');
     *
     * $table    table name
     * $fieldArray associative array of data (you must quote strings yourself).
     * $keyCol   the primary key field name or if compound key, array of field names
     * autoQuote   set to true to use a hueristic to quote strings. Works with nulls and numbers
     *         but does not work with dates nor SQL functions.
     * has_autoinc the primary key is an auto-inc field, so skip in insert.
     *
     * Currently blob replace not supported
     *
     * returns 0 = fail, 1 = update, 2 = insert
     */
    public function Replace($table, array $fieldArray, $keyCol, $autoQuote=false, $hasAutoinc=false)
    {
        if (!$autoQuote)
            throw new \BadMethodCallException("autoQuote=false is not supported. That would actually be crazy.");

        $this->_resetQuery();

        $criteriaKeys = array();

        if (!is_array($keyCol))
            $keyCol = array($keyCol);

        foreach ($keyCol as $key) {
            if (isset($fieldArray[$key]))
                $criteriaKeys[$key] = $fieldArray[$key];

            if ($hasAutoinc)
                unset($fieldArray[$key]);
        }

        // sanity check on keys
        // $keysCount should = 0 (for an insert)
        // or equal the same number that we passed
        $keysCount = count($criteriaKeys);
        if (! (($keysCount == count($keyCol)) || ($keysCount == 0))) {
                $this->_raiseError('REPLACE', -1, "Key column condition mismatch");

                return 0;
        }

        try {
            if ($keysCount) {
                $criteria = new \Pheasant\Query\Criteria($criteriaKeys);

                $keyexistsq = new \Pheasant\Query\Query($this->_connection);
                $keyexistsq
                    ->from($table)
                    ->where($criteria);
                $keyexists = $keyexistsq->count();
            } else {
                $keyexists = false;
            }

            $phtable = $this->_connection->table($table);
            if ($keyexists) {
                $this->_lastResult = $phtable->update($fieldArray, $criteria);

                return 1;
            } else {
                $this->_lastResult = $phtable->insert($fieldArray);

                return 2;
            }
        } catch (\Exception $e) {
            $this->_raiseError('REPLACE', $e->getCode(), $e->getMessage(), $table, $fieldArray);

            return 0;
        }
    }

    public function AutoExecute($table, $fieldsValues, $mode = 'INSERT', $where = FALSE)
    {
        // normalise $mode
        if ($mode == 'UPDATE' || $mode == 2)
            $mode = 'UPDATE';
        elseif ($mode == 'INSERT' || $mode == 1)
            $mode = 'INSERT';
        else
            throw new \BadMethodCallException("AutoExecute: Unknown mode=$mode");

        if ($mode == 'UPDATE' && !$where)
            throw new \BadMethodCallException('AutoExecute: Illegal mode=UPDATE with empty WHERE clause');

        $this->_resetQuery();

        $tableP = $this->_connection->table($table);
        if (!$tableP->exists()) {
            $this->_raiseError('AUTOEXECUTE', -1, "Table $table doesn't exist", $table, $fieldsValues);

            return false;
        }

        // Clean up $fieldsValues
        // Ignore non-existant columns
        // Allows for keys with different casing
        $tableCols = array_keys($tableP->columns());
        $tableColsMap = array_combine(array_change_key_case($tableCols), $tableCols);

        $validFieldValues = array();
        foreach ($fieldsValues as $col => $val) {
            $colkey = strtolower($col);
            if (isset($tableColsMap[$colkey]))
                $validFieldValues[$tableColsMap[$colkey]] = $val;
        }

        try {
            if ($mode == 'INSERT') {
                $this->_lastResult = $tableP->insert($validFieldValues);
            } else {
                $criteria = new \Pheasant\Query\Criteria($where);
                $this->_lastResult = $tableP->update($validFieldValues, $criteria);
            }
        } catch (\Exception $e) {
            $this->_raiseError('AUTOEXECUTE', $e->getCode(), $e->getMessage(), $table, $fieldsValues);

            return false;
        }

        return true;
    }

    public function Close()
    {
        $this->_connection->close();
    }

    private function _adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2)
    {
        throw new Exception($dbms, $fn, $errno, $errmsg, $p1, $p2);
    }

    private function _findCallableFn($fnName)
    {
        if(is_callable(array($this, $fnName)))

            return array($this, $fnName);
        else if (is_callable($fnName))
            return $fnName;
        else
            return false;
    }

    private function _raiseError($functionName, $errno=-1, $errmsg='', $p1='', $p2='')
    {
        $this->_errorNo = $errno;
        $this->_errorMsg = $errmsg;

        $callable = $this->_findCallableFn($this->raiseErrorFn);
        if ($callable)
            call_user_func($callable, $this->databaseType, $functionName, $errno, $errmsg, $p1, $p2);
    }

    // --------------------------------------
    // Transaction methods

    private function ADODB_TransMonitor()
    {
        $this->_transOK = false;

        $callable = $this->_findCallableFn($this->_oldRaiseFn);
        if ($callable)
            call_user_func_array($callable, func_get_args());
    }

    /**
     * Improved method of initiating a transaction. Used together with CompleteTrans().
     * Advantages include:
     *
     * a. StartTrans/CompleteTrans is nestable, unlike BeginTrans/CommitTrans/RollbackTrans.
     *   Only the outermost block is treated as a transaction.<br>
     * b. CompleteTrans auto-detects SQL errors, and will rollback on errors, commit otherwise.<br>
     * c. All BeginTrans/CommitTrans/RollbackTrans inside a StartTrans/CompleteTrans block
     *   are disabled, making it backward compatible.
     */
    public function StartTrans($errfn = 'ADODB_TransMonitor')
    {
        if ($this->transOff > 0) {
            $this->transOff += 1;

            return;
        }

        $this->_oldRaiseFn = $this->raiseErrorFn;
        $this->raiseErrorFn = $errfn;
        $this->_transOK = true;

        $this->BeginTrans();
        $this->transOff = 1;
    }

    /**
     * Used together with StartTrans() to end a transaction. Monitors connection
     * for sql errors, and will commit or rollback as appropriate.
     *
     * @autoComplete if true, monitor sql errors and commit and rollback as appropriate,
     * and if set to false force rollback even if no SQL error detected.
     * @returns true on commit, false on rollback.
     */
    public function CompleteTrans($autoComplete = true)
    {
        if ($this->transOff > 1) {
            $this->transOff -= 1;

            return true;
        }
        $this->raiseErrorFn = $this->_oldRaiseFn;

        $this->transOff = 0;
        if ($this->_transOK && $autoComplete) {
            if (!$this->CommitTrans()) {
                $this->_transOK = false;
            }
        } else {
            $this->_transOK = false;
            $this->RollbackTrans();
        }

        return $this->_transOK;
    }

    /**
     * At the end of a StartTrans/CompleteTrans block, perform a rollback.
     */
    public function FailTrans()
    {
        $this->_transOK = false;
    }

    /**
     * Check if transaction has failed, only for Smart Transactions.
     */
    public function HasFailedTrans()
    {
        if ($this->transOff > 0)
            return $this->_transOK == false;
        return false;
    }

    public function BeginTrans()
    {
        if ($this->transOff)
            return true;

        $this->transCnt += 1;
        $this->Execute('SET AUTOCOMMIT=0');
        $this->Execute('BEGIN');

        return true;
    }

    public function CommitTrans($ok=true)
    {
        if ($this->transOff)
            return true;
        if (!$ok)
            return $this->RollbackTrans();

        if ($this->transCnt)
            $this->transCnt -= 1;
        $this->Execute('COMMIT');
        $this->Execute('SET AUTOCOMMIT=1');

        return true;
    }

    public function RollbackTrans()
    {
        if ($this->transOff)
            return true;
        if ($this->transCnt)
            $this->transCnt -= 1;
        $this->Execute('ROLLBACK');
        $this->Execute('SET AUTOCOMMIT=1');

        return true;
    }

    private function _query($sql, $inputarr)
    {
        // pheasant expects an array
        if($inputarr === false)
            $inputarr = array();
        if (!is_array($inputarr))
            $inputarr = array($inputarr);

        // adodb doesn't care about extra parameters
        // But pheasant throws an exception when extra params are present
        // Here is a massive kludge to do the binding ourselves, then modify the params we get an exception
        try {
            $sql = $this->_connection->binder()->bind($sql, $inputarr);
            $inputarr = array();
        } catch (\Exception $e) {
            // For "Parameters left over" exception,
            // we log and truncate array to match adodb behavior
            if (preg_match('/Parameters left over/', $e->getMessage()) && !empty($e->leftOverParams)) {
                trigger_error((string)$e, E_USER_WARNING);
                $inputarr = array_slice($inputarr, 0, 0-count($e->leftOverParams));
            } else {
                $this->_raiseError('EXECUTE', $e->getCode(), $e->getMessage(), $sql, $inputarr);

                return false;
            }
        }

        try {
            $this->_lastSql = $sql;

            return $this->_lastResult = $this->_connection->execute($sql, $inputarr);
        } catch (\Exception $e) {
            $this->_raiseError('EXECUTE', $e->getCode(), $e->getMessage(), $sql, $inputarr);

            return false;
        }
    }

    /**
     * Executes an sql statement, notifies all observers
     */
    public function &Execute($sql, $inputarr=false)
    {
        $this->_resetQuery();

        $resultset = $this->_query($sql, $inputarr);
        if ($resultset)
            $recordset = new RecordSet($resultset, $this->_lastSql);

        return $recordset;
    }


    // --------------------------------------
    // Meta methods

    public function MetaTables($ttype=false, $showSchema=false, $mask=false)
    {
        if($showSchema)
            throw new \BadMethodCallException('$showSchema not implemented for MetaTables');

        if($mask)
            throw new \BadMethodCallException('$mask not implemented for MetaTables');

        return $ttype == 'VIEWS'
            ? $this->GetCol('SHOW VIEWS')
            : $this->GetCol('SHOW TABLES')
            ;
    }

    public function &MetaColumns($table)
    {
        $tbl = $this->_connection->table($table);
        $cols = $tbl->columns();

        $retarr = array();
        foreach ($cols as $name => $data) {
            $fld = new FieldObject();
            $fld->name = $name;
            $type = $data['Type'];

            // split type into type(length):
            $fld->scale = null;
            if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
                $fld->type = $query_array[1];
                $fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
                $fld->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
            } elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
                $fld->type = $query_array[1];
                $fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
            } elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
                $fld->type = $query_array[1];
                $arr = explode(",",$query_array[2]);
                $fld->enums = $arr;
                $zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
                $fld->max_length = ($zlen > 0) ? $zlen : 1;
            } else {
                $fld->type = $type;
                $fld->max_length = -1;
            }
            $fld->not_null = ($data['Null'] != 'YES');
            $fld->primary_key = ($data['Key'] == 'PRI');
            $fld->auto_increment = (strpos($data['Extra'], 'auto_increment') !== false);
            $fld->binary = (strpos($type,'blob') !== false);
            $fld->unsigned = (strpos($type,'unsigned') !== false);

            if (!$fld->binary) {
                $d = $data['Default'];
                if ($d != '' && $d != 'NULL') {
                    $fld->has_default = true;
                    $fld->default_value = $d;
                } else {
                    $fld->has_default = false;
                }
            }

                $retarr[strtoupper($fld->name)] = $fld;
            }

            return $retarr;
    }

    /**
     * Quotes a string, without prefixing nor appending quotes.
     */
    public function escape($s)
    {
        if ($this->replaceQuote[0] == '\\')
            $s = str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);

        return str_replace("'",$this->replaceQuote,$s);
    }

    /**
     * Correctly quotes a string so that all strings are escaped. We prefix and append
     * to the string single-quotes.
     * An example is  $db->qstr("Don't bother",magic_quotes_runtime());
     *
     * @param s     the string to quote
     *
     * @return  quoted string to be sent back to database
     */
    public function qstr($s)
    {
        return $this->_connection->binder()->quote(
            $this->_connection->binder()->escape($s)
        );
    }
}
