<?php
flush();
ob_start();
ob_implicit_flush(1);

$load = sys_getloadavg();

$telegram_ip_ranges = [
    ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'],
    ['lower' => '91.108.4.0',    'upper' => '91.108.7.255'],
];

$ip_dec = (float) sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
$ok = false;

foreach ($telegram_ip_ranges as $telegram_ip_range) if (!$ok) {
    $lower_dec = (float) sprintf("%u", ip2long($telegram_ip_range['lower']));
    $upper_dec = (float) sprintf("%u", ip2long($telegram_ip_range['upper']));
    if ($ip_dec >= $lower_dec and $ip_dec <= $upper_dec) $ok = true;
}

if (!$ok) header("Location: https://t.me/DvixTimes");

include 'config.php';
include 'currencies.php';
include('lib/jdf.php');
//==========================// bot //==========================//
function bot($method, $datas = [])
{
    static $ch = null;

    if ($ch === null) {

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_FRESH_CONNECT => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_DNS_CACHE_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_NOSIGNAL => 1,
        ]);
    }

    curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . API_KEY . '/' . $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);

    $res = curl_exec($ch);

    if ($res === false) {
        error_log(curl_error($ch));
        return false;
    }

    return json_decode($res);
}
//========================== // update // ==============================
$update = json_decode(file_get_contents('php://input'));

include 'inline.php';

if (isset($update->message)) {
    $message = $update->message;
    $message_id = $message->message_id;
    $text = safe($message->text);
    $chat_id = $message->chat->id;
    $tc = $message->chat->type;
    $first_name = $message->from->first_name;
    $username = $message->from->username;
    $from_id = $message->from->id;
}

if (isset($update->callback_query)) {
    $callback_query = $update->callback_query;
    $callback_query_id = $callback_query->id;
    $data = $callback_query->data;
    $from_id = $callback_query->from->id;
    $message_id = $callback_query->message->message_id;
    $chat_id = $callback_query->message->chat->id;
}

$creator = $admin;

//=====// DataBase //=====//
$user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `user` WHERE `id` = '$from_id' LIMIT 1"));
$block = mysqli_query($connect, "SELECT * FROM `block` WHERE `id` = '$from_id' LIMIT 1");
$admin = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `admin` WHERE `admin` = '$from_id' LIMIT 1"));
$send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
$group_info = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `group` WHERE `id` = '$chat_id' LIMIT 1"));

$namegroup = safe($update->message->chat->title);

date_default_timezone_set('Asia/Tehran');
$timestamp = time();
$time = date('H:i');
$date = gregorian_to_jalali(date('Y'), date('m'), date('d'), '/');
$ToDay = jdate('l');
//==========================// function //==========================//
function curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $res = curl_exec($ch);
    curl_close($ch);

    return json_decode($res, true);
}

function emoji_flag($cc)
{
    $cc = strtoupper($cc);
    return mb_convert_encoding('&#' . (127397 + ord($cc[0])) . ';', 'UTF-8', 'HTML-ENTITIES') .
        mb_convert_encoding('&#' . (127397 + ord($cc[1])) . ';', 'UTF-8', 'HTML-ENTITIES');
}

function Takhmin($fil)
{
    if ($fil <= 400) {
        return "1";
    } else {
        $besanie = $fil / 400;
        return ceil($besanie) + 1;
    }
}

function safe($text)
{
    global $connect;
    $text = $connect->real_escape_string($text);
    $array = ['$', ';', '"', "'", '<', '>'];
    return str_replace($array, '', $text);
}

function getChatstats($chat_id, $token)
{
    $url = 'https://api.telegram.org/bot' . $token . '/getChatAdministrators?chat_id=' . $chat_id;
    $result = file_get_contents($url);
    $result = json_decode($result);
    $result = $result->ok;
    return $result;
}

// function tab_latin($string)
// {

//     $persian_num = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
//     $latin_num = range(0, 9);
//     $string = str_replace($persian_num, $latin_num, $string);

//     return $string;
// }

function tab_latin($input)
{
    // هر چیزی بود (عدد، رشته، …) رو رشته کن
    $string = (string) $input;

    // پاک‌سازی کاراکترهای جهت‌دهی که نمایش رو خراب می‌کنن
    $string = preg_replace('/[\x{200E}\x{200F}\x{061C}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $string);

    // تبدیل اعداد فارسی/عربی به انگلیسی
    $map = [
        // فارسی (U+06F0..U+06F9)
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',

        // عربی/هندی (U+0660..U+0669)
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',

        // جداکننده‌ها
        '٫' => '.',   // ممیز عربی
        '٬' => ',',   // هزارگان عربی
        '،' => ',',   // ویرگول عربی
    ];

    return strtr($string, $map);
}

function format_number($number)
{
    $number_str = (string)$number;

    if (stripos($number_str, 'e') !== false) {
        $number_str = sprintf('%.20f', $number_str);

        $number_str = rtrim($number_str, '0');

        if (substr($number_str, -1) === '.') {
            $number_str = substr($number_str, 0, -1);
        }
    } else {
        if (strpos($number_str, '.') !== false) {
            $number_str = rtrim($number_str, '0');

            if (substr($number_str, -1) === '.') {
                $number_str = substr($number_str, 0, -1);
            }
        }
    }

    $number = (float)$number_str;

    $decimal_count = strpos($number_str, '.') !== false ? strlen(substr(strrchr($number_str, "."), 1)) : 0;
    $decimal_count = min($decimal_count, 6);
    $formatted_number = number_format($number, $decimal_count, '.', ',');

    return $formatted_number;
}

function getBitpinPrice($coin)
{
    global $api;

    $get_api = curl("$api/bitpin/?type=$coin");
    $coin_irt = $get_api['price_toman'];

    return $coin_irt;
}
//================// Join Function //================//
function is_join($id)
{
    global $botname;
    global $connect;

    $chs = mysqli_query($connect, "SELECT idoruser FROM channels");
    $fil = mysqli_num_rows($chs);
    while ($row = mysqli_fetch_assoc($chs)) {
        $ar[] = $row["idoruser"];
    }
    for ($i = 0; $i < $fil; $i++) {
        $by = $i + 1;
        $okk = $ar[$i];
        $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$okk' LIMIT 1"));
        $link = $ch['link'];
        $ch_id = $ch['idoruser'];

        $in_ch = array();
        $type = bot("getChatMember", ["chat_id" => "$ch_id", "user_id" => $id]);
        $type = (is_object($type)) ? $type->result->status : $type['result']['status'];
        if ($type == 'creator' || $type == 'administrator' || $type == 'member') {
            $in_ch[$ch_id] = $type;
        } else {
            $keyboard = array();
            for ($j = 0; $j < $fil; $j++) {
                $by = $j + 1;
                $okk = $ar[$j];
                $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$okk' LIMIT 1"));
                $link = $ch['link'];
                $ch_id = $ch['idoruser'];

                $ch_info = bot("getChat", ["chat_id" => "$ch_id"]);
                $ch_name = (is_object($ch_info)) ? $ch_info->result->title : $ch_info['result']['title'];
                $keyboard[] = array(array('text' => $ch_name, 'url' => $link));
            }
            $keyboard[] = array(array('text' => '✅ تایید عضویت', 'callback_data' => 'join'));
            $text = "☑️ برای استفاده از ربات « $botname » ابتدا باید وارد کانال های ما شوید

👇 بعد از عضویت در کانال روی دکمه « ✅ تایید عضویت » بزنید 👇";
            bot('sendMessage', [
                'chat_id' => $id,
                'text' => "$text",
                'parse_mode' => 'MarkDown',
                'reply_markup' => json_encode(array('inline_keyboard' => $keyboard))
            ]);
            exit;
        }
    }
}

function check_join($id)
{
    global $connect;
    $in_ch = [];
    $chs = mysqli_query($connect, "SELECT idoruser FROM channels");
    $fil = mysqli_num_rows($chs);
    if ($fil == 0) {
        return true;
    }
    for ($i = 0; $i < $fil; $i++) {
        $okk = mysqli_fetch_assoc($chs)['idoruser'];
        $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$okk' LIMIT 1"));
        $link = $ch['link'];
        $ch_id = $ch['idoruser'];

        $type = bot("getChatMember", ["chat_id" => "$ch_id", "user_id" => $id]);
        $type = (is_object($type)) ? $type->result->status : $type['result']['status'];
        if ($type == 'creator' || $type ==  'administrator' || $type ==  'member') {
            $in_ch[$ch_id] = $type;
        } else {
            return false;
        }
    }
    return true;
}
//==============================// keybord and Text //==============================//
$home = json_encode([
    'inline_keyboard' => [
        [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'success', 'icon_custom_emoji_id' => '5397916757333654639']],
        [['text' => "راهنما ربات", 'callback_data' => "help", 'style' => 'primary', 'icon_custom_emoji_id' => '5821020548372634550'], ['text' => "قیمت ارزها", 'callback_data' => "currency", 'style' => 'primary', 'icon_custom_emoji_id' => '5792010685693041925']],
        [['text' => "گزارش خرابی و ارائه پیشنهادات", 'url' => "http://t.me/$bug_report", 'style' => 'danger', 'icon_custom_emoji_id' => '5447644880824181073']],
    ],
    'resize_keyboard' => true,
]);

$back = json_encode([
    'inline_keyboard' => [
        [['text' => "صفحه قبل", 'callback_data' => "back", 'style' => 'danger', 'icon_custom_emoji_id' => '5274014342482773380']],
    ],
    'resize_keyboard' => true,
]);

$admin_panel = json_encode([
    'keyboard' => [
        [['text' => "📊 آمار ربات 📊"]],
        [['text' => "💬 بخش ارسال"], ['text' => "🤖 تنظیمات ربات"], ['text' => "👤 بخش کاربران"]],
        [['text' => "🔙 بازگشت"]],
    ],
    'resize_keyboard' => true,
]);

$user_section = json_encode([
    'keyboard' => [
        [['text' => "❌ حذف مسدودیت"], ['text' => "⚠️ مسدود کردن"]],
        [['text' => "برگشت 🔙"]],
    ],
    'resize_keyboard' => true,
]);

$send_section = json_encode([
    'keyboard' => [
        [['text' => "💬 پیام همگانی"], ['text' => "↗️ فوروارد همگانی"]],
        [['text' => "💬 پیام گروه"], ['text' => "↗️ فوروارد گروه"]],
        [['text' => "برگشت 🔙"]],
    ],
    'resize_keyboard' => true,
]);

$setting_section = json_encode([
    'keyboard' => [
        [['text' => "📣 مدیریت قفل ها"], ['text' => "👤 مدیریت ادمین ها"]],
        [['text' => "برگشت 🔙"]],
    ],
    'resize_keyboard' => true,
]);

$manage_admin = json_encode([
    'keyboard' => [
        [['text' => "➕ افزودن ادمین"]],
        [['text' => "👤 پنل مدیریت 👤"], ['text' => "📚 لیست ادمین ها"]],
    ],
    'resize_keyboard' => true,
]);

$manage_channel = json_encode([
    'keyboard' => [
        [['text' => "➕ افزودن چنل"]],
        [['text' => "👤 پنل مدیریت 👤"], ['text' => "📚 لیست چنل ها"]],
    ],
    'resize_keyboard' => true,
]);

$backpanel = json_encode([
    'keyboard' => [
        [['text' => "برگشت 🔙"]],
    ],
    'resize_keyboard' => true,
]);

$welcome = "<tg-emoji emoji-id='5472055112702629499'>👋</tg-emoji> سلام، به بهترین ربات قیمت کریپتو بصورت *لحظه ای* خوش اومدی!

<tg-emoji emoji-id='6016895216261272344'>😎</tg-emoji> برای استفاده از ربات، از طریق دکمه زیر منو به یه گروه اضافه کن:

<tg-emoji emoji-id='5373098009640836781'>📚</tg-emoji> اگه نیاز به راهنما داری از دستور /help استفاده کن!";

//==============================//  Anti Spam //==============================//
if ($user["spam"] > time()) {
    exit();
}

if (!($admin['admin'] == $from_id)) {
    $tt = time() + 0.2;
    $connect->query("UPDATE `user` SET `spam` = '$tt' WHERE `id` = '$from_id' LIMIT 1");
}
//===========================// checking //===========================//
if (mysqli_num_rows($block) > 0) {
    exit();
}

if ($tc == "private") {
    if ($user['id'] != true) {
        $connect->query("INSERT INTO `user` (`id` , `spam` , `create_at` , `update_at`) VALUES ('$from_id' , '$timestamp' , '$timestamp' , '$timestamp')");

        bot('sendMessage', [
            'chat_id' => $group_log,
            'text' => "یه استارت جدید ثبت شد\n\ndata: $from_id | $first_name | @$username",
        ]);
    }
}
//===========================// update //===========================//
if (($message or $data) && $tc == "private") {
    $connect->query("UPDATE `user` SET `update_at` = '$timestamp' WHERE `id` = '$from_id' LIMIT 1");
}

if (($message or $data) && ($message->chat->type == 'group' || $message->chat->type == 'supergroup')) {

    $memberCount = bot('getChatMembersCount', [
        'chat_id' => $chat_id
    ])->result;

    $connect->query("UPDATE `group` SET `update_at` = '$timestamp' , `member` = '$memberCount' WHERE `id` = '$chat_id' LIMIT 1");
}
//===========================// install //===========================//
if ($text == "نصب" or $text == "install") {
    if ($message->chat->type == 'group' or $message->chat->type == 'supergroup') {
        if ($group_info['id'] != true) {

            $memberCount = bot('getChatMembersCount', [
                'chat_id' => $chat_id
            ]);

            $memberCount = $memberCount->result;

            $connect->query("INSERT INTO `group` (`id` , `name` , `member` , `time` , `date` , `join_at`) VALUES ('$chat_id' , '$namegroup' , '$memberCount' , '$time' , '$date' , '$timestamp')");

            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<tg-emoji emoji-id='5372981976804366741'>🤖</tg-emoji> ربات با موفقیت در گروه شما نصب شد

<tg-emoji emoji-id='5334882760735598374'>📝</tg-emoji> اطلاعات گروه :

🔢 شناسه گروه: $chat_id
<tg-emoji emoji-id='5372926953978341366'>👥</tg-emoji> نام گروه: $namegroup
<tg-emoji emoji-id='5460795800101594035'>🗣</tg-emoji> تعداد اعضا: $memberCount
<tg-emoji emoji-id='5431897022456145283'>📆</tg-emoji> تاریخ عضویت: $date | $time",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "کانال ما", 'url' => "https://t.me/$channel", 'style' => 'primary']],
                    ]
                ])
            ]);

            bot('sendMessage', [
                'chat_id' => $group_log,
                'text' => "<tg-emoji emoji-id='5372981976804366741'>🤖</tg-emoji> ربات در یک گروه جدید نصب شد!

<tg-emoji emoji-id='5334882760735598374'>📝</tg-emoji> اطلاعات گروه:

🔢 شناسه گروه: $chat_id
<tg-emoji emoji-id='5372926953978341366'>👥</tg-emoji> نام گروه: $namegroup
<tg-emoji emoji-id='5460795800101594035'>🗣</tg-emoji> تعداد اعضا: $memberCount
<tg-emoji emoji-id='5431897022456145283'>📆</tg-emoji> تاریخ عضویت: $date | $time",
                'parse_mode' => "HTML",
            ]);
        }
    }
}
//===========================// start //===========================//
if (($text == "/start" or $text == "🔙 بازگشت") and $tc == 'private') {

    if ($user['id'] != true) {
        $connect->query("INSERT INTO `user` (`id` , `spam` , `create_at` , `update_at`) VALUES ('$from_id' , '$timestamp' , '$timestamp' , '$timestamp')");
    }

    if ($text == "🔙 بازگشت") {
        bot('sendMessage', [
            'chat_id' => $from_id,
            'text' => "↩️ به صفحه قبل برگشتیم:",
            'reply_markup' => json_encode([
                'remove_keyboard' => true
            ])
        ]);
    }

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $welcome,
        'parse_mode' => "HTML",
        'reply_markup' => $home
    ]);

    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    exit;
}

if ($text == "/start@$usernamebot" and ($message->chat->type == 'group' or $message->chat->type == 'supergroup')) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<tg-emoji emoji-id='6016895216261272344'>😎</tg-emoji> سلام، ممنون که منو به گروهتون اضافه کردین!

<tg-emoji emoji-id='5805650110255734468'>⭐️</tg-emoji> برای استفاده از من و استعلام قیمت ارز های دیجیتال من رو توی گروه ادمین کنید و دستور «`نصب`» رو ارسال کنید!

<tg-emoji emoji-id='5373098009640836781'>📚</tg-emoji> درصورتی که نیاز به راهنما داشتید از دستور «/help» استفاده کنید!",
        'parse_mode' => "HTML"
    ]);
}
//===========================// join checker //===========================//
if (check_join("$from_id") != 'true' and $tc == 'private') {
    is_join("$from_id");
    exit;
}
//===========================// data //===========================//
if ($data == 'join') {
    if (check_join("$from_id") == 'true') {
        bot('deletemessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $welcome,
            'reply_markup' => $home,
            'parse_mode' => "HTML"
        ]);
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $callback_query_id,
            'text' => "⚠️ - لطفا ابتدا عضو کانال های جوین اجباری شوید.",
            'show_alert' => true
        ]);
    }
}

if ($data == "back") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $welcome,
        'reply_markup' => $home,
        'parse_mode' => "HTML"
    ]);
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
}

//=====================// Help //=====================//

if ($text == '/help' or $text == "/help@$usernamebot" or $data == "help") {

    $params = [
        'chat_id' => $chat_id,
        'text' => "<tg-emoji emoji-id='6016895216261272344'>😎</tg-emoji> به راهنمای ربات ما خوش اومدی!

<tg-emoji emoji-id='5280922999241859582'>💎</tg-emoji> اگه میخوای قیمت یه ارز خاص رو بگیری از اسم اون ارز استفاده کن مثلا «<code>نات کوین</code>» یا «<code>بیت کوین</code>»!

<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji> اگه میخوای قیمت یک ارز رو با توجه به تعداد محاسبه کنی اول مقدار و بعد اسم ارز مثلا «<code>۱۰۰۰ نات کوین</code>» یا «<code>۲ بیت کوین</code>»!

<tg-emoji emoji-id='5370601486885591701'>😃</tg-emoji> ربات ما تقریبا همه ارز هارو داره و میتونید از رباتمون برای محاسبه تمامی ارز ها استفاده کنید؛

<tg-emoji emoji-id='5409048419211682843'>💵</tg-emoji> برای مشاهده لیست ارز ها از دکمه های زیر استفاده کنید <tg-emoji emoji-id='5800879101669544181'>👇</tg-emoji>

$date | $time",
        'message_id' => $message_id,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "ارز های حقیقی", 'callback_data' => "help_money", 'style' => 'success', 'icon_custom_emoji_id' => '5409048419211682843']],
                [['text' => "ارز های دیجیتال", 'callback_data' => "help_crypto", 'style' => 'success', 'icon_custom_emoji_id' => '5805190183682842212'], ['text' => "ارز های طلا", 'callback_data' => "help_gold", 'style' => 'success', 'icon_custom_emoji_id' => '5427168083074628963']],
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ];

    if ($text) {
        bot('sendMessage', $params);
    } else {
        $params['message_id'] = $message_id;

        $params['reply_markup'] =  json_encode([
            'inline_keyboard' => [
                [['text' => "ارز های حقیقی", 'callback_data' => "help_money", 'style' => 'success', 'icon_custom_emoji_id' => '5409048419211682843']],
                [['text' => "ارز های دیجیتال", 'callback_data' => "help_crypto", 'style' => 'success', 'icon_custom_emoji_id' => '5805190183682842212'], ['text' => "ارز های طلا", 'callback_data' => "help_gold", 'style' => 'success', 'icon_custom_emoji_id' => '5427168083074628963']],
                [['text' => "صفحه قبل", 'callback_data' => "back", 'style' => 'danger', 'icon_custom_emoji_id' => '5274014342482773380']],
            ]
        ]);

        bot('editMessageText', $params);
    }
}


if ($data == "back_help") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'text' => "<tg-emoji emoji-id='6016895216261272344'>😎</tg-emoji> به راهنمای ربات ما خوش اومدی!

<tg-emoji emoji-id='5280922999241859582'>💎</tg-emoji> اگه میخوای قیمت یه ارز خاص رو بگیری از اسم اون ارز استفاده کن مثلا «<code>نات کوین</code>» یا «<code>بیت کوین</code>»!

<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji> اگه میخوای قیمت یک ارز رو با توجه به تعداد محاسبه کنی اول مقدار و بعد اسم ارز مثلا «<code>۱۰۰۰ نات کوین</code>» یا «<code>۲ بیت کوین</code>»!

<tg-emoji emoji-id='5370601486885591701'>😃</tg-emoji> ربات ما تقریبا همه ارز هارو داره و میتونید از رباتمون برای محاسبه تمامی ارز ها استفاده کنید؛

<tg-emoji emoji-id='5409048419211682843'>💵</tg-emoji> برای مشاهده لیست ارز ها از دکمه های زیر استفاده کنید <tg-emoji emoji-id='5800879101669544181'>👇</tg-emoji>

$date | $time",
        'message_id' => $message_id,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "ارز های حقیقی", 'callback_data' => "help_money", 'style' => 'success', 'icon_custom_emoji_id' => '5409048419211682843']],
                [['text' => "ارز های دیجیتال", 'callback_data' => "help_crypto", 'style' => 'success', 'icon_custom_emoji_id' => '5805190183682842212'], ['text' => "ارز های طلا", 'callback_data' => "help_gold", 'style' => 'success', 'icon_custom_emoji_id' => '5427168083074628963']],
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

if ($data == "help_money") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'text' => "<tg-emoji emoji-id='5801148099766260058'>👈</tg-emoji> لیست ارز های حقیقی [پشتیبانی شده] به شرح زیر است:

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>یورو</code> => <code>Euro</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>پوند</code> => <code>Pound</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>درهم</code> => <code>Derham</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>لیر</code> => <code>Lira</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>فرانک</code> => <code>Franc</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>روبل</code> => <code>Ruble</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>دینار</code> => <code>Dinar</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>افغانی</code> => <code>Afghani</code>

[<tg-emoji emoji-id='5805303510689914163'>💰</tg-emoji>] <code>یوآن</code> => <code>Yuan</code>

$date | $time",
        'message_id' => $message_id,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "↩️ صفحه قبل", 'callback_data' => "back_help"]],
            ]
        ])
    ]);
}

if ($data == "help_crypto") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'text' => "👈 لیست ارز های دیجیتال [پشتیبانی شده] به شرح زیر است:

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>اتریوم</code> => <code>Ethereum</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>دوج کوین</code> => <code>Dogecoin</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>سولانا</code> => <code>Solana</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>همستر</code> => <code>Hamster Kombat</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>داگز</code> => <code>DOGS</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>تون</code> => <code>Ton</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>بیبی دوج</code> => <code>Babydoge</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>نات کوین</code> => <code>Notcoin</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>بیت کوین</code> => <code>Bitcoin</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>تتر</code> => <code>Tether</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>شیبا</code> => <code>Shiba</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>ترون</code> => <code>Tron</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>کتز</code> => <code>Cats</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] <code>وات</code> => <code>WatBird</code>

[<tg-emoji emoji-id='4956696682570974708'>💲</tg-emoji>] و +300 ارز دیگر!

$date | $time",
        'message_id' => $message_id,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "↩️ صفحه قبل", 'callback_data' => "back_help"]],
            ]
        ])
    ]);
}

if ($data == "help_gold") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'text' => "👈 لیست ارز های طلا [پشتیبانی شده] به شرح زیر است:

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>سکه گرمی</code> => <code>GramCoin</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>طلای 18 عیار</code> => <code>GramGold18</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>طلای 24 عیار</code> => <code>GramGold24</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>سکه بهار آزادی</code> => <code>BaharCoin</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>سکه امامی</code> => <code>ImamiCoin</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>نیم سکه</code> => <code>HalfCoin</code>

[<tg-emoji emoji-id='5199552030615558774'>🪙</tg-emoji>] <code>سکه کوارتر</code> => <code>QuarterCoin</code>

$date | $time",
        'message_id' => $message_id,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "↩️ صفحه قبل", 'callback_data' => "back_help"]],
            ]
        ])
    ]);
}

//=======================// List Arz //=======================//

if ($text == 'لیست ارز' or $data == "currency") {

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $currency_data = curl("$api/chand/?$timestamp");
    $currencies = $currency_data['currencies'];

    $usd_price = 0;
    foreach ($currencies as $c) {
        if ($c['code'] == 'usd') {
            $usd_price = $c['price'];
            break;
        }
    }

    $wanted = ['usd', 'eur', 'gbp', 'aed', 'try', 'chf', 'rub', 'kwd', 'afn', 'cny'];

    $text_msg = "◄ نرخ ارزهای حقیقی:\n\n";

    foreach ($currencies as $c) {
        if (in_array($c['code'], $wanted)) {
            $price_irt = format_number($c['price']);
            $price_usd = ($c['code'] != 'usd' && $usd_price > 0) ? format_number($c['price'] / $usd_price) : null;

            $flag = emoji_flag($c['country']);

            $text_msg .= "\xE2\x80\x8F╕ $flag {$c['en']}\n";
            if ($price_usd) {
                $text_msg .= "\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $price_usd دلار\n";
            }
            $text_msg .= "\xE2\x80\x8F╛ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $price_irt تومان\n\n";
        }
    }

    $response = curl("$api/bitpin/?$timestamp");

    $cryptos = $response['currencies'];

    $text_msg .= "◄ قیمت لحظه‌ای ارزهای دیجیتال:\n\n";

    foreach ($cryptos as $coin) {
        $symbol = $coin['symbol'];
        $name   = $coin['name_fa'];

        if ($symbol == "USDT") {
            $toman = number_format($coin['price_toman']);
            $text_msg .= "<tg-emoji emoji-id='5201873447554145566'>💵</tg-emoji> {$name}: {$toman} تومان\n";
        } else {
            $usd = $coin['price_usdt'];
            $usd_fmt = format_number($usd, 2);
            $text_msg .= "<tg-emoji emoji-id='5409048419211682843'>💵</tg-emoji> {$name}: {$usd_fmt} دلار\n";
        }
    }

    $text_msg .= "\n{$date} | {$time}";

    $params =  [
        'chat_id' => $chat_id,
        'text' => $text_msg,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ];

    if ($text) {
        bot('sendMessage', $params);
    } else {
        $params['reply_markup'] = $back;
        $params['message_id'] = $message_id;

        bot('editMessageText', $params);
    }
}

//=======================// نرخ ارز //=======================//

if ($text == 'نرخ ارز') {

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $currency_data = curl("$api/chand/?$timestamp");

    $currencies = $currency_data['currencies'];

    $usd_price = 0;
    foreach ($currencies as $c) {
        if ($c['code'] == 'usd') {
            $usd_price = $c['price'];
            break;
        }
    }

    $wanted = ['usd', 'eur', 'gbp', 'aed', 'try', 'chf', 'rub', 'kwd', 'afn', 'cny'];

    $text_msg = "◄ نرخ ارزهای حقیقی:\n\n";

    foreach ($currencies as $c) {
        if (in_array($c['code'], $wanted)) {
            $price_irt = format_number($c['price']);
            $price_usd = ($c['code'] != 'usd' && $usd_price > 0) ? format_number($c['price'] / $usd_price) : null;

            $flag = emoji_flag($c['country']);

            $text_msg .= "\xE2\x80\x8F╕ $flag {$c['en']}\n";
            if ($price_usd) {
                $text_msg .= "\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $price_usd دلار\n";
            }
            $text_msg .= "\xE2\x80\x8F╛ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $price_irt تومان\n\n";
        }
    }

    $text_msg .= "$date | $time";

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text_msg,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

//=======================// نرخ کریپتو //=======================//

if ($text == 'نرخ کریپتو') {

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $response = curl("$api/bitpin/?$timestamp");

    $currencies = $response['currencies'];

    $list = "◄ قیمت لحظه‌ای ارزهای دیجیتال:\n\n";

    foreach ($currencies as $coin) {
        $symbol = $coin['symbol'];
        $name   = $coin['name_fa'];

        if ($symbol == "USDT") {
            $toman = number_format($coin['price_toman']);
            $list .= "<tg-emoji emoji-id='5201873447554145566'>💵</tg-emoji> {$name}: {$toman} تومان\n";
        } else {
            $usd = $coin['price_usdt'];
            $usd_fmt = format_number($usd, 2);
            $list .= "<tg-emoji emoji-id='5409048419211682843'>💵</tg-emoji> {$name}: {$usd_fmt} دلار\n";
        }
    }

    $list .= "\n{$date} | {$time}";

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $list,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

//=======================// نرخ طلا و سکه //=======================//

function tgju_list_message(array $data, array $keys, string $title, string $date, string $time): string
{
    $list = "◄ {$title}:\n\n";

    $usdt_price = getBitpinPrice('USDT');

    foreach ($keys as $key) {
        if (!isset($data[$key])) {
            continue;
        }

        $item = $data[$key];

        if (!isset($item['price']) || $item['price'] === null) {
            continue;
        }

        $name   = htmlspecialchars($item['fa'] ?? $key, ENT_QUOTES, 'UTF-8');
        $price  = number_format((float) $item['price'] / 10);
        $price_usd = number_format((($item['price'] / 10) / $usdt_price), 2);
        $change = number_format((float) $item['change'] / 10);
        $change_usd = number_format((($item['change'] / 10) / $usdt_price), 2);

        $trendIcon = "<tg-emoji emoji-id='5451882707875276247'>🕯</tg-emoji>";
        if (($item['trend'] ?? '') === 'high') {
            $trendIcon = "<tg-emoji emoji-id='5244837092042750681'>📈</tg-emoji>";
        } elseif (($item['trend'] ?? '') === 'low') {
            $trendIcon = "<tg-emoji emoji-id='5246762912428603768'>📉</tg-emoji>";
        }

        if ($title == "قیمت لحظه‌ای طلا") {
            $coinIcon = "<tg-emoji emoji-id='5775927158450950245'>⭐️</tg-emoji>";
        } else {
            $coinIcon = "<tg-emoji emoji-id='5767179976516312812'>🪙</tg-emoji>";
        }

        $usdIcon = "<tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji>";

        $list .= "\xE2\x80\x8F╕ {$coinIcon} {$name}: {$price} تومان\n";
        $list .= "\xE2\x80\x8F╡ {$usdIcon} قیمت دلاری: $$price_usd دلار\n";
        $list .= "\xE2\x80\x8F╛ {$trendIcon} تغییر: {$change} تومان معادل $$change_usd دلار\n\n";
    }

    $list .= "\n{$date} | {$time}";

    return $list;
}

if ($text == 'نرخ طلا') {

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $response = curl("$api/tgju2/?$timestamp");
    $data = $response['data'];

    $gold_keys = [
        'gold_18k',
        'gold_18k_740',
        'gold_24k',
        'gold_second_hand',
        'gold_mesghal',
        'gold_mesghal_no_bubble',
        'gold_melted_bubble',
        'gold_mesghal_based_on_coin',
        'gold_melted_cash',
        'gold_melted_trading',
        'gold_melted_wholesale',
        'gold_melted_under_kg',
    ];

    $list = tgju_list_message($data, $gold_keys, 'قیمت لحظه‌ای طلا', $date, $time);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $list,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

if ($text == 'نرخ سکه') {

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $response = curl("$api/tgju2/?$timestamp");
    $data = $response['data'];

    $coin_keys = [
        'coin_emami',
        'coin_bahar',
        'coin_half',
        'coin_quarter',
        'coin_gerami',
        'retail_coin_emami',
        'retail_coin_bahar',
        'retail_coin_half',
        'retail_coin_quarter',
        'retail_coin_gerami',
        'coin_emami_pre86',
        'coin_half_pre86',
        'coin_quarter_pre86',
    ];

    $list = tgju_list_message($data, $coin_keys, 'قیمت لحظه‌ای سکه', $date, $time);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $list,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

//===============// Crypto Price [BitPin] //===============//

foreach ($currencies_bitpin as $code => $names) {
    $english = $names[0];
    $persian = $names[1];

    $text = tab_latin($text);

    if (preg_match('/^([\d.]*)\s*(.+)/u', $text, $matches)) {
        $amount = !empty($matches[1]) ? $matches[1] : 1;
        $currency_name = strtolower($matches[2]);

        if ($currency_name == strtolower($english) || $currency_name == strtolower($persian)) {

            if ($message->chat->type == 'group' || $message->chat->type == 'supergroup') {
                if ($group_info['id'] != true) {
                    bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
                        'parse_mode' => "markdown"
                    ]);
                    exit;
                }
            }

            $response = curl("$api/bitpin/?type=$code");

            $latest_prc = $response['price_usdt'];
            $latestirt_prc = $response['price_toman'];

            $total_prc_usd = format_number($amount * $latest_prc);
            $total_prc_irt = number_format($amount * $latestirt_prc);

            $change_prc = $response['percent_change_24h'];

            if (strpos($change_prc, '-') === 0) {
                $change_icon = "🔴";
                $change_icon2 = "🔻";
                $change_name = "ضرر";
                $change_emoji = "-";
            } else {
                $change_icon = "🟢";
                $change_icon2 = "🔺";
                $change_name = "سود";
                $change_emoji = "";
            }

            $usdt_latest = (float) $latest_prc;
            $usdt_dayChange = (float) str_replace("-", '', $change_prc);

            $irt_latest = (float) $latestirt_prc;
            $irt_dayChange = (float) str_replace("-", '', $change_prc);

            $usdt_change_amount = (($usdt_latest * $usdt_dayChange) / 100) * $amount;
            $irt_change_amount = (($irt_latest * $irt_dayChange) / 100) * $amount;

            $usdt_change_amount = number_format($usdt_change_amount, 6);
            $irt_change_amount = number_format($irt_change_amount, 2);

            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "\xE2\x80\x8F╕ <tg-emoji emoji-id='5990147899403539264'>💰</tg-emoji>$amount $persian ($english)
\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $$total_prc_usd دلار
\xE2\x80\x8F╡ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $total_prc_irt تومان
\xE2\x80\x8F╡ $change_icon $change_prc%
\xE2\x80\x8F╛ $change_icon2 $usdt_change_amount$ / $irt_change_amount تومان

$date | $time",
                'parse_mode' => 'HTML',
                'reply_to_message_id' => $message_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
                        [['text' => "$change_icon", 'callback_data' => "none", 'style' => 'primary'], ['text' => "$change_prc" . "%", 'callback_data' => "none", 'style' => 'primary']],
                    ]
                ])
            ]);
            break;
        }
    }
}

//===============// Crypto Chart //===============//

foreach ($currencies_bitpin as $code => $names) {
    $english = strtolower($names[0]);
    $persian = strtolower($names[1]);

    $text = trim(tab_latin($text));
    $text_lower = mb_strtolower($text);

    if (preg_match('/^چارت\s+(.+)/u', $text_lower, $matches)) {

        $currency_name = trim($matches[1]);
        $amount = 1;

        if ($currency_name == $english || $currency_name == $persian) {

            if ($message->chat->type == 'group' || $message->chat->type == 'supergroup') {
                if ($group_info['id'] != true) {
                    bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
                        'parse_mode' => "markdown"
                    ]);
                    exit;
                }
            }

            $response = curl("$api/bitpin/?type=$code");

            $latest_prc = $response['price_usdt'];
            $latestirt_prc = $response['price_toman'];

            $total_prc_usd = format_number($amount * $latest_prc);
            $total_prc_irt = number_format($amount * $latestirt_prc);

            $change_prc = $response['percent_change_24h'];

            if (strpos($change_prc, '-') === 0) {
                $change_icon = "🔴";
                $change_icon2 = "🔻";
            } else {
                $change_icon = "🟢";
                $change_icon2 = "🔺";
            }

            $usdt_latest = (float) $latest_prc;
            $usdt_dayChange = (float) str_replace("-", '', $change_prc);

            $irt_latest = (float) $latestirt_prc;
            $irt_dayChange = (float) str_replace("-", '', $change_prc);

            $usdt_change_amount = (($usdt_latest * $usdt_dayChange) / 100) * $amount;
            $irt_change_amount = (($irt_latest * $irt_dayChange) / 100) * $amount;

            $usdt_change_amount = number_format($usdt_change_amount, 6);
            $irt_change_amount = number_format($irt_change_amount, 2);

            $get_chart = curl("$api/chart/?symbol={$code}USDT&interval=1h&username=@$usernamebot");
            $chart = $get_chart['image'];

            bot('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => $chart,
                'caption' => "\xE2\x80\x8F╕ <tg-emoji emoji-id='5990147899403539264'>💰</tg-emoji> {$names[1]} ({$names[0]})
\xE2\x80\x8F╞ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $$total_prc_usd دلار
\xE2\x80\x8F╞ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $total_prc_irt تومان
\xE2\x80\x8F╞ $change_icon $change_prc%
\xE2\x80\x8F╛ $change_icon2 $usdt_change_amount$ / $irt_change_amount تومان

$date | $time",
                'parse_mode' => 'HTML',
                'reply_to_message_id' => $message_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => "اضافه کردن به گروه",
                                'url' => "http://t.me/$usernamebot?startgroup=new"
                            ]
                        ]
                    ]
                ])
            ]);

            break;
        }
    }
}

//===============// Real Money //===============//

foreach ($currencies_money as $code => $names) {

    $english = strtolower($names[0]);
    $persian = strtolower($names[1]);

    $text = tab_latin($text);

    if (preg_match('/^(\d*\.?\d*)\s*(.+)/u', $text, $matches)) {
        $amount = !empty($matches[1]) ? $matches[1] : 1;
        $currency_name = strtolower(trim($matches[2]));
    } else {
        $amount = 1;
        $currency_name = strtolower(trim($text));
    }

    if (
        $currency_name == strtolower($code) ||
        $currency_name == $english ||
        $currency_name == $persian
    ) {

        if ($message->chat->type == 'group' || $message->chat->type == 'supergroup') {
            if ($group_info['id'] != true) {
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
                    'parse_mode' => "markdown"
                ]);
                exit;
            }
        }

        $response = curl("$api/chand/?$timestamp");

        $currencies = $response['currencies'];

        $index = array_search($code, array_column($currencies, 'code'));

        $index_USD = array_search('usd', array_column($currencies, 'code'));

        $USD_Price = $currencies[$index_USD]['price'];
        $Currency_T = $currencies[$index]['price'];

        $USDT_cur = number_format($amount * ($Currency_T / $USD_Price), 3);
        $IRT_cur = number_format($amount * $Currency_T);

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "\xE2\x80\x8F╕<tg-emoji emoji-id='5990147899403539264'>💰</tg-emoji>$amount {$names[1]} ({$code})
\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $$USDT_cur دلار
\xE2\x80\x8F╛ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $IRT_cur تومان

$date | $time",
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id,
        ]);

        break;
    }
}

//===============// Gift Price //===============//

$gifts_emoji = [
    "FaithAmulet" => "<tg-emoji emoji-id='5426849198932784730'>🎁</tg-emoji>",
];

if (strpos($text, 'گیفت ') === 0) {
    $gift_name = str_replace("گیفت ", "", $text);

    preg_match('/(?:https?:\/\/)?t\.me\/nft\/([A-Za-z0-9_]+)-\d+/i', $gift_name, $match);
    $gift_name = $match[1] ?? $gift_name;
    $gift_emoji = $gifts_emoji[$gift_name] ?? "<tg-emoji emoji-id='5447213743417105726'>🎁</tg-emoji>";

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $data = curl("$api/gifts/v1/?q=" . urlencode($gift_name));

    $gift = $data['collection']['name'];

    if ($gift) {

        $tonPrice = getBitpinPrice('ton');

        $floor = $data['collection']['floor_price'];

        $msg =
            "$gift_emoji قیمت کف ({$gift}):\n" .
            "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$data['collection']['floor_price']} تون " .
            "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
            "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
            number_format($data['collection']['floor_price'] * $tonPrice) .
            " تومان\n" .
            "━━━━━━━━━━━━━━━\n";

        foreach ($data['floors']['model_floors'] as $model => $item) {
            if (!$item['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5375479315603420454'>🟫</tg-emoji> مدل: {$model}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$item['price']} تون " .
                "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($item['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        foreach ($data['floors']['backdrop_floors'] as $backdrop => $item) {
            if (!$item['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5431456208487716895'>🎨</tg-emoji> بک‌دراپ: {$backdrop}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$item['price']} تون " .
                "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($item['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        foreach ($data['floors']['combo_floors'] as $combo) {
            if (!$combo['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5375479315603420454'>🟫</tg-emoji> مدل: {$combo['model']}\n" .
                "<tg-emoji emoji-id='5431456208487716895'>🎨</tg-emoji> بک‌دراپ: {$combo['backdrop']}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$combo['price']} تون " .
                "<a href='https://t.me/mrkt/app?startapp'>(Mrkt)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($combo['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "$msg

$date | $time",
            'parse_mode' => "HTML",
            'disable_web_page_preview' => true,
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
                ]
            ])
        ]);
    }
}

if (preg_match('~^(?:https://)?t\.me/nft/~i', trim($text))) {

    preg_match('/(?:https?:\/\/)?t\.me\/nft\/([A-Za-z0-9_]+)-\d+/i', $text, $match);
    $gift_name = $match[1] ?? $text;
    $gift_emoji = $gifts_emoji[$gift_name] ?? "<tg-emoji emoji-id='5447213743417105726'>🎁</tg-emoji>";

    $gift_name = urlencode($text);

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $data = curl("$api/gifts/v1/?q=$gift_name");

    $gift = $data['collection']['name'];

    if ($gift) {

        $tonPrice = getBitpinPrice('ton');

        $floor = $data['collection']['floor_price'];

        $msg =
            "$gift_emoji قیمت کف ({$gift}):\n" .
            "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$data['collection']['floor_price']} تون " .
            "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
            "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
            number_format($data['collection']['floor_price'] * $tonPrice) .
            " تومان\n" .
            "━━━━━━━━━━━━━━━\n";

        foreach ($data['floors']['model_floors'] as $model => $item) {
            if (!$item['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5375479315603420454'>🟫</tg-emoji> مدل: {$model}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$item['price']} تون " .
                "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($item['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        foreach ($data['floors']['backdrop_floors'] as $backdrop => $item) {
            if (!$item['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5431456208487716895'>🎨</tg-emoji> بک‌دراپ: {$backdrop}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$item['price']} تون " .
                "<a href='https://t.me/portals/market?startapp'>(Portals)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($item['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        foreach ($data['floors']['combo_floors'] as $combo) {
            if (!$combo['price']) continue;

            $msg .=
                "$gift_emoji گیفت: {$gift}\n" .
                "<tg-emoji emoji-id='5375479315603420454'>🟫</tg-emoji> مدل: {$combo['model']}\n" .
                "<tg-emoji emoji-id='5431456208487716895'>🎨</tg-emoji> بک‌دراپ: {$combo['backdrop']}\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: {$combo['price']} تون " .
                "<a href='https://t.me/mrkt/app?startapp'>(Mrkt)</a>\n" .
                "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> تومان: " .
                number_format($combo['price'] * $tonPrice) .
                " تومان\n" .
                "━━━━━━━━━━━━━━━\n";
        }

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "$msg

$date | $time",
            'parse_mode' => "HTML",
            'disable_web_page_preview' => true,
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
                ]
            ])
        ]);
    }
}

#============# دیتا کامل گیفت #============#

if (
    preg_match('~^(?:خریدها|خرید های اخیر|خریدهای اخیر|فروشها|فروش ها|فروش‌های اخیر|اطلاعات|info|sales)\s+(.+)$~ui', trim($text), $cmdMatch)
) {

    $input = trim($cmdMatch[1] ?? $text);

    if (preg_match('/(?:https?:\/\/)?t\.me\/nft\/([A-Za-z0-9_]+)-\d+/i', $input, $match)) {
        $collectionName = $match[1]; // مثال: ChillFlame
    } else {
        $collectionName = $input; // مثال: Chill Flame
    }

    if (($message->chat->type == 'group' || $message->chat->type == 'supergroup') && $group_info['id'] != true) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $url = "$api/gifts/v3/?collection=" . urlencode($collectionName) . "&last_sales=1";
    $data = curl($url);

    if (empty($data['ok']) || empty($data['collection']['name'])) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ اطلاعاتی برای این گیفت پیدا نشد.\n\n<code>" . htmlspecialchars($collectionName, ENT_QUOTES, 'UTF-8') . "</code>",
            'parse_mode' => "HTML",
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

    $tonPrice = getBitpinPrice('ton');

    $collection = $data['collection'];
    $gift = $collection['name'] ?? $collectionName;

    $giftKey = str_replace([' ', '-', "'", '’'], '', $gift);
    $gift_emoji = $gifts_emoji[$gift] ?? $gifts_emoji[$giftKey] ?? "<tg-emoji emoji-id='5447213743417105726'>🎁</tg-emoji>";

    $floor = $collection['floor_price'] ?? 0;
    $supply = $collection['total_supply'] ?? $collection['supply'] ?? 0;
    $listed = $collection['listed_count'] ?? 0;
    $volume = $collection['volume'] ?? 0;
    $marketCap = $collection['market_cap'] ?? 0;

    $telegramBase = $collection['links']['telegram_nft_base'] ?? "";
    $portalUrl = $collection['links']['portal_market'] ?? ($collection['marketplace']['frontend_url'] ?? "https://portal-market.com/");

    $salesBox = $data['sales']['last_10_sales_collection'] ?? [];
    $sales = $salesBox['items'] ?? [];
    $stats = $salesBox['stats'] ?? [];

    $msg =
        "$gift_emoji <b>مارکت اینفو {$gift}</b>\n" .
        "━━━━━━━━━━━━━━━\n" .
        "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> <b>Floor:</b> {$floor} TON\n" .
        "<tg-emoji emoji-id='5803338120770363277'>🇮🇷</tg-emoji> <b>تومان:</b> " . number_format($floor * $tonPrice) . " تومان\n" .
        "<tg-emoji emoji-id='5452133753008696531'>📦</tg-emoji> <b>Supply:</b> " . number_format($supply) . "\n" .
        "<tg-emoji emoji-id='5240228673738527951'>🏷</tg-emoji> <b>Listed:</b> " . number_format($listed) . "\n" .
        "<tg-emoji emoji-id='5792057539491274508'>📊</tg-emoji> <b>Volume:</b> " . number_format($volume, 2) . " TON\n" .
        "<tg-emoji emoji-id='5805460079427723173'>💰</tg-emoji> <b>Market Cap:</b> " . number_format($marketCap, 2) . " TON\n" .
        "<tg-emoji emoji-id='5271604874419647061'>🔗</tg-emoji> <a href='" . htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') . "'>مشاهده در Portals</a>\n" .
        "━━━━━━━━━━━━━━━\n";

    if (!empty($stats) && !empty($stats['count'])) {
        $msg .=
            "<tg-emoji emoji-id='5424972470023104089'>🔥</tg-emoji> <b>آمار خریدهای اخیر</b>\n" .
            "<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji> تعداد: <b>{$stats['count']}</b>\n" .
            "<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji> کمترین: <b>{$stats['min']} TON</b>\n" .
            "<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji> بیشترین: <b>{$stats['max']} TON</b>\n" .
            "<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji> میانگین: <b>{$stats['avg']} TON</b>\n" .
            "<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji> میانه: <b>{$stats['median']} TON</b>\n" .
            "━━━━━━━━━━━━━━━\n";
    }

    if (empty($sales)) {
        $msg .= "<tg-emoji emoji-id='5823183025751464576'>❌</tg-emoji> خرید اخیری برای این گیفت پیدا نشد.\n";
    } else {
        $msg .= "<tg-emoji emoji-id='5780824606579364273'>🛒</tg-emoji> <b>آخرین خریدها</b>\n\n";

        $msg .= "<blockquote expandable>";

        $i = 1;
        foreach (array_slice($sales, 0, 10) as $sale) {
            $price = $sale['price'] ?? 0;
            if (!$price) continue;

            $number = $sale['number'] ?? null;
            $model = $sale['model'] ?? '-';
            $backdrop = $sale['backdrop'] ?? '-';
            $symbol = $sale['symbol'] ?? '-';

            $tgId = $sale['nft']['tg_id'] ?? '';
            $nftUrl = $sale['nft']['url'] ?? ($tgId ? "https://t.me/nft/$tgId" : $telegramBase);

            $soldAt = $sale['date'] ?? '';
            $soldTime = $soldAt ? date('Y/m/d H:i', strtotime($soldAt)) : '-';

            $modelRarity = $sale['model_rarity'] ?? null;
            $rarityText = $modelRarity !== null ? " | رریتی مدل: <b>{$modelRarity}‰</b>" : "";

            $safeUrl = htmlspecialchars($nftUrl, ENT_QUOTES, 'UTF-8');
            $safeTgId = htmlspecialchars($tgId, ENT_QUOTES, 'UTF-8');
            $safeModel = htmlspecialchars($model, ENT_QUOTES, 'UTF-8');
            $safeBackdrop = htmlspecialchars($backdrop, ENT_QUOTES, 'UTF-8');
            $safeSymbol = htmlspecialchars($symbol, ENT_QUOTES, 'UTF-8');

            $msg .=
                "<tg-emoji emoji-id='5444856076954520455'>🧾</tg-emoji> <b>خرید #{$i}</b> ";

            if ($number && $nftUrl) {
                $msg .= "<a href='{$safeUrl}'>#{$number}</a>\n";
            } else {
                $msg .= "\n";
            }

            $msg .=
                "<tg-emoji emoji-id='5222444124698853913'>🔖</tg-emoji> <code>{$safeTgId}</code>\n" .
                "<tg-emoji emoji-id='5978776771623914876'>🟫</tg-emoji> مدل: <b>{$safeModel}</b>\n" .
                "<tg-emoji emoji-id='5431456208487716895'>🎨</tg-emoji> بک‌دراپ: <b>{$safeBackdrop}</b>\n" .
                "<tg-emoji emoji-id='5242354021125076937'>🔣</tg-emoji> سمبل: <b>{$safeSymbol}</b>\n" .
                "<tg-emoji emoji-id='5796188481986239685'>💎</tg-emoji> قیمت: <b>{$price} TON</b>\n" .
                "<tg-emoji emoji-id='5967357656174694472'>🇮🇷</tg-emoji> تومان: <b>" . number_format($price * $tonPrice) . "</b>\n" .
                "<tg-emoji emoji-id='5382194935057372936'>⏱️</tg-emoji> زمان: <b>{$soldTime}</b>{$rarityText}";

            if ($i < min(count($sales), 10)) {
                $msg .= "\n\n";
            }

            $i++;
        }

        $msg .= "</blockquote>\n";
    }

    $msg .= "\n\n$date | $time";

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => "HTML",
        'disable_web_page_preview' => true,
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "مشاهده مارکت", 'url' => $portalUrl, 'style' => 'success', 'icon_custom_emoji_id' => '5798802553701408501']],
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);

    exit;
}

#===============# قیمت استارز #===============#

if (preg_match('/^(\d+)?\s*استارز$/u', trim($text), $match)) {

    $count = !empty($match[1]) ? (int)$match[1] : 1;

    $stars_price = 0.015;

    $total_usdt = $count * $stars_price;

    $usdt_price = getBitpinPrice('USDT');

    $total_toman = number_format($total_usdt * $usdt_price);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "\xE2\x80\x8F╕<tg-emoji emoji-id='5798802553701408501'>⭐️</tg-emoji>$count استارز (STARS)
\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> $$total_usdt USDT
\xE2\x80\x8F╛ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> $total_toman تومان

$date - $time",
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

#===============# قیمت پرمیوم #===============#

if ($text == 'قیمت پرمیوم' or $text == 'پرمیوم') {

    $premiums = [
        3  => 11.99,
        6  => 15.99,
        12 => 28.99
    ];

    $usdt_price = getBitpinPrice('USDT');

    $get_api = curl("https://api.binance.com/api/v3/ticker/price?symbol=TONUSDT");

    $ton_price = (float)($get_api['price'] ?? 0);

    $msg = "◄ قیمت تلگرام پرمیوم:\n\n";

    foreach ($premiums as $month => $price_usdt) {

        $price_ton = number_format($price_usdt / $ton_price, 2);
        $price_toman = number_format($price_usdt * $usdt_price);

        $msg .= "\xE2\x80\x8F╕<tg-emoji emoji-id='4956669619982042309'>💎</tg-emoji> {$month} ماهه\n";
        $msg .= "\xE2\x80\x8F╡<tg-emoji emoji-id='5814462683067454974'>🪙</tg-emoji> {$price_ton} TON\n";
        $msg .= "\xE2\x80\x8F╡<tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> {$price_usdt} USDT\n";
        $msg .= "\xE2\x80\x8F╛<tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> {$price_toman} تومان\n\n";
    }

    $msg .= "$date - $time";

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "کانال ما", 'url' => "http://t.me/$channel", 'style' => 'primary', 'icon_custom_emoji_id' => '5424818078833715060']],
                [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
            ]
        ])
    ]);
}

#===============# تشخیص خودکار ولت TON / TRON #===============#
if ($text) {

    $text = trim($text);

    $is_tron = preg_match('/^T[a-zA-Z0-9]{33}$/', $text);

    $is_ton = preg_match('/^(UQ|EQ)[a-zA-Z0-9\-_]{46,}$/', $text);

    #===============# TRON Wallet #===============#

    if ($is_tron) {

        $wallet = $text;

        $response = file_get_contents(
            "https://apilist.tronscanapi.com/api/account?address=$wallet"
        );

        $data = json_decode($response, true);

        if (!isset($data['address'])) {
            exit;
        }

        $trx_balance = ($data['balance'] ?? 0) / 1000000;

        $msg = "<tg-emoji emoji-id='5814643080283821212'>🪙</tg-emoji> اطلاعات ولت TRON\n\n";

        $msg .= "<tg-emoji emoji-id='5881996022980286814'>🪙</tg-emoji> <code>$wallet</code>\n\n";

        $msg .= "<tg-emoji emoji-id='5792010685693041925'>💵</tg-emoji> موجودی TRX: " . number_format($trx_balance, 3) . "\n\n";

        if (!empty($data['tokens'])) {

            $msg .= "<tg-emoji emoji-id='5767179976516312812'>🪙</tg-emoji> توکن‌ها:\n\n";

            foreach ($data['tokens'] as $token) {

                $name = $token['tokenName'] ?? 'Unknown';

                $symbol = strtoupper($token['tokenAbbr'] ?? '---');

                $decimals = (int)($token['tokenDecimal'] ?? 6);

                $raw_balance = (float)($token['balance'] ?? 0);

                $amount = $raw_balance / pow(10, $decimals);

                if ($symbol == 'TRX') {

                    $trx_price_api = json_decode(
                        file_get_contents(
                            "https://api.binance.com/api/v3/ticker/price?symbol=TRXUSDT"
                        ),
                        true
                    );

                    $trx_price = (float)($trx_price_api['price'] ?? 0);

                    $usd = $amount * $trx_price;
                } else {

                    $usd = (float)($token['tokenValueInUsd'] ?? 0);
                }

                if ($amount <= 0) {
                    continue;
                }

                if ($usd <= 0 && $symbol != 'TRX') {
                    continue;
                }

                $msg .= "╕ ارز: $name ($symbol)\n";

                $msg .= "╡ تعداد: " . number_format($amount, 3) . "\n";

                $msg .= "╛ ارزش: $" . number_format($usd, 2) . "\n\n";
            }
        }

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id
        ]);
    }

    #===============# TON Wallet #===============#

    $is_ton = preg_match('/^(UQ|EQ)[a-zA-Z0-9\-_]{46,}$/', trim($text));

    if ($is_ton) {

        $wallet = trim($text);

        if ($wallet === '') {
            exit;
        }

        function fetch_json(string $url): array
        {
            $raw = @file_get_contents($url);
            $json = json_decode($raw ?: '', true);
            return is_array($json) ? $json : [];
        }

        function tg_escape(string $text): string
        {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        function clean_nft_slug(string $text): string
        {
            $text = trim($text);
            $text = preg_replace('/[^a-zA-Z0-9]+/', '', $text);
            return $text ?: 'NFT';
        }

        function format_tehran_datetime(int $timestamp): string
        {
            $dt = new DateTime('@' . $timestamp);
            $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
            return $dt->format('Y-m-d H:i:s');
        }

        function format_jalali_datetime(int $timestamp): string
        {
            $dt = new DateTime('@' . $timestamp);
            $dt->setTimezone(new DateTimeZone('Asia/Tehran'));

            if (function_exists('jdate')) {
                return jdate('Y/m/d | H:i:s', $timestamp);
            }

            if (class_exists('IntlDateFormatter')) {
                $fmt = new IntlDateFormatter(
                    'fa_IR@calendar=persian',
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::NONE,
                    'Asia/Tehran',
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
                );

                $date = $fmt->format($timestamp);
                if ($date !== false) {
                    return $date . ' | ' . $dt->format('H:i:s');
                }
            }

            return $dt->format('Y/m/d | H:i:s');
        }

        function is_telegram_username_nft(array $nft): bool
        {
            $meta = $nft['metadata'] ?? [];
            $name = trim($meta['name'] ?? '');
            $desc = trim($meta['description'] ?? '');
            $collection = trim($nft['collection']['name'] ?? '');

            if ($collection === 'Telegram Usernames') {
                return true;
            }

            if ($name !== '' && substr($name, 0, 1) === '@') {
                return true;
            }

            $desc_low = strtolower($desc);

            if (strpos($desc_low, 'username on telegram') !== false) {
                return true;
            }

            if (strpos($desc_low, 'telegram username') !== false) {
                return true;
            }

            if (strpos($desc_low, 'aliases:') !== false && strpos($desc_low, 't.me/') !== false) {
                return true;
            }

            return false;
        }

        function is_telegram_gift_nft(array $nft): bool
        {
            $meta = $nft['metadata'] ?? [];
            $collection_name = trim($nft['collection']['name'] ?? '');
            $collection_desc = strtolower(trim($nft['collection']['description'] ?? ''));
            $meta_desc = strtolower(trim($meta['description'] ?? ''));
            $image = trim($meta['image'] ?? '');
            $name = trim($meta['name'] ?? '');

            if ($collection_name === 'Telegram Usernames' || $collection_name === 'TON DNS Domains') {
                return false;
            }

            if (($nft['trust'] ?? '') !== 'whitelist' && empty($nft['verified'])) {
                return false;
            }

            if (strpos($image, '/gift/') !== false) {
                return true;
            }

            if (strpos($collection_desc, 'gifts section') !== false) {
                return true;
            }

            if (strpos($collection_desc, 'exclusive nft collection by telegram') !== false) {
                return true;
            }

            if (preg_match('/^(.*?)\s*#(\d+)$/u', $name)) {
                return true;
            }

            if (strpos($meta_desc, 'gift') !== false && strpos($meta_desc, 'telegram') !== false) {
                return true;
            }

            return false;
        }

        function build_gift_link(array $nft): ?string
        {
            $name = trim($nft['metadata']['name'] ?? '');
            $image = trim($nft['metadata']['image'] ?? '');

            if ($name === '') {
                return null;
            }

            if (preg_match('/^(.*?)\s*#(\d+)$/u', $name, $m)) {
                $slug = clean_nft_slug($m[1]);
                $id = $m[2];

                return "https://t.me/nft/{$slug}-{$id}";
            }

            if (preg_match('~/gift/([a-zA-Z0-9]+)-(\d+)\.webp~', $image, $m)) {
                $slug = clean_nft_slug($name);
                $id = $m[2];

                return "https://t.me/nft/{$slug}-{$id}";
            }

            return null;
        }

        $account = fetch_json("https://tonapi.io/v2/accounts/" . rawurlencode($wallet));
        $jettons = fetch_json("https://tonapi.io/v2/accounts/" . rawurlencode($wallet) . "/jettons");
        $nfts    = fetch_json("https://tonapi.io/v2/accounts/" . rawurlencode($wallet) . "/nfts?limit=100");
        $txs     = fetch_json("https://tonapi.io/v2/blockchain/accounts/" . rawurlencode($wallet) . "/transactions?limit=1&sort_order=desc");
        $binance = fetch_json("https://api.binance.com/api/v3/ticker/price?symbol=TONUSDT");

        if (!isset($account['balance'])) {
            exit;
        }

        $ton_price   = (float)($binance['price'] ?? 0);
        $ton_balance = ((float)$account['balance']) / 1000000000;
        $ton_usd     = $ton_balance * $ton_price;

        $usd_to_toman = 165000;
        $ton_toman    = $ton_usd * $usd_to_toman;

        $last_tx_time = null;
        if (!empty($txs['transactions'][0])) {
            $last_tx_time = (int)($txs['transactions'][0]['utime']
                ?? $txs['transactions'][0]['timestamp']
                ?? 0);

            if ($last_tx_time <= 0) {
                $last_tx_time = null;
            }
        }

        $telegram_usernames = [];
        $telegram_gifts = [];

        foreach (($nfts['nft_items'] ?? []) as $nft) {
            $meta = $nft['metadata'] ?? [];
            $name = trim($meta['name'] ?? '');

            if ($name === '') {
                continue;
            }

            if (is_telegram_username_nft($nft)) {
                $username = ltrim($name, '@');
                if ($username !== '') {
                    $telegram_usernames[] = '@' . $username;
                }
                continue;
            }

            if (is_telegram_gift_nft($nft)) {
                $link = build_gift_link($nft);
                if ($link) {
                    $telegram_gifts[] = [
                        'name' => $name,
                        'link' => $link,
                    ];
                }
            }
        }

        $telegram_usernames = array_values(array_unique($telegram_usernames));

        $unique_gifts = [];
        foreach ($telegram_gifts as $item) {
            $key = $item['name'] . '||' . $item['link'];
            $unique_gifts[$key] = $item;
        }
        $telegram_gifts = array_values($unique_gifts);

        $msg  = "<tg-emoji emoji-id='5814462683067454974'>🪙</tg-emoji> اطلاعات ولت TON شما به شرح زیر است:\n\n";
        $msg .= "<tg-emoji emoji-id='6001287064589439895'>🪙</tg-emoji> آدرس: <code>" . tg_escape($wallet) . "</code>\n";
        $msg .= "<tg-emoji emoji-id='5805460079427723173'>💰</tg-emoji> موجودی: " . number_format($ton_balance, 2) . " TON\n";
        $msg .= "<tg-emoji emoji-id='5792010685693041925'>💵</tg-emoji> معادل دلار: $" . number_format($ton_usd, 2) . "\n";
        $msg .= "<tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> معادل تومان: " . number_format($ton_toman, 0) . "\n";
        $msg .= "<tg-emoji emoji-id='5820994795748728088'>📆</tg-emoji> آخرین تراکنش: " . ($last_tx_time ? format_tehran_datetime($last_tx_time) : '-') . "\n\n";

        if (!empty($telegram_usernames)) {
            $msg .= "<tg-emoji emoji-id='5820914561464671642'>🆔</tg-emoji> Telegram Usernames\n";
            $msg .= "<blockquote>";
            foreach ($telegram_usernames as $username) {
                $msg .= tg_escape($username) . "\n";
            }
            $msg .= "</blockquote>\n\n";
        }

        if (!empty($telegram_gifts)) {
            $msg .= "<tg-emoji emoji-id='5821212988677299944'>🎁</tg-emoji> Telegram Gifts\n";
            $msg .= "<blockquote>";
            foreach ($telegram_gifts as $gift) {
                $msg .= '<a href="' . tg_escape($gift['link']) . '">' . tg_escape($gift['name']) . "</a>\n";
            }
            $msg .= "</blockquote>\n\n";
        }

        $msg .= "<tg-emoji emoji-id='5798770805303155969'>⏰</tg-emoji> " . format_jalali_datetime(time());

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $msg,
            'disable_web_page_preview' => true,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id
        ]);
    }
}

#===============# ماشین حساب #===============#

$calc_text = trim($text);

$calc_text = str_replace(
    ['×', '÷', '−', '–', ' '],
    ['*', '/', '-', '-', ''],
    $calc_text
);

$calc_text = tab_latin($calc_text);

if (
    preg_match('/^[0-9\.\+\-\*\/\(\)%]+$/', $calc_text) &&
    preg_match('/[\+\-\*\/%]/', $calc_text)
) {

    $result = 0;

    eval('$result = ' . $calc_text . ';');

    if (is_float($result)) {
        $result = round($result, 8);
    }

    $result = number_format($result);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🧮 $text = $result",
        'reply_to_message_id' => $message_id
    ]);
}

#===============# تبدیل هوشمند ارز / کریپتو / تومان / استارز #===============#

$norm = function ($s) {
    $s = tab_latin((string)$s);
    $s = strtolower($s);
    $s = str_replace(['ي', 'ك', 'ة', 'ۀ', '‌', '٫', '٬', ',', '،'], ['ی', 'ک', 'ه', 'ه', ' ', '.', '', '', ''], $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
};

$toFloat = function ($v) {
    $v = tab_latin((string)$v);
    $v = str_replace(['٫', '٬', ',', ' ', '$', 'تومان', 'تومن'], ['.', '', '', '', '', '', ''], $v);
    return (float)$v;
};

$fmt = function ($n, $p = 8) {
    if (function_exists('format_number')) {
        return format_number($n, $p);
    }

    $x = number_format((float)$n, $p, '.', ',');
    return rtrim(rtrim($x, '0'), '.');
};

$text_convert = $norm($text);

# ---------- ساخت لیست همه دارایی‌ها ----------
$assets = [];
$aliases = [];

$addAlias = function ($alias, $id) use (&$aliases, $norm) {
    $alias = $norm($alias);
    if ($alias !== '') {
        $aliases[$alias] = $id;
    }
};

$assets['toman:IRT'] = [
    'type' => 'toman',
    'code' => 'IRT',
    'fa' => 'تومان',
    'en' => 'Toman'
];

foreach (['تومان', 'تومن', 'تومنی', 'تومنه', 'irt', 'ریال تومان'] as $a) {
    $addAlias($a, 'toman:IRT');
}

$assets['stars:STARS'] = [
    'type' => 'stars',
    'code' => 'STARS',
    'fa' => 'استارز',
    'en' => 'Stars'
];

foreach (['استارز', 'استار', 'ستاره', 'stars', 'star', 'xtr', 'telegram stars'] as $a) {
    $addAlias($a, 'stars:STARS');
}

$extraCryptoAliases = [
    'BTC' => ['بیت کوین', 'بیتکوین', 'bitcoin'],
    'ETH' => ['اتریوم', 'اتر', 'ethereum'],
    'USDT' => ['تتر', 'دلار تتر', 'tether'],
    'TON' => ['تون', 'تون کوین', 'تن', 'تن کوین', 'toncoin'],
    'BNB' => ['بایننس', 'بایننس کوین'],
    'SOL' => ['سولانا'],
    'TRX' => ['ترون'],
    'DOGE' => ['دوج', 'دوج کوین'],
    'SHIB' => ['شیبا'],
    'XRP' => ['ریپل'],
    'ADA' => ['کاردانو'],
    'LTC' => ['لایت کوین'],
    'NOT' => ['نات', 'نات کوین'],
    'HMSTR' => ['همستر', 'همستر کامبت'],
];

foreach ($currencies_bitpin as $code => $names) {
    $code = strtoupper($code);
    $id = "crypto:$code";

    $assets[$id] = [
        'type' => 'crypto',
        'code' => $code,
        'fa' => $names[1],
        'en' => $names[0]
    ];

    $addAlias($code, $id);
    $addAlias($names[0], $id);
    $addAlias($names[1], $id);

    if (isset($extraCryptoAliases[$code])) {
        foreach ($extraCryptoAliases[$code] as $a) {
            $addAlias($a, $id);
        }
    }
}

$extraMoneyAliases = [
    'usd' => ['دلار', 'دلار آمریکا', 'دلار امریکا', 'dollar', 'us dollar'],
    'eur' => ['یورو', 'euro'],
    'gbp' => ['پوند', 'پوند انگلیس', 'pound'],
    'aed' => ['درهم', 'درهم امارات'],
    'try' => ['لیر', 'لیر ترکیه'],
    'cad' => ['دلار کانادا'],
    'aud' => ['دلار استرالیا'],
    'jpy' => ['ین', 'ین ژاپن'],
    'cny' => ['یوان', 'یوان چین'],
    'rub' => ['روبل'],
    'chf' => ['فرانک', 'فرانک سوئیس'],
];

foreach ($currencies_money as $code => $names) {
    $code = strtolower($code);
    $id = "money:$code";

    $assets[$id] = [
        'type' => 'money',
        'code' => $code,
        'fa' => $names[1],
        'en' => $names[0]
    ];

    $addAlias($code, $id);
    $addAlias(strtoupper($code), $id);
    $addAlias($names[0], $id);
    $addAlias($names[1], $id);

    if (isset($extraMoneyAliases[$code])) {
        foreach ($extraMoneyAliases[$code] as $a) {
            $addAlias($a, $id);
        }
    }
}

$aliasKeys = array_keys($aliases);

usort($aliasKeys, function ($a, $b) {
    return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
});

# ---------- تشخیص عددهای مثل ۳ میلیون و ۵۰۰ هزار ----------
$scales = [
    'هزار' => 1000,
    'کا' => 1000,
    'k' => 1000,

    'میلیون' => 1000000,
    'ملیون' => 1000000,
    'm' => 1000000,

    'میلیارد' => 1000000000,
    'ملیارد' => 1000000000,
    'b' => 1000000000,
];

$scaleKeys = array_keys($scales);

usort($scaleKeys, function ($a, $b) {
    return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
});

$scaleRegex = implode('|', array_map(function ($x) {
    return preg_quote($x, '/');
}, $scaleKeys));

$wordNums = [
    'یه' => 1,
    'یک' => 1,
    'دو' => 2,
    'سه' => 3,
    'چهار' => 4,
    'پنج' => 5,
    'شش' => 6,
    'شیش' => 6,
    'هفت' => 7,
    'هشت' => 8,
    'نه' => 9,
    'ده' => 10,
];

foreach ($wordNums as $w => $n) {
    $text_convert = preg_replace('/(^|\s)' . preg_quote($w, '/') . '(?=\s*(' . $scaleRegex . '))/u', '$1' . $n, $text_convert);
}

$amount = 1;
$rest = $text_convert;

if (preg_match('/^((?:\d+(?:\.\d+)?\s*(?:' . $scaleRegex . ')\s*(?:و\s*)?)+)(.*)$/u', $text_convert, $m)) {
    $amount = 0;

    preg_match_all('/(\d+(?:\.\d+)?)\s*(' . $scaleRegex . ')/u', $m[1], $items, PREG_SET_ORDER);

    foreach ($items as $item) {
        $amount += ((float)$item[1] * $scales[$item[2]]);
    }

    $rest = trim($m[2]);
} elseif (preg_match('/^(\d+(?:\.\d+)?)(?:\s*(' . $scaleRegex . '))?\s*(.*)$/u', $text_convert, $m)) {
    $amount = (float)$m[1];

    if (!empty($m[2]) && isset($scales[$m[2]])) {
        $amount *= $scales[$m[2]];
    }

    $rest = trim($m[3]);
}

# ---------- پیدا کردن مبدا و مقصد ----------
$findAsset = function ($s) use ($norm, $aliasKeys, $aliases) {
    $s = $norm($s);
    $s = preg_replace('/^(از|به|ب|to|from|into|in)\s+/u', '', $s);
    $s = trim($s);

    foreach ($aliasKeys as $alias) {
        $q = preg_quote($alias, '/');

        if (preg_match('/^' . $q . '(\s+|$)/u', $s)) {
            $rest = preg_replace('/^' . $q . '(\s+|$)?/u', '', $s, 1);

            return [
                'id' => $aliases[$alias],
                'rest' => trim($rest)
            ];
        }
    }

    return null;
};

$coin_from = null;
$to_id = null;

$rest = preg_replace('/\s*(->|=>|>|=|\/)\s*/u', ' به ', $rest);
$parts = preg_split('/\s+(به|to|into|in|ب)\s+/u', $rest, 2);

if (count($parts) == 2) {
    $a = $findAsset($parts[0]);
    $b = $findAsset($parts[1]);

    if ($a && $b && $a['rest'] == '' && $b['rest'] == '') {
        $coin_from = $a['id'];
        $to_id = $b['id'];
    }
}

if (!$coin_from || !$to_id) {
    $a = $findAsset($rest);

    if ($a) {
        $b = $findAsset($a['rest']);

        if ($b && $b['rest'] == '') {
            $coin_from = $a['id'];
            $to_id = $b['id'];
        }
    }
}

if ($coin_from && $to_id && isset($assets[$coin_from]) && isset($assets[$to_id])) {

    if ($message->chat->type == 'group' || $message->chat->type == 'supergroup') {
        if ($group_info['id'] != true) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "◄ لطفا اول با دستور «`نصب`» ربات رو در گروه نصب کنید!",
                'parse_mode' => "markdown"
            ]);
            return;
        }
    }

    $from = $assets[$coin_from];
    $to = $assets[$to_id];

    # ---------- گرفتن قیمت‌ها ----------
    $bitpinCache = [];
    $chandData = null;

    $getChand = function () use (&$chandData, $api, $timestamp) {
        if ($chandData === null) {
            $chandData = curl("$api/chand/?$timestamp");
        }

        return $chandData;
    };

    $getMoneyToman = function ($code) use ($getChand, $toFloat) {
        $data = $getChand();

        if (!isset($data['currencies'])) {
            return 0;
        }

        foreach ($data['currencies'] as $cur) {
            if (strtolower($cur['code']) == strtolower($code)) {
                return $toFloat($cur['price']);
            }
        }

        return 0;
    };

    $usdToman = $getMoneyToman('usd');

    $getUsdtToman = function () use (&$bitpinCache, $api, $toFloat, $usdToman) {
        if (!isset($bitpinCache['USDT'])) {
            $bitpinCache['USDT'] = curl("$api/bitpin/?type=USDT");
        }

        $p = isset($bitpinCache['USDT']['price_toman']) ? $toFloat($bitpinCache['USDT']['price_toman']) : 0;

        return $p > 0 ? $p : $usdToman;
    };

    $getPriceToman = function ($asset) use (&$bitpinCache, $api, $toFloat, $getMoneyToman, $getUsdtToman) {
        if ($asset['type'] == 'toman') {
            return 1;
        }

        if ($asset['type'] == 'stars') {
            return 0.015 * $getUsdtToman();
        }

        if ($asset['type'] == 'money') {
            return $getMoneyToman($asset['code']);
        }

        if ($asset['type'] == 'crypto') {
            $code = strtoupper($asset['code']);

            if (!isset($bitpinCache[$code])) {
                $bitpinCache[$code] = curl("$api/bitpin/?type=$code");
            }

            $data = $bitpinCache[$code];

            $toman = isset($data['price_toman']) ? $toFloat($data['price_toman']) : 0;
            $usdt = isset($data['price_usdt']) ? $toFloat($data['price_usdt']) : 0;

            if ($toman <= 0 && $usdt > 0) {
                $toman = $usdt * $getUsdtToman();
            }

            return $toman;
        }

        return 0;
    };

    $from_price_toman = $getPriceToman($from);
    $to_price_toman = $getPriceToman($to);

    if ($from_price_toman > 0 && $to_price_toman > 0) {

        $total_toman = $amount * $from_price_toman;
        $result = $total_toman / $to_price_toman;
        $rate = $from_price_toman / $to_price_toman;

        $usdt_toman = $getUsdtToman();
        $usd_value = $usdt_toman > 0 ? $total_toman / $usdt_toman : 0;

        $from_code_show = $from['type'] == 'money' ? strtoupper($from['code']) : $from['code'];
        $to_code_show = $to['type'] == 'money' ? strtoupper($to['code']) : $to['code'];

        $amount_show = $from['type'] == 'toman' ? number_format($amount) : $fmt($amount, 8);
        $result_show = $to['type'] == 'toman' ? number_format(round($result)) : $fmt($result, 8);
        $rate_show = $fmt($rate, 10);

        $from_label = htmlspecialchars($from['fa'] . " ($from_code_show)", ENT_QUOTES, 'UTF-8');
        $to_label = htmlspecialchars($to['fa'] . " ($to_code_show)", ENT_QUOTES, 'UTF-8');

        $msg = "\xE2\x80\x8F╕<tg-emoji emoji-id='5990147899403539264'>💱</tg-emoji> $amount_show $from_label";

        if ($usd_value > 0) {
            $msg .= "\n\xE2\x80\x8F╡ <tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> ≈ $" . $fmt($usd_value, 4) . " دلار";
        }

        if ($from['type'] != 'toman' && $to['type'] != 'toman') {
            $msg .= "\n\xE2\x80\x8F╡ <tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> ≈ " . number_format(round($total_toman)) . " تومان";
        }

        $msg .= "\n\xE2\x80\x8F╡ <tg-emoji emoji-id='5798802553701408501'>🔄</tg-emoji> $result_show $to_label";
        $msg .= "\n\xE2\x80\x8F╛ <tg-emoji emoji-id='5805590199756922614'>💱</tg-emoji> نرخ: 1 $from_code_show = $rate_show $to_code_show";
        $msg .= "\n\n$date | $time";

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "اضافه کردن به گروه", 'url' => "http://t.me/$usernamebot?startgroup=new", 'style' => 'primary', 'icon_custom_emoji_id' => '5397916757333654639']],
                    [['text' => "$from_code_show → $to_code_show", 'callback_data' => "none", 'style' => 'primary', 'icon_custom_emoji_id' => '5402186569006210455']]
                ]
            ], JSON_UNESCAPED_UNICODE)
        ]);

        return;
    }
}

#===============# Time / Date #===============#

if ($text == 'time' || $text == 'date' || $text == 'time/date' || $text == 'زمان' || $text == 'تاریخ') {

    date_default_timezone_set('Asia/Tehran');

    $iran_time = date('H:i');

    $europe = new DateTime('now', new DateTimeZone('Europe/Berlin'));

    $europe_time = $europe->format('H:i');

    $fa_days = [
        'Saturday' => 'شنبه',
        'Sunday' => 'یکشنبه',
        'Monday' => 'دوشنبه',
        'Tuesday' => 'سه‌شنبه',
        'Wednesday' => 'چهارشنبه',
        'Thursday' => 'پنجشنبه',
        'Friday' => 'جمعه'
    ];

    $en_day = date('l');

    $fa_day = $fa_days[$en_day] ?? $en_day;

    $fa_date_text = jdate('Y/m/d');
    $fa_date_full = "$fa_day - " . jdate('j F Y');

    $en_date_1 = date('Y d F');
    $en_date_2 = date('d.m.Y');

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<tg-emoji emoji-id='5413704112220949842'>⏰</tg-emoji> <b>Time / Date</b>

<tg-emoji emoji-id='4992380855709074224'>🇮🇷</tg-emoji> Iran  <code>$iran_time</code>
<tg-emoji emoji-id='4992280855985521360'>🇺🇸</tg-emoji> Europe <code>$europe_time</code>

<tg-emoji emoji-id='5820994795748728088'>📆</tg-emoji> $fa_date_full | $fa_date_text
<tg-emoji emoji-id='5820994795748728088'>📆</tg-emoji> $en_day - $en_date_1 | $en_date_2",
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🇮🇷 $iran_time", 'callback_data' => 'none', 'style' => 'primary'],
                    ['text' => "🇪🇺 $europe_time", 'callback_data' => 'none', 'style' => 'primary']
                ]
            ]
        ])
    ]);
}

//===========================// Admin Panel //===========================//
if (($text == '/panel' || $text == '👤 پنل مدیریت 👤' || $text == 'برگشت 🔙') && $tc == 'private' && $admin['admin'] == $from_id) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "👋 ادمین عزیز به پنل مدیریت ربات خوش آمدید.",
        'reply_markup' => $admin_panel
    ]);
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    exit;
}
//===========================// Admin Section //===========================//
if (($text == "👤 بخش کاربران") && $admin['admin'] == $from_id && $tc = "private") {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "❕️ به بخش مدیریت کاربران خوش آمدید

لطفا از بین گزینه های زیر انتخاب کنید.",
        'reply_markup' => $user_section
    ]);
}

if (($text == "💬 بخش ارسال") && $admin['admin'] == $from_id && $tc = "private") {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "❕️ به بخش مدیریت پیام های همگانی خوش آمدید

لطفا از بین گزینه های زیر انتخاب کنید.",
        'reply_markup' => $send_section
    ]);
}

if (($text == "🤖 تنظیمات ربات") && $admin['admin'] == $from_id && $tc = "private") {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "❕️ به بخش مدیریت ربات خوش آمدید

لطفا از بین گزینه های زیر انتخاب کنید.",
        'reply_markup' => $setting_section
    ]);
}
//====================// آمار ربات //====================//
if ($text == '📊 آمار ربات 📊' and $tc == 'private' and $admin['admin'] == $from_id) {
    $alluser = number_format(mysqli_num_rows(mysqli_query($connect, "select `id` from `user`")));
    $allblock = number_format(mysqli_num_rows(mysqli_query($connect, "select `id` from `block`")));
    $load = sys_getloadavg();

    $serverIP = gethostbyname(gethostname());
    $starttime = microtime(true);
    $socket = fsockopen($serverIP, 80, $errno, $errstr, 10);
    $stoptime  = microtime(true);
    $status    = 0;
    if (!$socket) {
        $status = -1;
    } else {
        fclose($socket);
        $server_ping = ($stoptime - $starttime) * 1000;
        $server_ping = round($server_ping, 2);
    }
    $mem = number_format(memory_get_usage());
    $ver = phpversion();
    $ip_add = $_SERVER['SERVER_ADDR'];
    $domain = $_SERVER['SERVER_NAME'];

    $onlineUsers = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `update_at` > $timestamp - 60")) ?: 0);
    $onlineUsers1 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `update_at` > $timestamp - 86400")) ?: 0);
    $onlineUsers2 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `update_at` > $timestamp - 604800")) ?: 0);
    $onlineUsers3 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `update_at` > $timestamp - 2592000")) ?: 0);
    $hourlyUsers = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `create_at` > $timestamp - 3600")) ?: 0);
    $dailyUsers = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `create_at` > $timestamp - 86400")) ?: 0);
    $weeklyUsers = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `create_at` > $timestamp - 604800")) ?: 0);
    $monthlyUsers = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `user` WHERE `create_at` > $timestamp - 2592000")) ?: 0);

    $onlineGroup = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `update_at` > $timestamp - 60")) ?: 0);
    $onlineGroup1 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `update_at` > $timestamp - 86400")) ?: 0);
    $onlineGroup2 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `update_at` > $timestamp - 604800")) ?: 0);
    $onlineGroup3 = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `update_at` > $timestamp - 2592000")) ?: 0);
    $hourlyGroup = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `join_at` > $timestamp - 3600")) ?: 0);
    $dailyGroup = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `join_at` > $timestamp - 86400")) ?: 0);
    $weeklyGroup = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `join_at` > $timestamp - 604800")) ?: 0);
    $monthlyGroup = number_format(mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `group` WHERE `join_at` > $timestamp - 2592000")) ?: 0);

    $allgroup = number_format(mysqli_num_rows(mysqli_query($connect, "select `id` from `group`")));
    $allgroup_mem = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(`member`) as total_members FROM `group`"));
    $allgroup_mem = number_format($allgroup_mem['total_members']);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📊 آمار ربات شما به شرح زیر است:

━━━━━━━━━━━━━━━━━
👥 <b>تعداد کاربران :</b> <code>$alluser</code>
⛔️ <b>کاربران مسدود :</b> <code>$allblock</code>
        
💎 <b>فعالیت کاربران ربات شما به شرح زیر میباشد</b> 👇
        
🟢 <b>کاربران آنلاین :</b> <code>$onlineUsers</code> 
🕛 <b>24 ساعت گذشته :</b> <code>$onlineUsers1</code> 
📅 <b>7 روز گذشته :</b> <code>$onlineUsers2</code> 
🗓 <b>31 روز گذشته :</b> <code>$onlineUsers3</code>
        
💎 <b>آمار کاربران جدید ربات شما به شرح زیر میباشد</b> 👇
        
🕛 <b>24 ساعت گذشته:</b> <code>$dailyUsers</code> 
📅 <b>7 روز گذشته:</b> <code>$weeklyUsers</code>
🗓 <b>31 روز گذشته:</b> <code>$monthlyUsers</code>
━━━━━━━━━━━━━━━━━
⭐️ <b>تعداد گروه‌ها :</b> <code>$allgroup</code>
👥 <b>اعضای گروه‌ها :</b> <code>$allgroup_mem</code>

💎 <b>فعالیت گروه های ربات شما به شرح زیر میباشد</b> 👇
        
🟢 <b>گروه های آنلاین :</b> <code>$onlineGroup</code> 
🕛 <b>24 ساعت گذشته :</b> <code>$onlineGroup1</code> 
📅 <b>7 روز گذشته :</b> <code>$onlineGroup2</code> 
🗓 <b>31 روز گذشته :</b> <code>$onlineGroup3</code>
        
💎 <b>آمار گروه های جدید ربات شما به شرح زیر میباشد</b> 👇
        
🕛 <b>24 ساعت گذشته:</b> <code>$dailyGroup</code> 
📅 <b>7 روز گذشته:</b> <code>$weeklyGroup</code>
🗓 <b>31 روز گذشته:</b> <code>$monthlyGroup</code>
━━━━━━━━━━━━━━━━━
📶 <b>Server Ping :</b> <code>$server_ping</code>
🎛 <b>LoadAvg :</b> <code>$load[0]</code>
        
📍 <b>IP Address :</b> <code>$ip_add</code>
🗂 <b>Memory Usage :</b> <code>$mem</code>
        
💯 <b>PHP Version :</b> <code>$ver</code>
━━━━━━━━━━━━━━━━━
        
🌐 <b>Domain :</b> <code>$domain</code>",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📅 : $date", 'callback_data' => "none"], ['text' => "🗓 : $ToDay", 'callback_data' => "none"], ['text' => "⏰ : $time", 'callback_data' => "none"]],
            ]
        ])
    ]);
}
//===================// مدیریت قفل ها //===================//
if ($text == "📣 مدیریت قفل ها") {
    if ($admin['admin'] == $from_id) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❗️ به بخش تنظیم چنل های قفل خوش آمدید.

💯 برای حذف چنل، از بخش لیست چنل چنل مورد نظر را حذف کنید .",
            'parse_mode' => "HTML",
            'reply_markup' => $manage_channel
        ]);
    }
}
//=====// افزودن چنل //=====//
if ($text == "➕ افزودن چنل") {
    if ($admin['admin'] == $from_id) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "لطفا نوع چنلی که میخواهید اضافه کنید را از کیبورد انتخاب کنید :",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'keyboard' => [
                    [['text' => "عمومی"], ['text' => "خصوصی"]],
                    [['text' => "👤 پنل مدیریت 👤"]],
                ],
                'resize_keyboard' => true
            ])
        ]);
        $connect->query("UPDATE user SET step = 'addch1' WHERE id = '$from_id' LIMIT 1");
    }
}
//=====// چنل عمومی //=====//
if ($text == "عمومی" && $user['step'] == "addch1") {
    if ($admin['admin'] == $from_id) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "لطفا یوزرنیم چنل عمومی را بدون @ ارسال کنید ( ربات را قبل ارسال بر ان چنل آدمین کرده باشید )",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'keyboard' => [
                    [['text' => "👤 پنل مدیریت 👤"]],
                ],
                'resize_keyboard' => true
            ])
        ]);
        $connect->query("UPDATE user SET step = 'addchpub' WHERE id = '$from_id' LIMIT 1");
    }
}
if ($user['step'] == "addchpub" && $text != "👤 پنل مدیریت 👤") {
    if ($admin['admin'] == $from_id) {
        $textt = str_replace("@", '', $text);
        $texttt = "@" . $textt;
        $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$texttt' LIMIT 1"));
        if ($ch['link'] == null) {
            $admini = getChatstats("@$textt", API_KEY);
            if ($admini == true) {
                $linkk = "https://t.me/$textt";
                $connect->query("INSERT INTO channels (idoruser , link) VALUES ('$texttt', '$linkk')");
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "چنل @$textt با موفقیت افزوده شد .",
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [['text' => "➕ افزودن چنل"]],
                            [['text' => "👤 پنل مدیریت 👤"], ['text' => "📚 لیست چنل ها"]],
                        ],
                        'resize_keyboard' => true
                    ])
                ]);
                $connect->query("UPDATE user SET step = 'addch1' WHERE id = '$from_id' LIMIT 1");
            } else {
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "خطا ! ربات بر چنل @$textt آدمین نیست !

ابتدا ربات را ادمین و سپس ارسال کنید تا افزوده شود.",
                    'parse_mode' => "HTML",
                ]);
            }
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "خطا ! قبلا چنلی با این ایدی ثبت شده !

لطفا دوباره ارسال فرمایید :",
                'parse_mode' => "HTML",
            ]);
        }
    }
}
//=====// چنل خصوصی //=====//
if ($text == "خصوصی" && $user['step'] == "addch1") {
    if ($admin['admin'] == $from_id) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "لطفا آیدی عددی چنل خصوصی را ارسال کنید .
نمونه ایدی عددی چنل : 
-1009876262727
ربات را قبل ارسال حتما ادمین کرده باشید.",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'keyboard' => [
                    [['text' => "👤 پنل مدیریت 👤"]],
                ],
                'resize_keyboard' => true
            ])
        ]);
        $connect->query("UPDATE user SET step = 'addcpr' WHERE id = '$from_id' LIMIT 1");
    }
}

if ($user['step'] == "addcpr" && $text != "👤 پنل مدیریت 👤" && !$data) {
    if ($admin['admin'] == $from_id) {
        $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$text' LIMIT 1"));
        if ($ch['link'] == null) {
            $admini = getChatstats($text, API_KEY);
            if (strpos($text, "-100") !== false && $admini == true) {
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "لطفا لینک خصوصی دعوت را ارسال کنید :",
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [['text' => "👤 پنل مدیریت 👤"]],
                        ],
                        'resize_keyboard' => true
                    ])
                ]);
                $connect->query("UPDATE user SET data = '$text' WHERE id = '$from_id' LIMIT 1");
                $connect->query("UPDATE user SET step = 'addchpr1' WHERE id = '$from_id' LIMIT 1");
            } else {
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "خطا ! ربات بر چنل $text آدمین نیست و یا ایدی ارسالی حاوی -100 نیست.

ابتدا ربات را ادمین و سپس ارسال کنید تا افزوده شود.",
                    'parse_mode' => "HTML",
                ]);
            }
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "خطا ! قبلا چنلی با این ایدی ثبت شده !

لطفا دوباره ارسال فرمایید :",
                'parse_mode' => "HTML",
            ]);
        }
    }
}

if ($user['step'] == "addchpr1" && $text != "👤 پنل مدیریت 👤" && !$data) {
    if ($admin['admin'] == $from_id) {
        if (strpos($text, "://t.me/") !== false) {
            $idus = $user['data'];
            $connect->query("INSERT INTO channels (idoruser , link) VALUES ('$idus', '$text')");
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "چنل با موفقیت افزوده شد .",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [['text' => "➕ افزودن چنل"]],
                        [['text' => "👤 پنل مدیریت 👤"], ['text' => "📚 لیست چنل ها"]],
                    ],
                    'resize_keyboard' => true
                ])
            ]);
            $connect->query("UPDATE user SET step = 'addch1' WHERE id = '$from_id' LIMIT 1");
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "خطا! لینک ارسالی اشتباه است !

لطفا دوباره ارسال کنید:",
                'parse_mode' => "HTML",
            ]);
        }
    }
}

//=====// لیست چنل ها //=====//

if ($text == "📚 لیست چنل ها") {
    if ($admin['admin'] == $from_id) {
        $chs = mysqli_query($connect, "select idoruser from channels");
        $fil = mysqli_num_rows($chs);
        if ($fil != 0) {
            while ($row = mysqli_fetch_assoc($chs)) {
                $ar[] = $row["idoruser"];
            }
            for ($i = 0; $i <= $fil; $i++) {

                $by = $i + 1;
                $okk = $ar[$i];
                $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$okk' LIMIT 1"));
                $link = $ch['link'];
                if ($link != null) {
                    $d4[] = [['text' => "چنل شماره $by", 'url' => $link], ['text' => "❌ حذف", 'callback_data' => "delc_$okk"]];
                }
            }
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "👇🏻 لیست تمام چنل های قفل",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $d4
                ])
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ هیچ چنل قفلی تنظیم نشده.",
                'parse_mode' => "HTML",
            ]);
        }
    }
}


if (strpos($data, "delc_") === 0) {
    if ($admin['admin'] == $from_id) {
        $ok = str_replace("delc_", '', $data);
        $chs = mysqli_query($connect, "select idoruser from channels");
        $fil = mysqli_num_rows($chs);
        if ($fil == 1) {
            $connect->query("DELETE FROM channels WHERE idoruser = '$ok'");
            bot('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "👇🏻 لیست تمام چنل های قفل

❌ تمام چنل ها حذف شده است.",
                'parse_mode' => "HTML",
            ]);
            bot('answercallbackquery', [
                'callback_query_id' => $update->callback_query->id,
                'text' => "✅ چنل حذف شد .",
                'show_alert' => false
            ]);
        } else {
            $connect->query("DELETE FROM channels WHERE idoruser = '$ok'");
            $chs = mysqli_query($connect, "select idoruser from channels");
            $fil = mysqli_num_rows($chs);
            while ($row = mysqli_fetch_assoc($chs)) {
                $ar[] = $row["idoruser"];
            }
            for ($i = 0; $i <= $fil; $i++) {

                $by = $i + 1;
                $okk = $ar[$i];
                $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM channels WHERE idoruser = '$okk' LIMIT 1"));
                $link = $ch['link'];
                if ($link != null) {
                    $d4[] = [['text' => "چنل شماره $by", 'url' => $link], ['text' => "❌ حذف", 'callback_data' => "delc_$okk"]];
                }
            }
            bot('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "👇🏻 لیست تمام چنل های قفل

❌ چنل حذف شد .",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $d4
                ])
            ]);
            bot('answercallbackquery', [
                'callback_query_id' => $update->callback_query->id,
                'text' => "✅ چنل حذف شد .",
                'show_alert' => false
            ]);
        }
    }
}
//===================// مدیریت ادمین ها //===================//
if ($text == "👤 مدیریت ادمین ها" and $tc == 'private' and ($admin['admin'] == $from_id)) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "❗️ به بخش تنظیم ادمین خوش آمدید.

💯 برای حذف ادمین، از بخش لیست ادمین . ادمین مورد نظر را حذف کنید .",
        'parse_mode' => "HTML",
        'reply_markup' => $manage_admin
    ]);
}
//=====// افزودن ادمین //=====//
if ($text == "➕ افزودن ادمین"  and $tc == 'private' and ($admin['admin'] == $creator)) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "لطفا آیدی عددی فرد موردنظر را وارد نمایید ✅",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'keyboard' => [
                [['text' => "👤 پنل مدیریت 👤"]],
            ],
            'resize_keyboard' => true,
            'input_field_placeholder' => "$from_id"

        ])
    ]);
    $connect->query("UPDATE user SET step = 'addadmin' WHERE id = '$from_id' LIMIT 1");
}
if ($user['step'] == "addadmin" && $text != "👤 پنل مدیریت 👤"  and $tc == 'private' and ($admin['admin'] == $from_id)) {
    $ad = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM admin WHERE admin = '$text' LIMIT 1"));
    if ($ad['admin'] == null) {
        $connect->query("INSERT INTO admin (admin) VALUES ('$text')");
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "کاربر $text با موفقیت افزوده شد .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'keyboard' => [
                    [['text' => "➕ افزودن ادمین"]],
                    [['text' => "👤 پنل مدیریت 👤"], ['text' => "📚 لیست ادمین ها"]],
                ],
                'resize_keyboard' => true,
                'input_field_placeholder' => "$from_id"

            ])
        ]);
        $connect->query("UPDATE user SET step = 'none' WHERE id = '$from_id' LIMIT 1");
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "ایدی عددی کاربر <code>$text</code> در لیست ادمین ها وجود دارد",
            'parse_mode' => "HTML",
        ]);
        $connect->query("UPDATE user SET step = 'none' WHERE id = '$from_id' LIMIT 1");
    }
}
//=====// لیست ادمین ها //=====//
if ($text == '📚 لیست ادمین ها' and $tc == 'private' and ($admin['admin'] == $from_id)) {
    $chs = mysqli_query($connect, "select admin from admin");
    $fil = mysqli_num_rows($chs);
    if ($fil != 0) {
        while ($row = mysqli_fetch_assoc($chs)) {
            $ar[] = $row["admin"];
        }
        for ($i = 0; $i <= $fil; $i++) {

            $by = $i + 1;
            $okk = $ar[$i];
            $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM admin WHERE admin = '$okk' LIMIT 1"));
            $link = $ch['admin'];
            if ($link != null) {
                $d4[] = [['text' => "$link", 'callback_data' => 'ok'], ['text' => "❌ حذف", 'callback_data' => "delad_$okk"]];
            }
        }
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👇🏻 لیست تمام ادمین ها",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => $d4
            ])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ هیچ ادمینی  تنظیم نشده.",
            'parse_mode' => "HTML",
        ]);
    }
}


if (strpos($data, "delad_") === 0 and $from_id == $creator) {
    $ok = str_replace("delad_", '', $data);
    $chs = mysqli_query($connect, "select admin from admin");
    $fil = mysqli_num_rows($chs);
    if ($fil == 1) {
        $connect->query("DELETE FROM admin WHERE admin = '$ok'");
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "👇🏻 لیست تمام ادمین های 

❌ تمام ادمین ها حذف شده است.",
            'parse_mode' => "HTML",
        ]);
        bot('answercallbackquery', [
            'callback_query_id' => $update->callback_query->id,
            'text' => "✅ ادمین حذف شد.",
            'show_alert' => false
        ]);
    } else {
        $connect->query("DELETE FROM admin WHERE admin = '$ok'");
        $chs = mysqli_query($connect, "select admin from admin");
        $fil = mysqli_num_rows($chs);
        while ($row = mysqli_fetch_assoc($chs)) {
            $ar[] = $row["admin"];
        }
        for ($i = 0; $i <= $fil; $i++) {

            $by = $i + 1;
            $okk = $ar[$i];
            $ch = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM admin WHERE admin = '$okk' LIMIT 1"));
            $link = $ch['admin'];
            if ($link != null) {
                $d4[] = [['text' => "$link", 'callback_data' => 'ior'], ['text' => "❌ حذف", 'callback_data' => "delad_" . $okk . ""]];
            }
        }
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "👇🏻 لیست تمام ادمین ها

❌ ادمین حذف شد .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => $d4,
            ])
        ]);
        bot('answercallbackquery', [
            'callback_query_id' => $update->callback_query->id,
            'text' => "✅ ادمین حذف شد .",
            'show_alert' => false
        ]);
    }
}
//===================// بخش همگانی //===================//
if ($text == '💬 پیام همگانی' and $tc == 'private' and $admin['admin'] == $from_id) {
    if ($send['admin'] == null) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👨🏻‍💻 لطفا متن یا رسانه خود را ارسال کنید [میتواند شامل عکس باشد]  همچنین میتوانید رسانه را همراه با کپشن [متن چسبیده به رسانه ارسال کنید]",
            'reply_markup' => $backpanel
        ]);
        $connect->query("UPDATE `user` SET `step` = 'sendtoall' WHERE `id` = '$from_id' LIMIT 1");
    } else {
        $tddd = $send['sended'];
        $users = mysqli_query($connect, "select id from `user`");
        $fil = mysqli_num_rows($users);
        $tfrigh = $fil - $tddd;
        $min = Takhmin($tfrigh);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ خطا برای انجام عملیات همگانی

ادمین زیر اقدام به همگانی کرده و هنوز همگانی به اتمام نرسیده ، لطفا تا پایان همگانی قبلی صبر کنید .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "👤 {$send['sended']}", 'callback_data' => "none"]],
                    [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                    [['text' => "🔸 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
                ]
            ])
        ]);
    }
}

if ($user['step'] == 'sendtoall') {
    $photo = $message->photo[count($message->photo) - 1]->file_id;
    $caption = $update->message->caption;
    $users = mysqli_query($connect, "select id from `user`");
    $fil = mysqli_num_rows($users);
    $min = Takhmin($fil);
    $tddd = 0;

    $id = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📣 <i>پیام به صف ارسال قرار گرفت !</i>

✅ <b>بعد از اتمام ارسال، به شما اطلاع داده میشود.</b>

👥 تعداد اعضای ربات: <code>$fil</code> نفر

🔹 تعداد افراد ارسال شده در دکمه شیشه ای زیر، قابل مشاهده است ( خودکار ادیت میشود )",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
            ]
        ])
    ])->result;
    $msgid22 = $id->message_id;
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    $connect->query("UPDATE `sendall` SET `step` = 'send' , `admin` = '$from_id' , `messageid` = '$msgid22' , `text` = '$text$caption' , `chat` = '$photo' LIMIT 1");
}

if ($text == '↗️ فوروارد همگانی' and $tc == 'private' and $admin['admin'] == $from_id) {
    if ($send['admin'] == null) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👨🏻‍💻 لطفا پیام خود را فوروارد کنید [پیام فوروارد شده میتوانید از شخص یا کانال باشد]",
            'reply_markup' => $backpanel
        ]);
        $connect->query("UPDATE `user` SET `step` = 'fortoall' WHERE `id` = '$from_id' LIMIT 1");
    } else {
        $tddd = $send['sended'];
        $users = mysqli_query($connect, "select id from `user`");
        $fil = mysqli_num_rows($users);
        $tfrigh = $fil - $tddd;
        $min = Takhmin($tfrigh);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ خطا برای انجام عملیات همگانی

ادمین زیر اقدام به همگانی کرده و هنوز همگانی به اتمام نرسیده ، لطفا تا پایان همگانی قبلی صبر کنید .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "👤 {$send['sended']}", 'callback_data' => "none"]],
                    [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                    [['text' => "🔸 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
                ]
            ])
        ]);
    }
}

if ($user['step'] == 'fortoall') {
    $users = mysqli_query($connect, "select id from `user`");
    $fil = mysqli_num_rows($users);
    $min = Takhmin($fil);
    $tddd = 0;

    $id = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📣 <i>پیام به صف فوروارد قرار گرفت !</i>

✅ <b>بعد از اتمام فوروارد، به شما اطلاع داده میشود.</b>
        
👥 تعداد اعضای ربات: <code>$fil</code> نفر

🔹 تعداد افراد ارسال شده در دکمه شیشه ای زیر، قابل مشاهده است ( خودکار ادیت میشود )",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
            ]
        ])
    ])->result;
    $msgid22 = $id->message_id;
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    $connect->query("UPDATE `sendall` SET `step` = 'forward' , `admin` = '$from_id' , `messageid` = '$msgid22' , `text` = '$message_id' , `chat` = '$chat_id' LIMIT 1");
}

if ($text == '💬 پیام گروه' and $tc == 'private' and $admin['admin'] == $from_id) {
    if ($send['admin'] == null) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👨🏻‍💻 لطفا متن یا رسانه خود را ارسال کنید [میتواند شامل عکس باشد]  همچنین میتوانید رسانه را همراه با کپشن [متن چسبیده به رسانه ارسال کنید]",
            'reply_markup' => $backpanel
        ]);
        $connect->query("UPDATE `user` SET `step` = 'sendtoall_g' WHERE `id` = '$from_id' LIMIT 1");
    } else {
        $tddd = $send['sended'];
        $groups = mysqli_query($connect, "select id from `group`");
        $fil = mysqli_num_rows($groups);
        $tfrigh = $fil - $tddd;
        $min = Takhmin($tfrigh);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ خطا برای انجام عملیات همگانی

ادمین زیر اقدام به همگانی کرده و هنوز همگانی به اتمام نرسیده ، لطفا تا پایان همگانی قبلی صبر کنید .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "👤 {$send['sended']}", 'callback_data' => "none"]],
                    [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                    [['text' => "🔸 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
                ]
            ])
        ]);
    }
}

if ($user['step'] == 'sendtoall_g') {
    $photo = $message->photo[count($message->photo) - 1]->file_id;
    $caption = $update->message->caption;
    $groups = mysqli_query($connect, "select id from `group`");
    $fil = mysqli_num_rows($groups);
    $min = Takhmin($fil);
    $tddd = 0;

    $id = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📣 <i>پیام به صف ارسال قرار گرفت !</i>
    
    ✅ <b>بعد از اتمام ارسال، به شما اطلاع داده میشود.</b>
    
    👥 تعداد گروه‌های ربات: <code>$fil</code>
    
    🔹 تعداد گروه‌های ارسال شده در دکمه شیشه ای زیر، قابل مشاهده است ( خودکار ادیت میشود )",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔹 تعداد گروه ارسال شده : $tddd", 'callback_data' => "none"]],
                [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
            ]
        ])
    ])->result;
    $msgid22 = $id->message_id;
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    $connect->query("UPDATE `sendall` SET `step` = 'send_g' , `admin` = '$from_id' , `messageid` = '$msgid22' , `text` = '$text$caption' , `chat` = '$photo' LIMIT 1");
}

if ($text == '↗️ فوروارد گروه' and $tc == 'private' and $admin['admin'] == $from_id) {
    if ($send['admin'] == null) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👨🏻‍💻 لطفا پیام خود را فوروارد کنید [پیام فوروارد شده میتوانید از شخص یا کانال باشد]",
            'reply_markup' => $backpanel
        ]);
        $connect->query("UPDATE `user` SET `step` = 'fortoall_g' WHERE `id` = '$from_id' LIMIT 1");
    } else {
        $tddd = $send['sended'];
        $groups = mysqli_query($connect, "select id from `group`");
        $fil = mysqli_num_rows($groups);
        $tfrigh = $fil - $tddd;
        $min = Takhmin($tfrigh);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ خطا برای انجام عملیات همگانی

ادمین زیر اقدام به همگانی کرده و هنوز همگانی به اتمام نرسیده ، لطفا تا پایان همگانی قبلی صبر کنید .",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "👤 {$send['sended']}", 'callback_data' => "none"]],
                    [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
                    [['text' => "🔸 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
                ]
            ])
        ]);
    }
}

if ($user['step'] == 'fortoall_g') {
    $groups = mysqli_query($connect, "SELECT `id` FROM `group`");
    $fil = mysqli_num_rows($groups);
    $min = Takhmin($fil);
    $tddd = 0;

    $id = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📣 <i>پیام به صف فوروارد قرار گرفت !</i>
    
    ✅ <b>بعد از اتمام فوروارد، به شما اطلاع داده میشود.</b>
        
    👥 تعداد گروه‌های ربات: <code>$fil</code>
    
    🔹 تعداد گروه‌های ارسال شده در دکمه شیشه ای زیر، قابل مشاهده است ( خودکار ادیت میشود )",
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔹 تعداد گروه ارسال شده : $tddd", 'callback_data' => "none"]],
                [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
            ]
        ])
    ])->result;
    $msgid22 = $id->message_id;
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
    $connect->query("UPDATE `sendall` SET `step` = 'forward_g' , `admin` = '$from_id' , `messageid` = '$msgid22' , `text` = '$message_id' , `chat` = '$chat_id' LIMIT 1");
}

//===================// بلاک آنبلاک //===================//
if ($text == '⚠️ مسدود کردن' and $tc == 'private' and $admin['admin'] == $from_id) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "👨🏻‍💻 لطفا شناسه کاربری فرد را ارسال کنید",
        'reply_markup' => $backpanel
    ]);
    $connect->query("UPDATE `user` SET `step` = 'block' WHERE `id` = '$from_id' LIMIT 1");
}

if ($user['step'] == 'block' && $tc == 'private') {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "✅ فرد با موفقیت مسدود شد",
    ]);
    $connect->query("INSERT INTO `block` (`id`) VALUES ('$text')");
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
}

if ($text == '❌ حذف مسدودیت' and $tc == 'private' and $admin['admin'] == $from_id) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "👨🏻‍💻 لطفا شناسه کاربری فرد را ارسال کنید",
        'reply_markup' => $backpanel
    ]);
    $connect->query("UPDATE `user` SET `step` = 'unblock' WHERE `id` = '$from_id' LIMIT 1");
}

if ($user['step'] == 'unblock' && $tc == 'private') {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "✅ فرد با موفقیت لغو مسدود شد",
    ]);
    $connect->query("DELETE FROM `block` WHERE `id` = '$text'");
    $connect->query("UPDATE `user` SET `step` = 'none' WHERE `id` = '$from_id' LIMIT 1");
}

$connect->close();
