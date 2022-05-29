<?php
// Сценарий рассылки уведомлений о задачах с датой выполнения, совпадающей с текущей

require_once('init.php');

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Запрос на получение id всех пользователей, у которых есть невыполненные задачи с датой выполнения, совпадающей с текущей
$sql = "SELECT DISTINCT u.user_id, user_name, user_email FROM users u INNER JOIN tasks t ON u.user_id = t.user_id WHERE task_status = 0 AND task_deadline = CURDATE() ORDER BY u.user_id";

$sql_result = mysqli_query($link, $sql);

$notified_users = mysqli_fetch_all($sql_result, MYSQLI_ASSOC);

// Выход, если нет пользователей с "горящими" задачами
$count = count($notified_users);

if (0 === $count) {
    exit;
}

// Подготовленное выражение запроса в БД на получение данных по всем невыполненным "горящим" задачам пользователя
$sql = "SELECT task_name, task_deadline FROM tasks WHERE user_id = ? AND task_status = 0 AND task_deadline = CURDATE()";

$stmt = mysqli_prepare($link, $sql);

// Запрос в БД на получение данных по всем невыполненным "горящим" задачам каждого пользователя, формирование и отправка ему сообщения
foreach ($notified_users as $notified_user) {

    mysqli_stmt_bind_param($stmt, 'i', $notified_user['user_id']);
    mysqli_stmt_execute($stmt);

    $sql_result = mysqli_stmt_get_result($stmt);

    $user_deadlines = mysqli_fetch_all($sql_result, MYSQLI_ASSOC);

    $count_tasks = count($user_deadlines);

    // Элементы текста и пунктуации для сообщения, если у пользователя одна "горящая" задача
    $you = 'У Вас запланирована задача ';
    $hyphen = '';
    $pointing = '.';

    // Элементы текста и пунктуации для сообщения, если у пользователя более одой "горящей" задачи
    if ($count_tasks > 1) {
        $you = 'У Вас запланированы задачи: '  . "<br>";
        $hyphen = '- ';
        $pointing = ';';
    }

    // Начало сообщения в зависимости от количества "горящих" задач
    $text_message = 'Уважаемый(ая), ' . $notified_user['user_name'] . '!' . "<br>" . "<br>" . $you;

    $counter = -1;

    // Формирование текста сообщения в зависимости от количества "горящих" задач у пользователя
    foreach ($user_deadlines as $user_deadline) {

        $counter++;

        if (($count_tasks - 1) === $counter) {
            $pointing = '.';
        }

        $text_message = $text_message . $hyphen . '"' . $user_deadline['task_name'] . '"' . ' на ' . date("Y-m-d", strtotime($user_deadline['task_deadline'])) . $pointing . "<br>";
    }

    // Добавление в текст сообщения подписи и email отправителя
    $text_message = $text_message . "<br>" . 'Ваш сервис «Дела в порядке»' . "<br>" . "<a href=" . $email_send_server . ">" . $email_send_server . "</a>";

    // Создание массива параметров отправляемого email
    $deadline_message = [
        'from' => $email_send_server,
        'to' => $notified_user['user_email'],
        'subject' => 'Уведомление от сервиса «Дела в порядке»',
        'text' => $text_message
    ];

    $transport = Transport::fromDsn($dsn);

    // Формирование сообщения
    $message = new Email();
    $message->to($deadline_message['to']);
    $message->from($email_send_server);
    $message->subject($deadline_message['subject']);
    $message->html($deadline_message['text']);

    // Отправка сообщения
    $mailer = new Mailer($transport);
    $mailer->send($message);
}
