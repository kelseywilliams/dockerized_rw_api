<?php
session_start();
if(isset($_SESSION["success"])){
    echo $_SESSION["success"];
    unset($_SESSION["success"]);
    exit();
}
else if(isset($_SESSION["error"])){
    echo $_SESSION["error"];
    unset($_SESSION["error"]);
    exit();
}
else{
    echo "empty";
    exit();
}
?>