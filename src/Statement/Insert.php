<?php

/**
 * @license MIT
 * @license http://opensource.org/licenses/MIT
 */

namespace FaaPz\PDO\Statement;

use FaaPz\PDO\AbstractStatement;
use FaaPz\PDO\DatabaseException;
use FaaPz\PDO\StatementInterface;
use PDO;

class Insert extends AbstractStatement
{
    /** @var string $table */
    protected $table;

    /** @var string[] $columns */
    protected $columns = [];

    /** @var array $values */
    protected $values = [];

    /** @var bool $ignore */
    protected $ignore = false;

    /**
     * @param PDO   $dbh
     * @param array $pairs
     */
    public function __construct(PDO $dbh, array $pairs = [])
    {
        parent::__construct($dbh);

        $this->columns(array_keys($pairs));
        $this->values(array_values($pairs));
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function into($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function values(array $values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return $this
     */
    public function ignore()
    {
        $this->ignore = true;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!isset($this->table)) {
            throw new DatabaseException('No table is set for insertion');
        }

        if (empty($this->columns)) {
            throw new DatabaseException('Missing columns for insertion');
        }

        if (empty($this->values) || count($this->columns) != count($this->values)) {
            throw new DatabaseException('Missing values for insertion');
        }

        $placeholders = '';
        foreach ($this->values as $value) {
            if ($value instanceof StatementInterface) {
                $placeholders .= "{$value}, ";
            } else {
                $placeholders .= '?, ';
            }
        }
        $placeholders = preg_replace('/, $/', '', $placeholders);

        $columns = implode(', ', $this->columns);

        $sql = 'INSERT';
        if ($this->ignore) {
            $sql .= ' IGNORE';
        }
        $sql .= " INTO {$this->table} ({$columns})";
        $sql .= " VALUES ({$placeholders})";

        return $sql;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        $values = [];
        foreach ($this->values as $value) {
            if ($value instanceof StatementInterface) {
                $values = array_merge($values, $value->getValues());
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @throws DatabaseException
     *
     * @return string|int
     */
    public function execute()
    {
        parent::execute();

        return $this->dbh->lastInsertId();
    }
}
