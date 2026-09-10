<?php
/**
 * Copyright (C) 2019 Phppot
 *
 * Distributed under MIT license with an exception that,
 * you don’t have to include the full MIT License in your code.
 * In essense, you can use it on commercial software, modify and distribute free.
 * Though not mandatory, you are requested to attribute this URL in your code or website.
 */
namespace Phppot;

/**
 * Generic datasource class for handling DB operations.
 * Uses MySqli and PreparedStatements.
 *
 * @version 2.5 - recordCount function added
 */
class DataSource
{

    // PHP 7.1.0 visibility modifiers are allowed for class constants.
    // when using above 7.1.0, declare the below constants as private
    const HOST = 'mysqlecuadorsf.mysql.database.azure.com';

    const USERNAME = 'xplora_mysql';

    const PASSWORD = 'XpL0r@Ec8Ad0R..';

    const DATABASENAME = 'luckyec_5pgo';

    private $conn;

    /**
     * PHP implicitly takes care of cleanup for default connection types.
     * So no need to worry about closing the connection.
     *
     * Singletons not required in PHP as there is no
     * concept of shared memory.
     * Every object lives only for a request.
     *
     * Keeping things simple and that works!
     */
    function __construct()
    {
        $this->conn = $this->getConnection();
    }

    /**
     * If connection object is needed use this method and get access to it.
     * Otherwise, use the below methods for insert / update / etc.
     *
     * @return \mysqli
     */
    public function getConnection()
    {
        $conn = new \mysqli(self::HOST, self::USERNAME, self::PASSWORD, self::DATABASENAME);

        if (mysqli_connect_errno()) {
            trigger_error("Problem with connecting to database.");
        }

        $conn->set_charset("latin1");
        return $conn;
    }

    /**
     * To get database results
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return array
     */
    public function select($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);

        if (!empty($paramType) && !empty($paramArray)) {

            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
        }

        if (!empty($resultset)) {
            return $resultset;
        }
    }

    /**
     * To truncate table
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return array
     */
    public function truncate($query)
    {
        $stmt = $this->conn->prepare("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt->execute();
        $stmt2 = $this->conn->prepare($query);
        $stmt2->execute();
    }

    /**
     * To insert
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return int
     */
    public function insert($query, $paramType, $paramArray)
    {
        $stmt = $this->conn->prepare($query);
        $this->bindQueryParams($stmt, $paramType, $paramArray);

        $stmt->execute();
        $insertId = $stmt->insert_id;
        return $insertId;
    }

    public function insertMultiple($query, $paramType, $paramArray)
    {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            //throw new Exception("Error al preparar la consulta: " . $this->conn->error);
        }

        // Enlazar los parámetros a la consulta preparada
        $this->bindQueryParams($stmt, $paramType, $paramArray);

        // Ejecutar la consulta
        $stmt->execute();

        // Verificar si ocurrieron errores durante la ejecución
        if ($stmt->errno) {
            //throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }

        // Obtener el número de filas afectadas por la inserción
        $affectedRows = $stmt->affected_rows;

        // Cerrar la consulta preparada
        $stmt->close();

        // Devolver el número de filas afectadas
        return $affectedRows;
    }

    /**
     * To update
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     */
    public function update($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);
        $update = false;
        if (!empty($paramType) && !empty($paramArray)) {
            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        if ($stmt->execute()) {
            $update = true;
        }
        return $update;
    }


     /**
     * To Delete
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     */
    public function delete($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);
        $delete = false;
        if (! empty($paramType) && ! empty($paramArray)) {
            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        if ($stmt->execute()) {
            $delete = true;
        }
        return $delete;
    }

    /**
     * To execute query
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     */
    public function execute($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);

        if (!empty($paramType) && !empty($paramArray)) {
            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        $stmt->execute();
    }

    /**
     * 1.
     * Prepares parameter binding
     * 2. Bind prameters to the sql statement
     *
     * @param string $stmt
     * @param string $paramType
     * @param array $paramArray
     */
    public function bindQueryParams($stmt, $paramType, $paramArray = array())
    {
        $paramValueReference[] = &$paramType;
        for ($i = 0; $i < count($paramArray); $i++) {
            $paramValueReference[] = &$paramArray[$i];
        }
        call_user_func_array(
            array(
                $stmt,
                'bind_param'
            ), $paramValueReference);
    }

    /**
     * To get database results
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return array
     */
    public function getRecordCount($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);
        if (!empty($paramType) && !empty($paramArray)) {

            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        $stmt->execute();
        $stmt->store_result();
        $recordCount = $stmt->num_rows;

        return $recordCount;
    }
}