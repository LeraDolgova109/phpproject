<?php
    include_once "./helpers/headers.php";
    include_once "./helpers/request.php";
    include_once "./helpers/helpers.php";
    function route($method, $urlList, $requestData)
    {
        if (!authorizationCheck()) {
            return;
        }
        $url = $_SERVER['QUERY_STRING'];
        $parametersList = explode('&', $url);

        $queryList = preg_replace('/(q=index.php)/', "", $parametersList);
        $queryList = preg_grep('/(q=[A-Za-z\/]+)/', $queryList);
        $query = preg_replace('/(q=)/', "", $queryList);
        $query = $query ['1'];
        global $Link;

        $id_dish = $urlList[3];
        $requestDish = createRequest("dish_info", null, $id_dish);
        if ($method == "GET")
        {
            switch($urlList[2])
            {
            case null:
                $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                $userFromToken = $Link->query($requestToken)->fetch_assoc();
                $id_user = $userFromToken['id_user'];

                $message = [];
                if($id_user)
                {
                    $request = createRequest("dish_basket", null, $id_user);
                    $dishInBasket = $Link->query($request);

                    while ($row = $dishInBasket->fetch_assoc())
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
                case !null:
                    $dishFromId = $Link->query($requestDish)->fetch_assoc();
                    $id = $dishFromId['id_dish'];

                    $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                    $userFromToken = $Link->query($requestToken)->fetch_assoc();
                    $id_user = $userFromToken['id_user'];

                    if($id)
                    {
                        $sent['id_user'] =$id_user;
                        $sent['id_dish'] =$id;

                        $requestDishBasket = createRequest("dish_basket_info", null, $sent);
                        $dishInBasket = $Link->query($requestDishBasket)->fetch_assoc();

                        if (!$dishInBasket)
                        {
                            $amount = 1;
                            $totalPrice = $dishFromId['price'];
                            $basketInsertResult = $Link->query("INSERT INTO DishBasket(id_dish, id_user, amount, totalPrice) VALUES('$id', '$id_user', '$amount', '$totalPrice')");
                        }
                        else
                        {
                            $amount = $dishInBasket['amount'] + 1;
                            $totalPrice = doubleval($dishFromId['price']) * $amount;
                            $basketInsertResult = $Link->query("UPDATE DishBasket  SET amount='$amount', totalPrice='$totalPrice' WHERE id_user='$id_user' AND id_dish='$id'");
                        }
                        if (!$basketInsertResult)
                        {
                            setHTTPStatus("500", "Internal Server Error", $Link->error);
                        }
                        else
                        {
                            setHTTPStatus("200", null, "Success");
                        }
                    }
                    else
                    {
                        setHTTPStatus("400", "Dish with id='$id_dish' don't in database");
                    }
                    break;
                default:
                    setHTTPStatus("404", "There is no path as '$query'");
            }
        }
        else if ($method == "DELETE")
        {
            switch($urlList[2])
            {
                case !null:
                    $dishFromId = $Link->query( $requestDish)->fetch_assoc();
                    $id = $dishFromId['id_dish'];

                    $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                    $userFromToken = $Link->query($requestToken)->fetch_assoc();
                    $id_user = $userFromToken['id_user'];

                    $increase = $requestData->parameters['increase'];

                    if($id)
                    {   $sent['id_user'] =$id_user;
                        $sent['id_dish'] =$id;
                        $requestDishBasket = createRequest("dish_basket_info", null, $sent);
                        $dishInBasket = $Link->query($requestDishBasket)->fetch_assoc();

                        if ($dishInBasket)
                        {
                            $amount = $dishInBasket['amount'] - 1;
                            if ($increase == "true" && $amount != 0)
                            {
                                $totalPrice = doubleval($dishFromId['price']) * $amount;
                                $basketInsertResult = $Link->query("UPDATE DishBasket  SET amount='$amount', totalPrice='$totalPrice' WHERE id_user='$id_user' AND id_dish='$id'");
                                if (!$basketInsertResult)
                                {
                                    setHTTPStatus("500", "Internal Server Error", $Link->error);
                                }
                                else
                                {
                                    setHTTPStatus("200", null,"Success");
                                }
                            }
                            else
                            {
                                $basketDeleteResult = $Link->query("DELETE FROM DishBasket WHERE id_user='$id_user' AND id_dish='$id'");
                                if (!$basketDeleteResult)
                                {
                                    setHTTPStatus("500", "Internal Server Error", $Link->error);
                                }
                                else
                                {
                                    setHTTPStatus("200", null,"Success");
                                }
                            }
                        }
                        else
                        {
                            setHTTPStatus("400", "Dish with id='$id_dish' don't in basket");
                        }
                    }
                    else
                    {
                        setHTTPStatus("400", "Dish with id='$id_dish' don't in database");
                    }
                    break;
                default:
                    setHTTPStatus("404", "There is no path as '$query'");
            }
        }
    }
?>