<?php

    function createResponse( $status = '200', $errors = null, $message = null)
    {
        $response = new stdClass();
        ($message) ? $response->message = $message : 0;
        ($errors) ? $response->errors = $errors : 0;
        echo json_encode($response);
    }

?>