<?php
    include_once "./helpers/headers.php";
    include_once "./helpers/request.php";
    include_once "./helpers/validation.php";
    include_once "./helpers/helpers.php";
    function route($method, $urlList, $requestData)
    {
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
            case null:
                $request = createRequest("menu", $parametersList, $requestData);
                if (!validateParameters($parametersList))
                {
                    return;
                }
                $menuPage = (int)$requestData->parameters["page"];
                $requestCount = createRequest("menu_count", null, substr($request, 83));
                $menuCount = $Link->query($requestCount)->fetch_assoc();
                $menuCount = (int)$menuCount["COUNT(*)"];
                $menuSize = 5;
                $menuCountPage = (($menuCount % 5 != 0) ? (int)($menuCount / 5) + 1 : (int)($menuCount / 5));

                if ($menuPage > $menuCountPage)
                {
                    setHTTPStatus("400", "Bad Request", "Invalid value for attribute page");
                    return;
                }
                $message = new stdClass();

                $dish = $Link->query($request);

                if (!$dish)
                {
                    setHTTPStatus("400", "Bad Request");
                    return;
                }
                else
                {
                    $menu = [];
                    $count = 0;
                    while ($row = $dish->fetch_assoc())
                    {
                        $count += 1;
                        if ($count <= $menuPage * 5 && $count >= ($menuPage - 1) * 5 + 1) {
                            $row['vegetarian'] = $row['vegetarian'] == 1;
                            $menu[] = $row;
                        }
                    }
                }
                $message->dishes = $menu;
                $message->pagination = [];
                $message->pagination["size"] = $menuSize;
                $message->pagination["count"] = $menuCountPage;
                $message->pagination["current"] = $menuPage;
                echo json_encode($message);
                break;
            case !null:
                $id = $urlList[2];
                switch ($urlList[3])
                {
                    case null:
                        $request = createRequest("dish_info", null, $id);

                        $dish = $Link->query($request)->fetch_assoc();

                        if($dish)
                        {
                            $message = [
                                'id' => $dish['id'],
                                'name' => $dish['name'],
                                'description' => $dish['description'],
                                'price' => $dish['price'],
                                'image' => $dish['image'],
                                'vegetarian' => $dish['vegetarian'] == 1,
                                'rating' => $dish['rating'],
                                'category' => $dish['category']
                                ];
                            echo json_encode($message);
                        }
                        else
                        {
                            setHTTPStatus("400", "Dish with id='$id' don't in database");
                        }
                        break;
                    case "rating":
                        if ($urlList[4] == "check")
                        {
                            if (!authorizationCheck()) {
                                break;
                            }
                            $requestDish = createRequest("dish_info", null, $id);
                            $dishFromId = $Link->query( $requestDish)->fetch_assoc();
                            $id_dish = $dishFromId['id_dish'];

                            $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                            $userFromToken = $Link->query($requestToken)->fetch_assoc();
                            $id_user = $userFromToken['id_user'];

                            $idOrder = $Link->query("SELECT `OrderDish`.id_order FROM `OrderDish` INNER JOIN `Order-Dish` ON `Order-Dish`.id_order=`OrderDish`.id_order WHERE `OrderDish`.id_user='$id_user' AND `Order-Dish`.id_dish='$id_dish'")->fetch_assoc();
                            $id_order = $idOrder['id_order'];
                            if($id_order)
                            {
                                $statusOrder = $Link->query("SELECT OrderDish.status FROM OrderDish WHERE id_order='$id_order'")->fetch_assoc();
                                $status = $statusOrder['status'];
                                if ($status == "Delivered")
                                {
                                    echo json_encode(true);
                                }
                                else
                                {
                                    echo json_encode(false);
                                }
                            }
                            else
                            {
                                setHTTPStatus("400", "User can't set rating on dish that wasn't ordered");
                            }
                            break;
                        }
                        break;
                    default:
                        setHTTPStatus("404", "There is no path as '$query'");
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
                    switch($urlList[3])
                    {
                        case "rating":
                            if (!authorizationCheck()) {
                                break;
                            }
                            $id = $urlList[2];
                            $requestDish = createRequest("dish_info", null, $id);
                            $dishFromId = $Link->query( $requestDish)->fetch_assoc();
                            $id_dish = $dishFromId['id_dish'];

                            $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
                            $userFromToken = $Link->query($requestToken)->fetch_assoc();
                            $id_user = $userFromToken['id_user'];

                            $idOrder = $Link->query("SELECT `OrderDish`.id_order FROM `OrderDish` INNER JOIN `Order-Dish` ON `Order-Dish`.id_order=`OrderDish`.id_order WHERE `OrderDish`.id_user='$id_user' AND `Order-Dish`.id_dish='$id_dish'")->fetch_assoc();
                            $id_order = $idOrder['id_order'];
                            if($id_order)
                            {
                                $statusOrder = $Link->query("SELECT OrderDish.status FROM OrderDish WHERE id_order='$id_order'")->fetch_assoc();
                                $status = $statusOrder['status'];
                                if ($status != "Delivered")
                                {
                                    setHTTPStatus("400", "User can't set rating on dish that wasn't ordered");
                                    break;
                                }
                            }
                            else
                            {
                                setHTTPStatus("400", "User can't set rating on dish that wasn't ordered");
                                break;
                            }
                            
                            $rating = $requestData->parameters['ratingScore'];
                            $ratingSearch = $Link->query("SELECT Rating.rating FROM Rating WHERE id_user='$id_user' AND id_dish='$id_dish'")->fetch_assoc();
                            
                            if (!$ratingSearch)
                            {
                                $ratingInsertResult = $Link->query("INSERT INTO web.Rating(id_dish, id_user, rating) VALUES('$id_dish', '$id_user', '$rating')");
                                if (!$ratingInsertResult)
                                {
                                    setHTTPStatus("500", "Internal Server Error", $Link->error);
                                }
                                else
                                {
                                    setRating($id_dish);
                                    setHTTPStatus("200", null,"Success");
                                }
                            }
                            else
                            {
                                $ratingUpdateResult = $Link->query("UPDATE web.`Rating` SET rating='$rating' WHERE id_user='$id_user' AND id_dish='$id_dish'");
                                if (!$ratingUpdateResult)
                                {
                                    setHTTPStatus("500", "Internal Server Error", $Link->error);
                                }
                                else
                                {
                                    setRating($id_dish);
                                    setHTTPStatus("200", null,"Success");
                                }
                            }
                            break;
                        default:
                            setHTTPStatus("404", "There is no path as '$query'");
                    }
                    break;
                default:
                    setHTTPStatus("404", "There is no path as '$query'");
            }
        }
    }
?>
