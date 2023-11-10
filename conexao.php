<?php
    
    $servername = 'doorsense-server.mysql.database.azure.com';
    $username = 'breno';
    $password = 'AcessoTech115';
    $dbname = 'doorsense';
    $cert = getenv('APPSETTING_PATH_CERT');

    // $conn = new mysqli($servername, $username, $password, $dbname);

    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, $cert, NULL, NULL);
    mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306); 

?>