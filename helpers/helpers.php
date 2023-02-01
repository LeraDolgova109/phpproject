<?php
    function generate_uuid(): string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    function authorizationCheck(): bool
    {
        global $Link;
        $requestToken = createRequest("token", null, substr(getallheaders()['Authorization'], 7));
        $userFromToken = $Link->query($requestToken)->fetch_assoc();
        $time = $userFromToken['time'];

        $date = date('Y-m-d H:i:s', time());
        if ($time < $date || $time == null)
        {
            setHTTPStatus("401", "Unauthorized");
            return false;
        }
        return true;
    }

    function setRating($id_dish)
    {
        global $Link;
        $sum = $Link->query("SELECT SUM(rating) AS sum FROM Rating WHERE id_dish='$id_dish'")->fetch_assoc();
        $count = $Link->query("SELECT COUNT(*) AS count FROM Rating WHERE id_dish='$id_dish'")->fetch_assoc();
        $rating = doubleval($sum['sum']/$count['count']);

        $ratingDishInsertResult = $Link->query("UPDATE web.`Dish` SET rating='$rating' WHERE id_dish='$id_dish'");
        if (!$ratingDishInsertResult) {
            setHTTPStatus("500", "Internal Server Error", $Link->error);
        }
        return;
    }

?>