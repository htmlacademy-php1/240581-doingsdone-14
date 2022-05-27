<?php

require_once('init.php');

// Если пользователь уже авторизован, то при попытке открыть страницу регистрации он будет перенаправлен на главную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Массив с функциями для валидации полей формы запроса
$rules = [
    'email' => function ($value) {
        return validate_email($value);
    },

    'password' => function ($value) {
        return validate_field_length($value, 6, 20);
    },

    'name' => function ($value) {
        return validate_field_length($value, 0, 50);
    }
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Подключение шаблона с формой
    $form_content = include_template('register.php', [
        'errors' => $errors,
        'error_message' => $error_message,
        'email_class' => $email_class,
        'password_class' => $password_class,
        'name_class' => $name_class
    ]);

    $layout_content = include_template('layout.php', [
        'content' => $form_content,
        'title' => $title,
        'user' => $user,
        'year' => $year
    ]);

    print($layout_content);

    exit;
}

// Получение данных из формы регистрации
$new_user = filter_input_array(INPUT_POST, ['email' => FILTER_DEFAULT, 'name' => FILTER_DEFAULT, 'password' => FILTER_DEFAULT], true);

if (false === (bool)$new_user) {
    header("Location: index.php");
    exit;
}

// Валидация данных, введённых в поля формы
foreach ($new_user as $key => $value) {
    if (isset($rules[$key])) {
        $rule = $rules[$key];
        $errors[$key] = $rule($value);
    }
}

$errors = array_filter($errors);

$new_user_email = [$new_user['email']];

//Проверка email-адреса на отсутствие в БД
$sql = "SELECT user_email FROM users WHERE user_email = ?";

$sql_result = get_result_prepare_sql($link, $sql, $new_user_email);

if (mysqli_fetch_row($sql_result)) {
    $errors['email'] = 'Указанный e-mail уже используется другим пользователем.';
}

// Вывод сообщений об ошибочно заполненных полях формы
if (count($errors)) {
    $form_content = include_template('register.php', [
        'errors' => $errors,
        'error_message' => $error_message,
        'email_class' => $email_class,
        'password_class' => $password_class,
        'name_class' => $name_class
    ]);

    $layout_content = include_template('layout.php', [
        'content' => $form_content,
        'title' => $title,
        'user' => $user,
        'year' => $year
    ]);

    print($layout_content);
    exit;
}

// Хеширование пароля
$new_user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Запрос в БД на добавление нового пользователя
$sql = "INSERT INTO users (user_email, user_name, user_password, user_date_add) VALUES (?, ?, ?, now())";

$stmt = get_prepare_stmt($link, $sql, $new_user) ?? output_error_sql($link, $error_template_data);

$result = mysqli_stmt_execute($stmt) ?? output_error_sql($link, $error_template_data);

$sql_result = mysqli_stmt_get_result($stmt);

// Переадресация пользователя на главную страницу после успешной регистрации
if (false === $sql_result) {
    header("Location: index.php");
}
