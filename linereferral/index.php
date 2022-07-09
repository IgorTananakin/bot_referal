<?php
//ini_set( 'error_reporting', E_ALL );
//ini_set( 'display_errors', 1 );
//ini_set( 'display_startup_errors', 1 );

//ini_set('log_errors', 'On');
//ini_set('error_log', '/assets/txt/log.txt');
// определяем кодировку
header('Content-type: text/html; charset=utf-8');
require_once('MySQL/connect.php');
$cron = $_GET['cron'];
if (!$cron) {
    $cron = 0;
}
$referrals_active_cron = $_GET['referrals_active'];
if (!$referrals_active_cron) {
    $referrals_active_cron = 0;
}
$bonus_day = $_GET['bonus_day'];
if (!$bonus_day) {
    $bonus_day = 0;
}
$delivery = $_GET['delivery'];
if (!$delivery) {
    $delivery = 0;
}
$clear_bonus_day = $_GET['clear_bonus_day'];
if (!$clear_bonus_day) {
    $clear_bonus_day = 0;
}
$notification = $_GET['notification'];
if (!$notification) {
    $notification = 0;
}
// Создаем объект бота
$bot = new Bot($db, $cron, $referrals_active_cron, $bonus_day, $delivery,$clear_bonus_day);
// Обрабатываем пришедшие данные
$bot->init('php://input');

/**
 * Class Bot
 */
class Bot
{
    // <bot_token> - созданный токен для нашего бота от @BotFather
    private $botToken = "5079472907:AAEmGZ1gCxqPIUK1NEC0Fc4sXqdUUHO3zAU";

    public $db;
    public $cron;
    public $referrals_active_cron;
    public $bonus_day;
    public $delivery;
    public $clear_bonus_day;
    private $notification;
    public $path_id_users = "assets/id_users/";
    public $path_img = "assets/img/";
    public $path_bid = "assets/json/bid.json";
    public $path_partners_channel = "assets/txt/partners_channel.txt";
    public $path_log = "assets/txt/log.txt";
    public $path_bonus_day = "assets/json/bonus_day.json";
    public $path_uniqid = "assets/txt/uniqid.txt";
    public $path_send_day = 'assets/txt/send_day.txt';
    public $path_lang = "assets/json/lang/";
    public $path_lang_users = "assets/txt/lang_users/";
    public $path_main_url = "https://botlinebet.ru/linereferral/";
    public $path_payment_data = "assets/json/payment_data.json";
    public $path_cron = "assets/json/cron.json";
    public $path_delivery = 'assets/json/delivery.json';
    public $path_delivery_text = 'assets/txt/delivery.txt';
    public function __construct(
        $db,
        $cron,
        $referrals_active_cron,
        $bonus_day,
        $delivery,
        $clear_bonus_day
    ) {
        $this->db = $db;
        $this->cron = $cron;
        $this->referrals_active_cron = $referrals_active_cron;
        $this->bonus_day = $bonus_day;
        $this->delivery = $delivery;
        $this->clear_bonus_day = $clear_bonus_day;
        $this->notification = $_GET['notification'];
    }

    // адрес для запросов к API Telegram
    private $apiUrl = "https://api.telegram.org/bot";
    // 659025951
    // 1440214573
    // -1001252257175 - banners
    // админы
    private $ADMIN = [1440214573];
    private $ADMIN_send = 1440214573;
    private $GROUP = -1001591758625;
    private $GROUP_APPLICATION = -1001533706316;
    private $disabled = [// ! массив кто жульничал
        1864080207,998948668705,
        2061577441,
        1789824724,
        2128630555,
        2063048706,
        1685123177,
        1588528486,
        1704025816,
        1121255802,
        1341463325,
        1683440038,
        1323520042,
        1995709989,
        924912588,
        1986360826,
        1363720126,
        1925424331,
        5172980518,
        1915036135,
        5263704544,
        5199702409,
        5292543144,
        5174978163,
        1481017394,
        1695222792,
        5215795152,
        5177296545,
        5026235312,
        5269937688,
        2110214060,
        1996831242,
        636191445,
        5190050436,
        5128107536,
        5162266074,
        1452703296,
        5250972722,
        5084749725,
        5206339093,
        1076739158,
        5045231779,
        5017743224,
        2020812142,
        1099356384,
        5056440683,
        1161957172,
        5235543774,
        1794325528,
        5296623110,
        5151268412,
        5225877823,
        5279064331,
        5013830606,
        5005448548,
        1555879537,
        5009649738,
        5022072214,
        2018446483,
        1889665429,
        5228665459,
        1645003473,
        1656611350,
        1308309933,
        1770510736,
        1947218260,
        1333799184,
        1470822171,
        1693698991,
        842987132,
        1893564626,
        1970086722,
        2076596550,
        2124158141,
        1756369119,
        1685123177,
        1992694386,
        1695222792,
        1693698991,
        1656611350,
        1994520226,
        1161661674,
        1615340315,
        1601165065,
        1511390300,
        1138837812,
        2049121542,
        1239421197,
        2141152786
    ];

    public function init($data_php)
    {
        // создаем массив из пришедших данных от API Telegram
        $data = $this->getData($data_php);
        // id чата отправителя


        //включаем логирование будет лежать рядом с этим файлом//! нужная 196 строка в лог файле ищем log по своему id 
        //$this->setFileLog( $data, $this->path_log );
        if ($this->clear_bonus_day) {
            $bonusDay = json_decode(file_get_contents($this->path_bonus_day), true);
            $bonusDay['completed'] = 0;
            $bonusDay['day'] = 0;
            file_put_contents($this->path_bonus_day, json_encode($bonusDay));
        }
        if ($this->bonus_day) {

            $bonusDay = json_decode(file_get_contents($this->path_bonus_day), true);

            if ($bonusDay['completed'] == 0) {
                $uniqid = file_get_contents($this->path_uniqid);
                $limitPlus = 250;
                if ($bonusDay['day'] == 0) {
                    $uniqid = uniqid('', true);
                    file_put_contents($this->path_uniqid, $uniqid);
                    file_put_contents($this->path_send_day, '');
                    $arrayUpdate = ["bonus_day = 1"];
                    $this->db->SQL_Update($arrayUpdate, 'users', "referrals_active = '1' ");
                    $bonusDay['limit'] = 0;
                    $bonusDay['day'] = 1;
                    $bonusDay['working'] = 1;
                }
                $limit = $bonusDay['limit'];
                $bonusDay['limit'] = $limit + $limitPlus;
                $array = ["user_id"];
                $limit_users = $this->db->SQL_SelectArray($array, 'users',
                    "referrals_active = '1' LIMIT $limit,$limitPlus", true);
                if (count($limit_users) != $limitPlus) {
                    $bonusDay['completed'] = 1;
                    $bonusDay['working'] = 0;
                }
                file_put_contents($this->path_bonus_day, json_encode($bonusDay));
                for ($i = 0; $i < count($limit_users);) {
                    if (!in_array($limit_users[$i]['user_id'], $this->disabled)) {

                        $array_lang = $this->select_lang_users($limit_users[$i]['user_id']);
                        $buttons_bonus_day = $this->getinline_KeyBoard([
                            [
                                [
                                    "text" => $array_lang["Olish💰"],
                                    "callback_data" => $uniqid
                                ]
                            ]
                        ]);
                        $dataSend = array(
                            'text' => $array_lang["Bugungi bonusiz 50 so'm"],
                            'chat_id' => $limit_users[$i]['user_id'],
                            'reply_markup' => $buttons_bonus_day,
                            "parse_mode" => "HTML"
                        );
                        $rezult_json = $this->requestToTelegram($dataSend, "sendMessage");
                        $rezult_array = json_decode($rezult_json, true);
                        $rezult_array['ok'] ? $flag = 1 : $flag = 0;
                        file_put_contents($this->path_send_day, "$flag|" . $limit_users[$i]['user_id'] . " ||| ",
                            FILE_APPEND);
                        if ($rezult_array['ok'] || $rezult_array['error_code'] == 403) {
                            $i++;
                        }
                    } else {
                        $i++;
                    }
                }
            }
        }
        if ($this->delivery) {// TODO нужная строка
            $start = microtime(true);
            $delivery = json_decode(file_get_contents($this->path_delivery), true);
            $bonusDay = json_decode(file_get_contents($this->path_bonus_day), true);
            if ($bonusDay['working'] == 0) {
                if ($delivery['completed'] == 0) {
                $limitPlus = 250;
                if ($delivery['day'] == 0) {
                    $delivery['limit'] = 0;
                    $delivery['day'] = 1;
                    $delivery['working'] = 1;
                    file_put_contents($this->path_delivery_text, '');
                }
                $limit = $delivery['limit'];
                $delivery['limit'] = $limit + $limitPlus;
                $array = ["user_id"];
                // user_id = 659025951
                $all_users = $this->db->SQL_SelectArray($array, 'users', "", true, "$limit,$limitPlus");// TODO сюда sql свой id 882013448
                if (count($all_users) != $limitPlus) {
                    $delivery['completed'] = 1;
                    $delivery['working'] = 0;
                }
                file_put_contents($this->path_delivery, json_encode($delivery));
                for ($i = 0; $i < count($all_users);) {
                    if (!in_array($all_users[$i]['user_id'], $this->disabled)) {
//                        $buttons_payment = $this->getinline_KeyBoard([
//                            [
//                                [
//                                    "text" => 'Получить бонус/Bonus olish',
//                                    "url" => "https://lb-aff.com//L?tag=d_1306793m_22611c_linereff&site=1306793&ad=22611&r=registration/"
//                                ]
//                            ]
//                        ]);
                        $rezult_json = $this->requestToTelegram([// ! после пункта e) размещаем
                            'chat_id' => $all_users[$i]['user_id'],// ! в случае видео поменять на видео document на документ
                            'video' => 'BAACAgIAAxkBARKwhWK9Y7pt8MvPE5pTXlutVYvG0Z_nAAJGFQACFOjxSSZjje1W1LFdKQQ',// ! в caption свой текст 
                            'caption' => "Linebet bilan tezroq ul ishlashni boshlang,\n<a href='https://bit.ly/3AdNhm4'>registrasiya</a>  qiling va hayotni yorqin tarafida buling !",
                            'parse_mode' => 'HTML',
//                            'reply_markup' => $buttons_payment,
                        ], "sendVideo");// !sendDocument
                        $rezult_array = json_decode($rezult_json, true);
                        $rezult_array['ok'] ? $flag = 1 : $flag = 0;

                        file_put_contents($this->path_delivery_text, "$flag|" . $all_users[$i]['user_id'] . " ||| ",
                            FILE_APPEND);
                        if ($rezult_array['ok'] || $rezult_array['error_code'] == 403) {
                            $i++;
                        }
                    } else {
                        $i++;
                    }
                }
                echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
            }
            }
        }
        if ($this->referrals_active_cron) {
            $this->selectStats();
        }
        if ($this->cron) {

            $start = microtime(true);
            $cron = json_decode(file_get_contents($this->path_cron), true);
            $delivery = json_decode(file_get_contents($this->path_delivery), true);
            $bonusDay = json_decode(file_get_contents($this->path_bonus_day), true);
            if ($bonusDay['working'] == 0 && $delivery['working'] == 0) {

            if ($cron['completed'] == 0) {
                $limitPlus = 80;
                if ($cron['day'] == 0) {
                    $cron['limit'] = 0;
                    $cron['day'] = 1;
                }
                $limit = $cron['limit'];
                $cron['limit'] = $limit + $limitPlus;
                $array = ["user_id", "referrals_active", "balance", "date_subscrip", "username"];
                $all_users = $this->db->SQL_SelectArray($array, 'users', "", true, "$limit,$limitPlus");
                if (count($all_users) != $limitPlus) {
                    $cron['completed'] = 1;
                }
                file_put_contents($this->path_cron, json_encode($cron));
                foreach ($all_users as $value) {
                    $array_users[$value['user_id']] = $value;
                }
                $channel = file_get_contents($this->path_partners_channel);
                if ($channel) {
                    $channel = explode(",", $channel);
                } else {
                    $channel = 0;
                }
                $bid = file_get_contents($this->path_bid);
                $bid = json_decode($bid, true);
                if ($channel) {
                    $time = time();
                    foreach ($array_users as $key => $value) {
                        $full_subscrip = 0;
                        $arr_no_subscrip = [];
                        for ($i = 0; $i < count($channel) - 1; $i++) {
                            $rez_subscrip = $this->getChatMember($key,
                                "@" . $channel[$i]); // @linebet_uz  @LinebetPromo
                            if ($rez_subscrip != "left" && $rez_subscrip != "banned") {
                                $full_subscrip++;
                            }

                            if ($rez_subscrip == "left") {
                                $arr_no_subscrip[] = $channel[$i];
                            }
                        }
                        if ($full_subscrip == count($channel) - 1) {

                            if ($value['referrals_active'] == null || $value['referrals_active'] == 0) {
                                $array_lang = $this->select_lang_users($value['refer']);
                                $array_users[$key]['referrals_active'] = 1;
                                $array_users[$key]['balance'] += $bid['subscrip'];
                                $array_users[$key]['date_subscrip'] = $time;
                            }
                            //                        if ($value[ 'referrals_active' ] == 1) {
                            //                            $start_date = new DateTime(date("d-m-Y H:i", $value[ 'date_subscrip' ]));
                            //                            $since_start = $start_date->diff(new DateTime(date("d-m-Y H:i")));
                            //                            if($since_start->d >= 15) {
                            ////                                $this->sendMessage($value['user_id'],"15");
                            //                                $array_users[ $key ][ 'balance' ] += $bid[ 'subscrip' ];
                            //                                $array_users[ $key ][ 'date_subscrip' ] = $time;
                            //                            }
                            //                        }
                        } else {
                            if ($value['referrals_active'] == 1) {
                                $array_users[$key]['referrals_active'] = 0;
                                $array_users[$key]['balance'] -= $bid['subscrip'];
                                $array_users[$key]['date_subscrip'] = $time;

                            }
//                            if ($array_users[$key]['referrals_active'] == 0 && $array_users[$key]['referrals_active'] !== null && $arr_no_subscrip) {
//                                $array_lang = $this->select_lang_users($value['user_id']);
//                                $rezult = $array_lang["Siz bu kanaldan"] . "\n";
//                                for ($i = 0; $i < count($arr_no_subscrip); $i++) {
//                                    $rezult .= "https://t.me/" . $arr_no_subscrip[$i] . "\n";
//                                }
//                                $rezult .= $array_lang["Iltimos Kanalga"];
////                            $this->sendMessage($value['user_id'], $rezult);
//                            }
                        }


                    }
                    foreach ($array_users as $key => $value) {
                        if ($value['referrals_active'] !== null) {
                            $array = ["referrals_active = '" . $value['referrals_active'] . "' , balance = '" . $value['balance'] . "' , date_subscrip = '" . $value['date_subscrip'] . "' "];
                            $where = "user_id = '" . $key . "'";
                            $this->db->SQL_Update($array, 'users', $where);
                        }
                    }

                }
                echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
            }
        }
        }
        if ($this->notification){
            $arr_time = [10,14,18];
            $hour = date('G');
            if(in_array($hour,$arr_time)) {
                $flag = false;
                $array = ["user_id", 'date'];
                $all_applications = $this->db->SQL_SelectArray($array, 'applications',
                    "status = 'working' AND date IS NOT NULL", true);

                for ($i = 0; $i < count($all_applications); $i++) {
                    $array = ["phone_payments"];
                    $users = $this->db->SQL_SelectArray($array, 'users',
                        "user_id = " . $all_applications[$i]['user_id'], true);
                    $count_phone = count(json_decode($users[0]['phone_payments'], true));
                    if ($count_phone >= 3) {
                        continue;
                    }
                    $date = date("Y-m-d H:i:s", $all_applications[$i]['date']);
                    $dateDiff = date_diff(new DateTime(), new DateTime($date));
                    if ($dateDiff->d >= 1) {
                        $flag = true;
                    }
                }
                if($flag === true) {
                    $this->sendMessage(122815990, 'Выплат не было больше 24 часов');
                    $this->sendMessage(659025951, 'Выплат не было больше 24 часов');
                }
            }
        }

        if ($data) {

            if (array_key_exists('message', $data)) {
                $chat_id = $data['message']['chat']['id'];
                $message = $data['message']['text'];
                if (in_array($chat_id, $this->disabled)) {
                    exit;
                }
                $array_lang = $this->select_lang_users($chat_id);
                $lang_buttons = $this->getKeyBoard([
                    [
                        [
                            "text" => "RUS 🇷🇺"
                        ],
                        [
                            "text" => "UZB 🇺🇿"
                        ],

                    ],
                    [
                        [
                            "text" => $array_lang["Bekor qilish"]
                        ]
                    ]
                ]);
                $start = $this->getKeyBoard([
                    [
                        ["text" => $array_lang["Pul ishlash 💸"]]
                    ],
                    [
                        ["text" => $array_lang["Balans 💰"]],
                        ["text" => $array_lang["Pul yechish 💳"]]

                    ],
                    [
                        ["text" => $array_lang["To'lovlar tarixi 🧾"]],
                        ["text" => $array_lang["Til"]]
                    ],
                    [
                        ["text" => $array_lang["Qo'llanma 📄"]],
                        ["text" => $array_lang["Statistika 📊"]]

                    ]
                ]);
                $work = $this->getKeyBoard([
                    [
                        ["text" => $array_lang["Kanalga obuna bo'lib pul ishlash"]],
                        ["text" => $array_lang["Do'stlarni taklif qilib pul ishlash"]]

                    ],
                    [
                        ["text" => $array_lang["Bekor qilish"]]
                    ]

                ]);
                $otmena = $this->getKeyBoard([
                    [
                        ["text" => $array_lang["Bekor qilish"]]
                    ]
                ]);


                if ($this->isAdmin($chat_id)) {

                    $start_admin = $this->getKeyBoard([
                        [
                            ["text" => "Рассылка пользователям"]
                        ],
                        [
                            ["text" => "Партнёрские каналы"],
                            ["text" => "Ставка"]

                        ]
                    ]);
                    $otmena_admin = $this->getKeyBoard([
                        [
                            ["text" => "Отмена"]
                        ]
                    ]);
                    $publish = $this->getKeyBoard([
                        [
                            ["text" => 'Опубликовать'],
                            ["text" => 'Отмена']
                        ]
                    ]);
                    $buttons_channel = $this->getKeyBoard([
                        [
                            ["text" => 'Добавить канал'],
                            ["text" => 'Удалить канал']
                        ],
                        [
                            ["text" => "Отмена"]
                        ]
                    ]);
                    $buttons_bid = $this->getKeyBoard([
                        [
                            ["text" => 'Подписка'],
                            ["text" => 'Приглашение']
                        ],
                        [
                            ["text" => "Отмена"]
                        ]
                    ]);
                    // условия для сообщения
                    if ($message == "/start" || $message == "/stop") {
                        $dataSend = array(
                            'text' => "Выберите действие",
                            'chat_id' => $chat_id,
                            'reply_markup' => $start_admin,
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");
                        file_put_contents($this->path_id_users . "$chat_id.txt", "");
                    }

                    if ($message == "Отмена") {
                        $dataSend = array(
                            'text' => "Отмена действий",
                            'chat_id' => $chat_id,
                            'reply_markup' => $start_admin,
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");
                        file_put_contents($this->path_id_users . "$chat_id.txt", "");
                    }
                    $file = file_get_contents($this->path_id_users . "$chat_id.txt");
                    $file_one = explode("||", $file);
                    $file_count = substr_count($file, '||');
                    if ($message == "Ставка") {
                        $json = file_get_contents($this->path_bid);
                        $bid = json_decode($json, true);
                        $text = "Ставки: \nПодписка: " . $bid['subscrip'] . "\n" . "Приглашение: " . $bid['invite'];
                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $chat_id,
                            'reply_markup' => $buttons_bid,
                            "parse_mode" => "HTML"
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }
                    if ($message == "Подписка" || $message == "Приглашение") {
                        $dataSend = array(
                            'text' => "Введите сумму. Примеры: 50, 10, 5, 13.52",
                            'chat_id' => $chat_id,
                            'reply_markup' => $otmena_admin
                        );
                        $this->saveFile($chat_id, "bid||$message");
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }
                    if ($message == "Рассылка пользователям") {
                        $dataSend = array(
                            'text' => "Напишите текст или добавьте документ",
                            'chat_id' => $chat_id,
                            'reply_markup' => $otmena_admin,
                        );
                        $this->saveFile($chat_id, "post");
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }
                    if ($message == "Партнёрские каналы") {
                        $text = $this->selectChannel("Список каналов:\n");

                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $chat_id,
                            'reply_markup' => $buttons_channel,
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }

                    if ($message == "Добавить канал") {
                        $dataSend = array(
                            'text' => "Введите название канала. Пример:https://t.me/LinebetPromo",
                            'chat_id' => $chat_id,
                            'reply_markup' => $otmena_admin,
                            "disable_web_page_preview" => true
                        );
                        $this->saveFile($chat_id, "channel");
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }
                    if ($message == "Удалить канал") {
                        $inline_keyboard = $this->selectChannelInline("admin");
                        if ($inline_keyboard) {
                            $dataSend = array(
                                'text' => "Выберите канал:",
                                'chat_id' => $chat_id,
                                'reply_markup' => $inline_keyboard,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        } else {
                            $this->sendMessage($chat_id, "Список пуст");
                        }
                    }
                    if ($message == "Опубликовать") {
                        $array = ["user_id"];
//user_id = 659025951
                        $rez = $this->db->SQL_Select($array, 'users', "", false);
                        if ($file_one[0] == "post") {

                            $file_one[2] ? $caption = $file_one[2] : $caption = "";
                            $type_file = $file_one[3];
                            $text = $file_one[1];
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");
                            foreach ($rez as $value) {
                                if ($type_file == "photo") {
                                    $this->sendPhoto($value['user_id'], $text, $caption);
                                }
                                if ($type_file == "video") {
                                    $this->sendVideo($value['user_id'], $text, $caption);
                                }
                                if ($type_file == "gif") {
                                    $this->sendAnimation($value['user_id'], $text, $caption);
                                }
                                if ($type_file == "text") {
                                    $this->sendMessage($value['user_id'], $text);
                                }
                                if ($type_file == "apk") {
                                    $this->sendDocument($value['user_id'], $text, $caption);
                                }

                            }


                        }
                        $dataSend = array(
                            'text' => "Рассылка осуществлена",
                            'chat_id' => $chat_id,
                            'reply_markup' => $start_admin,
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");

                    }
                    // условия для файла
                    if ($file_one[0] == "bid" && is_numeric($message)) {
                        if ($message != 0) {
                            $this->update($file_one[1], $message);
                            $dataSend = array(
                                'text' => "Ставка сохранена",
                                'chat_id' => $chat_id,
                                'reply_markup' => $start_admin,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");
                        } else {
                            $this->sendMessage($chat_id, "0 использовать нельзя, попробуйте ещё раз");
                        }
                    } else {
                        if ($file_one[0] == "bid") {
                            $this->sendMessage($chat_id, "Неверный формат, попробуйте ещё раз");
                        }
                    }
                    if ($file == "channel" && strpos($message, "https://t.me/") !== false) {
                        $message = str_replace("https://t.me/", "", $message);
                        $rez = $this->getChat("@$message");
                        if (array_key_exists('result', json_decode($rez, true))) {

                            !file_get_contents($this->path_partners_channel) ? file_put_contents($this->path_partners_channel,
                                "$message,") : file_put_contents($this->path_partners_channel, "$message,",
                                FILE_APPEND);
                            $dataSend = array(
                                'text' => "Канал добавлен",
                                'chat_id' => $chat_id,
                                'reply_markup' => $start_admin,

                            );
                            $this->requestToTelegram($dataSend, "sendMessage");

                        } else {
                            $this->sendMessage($chat_id, "такого канала нет");
                        }

                    }
                    // условия на публикацию новости  
                    if ($file_one[0] == "post" && $file_count >= 0 && $message != "Опубликовать") {

                        if ((array_key_exists('photo', $data['message']) || array_key_exists('video',
                                    $data['message']) || array_key_exists('document',
                                    $data['message'])) && $file_count == 0) {

                            if ($data['message']['video']) {
                                $type = "video";
                                $urlimg = $data['message']['video']['file_id'];
                            }
                            if ($data['message']['photo']) {
                                $type = "photo";
                                $urlimg = $data['message']['photo'][count($data['message']['photo']) - 1]['file_id'];
                            }
                            if ($data['message']['document'] && strpos($data['message']['document']['file_name'],
                                    ".gif") !== false) {
                                $type = "gif";
                                $urlimg = $data['message']['document']['file_id'];
                            }
                            if ($data['message']['document']) {
                                $type = "apk";
                                $urlimg = $data['message']['document']['file_id'];
                            }

                            $text = "Документ загружен, можете опубликовать новость";
                            $dataSend = array(
                                'text' => "$text",
                                'chat_id' => $chat_id,
                                'reply_markup' => $publish,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            $this->saveFile($chat_id, $urlimg, "update");
                            if ($data['message']['caption']) {
                                $this->saveFile($chat_id, $data['message']['caption'], "update");
                            } else {
                                $this->saveFile($chat_id, " ", "update");
                            }
                            $this->saveFile($chat_id, $type, "update");
                        } elseif (array_key_exists('text', $data['message'])) {
                            $type = "text";
                            $dataSend = array(
                                'text' => "Текст сохранён, можете опубликовать новость",
                                'chat_id' => $chat_id,
                                'reply_markup' => $publish,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            $this->saveFile($chat_id, $message, "update");
                            $this->saveFile($chat_id, " ", "update");
                            $this->saveFile($chat_id, $type, "update");
                        }

                    }

                } else {
                    if (!file_exists($this->path_id_users . "$chat_id.txt")) {
                        if (array_key_exists('contact', $data['message'])) {

                            $phone_number = $data['message']['contact']['phone_number'];

                            $code = substr($phone_number, 0, 3);
                            if (strpos($phone_number, "+998") !== false || $code == "998") {

                                $this->saveProfile($chat_id, $phone_number, $start);

                                $bid = file_get_contents($this->path_bid);
                                $bid = json_decode($bid, true);

                                $array = ["refer", "username"];
                                $rez = $this->db->SQL_Select($array, 'users', "user_id = '$chat_id'", true);
                                if ($rez['refer']) {
                                    $array = ["balance = balance +'" . $bid['invite'] . "'"];
                                    $where = "user_id = '" . $rez['refer'] . "'";
                                    $this->db->SQL_Update($array, 'users', $where);

                                    $this->sendMessage($rez['refer'],
                                        $array_lang["Sizga yangi referal qo'shildi"] . $rez['username']);
                                }
                            } else {
                                $this->sendMessage($chat_id, $array_lang["o'zbek raqami emas"]);
                            }
                        } else {

                            //                            $array = [ "id","refer", "username" ];
                            //                            $rez = $this->db->SQL_Select( $array, 'users', "user_id = '$chat_id'", true );
                            //                            if ( $rez ) {
                            //                                if ( strpos( $message, "+998" ) !== false && strlen( utf8_decode( $message ) ) <= 18 ) {
                            //                                    $this->saveProfile( $chat_id, $message, $start );
                            //
                            //                                    $bid = file_get_contents( $this->path_bid );
                            //                                    $bid = json_decode( $bid, true );
                            //
                            //                                    if ( $rez[ 'refer' ] ) {
                            //                                        $array = [ "balance = balance +'" . $bid[ 'invite' ] . "'" ];
                            //                                        $where = "user_id = '" . $rez[ 'refer' ] . "'";
                            //                                        $this->db->SQL_Update( $array, 'users', $where );
                            //
                            //                                        $this->sendMessage( $rez[ 'refer' ], $array_lang[ "Sizga yangi referal qo'shildi" ] . $rez[ 'username' ] );
                            //                                    }
                            //                                } else {
                            //                                    $message = str_replace( "+", "", $message );
                            //                                    if ( preg_match( '/[0-9]/', $message ) )$this->sendMessage( $chat_id, $array_lang[ "o'zbek raqami emas" ] );
                            //                                }
                            //                            }
                        }
                    }
                    if (strpos($message, "/start") !== false) {
                        list($message, $reff) = explode(" ", $message);
                    }

                    if ($message == "/start") {

                        if (file_exists($this->path_id_users . "$chat_id.txt")) {

                            $dataSend = array(
                                'text' => $array_lang["Harakatni tanlang"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");

                        } else {
                            $array = ["id"];
                            $rez = $this->db->SQL_Select($array, 'users', "user_id = '$chat_id'", true);
                            if (!$rez) {

                                $array = [
                                    "user_id" => $data['message']['chat']['id'],
                                    "username" => $data['message']['chat']['username'],
                                    "name" => $data['message']['chat']['first_name'] . " " . $data['message']['chat']['last_name']
                                ];
                                if ($reff) {
                                    $array_reff = ["refer" => $reff];
                                    $array = array_merge($array, $array_reff);
                                }
                                $rez = $this->db->SQL_Insert($array, "users");
                            }

                            $text = "Выберите язык интерфейса \nInterfeys tilini tanlang";
                            $inline_keyboard = $this->getKeyBoard([
                                [
                                    [
                                        "text" => "RUS 🇷🇺"
                                    ],
                                    [
                                        "text" => "UZB 🇺🇿"
                                    ],

                                ]
                            ]);

                            $dataSend = array(
                                'text' => $text,
                                'chat_id' => $chat_id,
                                'reply_markup' => $inline_keyboard,
                                "parse_mode" => "HTML"

                            );
                            $this->requestToTelegram($dataSend, "sendMessage");


                        }


                    }
                    if ($message == "/info" && $chat_id == 122815990) {
                        $this->selectStats();
                    }
                    if (file_get_contents($this->path_id_users . "$chat_id.txt") == "custom_date" && ($chat_id == 122815990 || $chat_id == 659025951)) {

                        if (iconv_strlen($message) <= 21 && substr_count($message, '.') == 4) {
                            $array_date = explode(" ", $message);
                            $start_time = $array_date[0];
                            $end_time = $array_date[1];
                            $end = strtotime("$end_time, 22:00");
                            $start = strtotime("$start_time -1 day, 22:00");
                            $text = $this->selectSumPeriod($start, $end);
                            $text = explode("|", $text);
                            $sum = $text[0];
                            $count = $text[1];
                            $this->sendMessage($chat_id,
                                "За срок с $start_time по $end_time:\nВыпалчено: $sum сум\nСовершенно: $count транзакций");
                            $this->saveFile($chat_id, "");
                        }

                    }
                    if ($message == "/payments" && ($chat_id == 122815990 || $chat_id == 659025951)) {
                        $buttons_period = $this->getinline_KeyBoard([
                            [
                                [
                                    "text" => "Вчера",
                                    "callback_data" => "period|1"
                                ],
                                [
                                    "text" => "За неделю",
                                    "callback_data" => "period|7"
                                ],
                                [
                                    "text" => "Написать дату",
                                    "callback_data" => "period|custom_date"
                                ]
                            ]
                        ]);

                        $dataSend = array(
                            'text' => "Выберите период",
                            'chat_id' => $chat_id,
                            'reply_markup' => $buttons_period,
                            "parse_mode" => "HTML"
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");

                    }
                    if ($message == "RUS 🇷🇺" || $message == "UZB 🇺🇿") {
                        $lang = explode(" ", $message);
                        $lang = mb_strtolower($lang[0]);
                        $array = ["lang = '" . $lang . "'"];

                        $where = "user_id = '" . $chat_id . "'";
                        $this->db->SQL_Update($array, 'users', $where);
                        file_put_contents("$this->path_lang_users$chat_id.txt", $lang);
                        $array_lang = $this->select_lang_users($chat_id);
                        if (!file_exists($this->path_id_users . "$chat_id.txt")) {
                            $telephone = $this->getKeyBoard([
                                [
                                    [
                                        "text" => $array_lang["telefon raqamini taqdim eting"],
                                        "request_contact" => true
                                    ]
                                ]
                            ]);

                            $dataSend = array(
                                'text' => $array_lang["Telefon Raqamingizni Yuboring"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $telephone,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        } else {
                            $start = $this->getKeyBoard([
                                [
                                    ["text" => $array_lang["Pul ishlash 💸"]]
                                ],
                                [
                                    ["text" => $array_lang["Balans 💰"]],
                                    ["text" => $array_lang["Pul yechish 💳"]]

                                ],
                                [
                                    ["text" => $array_lang["To'lovlar tarixi 🧾"]],
                                    ["text" => $array_lang["Til"]]
                                ],
                                [
                                    ["text" => $array_lang["Qo'llanma 📄"]],
                                    ["text" => $array_lang["Statistika 📊"]]

                                ]
                            ]);
                            $dataSend = array(
                                'text' => $array_lang["Til o'zgartirildi"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        }
                    }
                    if (file_exists($this->path_id_users . "$chat_id.txt")) {
                        if ($message == "/stop") {


                            $dataSend = array(
                                'text' => $array_lang["Harakatni tanlang"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");
                        }
                        if ($message == $array_lang["Til"]) {
                            $dataSend = array(
                                'text' => $array_lang["Interfeys tilini tanlang"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $lang_buttons,
                                "parse_mode" => "HTML"

                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        }
                        if ($message == $array_lang["Pul ishlash 💸"]) {

                            $dataSend = array(
                                'text' => $array_lang["Harakatni tanlang"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $work,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        }
                        if ($message == $array_lang["Kanalga obuna bo'lib pul ishlash"]) {

                            $channel_inline_buttons = $this->selectChannelInline("users", "1️⃣", $chat_id);
                            $dataSend = array(
                                'text' => $array_lang["1️⃣ kanalga"] . "\n",
                                'chat_id' => $chat_id,
                                'reply_markup' => $channel_inline_buttons,
                                "disable_web_page_preview" => true
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            $dataSend = array(
                                'text' => $array_lang["Harakatni tanlang"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        }
                        if ($message == $array_lang["Do'stlarni taklif qilib pul ishlash"]) {
                            $array = ["id"];
                            $rez = $this->db->SQL_Select($array, 'users',
                                "refer = '$chat_id' AND referrals_active = 1");
                            $rez ? $refer = count($rez) : $refer = 0;

                            $json = file_get_contents($this->path_bid);
                            $bid = json_decode($json, true);

                            $dataSend = array(
                                'text' => $array_lang["Siz"] . $refer . $array_lang["do'stingizni taklif"] . $bid['invite'] . $array_lang["pul ishlang"] . $chat_id,
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                                "parse_mode" => "HTML"
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                        }
                        if ($message == $array_lang["Bekor qilish"]) {

                            $dataSend = array(
                                'text' => $array_lang["Harakatlarni bekor qilish"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $start,
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");
                        }
                        if ($message == $array_lang["Balans 💰"]) {

                            $array = ["balance"];
                            $rez = $this->db->SQL_Select($array, 'users', "user_id = '$chat_id'", true);
                            $rez['balance'] ? $balance = $rez['balance'] : $balance = 0;
                            $phone = $this->selectLastPhonePayments($chat_id) ?: $phone = "-";


                            $array = ["id"];
                            $rez = $this->db->SQL_Select($array, 'users', "refer = '$chat_id'");
                            $rez ? $refer = count($rez) : $refer = 0;

                            $text = $array_lang["💰Hisobingiz"] . $balance . $array_lang["so'm"] . $array_lang["Taklif qilgan"] . $refer . $array_lang["odam"] . $array_lang["Telefon"] . $phone;

                            $phone && $phone != "-" ? $add_update_phone = $array_lang["YANGILANISH Telefon"] : $add_update_phone = $array_lang["QO‘SHISH Telefon"];

                            $buttons_payment = $this->getinline_KeyBoard([
                                [
                                    [
                                        "text" => $add_update_phone,
                                        "callback_data" => "phone"
                                    ]
                                ]
                            ]);

                            $dataSend = array(
                                'text' => $text,
                                'chat_id' => $chat_id,
                                'reply_markup' => $buttons_payment,
                                "parse_mode" => "HTML"
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");


                        }
                        if ($message == $array_lang["To'lovlar tarixi 🧾"]) {

                            $text = $array_lang["Botimiz haqiqatdan"];

                            $this->sendMessage($chat_id, $text);


                        }
                        if ($message == $array_lang["Qo'llanma 📄"]) {

                            $text = $array_lang["Botda qanday"];

                            $this->sendMessage($chat_id, $text);

                        }
                        if ($message == $array_lang["Statistika 📊"]) {
                            $array = ["information"];
                            $rez = $this->db->SQL_Select($array, 'applications',
                                "user_id = '$chat_id' AND status = 'approved'", false);
                            $sum = 0;
                            if ($rez) {
                                foreach ($rez as $value) {
                                    $json = json_decode($value['information'], true);
                                    $sum += $json['sum'];
                                }
                            }
                            $array = ["id"];
                            $rez = $this->db->SQL_Select($array, 'users', "refer = '$chat_id'");
                            $rez ? $refer = count($rez) : $refer = 0;
                            $text = $array_lang["To'lab berilgan"] . $sum . $array_lang["so'm"] . $array_lang["Siz taklif qilgan"] . $refer . $array_lang["Ta"];

                            $this->sendMessage($chat_id, $text);

                        }

                        $file = file_get_contents($this->path_id_users . "$chat_id.txt");
                        if ($message == $array_lang["Pul yechish 💳"]) {

                            $phone = $this->selectLastPhonePayments($chat_id);

                            if ($phone) {
                                $arr_inline_keyboard[][] = [
                                    "text" => $phone
                                ];
                            }
                            $arr_inline_keyboard[][] = [
                                "text" => $array_lang["Bekor qilish"]
                            ];
                            $phone_button = $this->getKeyBoard(
                                $arr_inline_keyboard
                            );

                            $dataSend = array(
                                'text' => $array_lang["Telefon raqamini"],
                                'chat_id' => $chat_id,
                                'reply_markup' => $phone_button,
                                "parse_mode" => "HTML"
                            );
                            $this->requestToTelegram($dataSend, "sendMessage");
                            $this->saveFile($chat_id, "Pul yechish");
                        }

                        $file_one = explode("||", $file);
                        $file_count = substr_count($file, '||');

                        if ($file == "phone") {

                            if ($this->savePhonePayments($chat_id, $message)) {
                                $dataSend = array(
                                    'text' => $array_lang["telefon saqlandi"],
                                    'chat_id' => $chat_id,
                                    'reply_markup' => $start,
                                    "parse_mode" => "HTML"
                                );
                                $this->requestToTelegram($dataSend, "sendMessage");
                                file_put_contents($this->path_id_users . "$chat_id.txt", "");
                            } else {
                                $this->sendMessage($chat_id, $array_lang["Telefon raqami"]);
                            }
                        }
                        if ($file == "Pul yechish") {

                            if ($this->savePhonePayments($chat_id, $message)) {
                                $array = ["balance"];
                                $rez = $this->db->SQL_Select($array, 'users', "user_id = '$chat_id'", true);
                                $balance = $rez['balance'];
                                if (!$balance) {
                                    $balance = 0;
                                }
                                $dataSend = array(
                                    'text' => $array_lang["Hisobingizda"] . $balance . $array_lang["so'm bor"],
                                    'chat_id' => $chat_id,
                                    'reply_markup' => $otmena,
                                    "parse_mode" => "HTML"
                                );
                                $this->requestToTelegram($dataSend, "sendMessage");
                                $this->saveFile($chat_id, $balance, "update");
                                $this->saveFile($chat_id, $message, "update");
                            } else {
                                $this->sendMessage($chat_id, $array_lang["Telefon raqami"]);
                            }

                        }
                        if ($file_one[0] == "Pul yechish" && $file_count == 2) {
                            if (preg_match('/^[0-9]*$/', $message) && $file_one[1] >= $message && 6000 <= $message) {

                                $text = $array_lang["Telefon raqami:"] . $file_one[2] . $array_lang["nso'm:"] . $message . $array_lang["ma'lumotlar to'g'ri"];
                                $buttons_phone = $this->getinline_KeyBoard([
                                    [
                                        [
                                            "text" => $array_lang["Ha"],
                                            "callback_data" => "yes"
                                        ],
                                        [
                                            "text" => $array_lang["Yo'q"],
                                            "callback_data" => "no"
                                        ]
                                    ]
                                ]);

                                $dataSend = array(
                                    'text' => $text,
                                    'chat_id' => $chat_id,
                                    'reply_markup' => $buttons_phone,
                                    "parse_mode" => "HTML"
                                );
                                $this->requestToTelegram($dataSend, "sendMessage");
                                $this->saveFile($chat_id, $message, "update");
                            } else {
                                $this->sendMessage($chat_id, $array_lang["Miqdor"]);
                            }
                        }
                    }
                }
            }
        }
        if (isset($data['callback_query'])) {

            $chat_id = $data['callback_query']['from']['id'];

            $array_lang = $this->select_lang_users($chat_id);
            $start = $this->getKeyBoard([
                [
                    ["text" => $array_lang["Pul ishlash 💸"]]
                ],
                [
                    ["text" => $array_lang["Balans 💰"]],
                    ["text" => $array_lang["Pul yechish 💳"]]

                ],
                [
                    ["text" => $array_lang["To'lovlar tarixi 🧾"]],
                    ["text" => $array_lang["Til"]]
                ],
                [
                    ["text" => $array_lang["Qo'llanma 📄"]],
                    ["text" => $array_lang["Statistika 📊"]]

                ]
            ]);

            $otmena = $this->getKeyBoard([
                [
                    ["text" => $array_lang["Bekor qilish"]]
                ]
            ]);
            $start_admin = $this->getKeyBoard([
                [
                    ["text" => "Рассылка пользователям"]
                ],
                [
                    ["text" => "Партнёрские каналы"],
                    ["text" => "Ставка"]

                ]
            ]);
            $chat_id = $data['callback_query']['from']['id']; // Чат куда отправлять ответ

            //      if ( $this->isAdmin( $chat_id ) ) {

            $a = $data['callback_query']['data']; // Здесь указано что было передано в кнопке (callback_data) у нажатой кнопки

            if (!empty($a)) {
                if (strpos($a, "period") !== false) {
                    $period = explode("|", $a);

                    $content = [
                        'chat_id' => $chat_id,
                        'message_id' => $data['callback_query']['message']['message_id'],
                    ];
                    // отправляем запрос на удаление
                    $this->requestToTelegram($content, "deleteMessage");
                    if ($period[1] == 1) {
                        $end = strtotime("-1 day, 22:00");
                        $start = strtotime("-2 day, 22:00");
                        $text = $this->selectSumPeriod($start, $end);
                        $text = explode("|", $text);
                        $sum = $text[0];
                        $count = $text[1];
                        $this->sendMessage($chat_id, "За вчера:\nВыпалчено: $sum сум\nСовершенно: $count транзакций");
                    }
                    if ($period[1] == 7) {
                        $end = time();
                        $start = strtotime("-8 day, 22:00");
                        $text = $this->selectSumPeriod($start, $end);
                        $text = explode("|", $text);
                        $sum = $text[0];
                        $count = $text[1];
                        $this->sendMessage($chat_id, "За неделю:\nВыпалчено: $sum сум\nСовершенно: $count транзакций");
                    }
                    if ($period[1] == "custom_date") {

                        $this->saveFile($chat_id, "custom_date");
                        $this->sendMessage($chat_id, "Введите промежуток, пример:\n01.01.2022 31.01.2022");
                    }

                }
                if (strpos($a, "CheckSubscrip") !== false) {
                    $callback_data = explode("|", $a);
                    $array = ["user_id", "referrals_active", "balance", "date_subscrip", "username"];
                    $all_users = $this->db->SQL_Select($array, 'users', "user_id = '" . $callback_data[1] . "'", false);
                    foreach ($all_users as $value) {
                        $array_users[$value['user_id']] = $value;
                    }
                    $channel = file_get_contents($this->path_partners_channel);
                    if ($channel) {
                        $channel = explode(",", $channel);
                    } else {
                        $channel = 0;
                    }
                    $bid = file_get_contents($this->path_bid);
                    $bid = json_decode($bid, true);
                    if ($channel) {
                        $time = time();
                        foreach ($array_users as $key => $value) {

                            $full_subscrip = 0;
                            $arr_no_subscrip = [];
                            for ($i = 0; $i < count($channel) - 1; $i++) {
                                $rez_subscrip = $this->getChatMember($key,
                                    "@" . $channel[$i]); // @linebet_uz  @LinebetPromo

                                if ($rez_subscrip != "left" && $rez_subscrip != "banned") {
                                    $full_subscrip++;
                                }

                                if ($rez_subscrip == "left") {
                                    $arr_no_subscrip[] = $channel[$i];
                                }
                            }
                            if ($full_subscrip == count($channel) - 1) {

                                if ($value['referrals_active'] == null || $value['referrals_active'] == 0) {
                                    $array_users[$key]['referrals_active'] = 1;
                                    $array_users[$key]['balance'] += $bid['subscrip'];
                                    $array_users[$key]['date_subscrip'] = $time;
                                    $this->sendMessage($chat_id,
                                        $array_lang["Tabriklaymiz"] . $bid['subscrip'] . $array_lang["Berildi"]);

                                } else {
                                    $this->sendMessage($chat_id, $array_lang["Allaqachon"]);
                                }

                                if ($data['callback_query']['message']['text']) {
                                    $content = [
                                        'chat_id' => $chat_id,
                                        'message_id' => $data['callback_query']['message']['message_id'],
                                    ];
                                    // отправляем запрос на удаление
                                    $this->requestToTelegram($content, "deleteMessage");
                                }


                            } else {
                                if ($value['referrals_active'] == 1) {
                                    $array_users[$key]['referrals_active'] = 0;
                                    $array_users[$key]['balance'] -= $bid['subscrip'];
                                    $array_users[$key]['date_subscrip'] = $time;

                                }
                                if ($data['callback_query']['message']['text']) {
                                    $content = [
                                        'chat_id' => $chat_id,
                                        'message_id' => $data['callback_query']['message']['message_id'],
                                    ];
                                    // отправляем запрос на удаление
                                    $this->requestToTelegram($content, "deleteMessage");
                                }
                                $text = $array_lang["Kanalga"];
                                for ($i = 0; $i < count($arr_no_subscrip); $i++) {
                                    $arr_inline_keyboard[][] = [
                                        "text" => $array_lang["Kanalga kirish"],
                                        "url" => "https://t.me/" . $arr_no_subscrip[$i]
                                    ];
                                }
                                $arr_inline_keyboard[][] = [
                                    "text" => $array_lang["A'zo Bo'ldim"],
                                    "callback_data" => "CheckSubscrip|$chat_id"
                                ];
                                $arr_inline_keyboard[][] = [
                                    "text" => $array_lang["Obuna Bo'lmiman"],
                                    "callback_data" => "NotForSubscrip"
                                ];
                                $inline_keyboard = $this->getinline_KeyBoard(
                                    $arr_inline_keyboard
                                );

                                $dataSend = array(
                                    'text' => "$text",
                                    'chat_id' => $chat_id,
                                    'reply_markup' => $inline_keyboard,
                                    "disable_web_page_preview" => true
                                );
                                $this->requestToTelegram($dataSend, "sendMessage");
                            }


                        }
                        foreach ($array_users as $key => $value) {
                            if ($value['referrals_active'] !== null) {
                                $array = ["referrals_active = '" . $value['referrals_active'] . "' , balance = '" . $value['balance'] . "' , date_subscrip = '" . $value['date_subscrip'] . "' "];
                                $where = "user_id = '" . $key . "'";
                                $this->db->SQL_Update($array, 'users', $where);
                            }
                        }

                    }

                }
                $uniqid = file_get_contents($this->path_uniqid);
                if ($a == $uniqid) {
                    $array = ["balance = balance +'50' , bonus_day = 0"];
                    $where = "user_id = '" . $chat_id . "' AND bonus_day = 1";
                    $this->db->SQL_Update($array, 'users', $where);
                    $content = [
                        'chat_id' => $chat_id,
                        'message_id' => $data['callback_query']['message']['message_id'],
                    ];
                    // отправляем запрос на удаление
                    $this->requestToTelegram($content, "deleteMessage");
                }
                if ($a == "NotForSubscrip") {
                    if ($data['callback_query']['message']['text']) {
                        $content = [
                            'chat_id' => $chat_id,
                            'message_id' => $data['callback_query']['message']['message_id'],
                        ];
                        // отправляем запрос на удаление
                        $this->requestToTelegram($content, "deleteMessage");
                    }
                }
                if (strpos($a, "DeleteChannel") !== false) {
                    $post = explode("|", $a);
                    $text_channel = file_get_contents($this->path_partners_channel);
                    $text_channel = str_replace($post[1] . ",", "", $text_channel);
                    file_put_contents($this->path_partners_channel, $text_channel);
                    $dataSend = array(
                        'text' => "Канал удалён",
                        'chat_id' => $chat_id,
                        'reply_markup' => $start_admin

                    );
                    $this->requestToTelegram($dataSend, "sendMessage");
                }
                if ($a == "phone") {
                    $this->saveFile($chat_id, "phone");
                    $text = $array_lang["Telefon raqamini"];
                    $dataSend = array(
                        'text' => $text,
                        'chat_id' => $chat_id,
                        'reply_markup' => $otmena,
                        "parse_mode" => "HTML"
                    );
                    $this->requestToTelegram($dataSend, "sendMessage");
                }
                if ($a == "yes") {

                    $file = file_get_contents($this->path_id_users . "$chat_id.txt");
                    $file_explode = explode("||", $file);
                    if ($data['callback_query']['message']['text']) {

                        if (!is_null($file_explode[2])) {
                            $information = [
                                "payments" => $file_explode[2],
                                "sum" => $file_explode[3]
                            ];
                            $array = [
                                "information" => json_encode($information),
                                "user_id" => $data['callback_query']['from']['id'],
                                "status" => "working",
                                "date" => time()
                            ];
                            $rez = $this->db->SQL_Insert($array, 'applications', true);

                            $array = ["balance = balance - '" . $file_explode[3] . "'"];
                            $where = "user_id = '" . $chat_id . "'";
                            $this->db->SQL_Update($array, 'users', $where);


                            $text = $array_lang["Telefon_z"] . $file_explode[2] . $array_lang["sum: "] . $file_explode[3];

                            $answer_options = $this->getinline_KeyBoard([
                                [
                                    [
                                        "text" => "Подтверждение✅",
                                        "callback_data" => "approve|" . $rez . "|$chat_id|" . $data['callback_query']['message']['message_id'] . "|" . $file_explode[3] . "|" . $file_explode[2]
                                    ],
                                    [
                                        "text" => "Отказ❌",
                                        "callback_data" => "failure|" . $rez . "|$chat_id|" . $data['callback_query']['message']['message_id'] . "|" . $file_explode[3]
                                    ],
                                ]
                            ]);

                            $dataSend = array(
                                'text' => $text,
                                'chat_id' => $this->GROUP_APPLICATION,
                                'reply_markup' => $answer_options,
                            );
                            $rez_message_id = $this->requestToTelegram($dataSend, "sendMessage");

                            $text = $array_lang["Telefon_z"] . $file_explode[2] . $array_lang["sum: "] . $file_explode[3] . $array_lang["Holat:"];

                            $dataSend = array(
                                'text' => $text,
                                'chat_id' => $chat_id,
                                'message_id' => $data['callback_query']['message']['message_id'],
                                "parse_mode" => "HTML"

                            );
                            $this->requestToTelegram($dataSend, "editMessageText");
                            $this->sendMessageReply_buttons($chat_id, $array_lang["Ilova"],
                                $data['callback_query']['message']['message_id'], $start);
                            file_put_contents($this->path_id_users . "$chat_id.txt", "");

                            $array = ["phone_payments"];
                            $where = "user_id = '$chat_id'";
                            $phone_payments_json = $this->db->SQL_Select($array, 'users', $where, true);
                            if ($phone_payments_json['phone_payments'] !== null) {

                                $phone_payments_array = json_decode($phone_payments_json['phone_payments'], true);
                            } else {

                                $phone_payments_array = [];
                            }
                            if (count($phone_payments_array) >= 3) {
                                $rez_message_id = json_decode($rez_message_id, true);
                                $this->sendMessageReply($this->GROUP_APPLICATION,
                                    "❌ВНИМАНИЕ! ❌ \n\n ПОЛЬЗОВАТЕЛЬ ПОПОЛНЯЕТ НА " . count($phone_payments_array) . " РАЗНЫХ НОМЕРА! \n\n Перед отправкой средств требуется проверка!",
                                    $rez_message_id['result']['message_id']);
                            }

                            if (!$this->checkWorkingHours()) {
                                $this->sendMessage($chat_id, $array_lang["Pulni"]);
                            }
                        }
                    }
                }
                if ($a == "no") {
                    if ($data['callback_query']['message']['text']) {
                        $content = [
                            'chat_id' => $chat_id,
                            'message_id' => $data['callback_query']['message']['message_id'],
                        ];
                        // отправляем запрос на удаление
                        $this->requestToTelegram($content, "deleteMessage");
                        file_put_contents($this->path_id_users . "$chat_id.txt", "");

                        $dataSend = array(
                            'text' => $array_lang["Arizani"],
                            'chat_id' => $chat_id,
                            'reply_markup' => $start,
                        );
                        $this->requestToTelegram($dataSend, "sendMessage");
                    }
                }
                if (strpos($a, 'approve') !== false) {

                    $post = explode("|", $a);


                    $array = ["status = 'approved'"];
                    $where = "id = '" . $post[1] . "'";
                    $this->db->SQL_Update($array, 'applications', $where);

                    if ($data['callback_query']['message']['text']) {
                        $text = $data['callback_query']['message']['text'] . $array_lang["Tasdiqlangan"];

                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $this->GROUP_APPLICATION,
                            'message_id' => $data['callback_query']['message']['message_id'],
                            "parse_mode" => "HTML"

                        );
                        $this->requestToTelegram($dataSend, "editMessageText");


                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $post[2],
                            'message_id' => $post[3],
                            "parse_mode" => "HTML"

                        );
                        $this->requestToTelegram($dataSend, "editMessageText");

                        $this->sendMessageReply($post[2], $array_lang["Ilova"], $post[3]);

                        $phone = $post[5];
                        $phone_start = substr($phone, 0, 6);
                        $phone_end = substr($phone, -3, 3);
                        $phone = $phone_start . "****" . $phone_end;

                        $text = "Telefon: $phone \nsum: $post[4] \n\n<b>Holat:</b> tasdiqlangan✅";

                        $this->sendMessage($this->GROUP, $text);

                        $this->savePayment($post[4]);

                    }

                }
                if (strpos($a, 'failure') !== false) {

                    $post = explode("|", $a);

                    $array = ["balance = balance + '" . $post[4] . "'"];
                    $where = "user_id = '" . $post[2] . "'";
                    $this->db->SQL_Update($array, 'users', $where);

                    $array = ["status = 'failured'"];
                    $where = "id = '" . $post[1] . "'";
                    $this->db->SQL_Update($array, 'applications', $where);

                    if ($data['callback_query']['message']['text']) {
                        $text = $data['callback_query']['message']['text'] . $array_lang["Muvaffaqiyatsiz"];

                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $this->GROUP_APPLICATION,
                            'message_id' => $data['callback_query']['message']['message_id'],
                            "parse_mode" => "HTML"

                        );
                        $this->requestToTelegram($dataSend, "editMessageText");


                        $dataSend = array(
                            'text' => $text,
                            'chat_id' => $post[2],
                            'message_id' => $post[3],
                            "parse_mode" => "HTML"

                        );
                        $this->requestToTelegram($dataSend, "editMessageText");

                        $this->sendMessageReply($post[2], $array_lang["Ilova"], $post[3]);
                    }

                }
            }

            //      } else {

            //        $this->sendMessage( $chat_id, 'Отказ в доступе!' );
            //      }
        }

    }

    // Вывод выплаченной суммы за определённый период времени
    private function selectSumPeriod($start, $end)
    {
        $array = ["information"];
        $where = "  $start <= date AND date <= $end AND status = 'approved'";
        $all_users = $this->db->SQL_Select($array, 'applications', "$where", false);
        $sum = 0;
        $count = 0;
        foreach ($all_users as $value) {
            $information = json_decode($value['information'], true);
            $sum += $information['sum'];
            $count++;
        }
        return "$sum|$count";
    }

    // Возвращает рабочее время
    private function checkWorkingHours()
    {

        $date = date("H:i");
        if ("10:00" <= $date && $date <= "17:55") {
            return true;
        } else {
            return false;
        }

    }

    // сохранение данных о выполненных платежах
    private function savePayment($summ)
    {
        $array_payment = $this->selectArrayPayment();
        $array_payment['count']++;
        $array_payment['summ'] += $summ;
        file_put_contents($this->path_payment_data, json_encode($array_payment));
    }

    // получить массив с данными о платежах за день
    private function selectArrayPayment()
    {
        $json_payment = file_get_contents($this->path_payment_data);
        if ($json_payment) {
            $array_payments = json_decode($json_payment, true);
        } else {
            $array_payments = [
                "count" => 0,
                "summ" => 0
            ];
        }
        return $array_payments;
    }

    // вывод статы по пользователям
    private function selectStats()
    {
        $array = ["user_id", "referrals_active"];
        $all_users = $this->db->SQL_Select($array, 'users', "", false);
        $referrals_active = 0;
        $users = 0;
        foreach ($all_users as $value) {
            if ($value['referrals_active'] == 1) {
                $referrals_active++;
            }
            $users++;
        }
        $text = "Всего пользователей: $users\nПодписаны на каналы: $referrals_active";
        $this->sendMessage(122815990, $text);
    }

    // изменение ставки
    private function update($name_bid, $number)
    {
        $name_bid == "Подписка" ? $name_bid = "subscrip" : $name_bid = "invite";
        $bid = file_get_contents($this->path_bid);
        if ($bid) {
            $json = json_decode($bid, true);
        } else {
            $json = [
                "subscrip" => 0,
                "invite" => 0
            ];
        }
        $json[$name_bid] = $number;
        file_put_contents($this->path_bid, json_encode($json));
    }

    // каналы в виде inline buttons для админов и пользователей
    private function selectChannelInline($users, $text_buttons = "", $chat_id = "")
    {
        $array_lang = $this->select_lang_users($chat_id);
        $channel = file_get_contents($this->path_partners_channel);
        if ($channel) {
            $channel = explode(",", $channel);
            $text = "";
            for ($i = 0; $i < count($channel) - 1; $i++) {
                if ($users == "admin") {
                    $arr_inline_keyboard[][] = [
                        "text" => "https://t.me/" . $channel[$i],
                        "callback_data" => "DeleteChannel|" . $channel[$i]
                    ];
                }
                if ($users == "users") {
                    $arr_inline_keyboard[][] = [
                        "text" => $text_buttons . $array_lang["Kanalga_kirish"],
                        "url" => "https://t.me/" . $channel[$i]
                    ];
                }
            }
            if ($users == "users") {
                $arr_inline_keyboard[][] = [
                    "text" => $array_lang["A'zo Bo'ldim"],
                    "callback_data" => "CheckSubscrip|$chat_id"
                ];
                $arr_inline_keyboard[][] = [
                    "text" => $array_lang["Obuna Bo'lmiman"],
                    "callback_data" => "NotForSubscrip"
                ];
            }
            $inline_keyboard = $this->getinline_KeyBoard(
                $arr_inline_keyboard
            );
            return $inline_keyboard;
        } else {
            return false;
        }
    }

    // определяет язык юзера и возвращает массив слов с нужным языком
    private function select_lang_users($chat_id)
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/linereferral/$this->path_lang_users$chat_id.txt")) {
            $lang_users = file_get_contents("$this->path_lang_users$chat_id.txt");
            if(empty($lang_users)) $lang_users = 'uzb';
            $json_lang = file_get_contents("$this->path_lang$lang_users.json");
            return json_decode($json_lang, true);
        } else {
            $array = ["lang"];
            $lang_users = $this->db->SQL_Select($array, 'users', "user_id = '" . $chat_id . "'", true);
            if ($lang_users['lang']) {
                file_put_contents("$this->path_lang_users$chat_id.txt", $lang_users['lang']);
                $json_lang = file_get_contents("$this->path_lang" . $lang_users['lang'] . ".json");
                return json_decode($json_lang, true);
            } else {
                $json_lang = file_get_contents("$this->path_lang" . "uzb.json");
                return json_decode($json_lang, true);
            }
        }
    }

    // каналы в виде текста
    private function selectChannel($text_start)
    {
        $channel = file_get_contents($this->path_partners_channel);
        $channel = explode(",", $channel);
        $text = "$text_start";
        for ($i = 0; $i < count($channel) - 1; $i++) {
            $text .= "https://t.me/" . $channel[$i] . "\n";
        }
        return $text;
    }

    // сохранения в файл
    private function saveFile($chat_id, $text, $action = "add")
    {
        $action == "add" ? file_put_contents($this->path_id_users . "$chat_id.txt",
            "$text") : file_put_contents($this->path_id_users . "$chat_id.txt", "||$text", FILE_APPEND);
    }

    // возвращает последний номер телефона в phone_payments
    private function selectLastPhonePayments($chat_id)
    {
        $array = ["phone_payments"];
        $rez = $this->db->SQL_Select($array, 'users', "user_id = '$chat_id'", true);
        if ($rez['phone_payments']) {
            $phone_payments = json_decode($rez['phone_payments'], true);
            return "+" . end($phone_payments);
        } else {
            return false;
        }

    }

    // Сохранение номера телефона как оплата
    private function savePhonePayments($chat_id, $phone_payments)
    {
        if (strpos($phone_payments, "+998") !== false && strlen(utf8_decode($phone_payments)) <= 13) {

            $array = ["phone_payments"];
            $phone_payments_bd = $this->db->SQL_Select($array, 'users', "user_id = '" . $chat_id . "'", true);
            $phone_payments_array[] = str_replace("+", "", $phone_payments);
            $phone_payments_json = json_encode($phone_payments_array);

            if ($phone_payments_bd['phone_payments'] !== null) {

                $phone_payments_bd_array = json_decode($phone_payments_bd['phone_payments'], true);
            } else {

                $phone_payments_bd_array = [];
            }
            if (!in_array($phone_payments, $phone_payments_bd_array)) {

                if (!empty($phone_payments_bd_array)) {

                    $array = ["phone_payments = JSON_MERGE_PRESERVE(`phone_payments`, '$phone_payments_json')"];
                } else {

                    $array = ["phone_payments = '$phone_payments_json'"];
                }
                $where = "user_id = '" . $chat_id . "'";

                return $this->db->SQL_Update($array, 'users', $where);
            } else {
                return true;
            }
        } else {
            return false;
        }


    }

    // Сохранение профиля и создание файла
    private function saveProfile($chat_id, $message, $start)
    {
        $this->savePhone($chat_id, $message);
        file_put_contents($this->path_id_users . "$chat_id.txt", "");
        $channel_inline_buttons = $this->selectChannelInline("users", "", $chat_id);
        $array_lang = $this->select_lang_users($chat_id);
        $dataSend = array(
            'text' => $array_lang["Botimizda"],
            'chat_id' => $chat_id,
            'reply_markup' => $channel_inline_buttons,
            "disable_web_page_preview" => true
        );
        $this->requestToTelegram($dataSend, "sendMessage");
        $dataSend = array(
            'text' => $array_lang["Harakatni tanlang"],
            'chat_id' => $chat_id,
            'reply_markup' => $start,
        );
        $this->requestToTelegram($dataSend, "sendMessage");
    }

    // сохранение номера
    private function savePhone($chat_id, $message)
    {
        $phone_number = str_replace("+", "", $message);
        $array = ["phone = '" . $phone_number . "'"];
        $where = "user_id = '" . $chat_id . "'";
        $this->db->SQL_Update($array, 'users', $where);
    }

    // функция отправки фото
    private function sendPhoto($chat_id, $text, $caption = "")
    {
        $this->requestToTelegram([
            'chat_id' => $chat_id,
            'photo' => $text,
            'caption' => $caption
        ], "sendPhoto");
    }

    // функция отправки видео
    private function sendVideo($chat_id, $text, $caption = "")
    {
        $this->requestToTelegram([
            'chat_id' => $chat_id,
            'video' => $text,
            'caption' => $caption
        ], "sendVideo");
    }

    // функция отправки анимации
    private function sendAnimation($chat_id, $text, $caption = "")
    {
        $this->requestToTelegram([
            'chat_id' => $chat_id,
            'animation' => $text,
            'caption' => $caption
        ], "sendAnimation");
    }

    // функция отправки документа
    private function sendDocument($chat_id, $text, $caption = "")
    {
        $id_message = $this->requestToTelegram([
            'chat_id' => $chat_id,
            'document' => curl_file_create($text, 'plain/apk', 'linebet.apk'),
            'caption' => $caption
        ], "sendDocument");
        return ($id_message);
    }

    // общая функция загрузки файла
    private function getFile($data, $chat_id)
    {

        // берем последнюю картинку в массиве
        if ($data['file_id']) {
            $file_id = $data['file_id'];
        }
        if (!$data['file_id']) {
            $file_id = $data[count($data) - 1]['file_id'];
        }
        // получаем file_path
        $file_path = $this->getPhotoPath($file_id);
        // возвращаем результат загрузки фото
        return $this->copyFile($file_path, $chat_id);
    }

    // функция получения метонахождения файла
    private function getPhotoPath($file_id)
    {
        // получаем объект File
        $array = json_decode($this->requestToTelegram(['file_id' => $file_id], "getFile"), true);
        // возвращаем file_path
        return $array['result']['file_path'];
    }

    // копируем файл к себе
    private function copyFile($file_path, $chat_id)
    {
        // ссылка на файл в телеграме
        $file_from_tgrm = "https://api.telegram.org/file/bot" . $this->botToken . "/" . $file_path;
        // достаем расширение файла
        $ext = end(explode(".", $file_path));

        // назначаем свое имя здесь время_в_секундах.расширение_файла
        $uniqid = uniqid('', true);
        $name_our_new_file = $uniqid . "." . $ext;


        // проверяем существование папки
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/linereferral/$this->path_img$chat_id/")) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . "/linereferral/$this->path_img$chat_id/", 0700);
        }
        $folder = "$this->path_img$chat_id/*";
        if (!empty($folder)) {
            $files = glob("$folder"); // get all file names
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file);
                } // delete file
            }
        }
        $name_our_new_file = str_replace(".jpeg", ".jpg", $name_our_new_file);
        copy($file_from_tgrm,
            $_SERVER['DOCUMENT_ROOT'] . "/linereferral/$this->path_img$chat_id/" . $name_our_new_file);

        return "$this->path_main_url$this->path_img$chat_id/" . $name_our_new_file;

    }


    //клавиатура
    private function getKeyBoard($data, $one_time_keyboard = false)
    {
        $keyboard = array(
            "keyboard" => $data,
            "one_time_keyboard" => $one_time_keyboard,
            "resize_keyboard" => true
        );
        return json_encode($keyboard);
    }

    //inline клавиатура
    private function getinline_KeyBoard($data)
    {
        $keyboard = array(
            "inline_keyboard" => $data,
            "one_time_keyboard" => false,
            "resize_keyboard" => true
        );
        return json_encode($keyboard);
    }

    // проверка на админа
    private function isAdmin($chat_id)
    {

        return in_array($chat_id, $this->ADMIN);
    }

    // функция ответа текстового сообщения
    private function sendMessageReply($chat_id, $text, $id)
    {
        $id_message = $this->requestToTelegram([
            'chat_id' => $chat_id,
            'text' => $text,
            "parse_mode" => "HTML",
            "disable_web_page_preview" => true,
            "reply_to_message_id" => $id,
        ], "sendMessage");
        return ($id_message);
    }

    // функция ответа текстового сообщения с кнопкой
    private function sendMessageReply_buttons($chat_id, $text, $id, $start)
    {
        $id_message = $this->requestToTelegram([
            'chat_id' => $chat_id,
            'text' => $text,
            "parse_mode" => "HTML",
            "disable_web_page_preview" => true,
            "reply_to_message_id" => $id,
            "reply_markup" => $start
        ], "sendMessage");
        return ($id_message);
    }

    // функция отправки текстового сообщения
    private function sendMessage($chat_id, $text)
    {
        $id_message = $this->requestToTelegram([
            'chat_id' => $chat_id,
            'text' => $text,
            "parse_mode" => "HTML",
            "disable_web_page_preview" => true,
        ], "sendMessage");
        return ($id_message);
    }

    // функция проверки наличия канала
    private function getChat($channel)
    {
        $id_message = $this->requestToTelegram([
            'chat_id' => $channel
        ], "getChat");
        return ($id_message);
    }

    // функция проверки участие в группе
    private function getChatMember($chat_id, $channel)
    {
        $rez_json = $this->requestToTelegram([
            'chat_id' => $channel,
            'user_id' => $chat_id
        ], "getChatMember");
        $array = json_decode($rez_json, true);
        return ($array['result']['status']);
    }

    // функция логирования в файл
    private function setFileLog($data, $file)
    {
        $fh = fopen($file, 'a') or die('can\'t open file');
        ((is_array($data)) || (is_object($data))) ? fwrite($fh, print_r($data, true) . "\n") : fwrite($fh,
            $data . "\n");
        fclose($fh);
    }

    /**
     * Парсим что приходит преобразуем в массив
     * @param $data
     * @return mixed
     */
    private function getData($data)
    {
        return json_decode(file_get_contents($data), true);
    }

    /** Отправляем запрос в Телеграмм
     * @param $data
     * @param string $type
     * @return mixed
     */
    private function requestToTelegram($data, $type)
    {
        $result = null;

        if (is_array($data)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $this->botToken . '/' . $type);
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return $result;
    }

}

?>