<?php
    include_once "./helpers/headers.php";
    include_once "./helpers/request.php";
    include_once "./helpers/helpers.php";
    include_once "./helpers/validation.php";
    
    function route($method, $urlList, $requestData)
    {
        if (!authorizationCheck()) {
            return;
        }
        global $Link;
        $url = $_SERVER['QUERY_STRING'];
        $parametersList = explode('&', $url);

        $queryList = preg_replace('/(q=index.php)/', "", $parametersList);
        $queryList = preg_grep('/(q=[A-Za-z\/]+)/', $queryList);
        $query = preg_replace('/(q=)/', "", $queryList);
        $query = $query ['1'];

        if ($method == "GET")
        {
            switch($urlList[2])
            {
            case !null:
                $message = [];

                $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                $userFromToken = $Link->query($requestToken)->fetch_assoc();
                $id_user = $userFromToken['id_user'];

                $id = $urlList[2];
                $requestOrder = createRequest("order", null, $id);
                $orderFromId = $Link->query($requestOrder)->fetch_assoc();
                $id_order = $orderFromId['id_order'];
                
                if (!$orderFromId) 
                {
                    setHTTPStatus("400", "Bad Request");
                } 
                else 
                {
                    $request = createRequest("dish_order", null, $id_order);
                    $dishInOrder = $Link->query($request);
                    $dishes = [];
                    while ($row = $dishInOrder->fetch_assoc())
                    {
                        $dishes[] = $row;
                    }
                    $message['dishes'] = $dishes;
                    $message['address'] = $orderFromId['address'];
                    $message['deliveryTime'] = $orderFromId['deliveryTime'];
                    $message['orderTime'] = $orderFromId['orderTime'];
                    $message['status'] = $orderFromId['status'];
                    $message['price'] = $orderFromId['price'];
                    $message['id'] = $orderFromId['id'];
                    echo json_encode($message);
                }
                break;
            case null:
                $message = [];

                $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                $userFromToken = $Link->query($requestToken)->fetch_assoc();
                $id_user = $userFromToken['id_user'];

                $orderList = $Link->query("SELECT id, deliveryTime, orderTime, status, price
                                            FROM `OrderDish`
                                            WHERE id_user = '$id_user'");                
                if (!$orderList) 
                {
                    setHTTPStatus("400", "Bad Request");
                } 
                else 
                {
                    while ($row = $orderList->fetch_assoc())
                    {
                        $message[] = $row;
                    }
                    echo json_encode($message);
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
                case null:
                    $deliveryTime = date('Y-m-d H:i:s', strtotime($requestData->body->deliveryTime) - 3*60*60);
                    if (!validateOrderDate($requestData))
                    {
                        return;
                    }
                    $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                    $userFromToken = $Link->query($requestToken)->fetch_assoc();
                    $id_user = $userFromToken['id_user'];
                    $totalPrice = 0;

                    $request = createRequest("dish_basket", null, $id_user);
                    $dishInBasket = $Link->query($request);
                    if (!$dishInBasket)
                    {
                        setHTTPStatus("400", "Error",  "Empty basket for user with id='$id_user'");
                        break;
                    }
                    $basket = new stdClass();
                    while ($row = $dishInBasket->fetch_assoc())
                    {
                        $basket->dishes[] = $row;
                        $totalPrice += $row['totalPrice'];
                    }
                    $address = $requestData->body->address;

                    $id =  generate_uuid();
                    $date = date('Y-m-d H:i:s', time() + 4*60*60);
                    $orderInsertResult = $Link->query("INSERT INTO OrderDish(id, address, status, price, id_user, deliveryTime, orderTime) VALUES('$id', '$address', 'InProcess', '$totalPrice', ' $id_user', '$deliveryTime', '$date')");

                    if (!$orderInsertResult)
                    {
                        setHTTPStatus("500", "Internal Server Error", $Link->error);
                        break;
                    }
                    else
                    {
                        $requestOrder = createRequest("order", null, $id);
                        $orderFromId = $Link->query($requestOrder)->fetch_assoc();
                        $id_order = $orderFromId['id_order'];
                        foreach ($basket->dishes as $value) {
                            $tempId = $value['id'];
                            $id_dish = $Link->query("SELECT id_dish FROM Dish WHERE id='$tempId'")->fetch_assoc();
                            $id_dish = $id_dish['id_dish'];
                            $totalPrice = $value['totalPrice'];
                            $amount = $value['amount'];
                            $dishInsertResult = $Link->query("INSERT INTO `Order-Dish`(id_dish, id_order, amount, totalPrice) VALUES('$id_dish', '$id_order', '$amount', '$totalPrice')");
                            if (!$dishInsertResult)
                            {
                                setHTTPStatus("400", "Bad Request ".$Link->error);
                                break;
                            }
                        }
                    }
                    $basketDeleteResult = $Link->query("DELETE FROM DishBasket WHERE id_user='$id_user'");
                    if (!$basketDeleteResult) {
                        setHTTPStatus("500", "Internal Server Error", $Link->error);
                        break;
                    }
                    else
                    {
                        setHTTPStatus("200", null,"Success");
                    }
                    break;
                case !null:
                switch($urlList[3]) {
                    case "status":
                        $id = $urlList[2];
                        $requestOrder = createRequest("order", null, $id);
                        $orderFromId = $Link->query($requestOrder)->fetch_assoc();
                        $id_order = $orderFromId['id_order'];
                        if ($orderFromId['status'] == 'Delivered') {
                            setHTTPStatus("400", "Can't update status for order with id='$id'");
                            return;
                        }

                        $statusUpdateResult = $Link->query("UPDATE `OrderDish` SET status='Delivered' WHERE id_order='$id_order'");
                        if (!$statusUpdateResult) {
                            setHTTPStatus("500", "Internal Server Error", $Link->error);
                        } else {
                            setHTTPStatus("200", null,"Success");
                        }
                        break;
                    default:
                        setHTTPStatus("404", "There is no path as '$query'");
                        break;
                }
                break;
            default:
                setHTTPStatus("404", "There is no path as '$query'");
            }
        }
    }
?>