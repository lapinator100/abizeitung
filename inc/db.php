<?php
    const DB_HOST = 'localhost';
    const DB_USERNAME = 'abizeitung';
    const DB_PASSWORD = '8743btcrw7834s7ez4';
    const DB_DB = 'Abizeitung';
    
    const ERROR_DB = 'Datenbankfehler';
    
    
    function getDatabaseConnection() {
        $db = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
        
        if ($db->connect_error) {
            die(ERROR_DB);
        }
        
        return $db;
    }
?>