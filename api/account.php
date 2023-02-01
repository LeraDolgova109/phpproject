<?php
    include_once "./helpers/headers.php";
    include_once "./helpers/validation.php";
    include_once "./helpers/request.php";
    include_once "./helpers/helpers.php";
    function route($method, $urlList, $requestData)
    {
        $url = $_SERVER['QUERY_STRING'];
        $parametersList = explode('&', $url);

        $queryList = preg_replace('/(q=index.php)/', "", $parametersList);
        $queryList = preg_grep('/(q=[A-Za-z\/]+)/', $queryList);
        $query = preg_replace('/(q=)/', "", $queryList);
        $query = $query ['1'];
        global $Link;
        if ($method == "GET")
        {
            switch($urlList[2])
            {
            case "profile":
                $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                $userFromToken = $Link->query($requestToken)->fetch_assoc();
                $id_user = $userFromToken['id_user'];

                $requestUser = createRequest("user_token", null, $id_user);
                $user = $Link->query($requestUser)->fetch_assoc();
                if (!$user)
                {
                    setHTTPStatus("401", "Unauthorized");
                }
                else
                {
                    echo json_encode($user);
                }
                break;
                default:
                setHTTPStatus("404", "There is no path as '$query'");
            }
        }
        else if ($method == "POST")
        {
            switch($urlList[2])
            {
                case "register":
                    if (validateUserNotNull($requestData)){
                        $email = $requestData->body->email;
                        $user = $Link->query("SELECT id_user FROM User WHERE Email= '$email'")->fetch_assoc();
                        if (!is_null($user))
                        {
                            $error["DuplicateUserName"] = ["Username '$email' is already taken."];
                            setHTTPStatus("400", $error, null);
                            return;
                        }
                        else if (validateUserData($requestData)){
                            $password = hash("sha1", $requestData->body->password);
                            $fullName = $requestData->body->fullName;
                            $address = $requestData->body->address;
                            $birthDate = date('Y-m-d H:i:s', strtotime($requestData->body->birthDate));
                            $gender = $requestData->body->gender;
                            $phoneNumber = $requestData->body->phoneNumber;
                            $userInsertResult = $Link->query("INSERT INTO User(fullName, birthDate, gender, phoneNumber, email, address, password, id) VALUES('$fullName', '$birthDate' , '$gender', '$phoneNumber', '$email', '$address', '$password', UUID())");
                            
                            if (!$userInsertResult)
                            {
                                setHTTPStatus("500", "Internal Server Error", $Link->error);
                            }
                            else
                            {
                                $request = createRequest("user_login", null, $requestData);
                                $createdUser = $Link->query($request)->fetch_assoc();
                                $id_user = $createdUser['id_user'];

                                $token = bin2hex(random_bytes(32));
                                $time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', time()) . ' +1 hour'));

                                $tokenInsertResult = $Link->query("INSERT INTO Token(value, time, id_user) VALUES('$token',  '$time', '$id_user')");
                                if (!$tokenInsertResult)
                                {
                                    setHTTPStatus("500", "Internal Server Error", $Link->error);
                                }
                                else
                                {
                                    echo json_encode(['token' => $token]);
                                }
                            }
                        }
                    }
                    break;
                case "login":
                    if (!validateLogin($requestData)) {
                        return;
                    }
                    $request = createRequest("user_login", null, $requestData);
                    $user = $Link->query($request)->fetch_assoc();
                    if (!is_null($user))
                    {
                        $id_user = $user['id_user'];
                        $token = bin2hex(random_bytes(32));
                        $time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', time()) . ' +1 hour'));

                        $tokenInsertResult = $Link->query("INSERT INTO Token(value, time, id_user) VALUES('$token', '$time', '$id_user')");
                        if (!$tokenInsertResult)
                        {
                            setHTTPStatus("400", "Bad Request");
                        }
                        else
                        {
                            echo json_encode(['token' => $token]);
                        }
                    }
                    else
                    {
                        setHTTPStatus("400", "Login failed");
                    }
                    break;
                case "logout":
                    if (!authorizationCheck()) {
                        break;
                    }
                    $token = substr(getallheaders()['Authorization'], 7);
                    $tokenDeleteResult = $Link->query("DELETE FROM Token WHERE value='$token'");
                    if (!$tokenDeleteResult)
                    {
                        setHTTPStatus("500", "Internal Server Error", $Link->error);
                    }
                    else
                    {
                        setHTTPStatus("200", null, "Success");
                    }

                    break;
                default:
                    setHTTPStatus("404", "There is no path as '$query'");
            }
        }
        else if ($method == "PUT")
        {
            switch($urlList[2])
            {
                case "profile":
                    if (!authorizationCheck()) {
                        break;
                    }
                    $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                    $userFromToken = $Link->query($requestToken)->fetch_assoc();
                    $id_user = $userFromToken['id_user'];

                    if (validateUserData($requestData)) {
                        $fullName = $requestData->body->fullName;
                        $address = $requestData->body->address;
                        $birthDate = date('Y-m-d h:m:s', strtotime($requestData->body->birthDate));
                        $gender = $requestData->body->gender;
                        $phoneNumber = $requestData->body->phoneNumber;
                        $userUpdateResult = $Link->query("UPDATE User SET fullName='$fullName', birthDate='$birthDate' , gender='$gender', phoneNumber='$phoneNumber', address='$address' WHERE id_user='$id_user'");

                        if (!$userUpdateResult) {
                            setHTTPStatus("500", "Internal Server Error", $Link->error);
                        } else {
                            setHTTPStatus("200", null, "Success");
                        }
                    }

                    break;
                default:
                    setHTTPStatus("404", "There is no path as '$query'");
            }
        }
    }
?>