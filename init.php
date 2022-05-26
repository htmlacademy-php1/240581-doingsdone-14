<?php

session_start();

ini_set('display_errors', '1');

ini_set('display_startup_errors', '1');

error_reporting(E_ALL);

require_once('vendor/autoload.php');
require_once('helpers.php');
require_once('config/mail.php');

$data_base = require_once('config/db.php');

//Инициализация переменных, используемых в шаблонах
$title = 'Дела в порядке';
$email_class = '';
$password_class = '';
$name_class = '';
$project_class = '';
$date_class = '';
$file_class = '';

//Инициализация переменных, используемых в сценариях
$project_id = false;
$show_complete_tasks = 1;
$filter = 1;
$projects = [];
$errors = [];
$content = '';

// Массив с данными пользователя по умолчанию
$user = [
    'user_id' => false,
    'user_name' => false
];

// Дата для вывода в футере сайта
$year = date("Y");

// Данные для шаблона с выводом ошибки подключения к базе данных (БД)
$error_template_data = [
    'title' => $title,
    'user' => $user,
    'year' => $year
];

// Подключение к БД
$link = mysqli_connect($data_base['host'], $data_base['user'], $data_base['password'], $data_base['database']);
mysqli_set_charset($link, "utf8");

// Вывод ошибки подключения к БД 
if (!$link) {
    output_error_sql($link, $error_template_data);
}

// Устанавливаем id и имя пользователя в сессии
if (isset($_SESSION['user_id'])) {
    $user['user_id'] = $_SESSION['user_id'];
    $user['user_name'] = $_SESSION['user_name'];
}

// Массив с разрешёнными для загрузки типами файлов
$mime_types = array(

    'txt' => 'text/plain',
    'htm' => 'text/html',
    'html' => 'text/html',
    'css' => 'text/css',
    'xml' => 'application/xml',

    // images
    'png' => 'image/png',
    'jpe' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'svg' => 'image/svg+xml',
    'svgz' => 'image/svg+xml',

    // archives
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',

    // audio/video
    'mp3' => 'audio/mpeg',
    'qt' => 'video/quicktime',
    'mov' => 'video/quicktime',

    // adobe
    'pdf' => 'application/pdf',
    'psd' => 'image/vnd.adobe.photoshop',

    // ms office
    'doc' => 'application/msword',
    'rtf' => 'application/rtf',
    'xls' => 'application/vnd.ms-excel',
    'ppt' => 'application/vnd.ms-powerpoint',

    // open office
    'odt' => 'application/vnd.oasis.opendocument.text',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
);
