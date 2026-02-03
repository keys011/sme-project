<?php
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','smepro_manager');

function getDBconnection(){
    try{
        $conn=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

        if($conn->connect_error){
            throw new exception("connection failed:".$conn->connect_error);
        }

        if($conn->set_charset("utf8mb4"));

        return $conn;
    }catch(exception $e){
        die("Database conection error:".$e->get message());
    }
}

function testconnection(){
    $conn=getDBconnection();
    if($conn){
        echo"Database connected successfully!";
        $conn->close();
    }
}
?>
