<?php
    var_dump(function_exists('mysqli_connect'));
    $METHOD = $_SERVER["REQUEST_METHOD"];
    if($METHOD == "POST"){
        session_start();
        $config = include("config.php");
        $DB_HOST = $config["host"];
        $DB_USERNAME = $config["username"];
        $DB_PASSWORD = $config["password"];
        $DB = $config["db"];

        $prefix = uniqid();
        $hash = hash("sha256", uniqid());
        $key = $prefix . "." . $hash;
        $expiration = time() + 604800;
        $last_op = time();
        $rate = 1;
        $client_email = $_POST["email"];

        if(!preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $client_email)){
            $_SESSION["error"] = "Error: Invalid email";
            header("Location: /", 301);
            exit();
        }
        
        try {
        $mysqli = new mysqli(
            $hostname = $DB_HOST, 
            $username = $DB_USERNAME, 
            $database = $DB
        );

    } catch (Exception $e) {
        echo $e;
        die("Connection failed: ". $e->getMessage());
    }

        $result = $mysqli->query("SELECT * FROM api_keys WHERE email=\"" . $client_email . "\";");
        echo $mysqli->error;
        if($result->num_rows > 0){
            $mysqli->close();
            $_SESSION["error"] = "Error: A key already exists for " . $client_email;
            header("Location: /", 301);
            exit();
        }

        $mysqli->set_charset("utf8mb4");
        $result = $mysqli->query("INSERT INTO api_keys (api_key, expiration, last_op, email) VALUES (\"" . $key . "\", " . $expiration . ", " . $last_op . ", \"" . $client_email . "\");");
        // If key was created and placed in database successfully
        if($result){
            $create_table = $mysqli->query("CREATE TABLE " . $hash . " (data MEDIUMTEXT, date TINYTEXT, flag TINYTEXT, id MEDIUMTEXT);");
            // If table was created under api key successfully
            if($create_table){
                ini_set( 'display_errors', 1 );
                error_reporting( E_ALL );
                $from = "admin@kelseywilliams.net";
                $to = $client_email;
                $subject = "api.kelseywilliams.net key";
                $message = "Your api key is: " . $key . "\nDo not lose this key.  This key will expire in 7 days.  In order to prevent spam, only one key can be allocated for every email address.  If you would like to renew this key please send a request to admin@kelseywilliams.net.\nRead the documentation on the kelseywilliams.net api homepage for details on connecting to and using the api. ";
                $headers = "From:" . $from;
                // If the email was sent successfuly
                if(mail($to,$subject,$message, $headers)) {
                    $_SESSION["success"] = "Success: Your api key has been sent to " . $client_email . "! Read below for instructions on how to use the service.  Enjoy!";
                } 
                else {
                    $_SESSION["error"] = "Error: An error occured when trying to send the email to " . $client_email . ".  No email was sent and no key was issued.  Please try again or contact the website adminstrator.";
                    $mysqli->query("DELETE FROM api_keys WHERE api_key=\"" . $key . "\";");
                }
                $_SESSION["success"] = "Success: Your api key has been sent to " . $client_email . "! Read below for instructions on how to use the service.  Enjoy!";

            }
            else{
                $_SESSION["error"] = "Error: An error occurred when creating a table under your api key.  No email was sent.  Please contact the website adminstrator.";
                $mysqli->query("DELETE FROM api_keys WHERE api_key=\"" . $key . "\";");
            }
        }
        else{
            $_SESSION["error"] = "Error: An error occurred when pushing your api key to the database.  No email was sent.  Please contact the website adminstrator.";
        }
        $mysqli->close();
        header("Location: /", 301);
    }
    else if($METHOD == "GET"){
        header("Location: /", 301);
        exit();
    }
?>
