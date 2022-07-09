<?php

class MySQL
{
    private $conn = "false";

    public function __construct($servername, $username, $password, $database)
    {
        $this->conn = mysqli_connect($servername, $username, $password, $database);
        $this->conn->set_charset('utf8mb4');
// Проверяем соединение
//if (!$this->conn) {
//    die("Connection failed: " . mysqli_connect_error());
//}
//echo "Connected successfully";

    }

    public function real_escape_string($text)
    {
        return $this->conn->real_escape_string($text);
    }


    public function SQL_Insert($array, $table, $rezult = null)
    {
        $pole = implode(",", array_keys($array));
        $sql = "INSERT INTO " . $table . "(" . $pole . ") VALUES ('" . implode("' , '", $array) . "') ";
        mysqli_query($this->conn, $sql) or die("database error:" . mysqli_error($this->conn));
        if (isset($rezult)) {
            return (mysqli_insert_id($this->conn));
        }

//   mysqli_close($this->conn);
    }

    public function SQL_Select($array, $table, $where = "", $fetch_assoc = true, $limit = "", $max = "")
    {
        if (!empty($where)) {
            $where = "WHERE $where";
        }
        if (!empty($limit)) {
            $limit = "LIMIT $limit";
        }
        if (!empty($max)) {
            $max = "max($max)";
        }
        $sql = "SELECT $max" . implode(" , ", $array) . " FROM $table $where $limit";
        $resultSet = mysqli_query($this->conn, $sql) or die("database error:" . mysqli_error($this->conn));
        if ($fetch_assoc) {
            $row = mysqli_fetch_assoc($resultSet);
        } else {
            $row = $resultSet;
        }

//       mysqli_close($this->conn);
        return ($row);
    }

    public function SQL_SelectArray($array, $table, $where = "", $fetch_assoc = true, $limit = "", $max = "")
    {
        if (!empty($where)) {
            $where = "WHERE $where";
        }
        if (!empty($limit)) {
            $limit = "LIMIT $limit";
        }
        if (!empty($max)) {
            $max = "max($max)";
        }
        $sql = "SELECT $max" . implode(" , ", $array) . " FROM $table $where $limit";
        $resultSet = mysqli_query($this->conn, $sql) or die("database error:" . mysqli_error($this->conn));
        if($fetch_assoc){
            for ($row = array (); $set = mysqli_fetch_assoc($resultSet); $row[] = $set);
        }else{
            $row = $resultSet;
        }

//       mysqli_close($this->conn);
        return ($row);
    }

    public function SQL_Update($array, $table, $where)
    {
        $sql = "UPDATE $table SET " . implode(" , ", $array) . " WHERE $where";

        return mysqli_query($this->conn, $sql) or die("database error:" . mysqli_error($this->conn));
    }

}