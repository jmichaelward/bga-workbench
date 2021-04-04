<?php

use Doctrine\DBAL\Connection;

class APP_DbObject extends APP_Object
{
    ////////////////////////////////////////////////////////////////////////
    // Testing methods
    private static $affectedRows = 0;

    /**
     * Execute a query on the database..
     *
     * It can execute any type of SELECT/UPDATE/DELETE/REPLACE/INSERT query on the database.
     *
     * You should use it for write queries. For SELECT queries, use a specialized method.
     *
     * @param string $sql
     * @return mysqli_result
     */
    public static function DbQuery($sql)
    {
        // Haven't yet found equivalent result type of mysqli->query via doctrine
        $conn = self::getDbConnection();
        //self::$affectedRows = $conn->executeQuery($sql)->rowCount();
        $host = $conn->getHost();
        if (!is_null($conn->getPort())) {
            $host .= ':' . $conn->getPort();
        }
        $miConn = new mysqli($host, $conn->getUsername(), $conn->getPassword(), $conn->getDatabase());
        $result = $miConn->query($sql);
        self::$affectedRows = $miConn->affected_rows;
        return $result;
    }

    /**
     * @return int
     */
    public static function DbAffectedRow()
    {
        return self::$affectedRows;
    }

    /**
     * Returns an associative array of rows for an database SELECT query.
     *
     * Array is indexed on the response is the first field specified in the SELECT query. Thus, if requesting a player ID,
     * for instance, the key of each row will be the player's ID. Other keys will match the other values requested,
     * e.g., 'id', 'name', 'score'.
     *
     * Resulting collection can be empty.
     *
     * @param string  $sql
     * @param boolean $bSingleValue If true, method returns an associatve array of field A => field B.
     * @return array
     */
    protected function getCollectionFromDB($sql, $bSingleValue = false)
    {
        $rows   = self::getObjectListFromDB($sql);
        $result = [];

        foreach ($rows as $row) {
            if ($bSingleValue) {
                $key          = reset($row);
                $result[$key] = next($row);
            } else {
                $result[reset($row)] = $row;
            }
        }

        return $result;
    }

    /**
     * Returns one row for the SQL SELECT query as an associatve array, or null if there is no result.
     *
     * Raises an exception if the query return is more than one row.
     *
     * As with APP_DbObject::getCollectionFromDb(), the associative array is indexed on the first value requested.
     *
     * @param string $sql The database SELECT query.
     * @throws BgaSystemException If more than 1 result is found.
     *
     * @return array|null
     */
    protected function getObjectFromDB($sql)
    {
        // @TODO Figure out how to stub this one.
        return array();
    }

    /**
     * @param string $sql
     * @param boolean $bUniqueValue
     * @return array
     */
    protected static function getObjectListFromDB($sql, $bUniqueValue = false)
    {
        return self::getDbConnection()->fetchAll($sql);
    }

    /**
     * Similar to self::getObjectFromDB, but raises an exception if no row is found.
     *
     * @param string $sql
     * @return array
     * @throws BgaSystemException
     */
    protected function getNonEmptyObjectFromDB($sql)
    {
        $rows = $this->getObjectListFromDB($sql);
        $count = count($rows);

        if ($count !== 1) {
            throw new BgaSystemException(__METHOD__ . " found {$count} results. One expected.");
        }

        return $rows[0];
    }

    /**
     * Same as getCollectionFromDB, but raises an exception if the collection is non-empty.
     *
     * This method does not have a 2nd argument as the previous one does.
     *
     * @param string $sql The SQL query.
     * @see APP_DbObject::getCollectionFromDB()
     *
     * @return array
     */
    protected function getNonEmptyCollectionFromDb($sql)
    {
        $result = self::getCollectionFromDB($sql);

        if (empty($result)) {
            throw new BgaSystemException('Requested collection is empty.');
        }

        return $result;
    }

    /**
     * Returns a unique value from the database or null if now value is found.
     *
     * @param string $sql SELECT query.
     * @return mixed
     */
    protected static function getUniqueValueFromDB($sql)
    {
        $rows = self::getDbConnection()->fetchArray($sql);

        if (count($rows) > 1) {
            throw new \RuntimeException('Non unique result');
        }

        return $rows[0] ?? null;
    }

    /**
     * @var Connection
     */
    private static $connection;

    /**
     * @param Connection $connection
     */
    public static function setDbConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    /**
     * @return Connection
     */
    private static function getDbConnection()
    {
        if (self::$connection === null) {
            throw new \RuntimeException('No db connection set');
        }
        return self::$connection;
    }
}
