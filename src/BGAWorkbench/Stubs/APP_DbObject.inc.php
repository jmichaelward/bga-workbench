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
     * @param string $sql
     * @param boolean $bSingleValue
     * @return array
     */
    protected function getCollectionFromDB($sql, $bSingleValue = false)
    {
        $rows = self::getObjectListFromDB($sql);
        $result = array();
        foreach ($rows as $row) {
            if ($bSingleValue) {
                $key = reset($row);
                $result[$key] = next($row);
            } else {
                $result[reset($row)] = $row;
            }
        }

        return $result;
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
     * @param string $sql
     * @return array
     * @throws BgaSystemException
     */
    protected function getNonEmptyObjectFromDB($sql)
    {
        $rows = $this->getObjectListFromDB($sql);
        if (count($rows) !== 1) {
            throw new BgaSystemException('Expected exactly one result');
        }

        return $rows[0];
    }

    /**
     * @param string $sql
     * @return mixed
     */
    protected static function getUniqueValueFromDB($sql)
    {
        // TODO: Throw exception if not unique
        $rows = self::getDbConnection()->fetchArray($sql);
        if (count($rows) !== 1) {
            throw new \RuntimeException('Non unique result');
        }
        return $rows[0];
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
