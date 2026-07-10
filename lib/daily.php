<?php
//======================// include //======================//
// include '../bot.php';
include '../config.php';
//=======================// Variables //=======================//
$send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
//=======================// Function //=======================//
function bot($method, $datas = [])
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . API_KEY . '/' . $method);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
  return json_decode(curl_exec($ch));
}
function Takhmin2($fil)
{
  if ($fil <= 400) {
    return "1";
  } else {
    $besanie = $fil / 400;
    return ceil($besanie) + 1;
  }
}
//======================// send //======================//
if ($send['step'] == 'send') {
  $alluser = mysqli_num_rows(mysqli_query($connect, "select id from `user`"));
  $users = mysqli_query($connect, "SELECT id FROM `user` LIMIT 400 OFFSET {$send['sended']}");
  $count = 0;
  if ($send['chat'] == false) {
    while ($row = mysqli_fetch_assoc($users)) {
      if (!$row['id']) continue;
      $response = bot('sendmessage', [
        'chat_id' => $row['id'],
        'text' => $send['text'],
      ])->result;
      if ($response) {
        $count++;
      }
    }
  } else {
    while ($row = mysqli_fetch_assoc($users)) {
      if (!$row['id']) continue;
      $response = bot('sendphoto', [
        'chat_id' => $row['id'],
        'photo' => $send['chat'],
        'caption' => $send['text'],
      ])->result;
      if ($response) {
        $count++;
      }
    }
  }

  $connect->query("UPDATE `sendall` SET `sended` = `sended` + 400 LIMIT 1");
  $send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
  $tddd = $send['sended'];
  $tfrigh = $alluser - $send['sended'];
  $min = Takhmin2($tfrigh);

  bot('editMessageReplyMarkup', [
    'chat_id' => $send['admin'],
    'message_id' => $send['messageid'],
    'reply_markup' => json_encode([
      'inline_keyboard' => [
        [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
        [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
      ]
    ])
  ]);

  if ($send['sended'] + 400 >= $alluser) {
    bot('sendMessage', [
      'chat_id' => $admin[0],
      'text' => "✅ عملیات همگانی پایان یافت !",
      'parse_mode' => "HTML",
    ]);
    bot('editMessageReplyMarkup', [
      'chat_id' => $send['admin'],
      'message_id' => $send['messageid'],
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => "✅ همگانی پایان یافت .", 'callback_data' => "none"]],
        ]
      ])
    ]);
    $connect->query("UPDATE `sendall` SET `step` = 'none' , `admin` = null , `messageid` = null , `text` = '' , `sended` = '0' , `chat` = '' LIMIT 1");
  }
}
//======================// forward //======================//
if ($send['step'] == 'forward') {
  $alluser = mysqli_num_rows(mysqli_query($connect, "select id from `user`"));
  $users = mysqli_query($connect, "SELECT id FROM `user` LIMIT 400 OFFSET {$send['sended']}");
  $count = 0;

  while ($row = mysqli_fetch_assoc($users)) {
    if (!$row['id']) continue;

    $response = bot('ForwardMessage', [
      'chat_id' => $row['id'],
      'from_chat_id' => $send['chat'],
      'message_id' => $send['text'],
    ])->result;

    if ($response) {
      $count++;
    }
  }


  $connect->query("UPDATE `sendall` SET `sended` = `sended` + 400 LIMIT 1");
  $send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
  $tddd = $send['sended'];
  $tfrigh = $alluser - $send['sended'];
  $min = Takhmin2($tfrigh);

  bot('editMessageReplyMarkup', [
    'chat_id' => $send['admin'],
    'message_id' => $send['messageid'],
    'reply_markup' => json_encode([
      'inline_keyboard' => [
        [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
        [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
      ]
    ])
  ]);

  if ($send['sended'] + 400 >= $alluser) {
    bot('sendMessage', [
      'chat_id' => $admin[0],
      'text' => "✅ عملیات همگانی پایان یافت !",
      'parse_mode' => "HTML",
    ]);
    bot('editMessageReplyMarkup', [
      'chat_id' => $send['admin'],
      'message_id' => $send['messageid'],
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => "✅ همگانی پایان یافت .", 'callback_data' => "none"]],
        ]
      ])
    ]);
    $connect->query("UPDATE `sendall` SET `step` = 'none' , `admin` = null , `messageid` = null , `text` = '' , `sended` = '0' , `chat` = '' LIMIT 1");
  }
}

//======================// send group //======================//
if ($send['step'] == 'send_g') {
  $allgroup = mysqli_num_rows(mysqli_query($connect, "select id from `group`"));
  $groups = mysqli_query($connect, "SELECT id FROM `group` LIMIT 400 OFFSET {$send['sended']}");
  $count = 0;
  if ($send['chat'] == false) {
    while ($row = mysqli_fetch_assoc($groups)) {
      if (!$row['id']) continue;
      $response = bot('sendmessage', [
        'chat_id' => $row['id'],
        'text' => $send['text'],
      ])->result;
      if ($response) {
        $count++;
      }
    }
  } else {
    while ($row = mysqli_fetch_assoc($groups)) {
      if (!$row['id']) continue;
      $response = bot('sendphoto', [
        'chat_id' => $row['id'],
        'photo' => $send['chat'],
        'caption' => $send['text'],
      ])->result;
      if ($response) {
        $count++;
      }
    }
  }

  $connect->query("UPDATE `sendall` SET `sended` = `sended` + 400 LIMIT 1");
  $send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
  $tddd = $send['sended'];
  $tfrigh = $allgroup - $send['sended'];
  $min = Takhmin2($tfrigh);

  bot('editMessageReplyMarkup', [
    'chat_id' => $send['admin'],
    'message_id' => $send['messageid'],
    'reply_markup' => json_encode([
      'inline_keyboard' => [
        [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
        [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
      ]
    ])
  ]);

  if ($send['sended'] + 400 >= $allgroup) {
    bot('sendMessage', [
      'chat_id' => $admin[0],
      'text' => "✅ عملیات همگانی پایان یافت !",
      'parse_mode' => "HTML",
    ]);
    bot('editMessageReplyMarkup', [
      'chat_id' => $send['admin'],
      'message_id' => $send['messageid'],
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => "✅ همگانی پایان یافت .", 'callback_data' => "none"]],
        ]
      ])
    ]);
    $connect->query("UPDATE `sendall` SET `step` = 'none' , `admin` = null , `messageid` = null , `text` = '' , `sended` = '0' , `chat` = '' LIMIT 1");
  }
}
//======================// forward group //======================//
if ($send['step'] == 'forward_g') {
  $allgroup = mysqli_num_rows(mysqli_query($connect, "select id from `group`"));
  $groups = mysqli_query($connect, "SELECT id FROM `group` LIMIT 400 OFFSET {$send['sended']}");
  $count = 0;

  while ($row = mysqli_fetch_assoc($groups)) {
    if (!$row['id']) continue;

    $response = bot('ForwardMessage', [
      'chat_id' => $row['id'],
      'from_chat_id' => $send['chat'],
      'message_id' => $send['text'],
    ])->result;

    if ($response) {
      $count++;
    }
  }


  $connect->query("UPDATE `sendall` SET `sended` = `sended` + 400 LIMIT 1");
  $send = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `sendall` LIMIT 1"));
  $tddd = $send['sended'];
  $tfrigh = $allgroup - $send['sended'];
  $min = Takhmin2($tfrigh);

  bot('editMessageReplyMarkup', [
    'chat_id' => $send['admin'],
    'message_id' => $send['messageid'],
    'reply_markup' => json_encode([
      'inline_keyboard' => [
        [['text' => "🔹 تعداد افراد ارسال شده : $tddd", 'callback_data' => "none"]],
        [['text' => "🚀 زمان تخمینی ارسال : $min دقیقه (باقیمانده)", 'callback_data' => "none"]],
      ]
    ])
  ]);

  if ($send['sended'] + 400 >= $allgroup) {
    bot('sendMessage', [
      'chat_id' => $admin[0],
      'text' => "✅ عملیات همگانی پایان یافت !",
      'parse_mode' => "HTML",
    ]);
    bot('editMessageReplyMarkup', [
      'chat_id' => $send['admin'],
      'message_id' => $send['messageid'],
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => "✅ همگانی پایان یافت .", 'callback_data' => "none"]],
        ]
      ])
    ]);
    $connect->query("UPDATE `sendall` SET `step` = 'none' , `admin` = null , `messageid` = null , `text` = '' , `sended` = '0' , `chat` = '' LIMIT 1");
  }
}
