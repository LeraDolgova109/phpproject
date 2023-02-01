<?php
    include_once "helpers/response.php";
    function setHTTPStatus($status = "200", $errors = null, $message = null)
    {
        switch ($status)
        {
            default:
            case "200":
                $status = "HTTP/1.0 200";
                break;
            case "400":
                $status = "HTTP/1.0 400";
                break;
            case "401":
                $status = "HTTP/1.0 401";
                break;
            case "403":
                $status = "HTTP/1.0 403";
                break;
            case "404":
                $status = "HTTP/1.0 404";
                break;
            case "500":
                $status = "HTTP/1.0 500";
                break;
        }
        header($status);
        createResponse($status, $errors, $message);
    }
?>