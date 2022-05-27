<?php
require_once('init.php');

// Если пользователь не авторизован, то при попытке открыть страницу добавления проекта он будет перенаправлен на главную страницу
if (false === $user['user_id']) {
    header("Location: index.php");
    exit;
}

// Запрос в БД списка проектов пользователя и количества задач в каждом из них
$projects = get_user_projects($link, $user['user_id'], $error_template_data);

$project_names = array_column($projects, 'project_name');

// Получение запроса из формы
$project = filter_input_array(INPUT_POST, ['name' => FILTER_DEFAULT], true);

// Подключение шаблонов страниц
if (false === (bool)$project) {

    $content_project = include_template('project_side.php', [
        'projects' => $projects,
        'project_id' => $project_id,
        'show_complete_tasks' => $show_complete_tasks,
        'filter' => $filter
    ]);

    // Подключение шаблона с формой добавления задачи
    $form_content = include_template('add_project.php', [
        'content_project' => $content_project,
        'projects' => $projects,
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

$project_name = $project['name'];

// Фильтрация запроса
$project_name = filter_string($project_name);

// Проверка заполненного поля на ошибки
$errors['name'] = validate_field_length($project_name, 0, 30);

if (!$errors['name']) {
    $errors['name'] = validate_project_name($link, $user, $project_name);
}

// Вывод сообщений об ошибочно заполненных полях формы добавления задачи
if ($errors['name']) {
    $content_project = include_template('project_side.php', [
        'projects' => $projects,
        'project_id' => $project_id,
        'show_complete_tasks' => $show_complete_tasks,
        'filter' => $filter
    ]);

    $form_content = include_template('add_project.php', [
        'content_project' => $content_project,
        'projects' => $projects,
        'name_class' => $name_class,
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

// Формирование и выполнение SQL-запроса в БД, в случае успешной проверки формы, на добавление нового проекта
$project['name'] = $project_name;

array_unshift($project, $user['user_id']);

$sql = "INSERT INTO projects (user_id, project_name) VALUES (?, ?)";

$stmt = get_prepare_stmt($link, $sql, $project)  ?? output_error_sql($link, $error_template_data);

$result = mysqli_stmt_execute($stmt)  ?? output_error_sql($link, $error_template_data);

$sql_result = mysqli_stmt_get_result($stmt);

// Переадресация пользователя на главную страницу после успешного добавления новой задачи
if (false === $sql_result) {
    header("Location: index.php");
}
