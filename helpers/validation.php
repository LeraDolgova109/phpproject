<?php
    function validateStringNotLess($str = '', $length = 1): bool
    {
        if (strlen($str) >= $length)
        {
            return true;
        }
        return false;
    }

    function validatePassword($str = ''): bool
    {
        if (preg_match('/[0-9]+/', $str) OR $str == null)
        {
            return true;
        }
        return false;
    }
    function validateEmail($str = ''): bool
    {
        if (filter_var($str, FILTER_VALIDATE_EMAIL))
        {
            return true;
        }
        return false;
    }

    function validateGender($str = ''): bool
    {
        $arr = ["Male", "Female"];
        if (in_array($str, $arr))
        {
            return true;
        }
        return false;
    }

    function validateDateFormat($str): bool
    {
        if (preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $str) > 0) {
            return true;
        } 
        return false;
    }
    function validateBirthDate($str): bool
    {
        if ($str == '' or ($str > '01.01.1900 0:00:00' and $str < '31.12.9999 0:00:00'))
        {
            return true;
        }
        return false;
    }

    function validateDate($str): bool
    {
        $date = date('Y-m-d H:i:s', time());
        if ($str == '' or ($str < $date))
        {
            return true;
        }
        return false;
    }
    
    function validateDeliveryTime($str): bool
    {
        $date = date('Y-m-d H:i:s', time() + 4*60*60);
        $minuteDiff = round((strtotime($str) - strtotime($date))/60, 1);
        if ($minuteDiff > 60)
        {
            return true;
        }
        return false;
    }
    function validateTelephone($str = ''): bool
    {
        if ($str == '' or preg_match('/\+7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}/', $str))
        {
            return true;
        }
        return false;
    }

    function validateUserNotNull($requestData): bool
    {
        $errors = new stdClass();
        $canRegister = true;
        if (!validateStringNotLess(strval($requestData->body->email), 1))
        {
            $errors->Email[] = "The Email field is required.";
            $canRegister = false;
        }
        if (!validateEmail(strval($requestData->body->email)))
        {
            $errors->Email[] = "The Email field is not a valid e-mail address.";
            $canRegister = false;
        }
        if (!validateStringNotLess(strval($requestData->body->fullName), 1))
        {
            $errors->FullName[] = "The FullName field is required.";
            $canRegister = false;
        }
        if (!validateStringNotLess(strval($requestData->body->password), 6))
        {
            $errors->Password[] = "The field Password must be a string or array type with a minimum length of '6'.";
            $canRegister = false;
        }
        if (!validateDateFormat($requestData->body->birthDate))
        {
            $errors->BirthDate[] = "Invalid Data Format.";
            $canRegister = false;
        }
        (!$canRegister) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;

        return $canRegister;
    }

    function validateLogin($requestData): bool
    {
        $errors = new stdClass();
        $canRegister = true;
        if (!validateStringNotLess(strval($requestData->body->email), 1))
        {
            $errors->Email[] = "The Email field is required.";
            $canRegister = false;
        }
        if (!validateEmail(strval($requestData->body->email)))
        {
            $errors->Email[] = "The Email field is not a valid e-mail address.";
            $canRegister = false;
        }
        if (!validateStringNotLess(strval($requestData->body->password), 6))
        {
            $errors->Password[] = "The field Password must be a string or array type with a minimum length of '6'.";
            $canRegister = false;
        }
        (!$canRegister) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;

        return $canRegister;
    }
    function validateUserDataCorrect($requestData): bool
    {
        $errors = new stdClass();
        $canRegister = true;
        if (!validateBirthDate(date('Y-m-d h:m:s', strtotime($requestData->body->birthDate))))
        {
            $errors->BirthDate[] = "The field BirthDate must be between 01.01.1900 0:00:00 and 31.12.9999 0:00:00.";
            $canRegister = false;
        }
        if (!validateTelephone($requestData->body->phoneNumber))
        {
            $errors->PhoneNumber[] = "The PhoneNumber field is not a valid phone number.";
            $canRegister = false;
        }
        if (!validateGender($requestData->body->gender))
        {
            $errors->Gender[] = "The JSON value could not be converted to Delivery.Api.Models.Storage.Enums.Gender.";
            $canRegister = false;
        }

        (!$canRegister) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;

        return $canRegister;
    }

    function validateUserData($requestData): bool
    {
        $canRegister = validateUserDataCorrect($requestData);
        if (!$canRegister)
        {
            return false;
        }

        $errors = new stdClass();
        if (!validatePassword($requestData->body->password))
        {
            $errors->Password[] = "Password requires at least one digit";
            $canRegister = false;
        }
        if (!validateDate(date('Y-m-d h:i:s', strtotime($requestData->body->birthDate))))
        {
            $errors->BirthDate[] = "Birth date can't be later than today";
            $canRegister = false;
        }

        (!$canRegister) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;

        return $canRegister;
    }

    function validateOrderData($requestData): bool
    {
        $canOrder = true;
        $errors = new stdClass();
        
        if (!validateDateFormat(strval($requestData->body->deliveryTime)))
        {
            $errors->DeliveryTime[] = "Invalid Data Format.";
            $canOrder = false;
        }
        if (!validateStringNotLess(strval($requestData->body->address), 1))
        {
            $errors->Address[] = "The Address field is required.";
            $canOrder = false;
        }
        (!$canOrder) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;

        return $canOrder;
    }
    function validateOrderDate($requestData): bool
    {
        $canOrder = validateOrderData($requestData);
        if (!$canOrder)
        {
            return false;
        }
        $deliveryTime = date('Y-m-d H:i:s', strtotime($requestData->body->deliveryTime) - 3*60*60);
        if (!validateDeliveryTime($deliveryTime)) 
        {
            $canOrder = false;
        }
        (!$canOrder) ? setHTTPStatus("400", "Error", "Invalid delivery time. Delivery time must be more than current datetime on 60 minutes") : 0;
        return $canOrder;
    }

    function validateParameters($parametersList) : bool
    {
        $errors = new stdClass();
        $canShow = true;
        $categoriesEnum = ["Wok", "Pizza", "Soup", "Dessert", "Drink"];
        $categoriesList = preg_grep('/(categories=[A-Za-z\/]+)/', $parametersList);
        foreach ($categoriesList as $value) {
            $value = preg_replace('/(categories=)/', "", $value);
            if (!in_array($value, $categoriesEnum))
            {
                $canShow = false;
                $errors->categories[] = "The value '$value' is invalid.";
                break;
            }
        }
        $sortingList = preg_grep('/(sorting=[A-Za-z\/]+)/', $parametersList);
        $sortingEnum = ["NameAsc", "NameDesc", "PriceAsc", "PriceDesc", "RatingAsc", "RatingDesc"];
        foreach ($sortingList as $value) {
            $value = preg_replace('/(sorting=)/', "", $value);
            if (!in_array($value, $sortingEnum))
            {
                $canShow = false;
                $errors->sorting[] = "The value '$value' is invalid.";
                break;
            }
        }
        (!$canShow) ? setHTTPStatus("400", $errors, "One or more validation errors occurred.") : 0;
        return $canShow;
    }
?>