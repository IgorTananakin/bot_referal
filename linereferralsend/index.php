<?php
// определяем кодировку
header('Content-type: text/html; charset=utf-8');
$approved_payments = $_GET['approved_payments'];
if (!$approved_payments) {
    $approved_payments = 0;
}
$total_approved_payments = $_GET['total_approved_payments'];
if (!$total_approved_payments) {
    $total_approved_payments = 0;
}
// Создаем объект бота
$bot = new Bot($approved_payments, $total_approved_payments);
// Обрабатываем пришедшие данные
$bot->init('php://input');

/**
 * Class Bot
 */
class Bot
{

    private $botToken = "5198879347:AAFFq_2AgVYjDZq5c29iCznEXWDhb4XQl2o";
    public $approved_payments;
    public $total_approved_payments;
    public $path_payment_data = "../linereferral/assets/json/payment_data.json";

    public function __construct(
        $approved_payments,
        $total_approved_payments
    ) {
        $this->approved_payments = $approved_payments;
        $this->total_approved_payments = $total_approved_payments;
    }

    // адрес для запросов к API Telegram
    private $apiUrl = "https://api.telegram.org/bot";
    private $GROUP = -1001591758625;

    public function init($data_php)
    {
        //включаем логирование будет лежать рядом с этим файлом
//                     $this->setFileLog( $data, $this->path_log );
        if ($this->total_approved_payments) {
            $this->selectTotalPayments();
        }
        if ($this->approved_payments) {
            if ($this->checkWorkingHours()) {
                $text = $this->selectPayment();
//                $number = rand(45, 260);
                echo $text;
//                sleep($number);
                $this->sendMessage($this->GROUP, $text);
            }

        }
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

    // Вывод итог платежей за день
    private function selectTotalPayments()
    {
        $array_payment = $this->selectArrayPayment();
        $text = "Kunning qisqacha mazmuni\n\n<b>Jami to'lovlar:</b> " . $array_payment['count'] . "\n<b>To'lov miqdori:</b> " . $array_payment['summ'];
        $this->sendMessage($this->GROUP, $text);
        $array_payment = [
            "count" => 0,
            "summ" => 0
        ];
        file_put_contents($this->path_payment_data, json_encode($array_payment));
    }

    // фейк вывод платежа
    private function selectPayment()
    {
        $array_number_phone = [1, 3, 4, 7, 9];
        $number_phone = array_rand($array_number_phone, 1);

        $array_summa = [0, 5];
        $summa = array_rand($array_summa, 1);

        $phone_text = "+9989" . $array_number_phone[$number_phone] . "****" . rand(100, 999);
        $summa_text = rand(60, 99) . $array_summa[$summa] . 0;
        $text = "Telefon: $phone_text\nsum: $summa_text\n\n<b>Holat:</b> tasdiqlangan ✅";
        $this->savePayment($summa_text);
        return $text;
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