<?php
    
    $servername = getenv('APPSETTING_BCD_SERVER');
    $username = getenv('APPSETTING_BCD_USER');
    $password = getenv('APPSETTING_BCD_PASS');
    $dbname = getenv('APPSETTING_BCD_NAME');
    $cert = getenv('APPSETTING_PATH_CERT');

    // $conn = new mysqli($servername, $username, $password, $dbname);

    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, $cert, NULL, NULL);
    mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306); 

?>