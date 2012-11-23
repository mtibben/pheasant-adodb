<?php

namespace PheasantAdodb;

class RecordSet implements \Iterator
{
    public $fields = array();
    public $EOF = true;

    public $sql;
    private $_resultSet;
    private $_iterator;
    private $_currentRow;
    private $_numOfRows = 0;
    private $_numOfFields = 0;

    public function __construct(\Pheasant\Database\Mysqli\ResultSet $resultset, $sql)
    {
        $this->_resultSet = $resultset;
        $this->sql = $sql;

        if ($resultset) {
            $this->_iterator = $this->_resultSet->getIterator();
            $this->_iterator->rewind();
            $this->_currentRow = 0;
            $this->EOF = !$this->_fetch();
            $this->_initrs();
        }
    }

    // Iterator API
    // -------------------------
    public function rewind()
    {
        $this->MoveFirst();
    }

    public function valid()
    {
        return !$this->EOF;
    }

    public function key()
    {
        return $this->_currentRow;
    }

    public function current()
    {
        return $this->fields;
    }

    public function next()
    {
        $this->MoveNext();
    }

    public function hasMore()
    {
        return !$this->EOF;
    }
    // -------------------------

    public function Close() {}

    private function _initrs()
    {
        $this->_numOfRows = $this->_resultSet->count();
        $this->_numOfFields = count($this->_resultSet->fields());
    }

    private function _seek($row)
    {
        $this->_iterator->seek($row);

        return true;
    }

    private function _fetch()
    {
        if ($this->_iterator->valid()) {
            $this->fields = $this->_iterator->current();
            $this->_iterator->next();
        } else {
            $this->fields = array();
        }

        return !empty($this->fields);
    }

    public function FieldCount()
    {
        return $this->fields()->count();
    }

    public function &FetchField($fieldOffset = -1)
    {
        $fields = $this->fields();
        $fld = new FieldObject();
        $fld->name = $fields[$fieldOffset]->name;
        $fld->type = 'VARCHAR'; // FIXME: is a type needed here?
        $fld->max_length = -1;

        return $fld;
    }

    /**
     * Fetch a row, returning false if no more rows.
     * This is PEAR DB compat mode.
     *
     * @return false or array containing the current record
     */
    public function &FetchRow()
    {
        if ($this->EOF) {
            $false = false;

            return $false;
        }
        $arr = $this->fields;
        $this->_currentRow++;
        if (!$this->_fetch())
            $this->EOF = true;

        return $arr;
    }

    public function MetaType($t, $len=-1, $fieldobj=false)
    {
        return 'C';
    }

    public function fields()
    {
        return $this->_resultSet->fields();
    }

    /**
     * synonyms RecordCount and RowCount
     *
     * @return the number of rows or -1 if this is not supported
     */
    public function RecordCount()
    {
        return $this->_numOfRows;
    }

    /**
     * Move to the first row in the recordset. Many databases do NOT support this.
     *
     * @return true or false
     */
    public function MoveFirst()
    {
        if ($this->_currentRow == 0)
            return true;

        return $this->Move(0);
    }

    /**
     * Move to next record in the recordset.
     *
     * @return true if there still rows available, or false if there are no more rows (EOF).
     */
    public function MoveNext()
    {
        if (!$this->EOF) {
            $this->_currentRow++;
            if ($this->_fetch())
                return true;
        }
        $this->EOF = true;

        return false;
    }

    /**
     * Random access to a specific row in the recordset. Some databases do not support
     * access to previous rows in the databases (no scrolling backwards).
     *
     * @param rowNumber is the row to move to (0-based)
     *
     * @return true if there still rows available, or false if there are no more rows (EOF).
     */
    public function Move($rowNumber = 0)
    {
        $this->EOF = false;
        if ($rowNumber == $this->_currentRow)
            return true;
        if ($rowNumber >= $this->_numOfRows) {
            if ($this->_numOfRows != -1)
                $rowNumber = $this->_numOfRows-2;
        }

        if ($this->_seek($rowNumber)) {
            $this->_currentRow = $rowNumber;
            if ($this->_fetch())
                return true;
        } else {
            $this->EOF = true;

            return false;
        }

        $this->fields = false;
        $this->EOF = true;

        return false;
    }

    /**
     * return recordset as a 2-dimensional array.
     *
     * @param [nRows]  is the number of rows to return. -1 means every row.
     *
     * @return an array indexed by the rows (0-based) from the recordset
     */
    public function &GetArray($nRows = -1)
    {
        $results = array();
        $cnt = 0;
        while (!$this->EOF && $nRows != $cnt) {
            $results[] = $this->fields;
            $this->MoveNext();
            $cnt++;
        }

        return $results;
    }

    public function &GetAll($nRows = -1)
    {
        $arr =& $this->GetArray($nRows);

        return $arr;
    }

    /**
     * return whole recordset as a 2-dimensional associative array if there are more than 2 columns.
     * The first column is treated as the key and is not included in the array.
     * If there is only 2 columns, it will return a 1 dimensional array of key-value pairs unless
     * $forceArray == true.
     *
     * @param [force_array] has only meaning if we have 2 data columns. If false, a 1 dimensional
     *  array is returned, otherwise a 2 dimensional array is returned. If this sounds confusing,
     *  read the source.
     *
     * @param [first2cols] means if there are more than 2 cols, ignore the remaining cols and
     * instead of returning array[col0] => array(remaining cols), return array[col0] => col1
     *
     * @return an associative array indexed by the first column of the array,
     *  or false if the  data has less than 2 cols.
     */
    public function &GetAssoc($forceArray = false, $first2cols = false)
    {
        $cols = $this->_numOfFields;
        if ($cols < 2)
            return false;

        $numIndex = isset($this->fields[0]);
        $results = array();

        if (!$first2cols && ($cols > 2 || $forceArray)) {
            if ($numIndex) {
                while (!$this->EOF) {
                    $results[trim($this->fields[0])] = array_slice($this->fields, 1);
                    $this->MoveNext();
                }
            } else {
                while (!$this->EOF) {
                    $results[trim(reset($this->fields))] = array_slice($this->fields, 1);
                    $this->MoveNext();
                }
            }
        } else {
            if ($numIndex) {
                while (!$this->EOF) {
                    $results[trim(($this->fields[0]))] = @$this->fields[1];
                    $this->MoveNext();
                }
            } else {
                while (!$this->EOF) {
                    $v1 = trim(reset($this->fields));
                    $v2 = ''.next($this->fields);
                    $results[$v1] = $v2;
                    $this->MoveNext();
                }
            }
        }

        return $results;
    }
}
