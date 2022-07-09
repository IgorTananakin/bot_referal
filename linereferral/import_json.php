<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
require_once('MySQL/connect.php');
$array = ["id","user_id", "information", "status", "date"];
$all_users = $db->SQL_Select($array, 'applications', "", false);
//print_r($all_users);
echo "<pre>";
foreach ($all_users as $value) {
    $array_rez[] = $value;
}
//print_r($array_rez);
echo "</pre>";
$history_json = file_get_contents("result.json");
$history_array = json_decode($history_json,true);
echo "<pre>";
foreach ($history_array['messages'] as $value) {
    if($value['from'] == "Line Referral") {
        $text = $value['text'];
//        print_r($value);
        if(is_array($text)) {
            $telephone = $text[1]['text'];
            $sum = $text[2];
            $sum = str_replace("sum: ","",$sum);
        } else {
            $telephone = 0;
            $sum = 0;
        }

        $date = str_replace("T", ' ', $value['date']); //
        $date = strtotime($date);

        foreach ($array_rez as $key=>$value1) {
            $information = json_decode($value1['information'],true);
            if($information['payments']) {
                if ($information['payments'] == $telephone && $information['sum'] == $sum) {
                    $array_rez[$key]['date'] = $date;
                }
            }
        }

    }
}

print_r($array_rez);
echo "</pre>";
//foreach ($array_rez as $value) {
//    $array = ["date = '" . $value['date'] . "'"];
//    $where = "id = '" . $value['id'] . "'";
//    $db->SQL_Update($array, 'applications', $where);
//}