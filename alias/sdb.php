<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

define('UPPER_DIR', dirname(__DIR__) . '/newlotis/');
date_default_timezone_set('Europe/Moscow');

include_once UPPER_DIR . 'lotis.php';
include_once 'connect.php';

$updateenable = true;

// Set MySQL connection parameters
$base = LTS::MySql($databasename, $databaseserver, $databaseuser, $databasepassword);

// 1 - Waiting: Admin's Start button. Everyone sees "Waiting". Admin can set game length and start the round.
// 2 - Game start: if it was 4 or 1 - show the word to non-spies, show the spy that he is a spy.
// 3 - Voting: (automatically) - if it was 2 - voting button (everyone sees the result, can re-vote), admin - show the result (if everyone)
// 4 - Result: reveal the spy. show the word to the spy. "New game" button for admin

// Clients
$clients = $base->table('clients');

// Users
$games = $base->table('games');
$games->int('code'); // game code
$games->int('len'); // game length in minutes
//$games->int('status'); // game status-
$games->int('level'); // game difficulty level
$games->string('secret', 250); // secret word
$games->table('spy', $clients); // spy
$games->date('begin'); // when it started
//$games->date('end'); // when it will end-
$games->index('code');

// Clients
$clients->parent($games);
$clients->string('name', 32);
$clients->bool('admin'); // am I admin
//$clients->bool('spy'); // am I a spy-
$clients->int('golos'); // who I voted for
$clients->int('kol'); // how many voted for me
$clients->date('tik'); // last time in game

// Messages
$messages = $base->table('messages');
$messages->parent($games);
$messages->parent($clients);
$messages->int('type');
$messages->string('message', 250);
// 1 - 99 - game events
// 100 - player joined
// 101 - player disconnected

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

// Error message window
$ErrWindow = LTS::Dialog('ErrorWin');
$ErrWindow->caption = 'Attention!';
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