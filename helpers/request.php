<?php
    function createRequest($name, $parametersList, $requestData)
    {
        switch ($name) {
            default:
            case "dish_info":
                $id = $requestData;
                return "SELECT * FROM Dish WHERE id= '$id'";
            case "dish_basket":
                $id_user = $requestData;
                return "SELECT `Dish`.id, `Dish`.name, `Dish`.price, `DishBasket`.totalPrice, `DishBasket`.amount, `Dish`.image FROM `DishBasket` INNER JOIN `Dish` ON `DishBasket`.`id_user` = '$id_user' WHERE `DishBasket`.`id_dish` = `Dish`.`id_dish`";
            case "dish_basket_info":
                $id_user = $requestData['id_user'];
                $id_dish = $requestData['id_dish'];
                return "SELECT * FROM DishBasket WHERE id_user='$id_user' AND id_dish='$id_dish'";
            case "menu":
                $request = "SELECT id, name, description, price, image, vegetarian, rating, category FROM Dish WHERE ";

                if ($requestData->parameters['vegetarian']) {
                    $vegetarian = ($requestData->parameters['vegetarian'] == "false") ? 0 : 1;
                    $request = ($vegetarian == 1) ? $request . "vegetarian=" . "'$vegetarian'" . " AND " : $request ;
                }

                if ($requestData->parameters['categories']) {
                    $categoriesList = preg_grep('/(categories=[A-Za-z\/]+)/', $parametersList);
                    $categories = "";
                    foreach ($categoriesList as $value) {
                        $categories = $categories . " OR " . "category='" . $value . "'";
                    }

                    $categories = preg_replace('/(categories=)/', "", $categories);
                    $categories = substr($categories, 4);
                    $categories = "(" . $categories . ")";
                    $request = $request . $categories . " ";
                }
                else
                {
                    $request = substr($request, 0, -5);
                }

                if ($requestData->parameters['sorting']) {
                    $sortingList = preg_grep('/(sorting=[A-Za-z\/]+)/', $parametersList);
                    $sorting = ' ORDER BY ';
                    $sortingAsc = "";
                    foreach ($sortingList as $value) {
                        if (substr($value, -3) == "Asc") {
                            $value = preg_replace('/(sorting=)/', "", $value);
                            switch ($value) {
                                case "NameAsc":
                                    $sortingAsc = $sortingAsc . "name, ";
                                    break;
                                case "PriceAsc":
                                    $sortingAsc = $sortingAsc . "price, ";
                                    break;
                                case "RatingAsc":
                                    $sortingAsc = $sortingAsc . "rating, ";
                                    break;
                            }
                        }
                    }
                    $sorting = ($sortingAsc != "") ? $sorting . substr($sortingAsc, 0, -2) . " ASC, " : " ORDER BY ";
                    $sortingDesc = "";
                    foreach ($sortingList as $value) {
                        if (substr($value, -4) == "Desc") {
                            $value = preg_replace('/(sorting=)/', "", $value);
                            switch ($value) {
                                case "NameDesc":
                                    $sortingDesc = $sortingDesc . "name, ";
                                    break;
                                case "PriceDesc":
                                    $sortingDesc = $sortingDesc . "price, ";
                                    break;
                                case "RatingDesc":
                                    $sortingDesc = $sortingDesc . "rating, ";
                                    break;
                            }
                        }
                    }
                    $sorting = ($sortingDesc != "") ? $sorting . substr($sortingDesc, 0, -2) . " DESC " : "";

                    $request = $request . $sorting;
                }
                return $request;
            case "menu_count":
                return "SELECT COUNT(*) FROM DISH $requestData";
            case "dish_order":
                $id_order = $requestData;
                return "SELECT `Dish`.id, `Dish`.name, `Dish`.price, `Order-Dish`.totalPrice, `Order-Dish`.amount, `Dish`.image FROM `Dish` INNER JOIN `Order-Dish` ON `Order-Dish`.id_dish = `Dish`.id_dish WHERE `Order-Dish`.`id_order`='$id_order'";
            case "order":
                $id = $requestData;
                return "SELECT * FROM OrderDish WHERE id='$id'";
            case "token":
                $token = $requestData;
                return "SELECT * FROM Token WHERE value= '$token'";
            case "user_login":
                $email = $requestData->body->email;
                $password = hash("sha1", $requestData->body->password);
                return "SELECT id_user FROM User WHERE email= '$email' AND password='$password'";
            case "user_token":
                $id_user = $requestData;
                return "SELECT id, fullName, birthDate, gender, address, email, phoneNumber FROM User WHERE id_user= '$id_user'";

        }
    }
?>