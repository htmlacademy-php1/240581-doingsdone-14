<?php

/**
 * Проверяет переданную дату на соответствие формату 'ГГГГ-ММ-ДД'
 *
 * Примеры использования:
 * is_date_valid('2019-01-01'); // true
 * is_date_valid('2016-02-29'); // true
 * is_date_valid('2019-04-31'); // false
 * is_date_valid('10.10.2010'); // false
 * is_date_valid('10/10/2010'); // false
 *
 * @param string $date Дата в виде строки
 *
 * @return bool true при совпадении с форматом 'ГГГГ-ММ-ДД', иначе false
 */
function validate_date(string $date)
{
    $format_to_check = 'YYYY-mm-dd';
    $dateTimeObj = date_create_from_format($format_to_check, $date);
    if ($dateTimeObj !== false && array_sum(date_get_last_errors()) === 0) {
        return 'Введите дату в формате «ГГГГ-ММ-ДД»';
    }
    return false;
}

/**
 * Создает подготовленное выражение на основе готового SQL запроса и переданных данных
 *
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 *
 * @return mysqli_stmt Подготовленное выражение
 */
function db_get_prepare_stmt($link, $sql, $data = [])
{
    $stmt = mysqli_prepare($link, $sql);

    if ($stmt === false) {
        $errorMsg = 'Не удалось инициализировать подготовленное выражение: ' . mysqli_error($link);
        die($errorMsg);
    }

    if ($data) {
        $types = '';
        $stmt_data = [];

        foreach ($data as $value) {
            $type = 's';

            if (is_int($value)) {
                $type = 'i';
            } else if (is_string($value)) {
                $type = 's';
            } else if (is_double($value)) {
                $type = 'd';
            }

            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }

        $values = array_merge([$stmt, $types], $stmt_data);

        $func = 'mysqli_stmt_bind_param';
        $func(...$values);

        if (mysqli_errno($link) > 0) {
            $errorMsg = 'Не удалось связать подготовленное выражение с параметрами: ' . mysqli_error($link);
            die($errorMsg);
        }
    }

    return $stmt;
}

/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки templates
 * @param array $data Ассоциативный массив с данными для шаблона
 * 
 * @return string Итоговый HTML
 */
function include_template($name, array $data = [])
{
    $name = 'templates/' . $name;
    $result = '';

    if (!is_readable($name)) {
        return $result;
    }

    ob_start();
    extract($data);
    require $name;

    $result = ob_get_clean();

    return $result;
}

/**
 * Вывод ошибки запроса в базу данных
 * @param $link mysqli Ресурс соединения 
 * 
 * @return - Подключение шаблона с выводом ошибки запроса
 */
function output_error_sql($link)
{
    # вывод ошибки запроса в базу данных
    $error = mysqli_error($link);
    $content = include_template('error.php', ['error' => $error]);
    $layout_content = include_template('layout.php', [
        'content' => $content,
        'title' => 'Дела в порядке'
    ]);
    print($layout_content);
    exit;
}

/**
 * Для запросов на выборку записей!
 * Выдаёт результат параметризованного SQL-запроса на чтение из ДБ и переданных данных
 *
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 *
 * @return mysqli_result результат выполнения подготовленного запроса
 */
function get_result_prepare_sql($link, $sql, array $data = [])
{
    $stmt = mysqli_prepare($link, $sql);

    if (false === $stmt) {
        return false;
    }

    if ($data) {
        $types = '';
        $stmt_data = [];

        foreach ($data as $value) {
            $type = 's';

            if (is_int($value)) {
                $type = 'i';
            } else if (is_string($value)) {
                $type = 's';
            } else if (is_double($value)) {
                $type = 'd';
            }

            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }

        $values = array_merge([$stmt, $types], $stmt_data);

        $func = 'mysqli_stmt_bind_param';
        $func(...$values);

        if (mysqli_errno($link) > 0) {
            return false;
        }
    }

    $result = mysqli_stmt_execute($stmt);

    if (false === $result) {
        return false;
    }

    return mysqli_stmt_get_result($stmt);
}

/**
 * Запрос в БД списка проектов и количества задач в каждом из них по id пользователя
 *
 * @param $link - mysqli Ресурс соединения
 * @param $id - id пользователя для вставки на место плейсхолдера
 *
 * @return  array Двумерный массив со списком проектов и количеством задач в каждом из них
 */
function get_user_projects($link, $id)
{
    $sql = "SELECT project_name, p.project_id, COUNT(task_name) AS count_tasks FROM projects p LEFT JOIN tasks t ON t.project_id = p.project_id WHERE p.user_id = ". $id . " GROUP BY project_name, p.project_id";

    $sql_result = mysqli_query($link, $sql);
    if (false === $sql_result) {
        output_error_sql($link);
    }
    
    return mysqli_fetch_all($sql_result, MYSQLI_ASSOC);
}

/**
 *Фильтрация текстового поля, полученного из POST-запрося
 *@param $name - фильтруемая строка
 *
 *@return - string (отфильтрованная строка)
 */
function get_post_val($name)
{
    return filter_input(INPUT_POST, $name);
}

/**
 *Проверка существования проекта в списке проектов пользователя по его id
 *@param int $id - id проекта
 *@param array $allowed_list - массив с id проектов пользователя
 *
 *@return - string | null
 */
function validate_project($id, $allowed_list)
{
    if (!in_array($id, $allowed_list)) {
        return 'Указан несуществующий проект';
    }
    return null;
}

/**
 * Функция проверки занятости имени для нового проекта
 * Реализована через дополнительный SQL-запрос из-за регистрозависимати PHP-функции "in_array()"
 * @param $link mysqli Ресурс соединения
 * @param array $user Массив с id пользователя
 * @param $project_name Имя проекта
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * 
 * @return mysqli_result результат выполнения подготовленного запроса
 */
function validate_project_name($link, $user, $project_name)
{
    $sql_data = [
        $user['user_id'],
        $project_name
    ];

    $sql = "SELECT project_name FROM projects WHERE user_id = ? AND project_name = ?";

    $sql_result = get_result_prepare_sql($link, $sql, $sql_data);

    $project_names = mysqli_fetch_all($sql_result, MYSQLI_ASSOC);

    if ($project_names) {
        return 'Такой проект уже существует';
    }
    return null;
}

/**
 *Проверка соответсвия длины строки на min и max допустимое значение
 *Использована функция "iconv_strlen", проверяющая количество симолов с учётом кодировки
 *Проверяет на пустоту поля и корретность email
 *@param $value - проверяемая строка
 *@param $min - минимальное значение
 *@param $max - максимальное значение
 *
 *@return - string | null
 */
function validate_field_length($value, $min, $max)
{
    if ($value) {
        $len = iconv_strlen($value);
        if ($len < $min) {
            return "Минимальное количество символов должно быть больше $min";
        }
        if ($len > $max) {
            return "Максимальное количество символов должно быть меньше $max";
        }
        return null;
    }
    return "Заполните поле!";
}

/**
 *Валидация email
 *Проверяет на пустоту поля и корретность email
 *@param $value - email
 *
 *@return - string | null
 */
function validate_email($value)
{
    if (!$value) {
        return 'Поле "e-mail" должно быть заполнено!';
    }

    $value = filter_var($value, FILTER_VALIDATE_EMAIL);
    if (!$value) {
        return 'E-mail введён некорректно.';
    }
    return null;
}

/**
 *Для запросов на изменение данных!
 *Подготавливает SQL-выражение к выполнению
 *@param $link mysqli - Ресурс соединения
 *@param $sql string - SQL запрос с плейсхолдерами вместо значений
 *
 *@param array $data - Данные для вставки на место плейсхолдеров
 */
function get_prepare_stmt($link, $sql, array $data = [])
{
    $stmt = mysqli_prepare($link, $sql);

    if (false === $stmt) {
        return false;
    }

    if ($data) {
        $types = '';
        $stmt_data = [];

        foreach ($data as $value) {
            $type = 's';

            if (is_int($value)) {
                $type = 'i';
            } else if (is_string($value)) {
                $type = 's';
            } else if (is_double($value)) {
                $type = 'd';
            }

            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }

        $values = array_merge([$stmt, $types], $stmt_data);

        $func = 'mysqli_stmt_bind_param';
        $func(...$values);

        if (mysqli_errno($link) > 0) {
            return false;
        }
    }

    return $stmt;
}

/** 
 * Подготовка дополнения к условию SQL-запроса к БД по дате для работы блока фильтров
 * @param $filter - Переменная с номером фильтра из GET-запросаЖ
 * - 1 - Все задачи;
 * - 2 - Задачи на сегодня;
 * - 3 - Задачи на завтра;
 * - 4 - Просроченные задачи.
 * 
 * @return $sql_add - Строка с дополнением к условию запроса
 */
function preparation_insert_filtration($filter)
{
    $sql_add = "";

    if (2 == $filter) {
        $sql_add = "AND DATE(task_deadline) = CURDATE() ";
    }
    if (3 == $filter) {
        $sql_add = "AND DATE(task_deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) ";
    }
    if (4 == $filter) {
        $sql_add = "AND DATE(task_deadline) < CURDATE() ";
    }
    return $sql_add;
}

/** Функция mb_ucfirst предназначена для преобразования первой буквы строки в "ВЕРХНИЙ РЕГИСТР".
 * Функция "ucfirst()" не использовалась, т.к. не работает с кириллицей
 * Взято: https://dwweb.ru/page/php/function/081_mb_ucfirst_php.html
 * @param $string - передаваемая строка
 * 
 * @return mb_strtoupper - Преобразованная строка
 */
function mb_ucfirst($string, $enc = 'UTF-8')
{
    return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc), $enc);
}

/** Фильтрация получаемой от пользователя строки:
 * - приводит к строчному типу;
 * - убирает пробелы в начале и конце строки;
 * - вычищает лишние пробелы в строке;
 * - преобразует первый символ в верхний регистр
 * @param $name - передаваемая строка
 * 
 * @return mb_strtoupper - Преобразованная строка
 */
function filter_string($name)
{
    $name = (string)$name;
    $name = trim($name);
    $name = preg_replace('/\s\s+/', ' ', $name);
    return mb_ucfirst($name);
}
