<?php

require_once('MySQL/connect.php');// TODO файл высчитывает формулы
$phone = $_GET['phone'];
if (!$phone) {
    $phone = 0;
}
function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}

$now = time();
$your_date = strtotime("2022-03-01");
$datediff = $now - $your_date;
$day = floor($datediff / (60 * 60 * 24));
$bonus_day_sum = $day * 50;

$arrayApplicationsPhone = selectApplications($db, "information LIKE '%$phone%'");

foreach ($arrayApplicationsPhone as $key => $userApplications) {
    $arrayApplicationsUsers = selectApplications($db, "user_id = $key");
    $array = ["balance", 'username', 'refer', 'phone_payments', 'user_id'];
    $users = $db->SQL_Select($array, 'users', "user_id = $key", false);
    $referUsers = $db->SQL_Select($array, 'users', "refer = $key AND `phone` IS NOT NULL", false);

    $countRefer = mysqli_num_rows($referUsers);
    for ($row = array(); $set = mysqli_fetch_assoc($referUsers); $row[] = $set) {
        ;
    }
    usort($row, "cmp");

    foreach ($users as $value) {
        echo "<u>UserId:</u> $key <br><u>Username:</u> {$value['username']}<br> <u>Refer:</u> {$value['refer']} <br> <u>CountRefer:</u> $countRefer<br> <u>PhonePayments:</u> {$value['phone_payments']}<br><br> <u>Calculator:</u> " . $countRefer * 300 + $bonus_day_sum . "<br><u>Total:</u> " . $arrayApplicationsUsers[$key]['sum']['total'] + $value['balance'] . "<br> <u>Balance:</u> {$value['balance']}<br> <u>Paid:</u> {$arrayApplicationsUsers[$key]['sum']['paid']}<br> <u>Fail:</u> {$arrayApplicationsUsers[$key]['sum']['fail'] }<br> <u>Expectation:</u> {$arrayApplicationsUsers[$key]['sum']['work']}<br><br>";
    }
    echo "<br><br>";
}

function selectApplications($db, $where)
{
    $array = ["user_id", 'information', 'status', 'date'];
    $usersApplications = $db->SQL_Select($array, 'applications', $where, false);
    for ($usersApplicationsArray = array(); $set = mysqli_fetch_assoc($usersApplications); $usersApplicationsArray[] = $set) {
    }

    $arrayApplications = [];
    foreach ($usersApplicationsArray as $itemApp) {
        $information = json_decode($itemApp['information'], true);
        if ($itemApp['status'] == 'approved') {
            $arrayApplications[$itemApp['user_id']]['sum']['paid'] += $information['sum'];
        }
        if ($itemApp['status'] == 'failured') {
            $arrayApplications[$itemApp['user_id']]['sum']['fail'] += $information['sum'];
        }
        if ($itemApp['status'] == 'working') {
            $arrayApplications[$itemApp['user_id']]['sum']['work'] += $information['sum'];
        }
        if ($itemApp['status'] != 'failured') {
            $arrayApplications[$itemApp['user_id']]['sum']['total'] += $information['sum'];
        }
    }
    return $arrayApplications;
}