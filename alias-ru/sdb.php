<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

define('UPPER_DIR', dirname(__DIR__) . '/newlotis/');
date_default_timezone_set('Europe/Moscow');

include_once UPPER_DIR . 'lotis.php';
include_once 'connect.php';

$updateenable = true;
$internetserver = true;

// Задаем параметры подключения к mysql
$base = LTS::MySql($databasename, $databaseserver, $databaseuser, $databasepassword);

// 1 - Ожидание: кнопка Старт у админа. У всех надпись "Ожидание". Админ может назначить длину игры и начать тур.
// 2 - Старт игры: если был 4 или 1 - не шпионам показать слово, шпиону - что он шпион. 
// 3 - Голосование: (автоматом) - если был 2 - кнопка голосования (всем видеть результат, можно переголосовать), админу - показать результат (если все)
// 4 - Результат: показать шпиона. шпиону показать слово. кнопка "Новая игра" у админа

// Клиенты
$clients = $base->table('clients');

// Пользователи
$games = $base->table('games');
$games->int('code'); // код игры
$games->int('len'); // длина игры в минутах
//$games->int('status'); // статус игры-
$games->int('level'); // уровень сложности игры
$games->string('secret', 250); // заданное слово
$games->table('spy', $clients); // шпион
$games->date('begin'); // когда началась
//$games->date('end'); // когда закончится-
$games->index('code');

// Клиенты
$clients->parent($games);
$clients->string('name', 32);
$clients->bool('admin'); // админ ли я
//$clients->bool('spy'); // шпион ли я-
$clients->int('golos'); // за кого я проголосовал
$clients->int('kol'); // сколько проголосовало за меня
$clients->date('tik'); // когда был в игре последний раз

// Сообщения
$messages = $base->table('messages');
$messages->parent($games);
$messages->parent($clients);
$messages->int('type');
$messages->string('message', 250);
// 1 - 99 - события игры 
// 100 - присоединился игрок
// 101 - отключился игрок

if($updateenable && array_key_exists('updatereg', $_REQUEST))
{
    $_reg = filter_input(INPUT_GET, 'updatereg', FILTER_SANITIZE_SPECIAL_CHARS);
    $_name = filter_input(INPUT_GET, 'table', FILTER_SANITIZE_SPECIAL_CHARS);
    if($_reg == 'create')
    {
        if($_name)
        {
            $_table = $base->table($_name);
            if($_table && ! $_table->create())
            {
                exit('<p>no:' . $base->geterror() . '</p>');
            }
        }
        else
        {
            $base->create();

            foreach($base->dbtables as $table)
                if(! $table->create())
                    exit('<p>no:' . $base->geterror() . '</p>');
        }
        exit('<p>Created!</p>');
    }
    if($_reg == 'update')
    {
        $_table = $base->table($_name);
        if($_table)
            $_table->update();   
        else
        {
            foreach($base->dbtables as $table)
                $table->update();
        }
        exit('<p>Updated!</p>');
    }
    if($_reg == 'exit')
    {
        $_SESSION['active_user'] = false;
    }
    exit('<p>Error updatereg!</p>');
}

// Окно с сообщением об ошибке
$ErrWindow = LTS::Dialog('ErrorWin');
$ErrWindow->caption = 'Внимание!';
$ErrWindow->autoopen(false)
    ->width(600)
	->option('modal', 'true')
	->addclass('ui-state-error')
	->button('Ok', 'timermessage = 0; LTS(ErrWindow).close();');
	
$ErrorMessage = LTS::Div($ErrWindow);
$ErrorMessage->css()->add('padding', '30px')
	->add('font-weight', 'bold')
	->add('font-size', '30px');
?>

