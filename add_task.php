<?php

require_once('init.php');

// Если пользователь не авторизован, то при попытке открыть страницу добавления проекта он будет перенаправлен на главную страницу
if (false === $user['user_id']) {
    header("Location: index.php");
    exit;
}

// Запрос в БД списка проектов пользователя и количества задач в каждом из них
$projects = get_user_projects($link, $user['user_id'], $error_template_data);

$project_ids = array_column($projects, 'project_id');

// Массив с функциями для валидации полей формы запроса
$rules = [
    'name' => function ($value) {
        return validate_field_length($value, 0, 50);
    },
    'project' => function ($value) use ($project_ids) {
        return validate_project($value, $project_ids);
    },
    'date' => function ($date) {
        return validate_date($date);
    }
];

// Получение данных, введённых в поля формы
$task = filter_input_array(INPUT_POST, ['name' => FILTER_DEFAULT, 'project' => FILTER_DEFAULT, 'date' => FILTER_DEFAULT], true);

// Подключение шаблонов страниц
if (false === (bool)$task) {
    $content_project = include_template('project_side.php', [
        'projects' => $projects,
        'project_id' => $project_id,
        'show_complete_tasks' => $show_complete_tasks,
        'filter' => $filter
    ]);

    // Подключение шаблона с формой добавления задачи
    $form_content = include_template('add_task.php', [
        'content_project' => $content_project,
        'projects' => $projects,
        'name_class' => $name_class,
        'project_class' => $project_class,
        'date_class' => $date_class,
        'file_class' => $file_class
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

// Фильтрация и валидация данных, введённых в поля формы
$task['name'] = filter_string($task['name']);

foreach ($task as $key => $value) {
    if (isset($rules[$key])) {
        $rule = $rules[$key];
        $errors[$key] = $rule($value);
    }
}

// Если дата завершения задачи пользователем не указана, устанавливаем null
if (empty($_POST['date'])) {
    $task['date'] = null;
}

if ($_POST['date'] < date('Y-m-d') && null !== $task['date']) {
    $errors['date'] = "Дата выполнения задачи не может быть ранее текущей!";
}

$errors = array_filter($errors);

// Получение данных о загруженном файле   
$tmp_name = $_FILES['file']['tmp_name'];
$file_type = $_FILES['file']['type'];
$file_name = $_FILES['file']['name'];
$file_size = $_FILES['file']['size'];

// Выделение расширения файла
$file_name_separated = explode(".", $file_name);
$file_extension = strtolower(end($file_name_separated));

// Валидация загруженного файла
if (!empty($file_name) && !in_array($file_type, $mime_types)) {
    $errors['file'] = 'Недопустимый тип файла';
}

if ($file_size > 2097152) {
    $errors['file'] = 'Превышен максимальный размер файла';
}

// Переименование файла
$file_name = uniqid() . "." . $file_extension;

$task['task_file'] = $file_name;

// Если файл пользователем не загружен, устанавливаем null
if (empty($_FILES['file']['name'])) {
    $task['task_file'] = null;
}

// Вывод сообщений об ошибочно заполненных полях формы добавления задачи
if (count($errors)) {
    $content_project = include_template('project_side.php', [
        'projects' => $projects,
        'project_id' => $project_id,
        'show_complete_tasks' => $show_complete_tasks,
        'filter' => $filter
    ]);

    $form_content = include_template('add_task.php', [
        'content_project' => $content_project,
        'projects' => $projects,
        'user' => $user,
        'errors' => $errors
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

// Формирование и выполнение SQL-запроса в БД, в случае успешной проверки формы, на добавление новой задачи
array_unshift($task, $user['user_id']);

$sql = "INSERT INTO tasks (user_id, task_name, project_id, task_date_create, task_deadline, task_file) VALUES (?, ?, ?, now(), ?, ?)";

$stmt = get_prepare_stmt($link, $sql, $task) ?? output_error_sql($link, $error_template_data);


$result = mysqli_stmt_execute($stmt) ?? output_error_sql($link, $error_template_data);

$sql_result = mysqli_stmt_get_result($stmt);

// Cохранение файла
move_uploaded_file($tmp_name, 'uploads/' . $file_name);

// Переадресация пользователя на главную страницу после успешного добавления новой задачи
if (false === $sql_result) {
    header("Location: index.php");
}
