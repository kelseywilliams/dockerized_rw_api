<?php
    class API{

        // All response bodies returned as JSON with keys status, data, date, flag
        function __construct($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB){
            $this->DB_HOST = $DB_HOST;
            $this->DB_USERNAME = $DB_USERNAME;
            $this->DB_PASSWORD = $DB_PASSWORD;
            $this->DB = $DB;
        }

        public static function authenticate_api_key($key, $db_host, $db_username, $db_password, $db){
            // table api_keys with fields key, boolean revoked, expiration_date in seconds since unix epoch
            $mysqli = new mysqli($db_host, $db_username, $db_password, $db);

            $response = $mysqli->query("SELECT * FROM api_keys WHERE api_key=\"" . $key . "\";");
            if($response->num_rows > 0){
                $response = $response->fetch_assoc();
                $time = time();
                $expiration = intval($response["expiration"]);
                $last_op = intval($response["last_op"]);
                $time_to_live = $expiration - $time;
                $last_request = $time - $last_op;
                $mysqli->query("UPDATE api_keys SET last_op=" . $time . " WHERE api_key=\"" . $key . "\";");
                if($last_request < 1){
                    $mysqli->close();
                    // Too many requests
                    http_response_code(429);
                    exit();
                }
                else if($time_to_live < 0){
                    $mysqli->close();
                    // Unauthorized: The server recognizes the API key, but API key has expired
                    http_response_code(401);
                    exit();
                }
                return true;
            }
            else{
                return false;
            }
        }

        // non indempotent
        // Returns response code 201 for created objects are 200 when not created
        function post(){
            // get response body
            $key = $_POST["key"];
            $data = $_POST["data"];
            $date = $_POST["date"];
            $flag = $_POST["flag"];
            $id = $_POST["id"];

            // If response body does not contain the elements data, date, and flag, return a status of 400 and exit
            if(!isset($_POST["data"]) || !isset($_POST["date"]) || !isset($_POST["flag"]) || !isset($_POST["id"])){
                http_response_code(400);
                exit();
            }

            // Break the api key into the prefix and hash as the database tables are named with the hash (no periods allowed in table names)
            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);


            $mysqli->set_charset("utf8mb4");

            // Prepare the sql statement and insert the data into the database
            $stmt = $mysqli->prepare("INSERT INTO " . $hash . " (data, date, flag, id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $data, $date, $flag, $id);
            $stmt->execute();
            $result = $stmt->affected_rows;
            $stmt->close();

            if($result > 0){
                // Created
                http_response_code(201);
            }
            else{
                // Ok
                http_response_code(200);
            }
            exit();
        }

        // indempotent
        function get(){
            // Get the api key
            $key = $_GET["key"];

            // Why is php so damn dramatic?  We got die() and now explode().  Did Michael Bay direct this language?
            // Break the api key into the prefix and hash as the database tables are named with the hash (no periods allowed in table names)
            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);

            // Get all data from table and return as a json string
            $response = $mysqli->query("SELECT * FROM " . $hash );
            if($response->num_rows > 0){
                $arr = array();
                while($row = $response->fetch_assoc()){
                    array_push($arr, array("data"=>$row["data"], "date"=>$row["date"], "flag"=>$row["flag"], "id"=>$row["id"]));
                }
            }
            else{
                $arr = array();
            }
            echo json_encode($arr);

            http_response_code(200);
        }
        
        //indempotent
        function put(){
            // Had to create a put variable like $_POST because php doesn't just come with it built in? lame
            parse_str(file_get_contents("php://input"), $put_var);

            // get response body
            $key = $put_var["key"];
            $data = $put_var["data"];
            $date = $put_var["date"];
            $flag = $put_var["flag"];
            $id = $put_var["id"];

            // If response body does not contain the elements data, date, and flag, return a status of 400 and exit
            if(!isset($put_var["data"]) || !isset($put_var["date"]) || !isset($put_var["flag"]) || !isset($put_var["id"])){
                http_response_code(400);
                exit();
            }

            // Break the api key into the prefix and hash as the database tables are named with the hash (no periods allowed in table names)
            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);


            $mysqli->set_charset("utf8mb4");

            // Prepare the sql statement and insert the data into the database
            $response = $mysqli->query("SELECT * FROM " . $hash . " WHERE id=" . $id . ";");
            if($response->num_rows > 0){
                $stmt = $mysqli->prepare("UPDATE " . $hash . " SET data=?, date=?, flag=? WHERE id=?;" );
                $stmt->bind_param("ssss", $data, $date, $flag, $id);
                $stmt->execute();
                $result = $stmt->affected_rows;
                $stmt->close();

                if($result > 0){
                    // Created
                    http_response_code(201);
                }
                else{
                    // No Content
                    http_response_code(204);
                }
                exit();
            }
            else{
                $stmt = $mysqli->prepare("INSERT INTO " . $hash . " (data, date, flag, id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $data, $date, $flag, $id);
                $stmt->execute();
                $result = $stmt->affected_rows;
                $stmt->close();
    
                if($result > 0){
                    // Created
                    http_response_code(201);
                }
                else{
                    // No Content
                    http_response_code(204);
                }
                exit();
            }
        }

        function delete(){
            parse_str(file_get_contents("php://input"), $del_var);

            // get response body
            $key = $del_var["key"];
            $id = $del_var["id"];

            // Break the api key into the prefix and hash as the database tables are named with the hash (no periods allowed in table names)
            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);
            $mysqli->set_charset("utf8mb4");

            // If no response body is included, delete everything from the database
            if(!isset($del_var["id"])){
                $mysqli->query("DELETE FROM " . $hash . ";");
                http_response_code(204);
                exit();
            }

            else{
                $stmt = $mysqli->prepare("DELETE FROM " . $hash . " WHERE id=?;" );
                $stmt->bind_param("s",$id);
                $stmt->execute();
                $stmt->close();
                http_response_code(204);
            }
            exit();
        }
    }

    $config = include("config.php");
    $DB_HOST = $config["host"];
    $DB_USERNAME = $config["username"];
    $DB_PASSWORD = $config["password"];
    $DB = $config["db"];

    $METHOD = $_SERVER["REQUEST_METHOD"];

    $api = new API($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

    if($METHOD == "POST"){
        $api->post();
    }

    if($METHOD == "GET"){
        $api->get();
    }

    if($METHOD == "PUT"){
        $api->put();
    }

    if($METHOD == "DELETE"){
        $api->delete();
    }
?>