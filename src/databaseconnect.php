<?php
    $servername = "mysql62-farm2.uni5.net";
    $database = "programaeficie";
    $username = "programaeficie";
    $password = "u7skGe";

    //create connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    //check connection
    if(!$conn){
        die("Connection failed: " . mysqli_connect_error());
    }
    
    echo "Connected sucessfuly";
    
    mysqli_close($conn);
?>