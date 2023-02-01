<?php
    include_once "helpers/headers.php";
    
    global $Link;
    $Link = mysqli_connect(
        "127.0.0.1", 
        "web", 
        "password",
        "web"
    );
    header('Content-type: application/json');

    function getData($method): stdClass
    {
        $data = new stdClass();
        if ($method != "GET")
        {  
            $data->body = json_decode(file_get_contents('php://input'));
        }
        $data->parameters = [];
        $dataGet = $_GET;
        foreach ($dataGet as $key => $value) {
            if ($key != "q")
            {
                $data->parameters[$key] = $value;
            }
        }
        return $data;
    }

    function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    if (!$Link)
    {
        setHTTPStatus("500", "Internal Server Error", "DB Connection error: ".mysqli_connect_error());
        exit;
    }

    $url = isset($_GET['q']) ? $_GET['q'] : '';
    $url = rtrim($url, '/');

    $urlList = explode('/', $url);

    $router = $urlList[1];
    $requestData = getData(getMethod());
    $method = getMethod();
    
    if (file_exists(realpath(dirname(__FILE__)) . '/api/' . $router . '.php'))
    {
        include_once 'api/' . $router . '.php';
        route($method, $urlList, $requestData); 
    }
    else
    {
        setHTTPStatus("404", "Not Found");
    }
?>
