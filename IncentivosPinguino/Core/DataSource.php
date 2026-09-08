<?php
namespace Phppot;

// Requerimos el archivo de configuración
require_once 'Config.php';

class DataSource
{
    private $conn;

    function __construct()
    {
        $this->conn = $this->getConnection();
    }

    public function getConnection()
    {
        // Usamos las constantes de la clase Config
        $conn = new \mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);

        if (mysqli_connect_errno()) {
            trigger_error("Problema conectando a la base de datos.");
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    }

    public function getLastError()
    {
        return $this->conn->error;
    }

    public function select($query, $paramTypeOrParams = "", $paramArray = [])
    {
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            throw new \Exception("Error al preparar: " . $this->conn->error);
        }

        if (is_string($paramTypeOrParams) && $paramTypeOrParams !== "") {
            $this->bindQueryParams($stmt, $paramTypeOrParams, $paramArray);
        } elseif (is_array($paramTypeOrParams) && !empty($paramTypeOrParams)) {
            if (substr_count($query, '?') !== count($paramTypeOrParams)) {
                throw new \Exception("Número de parámetros no coincide con placeholders");
            }
            $types = str_repeat('s', count($paramTypeOrParams));
            $stmt->bind_param($types, ...$paramTypeOrParams);
        }

        if (!$stmt->execute()) {
            throw new \Exception("Falló execute(): " . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($result === false) {
            $stmt->close();
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function truncate($query)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }

    public function insert($query, $paramType, $paramArray)
    {
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            return "Error al preparar la consulta: " . $this->conn->error;
        }
        
        $this->bindQueryParams($stmt, $paramType, $paramArray);
    
        if ($stmt->execute() === false) {
            return "Error al ejecutar la consulta: " . $stmt->error;
        }
        
        return $stmt->insert_id;
    }

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

    public function execute($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);

        if (!empty($paramType) && !empty($paramArray)) {
            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        $stmt->execute();
    }

    public function bindQueryParams($stmt, $paramType, $paramArray = array())
    {
        $paramValueReference[] = &$paramType;
        for ($i = 0; $i < count($paramArray); $i++) {
            $paramValueReference[] = &$paramArray[$i];
        }
        call_user_func_array(array($stmt, 'bind_param'), $paramValueReference);
    }

    public function getRecordCount($query, $paramType = "", $paramArray = array())
    {
        $stmt = $this->conn->prepare($query);
        if (!empty($paramType) && !empty($paramArray)) {
            $this->bindQueryParams($stmt, $paramType, $paramArray);
        }
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows;
    }
}