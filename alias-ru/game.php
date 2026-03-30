<?php
include_once 'sdb.php';

$gamevars = LTS::Vars();
$gameid = $gamevars->get('game');
$gamerid = $gamevars->get('gamer');
$gamername = $gamevars->get('name');
$isadmin = $gamevars->get('admin');
$oldgame = $gamevars->get('oldgame');

if($gameid === null)
{
    include_once 'index.php';
    exit();
}
if($isadmin)
{
    include_once 'admgame.php';
    exit();
}

$hellobox = LTS::Div('name')
    ->rowbox()
    ->capt('Привет, ' . $gamevars->get('name'));

$messagesbox = LTS::Div('messagesbox')
    ->addclass('messagesbox');

$chatinput = LTS::Html('input')->setid('chatinput')->attr('type', 'text');
$chat = LTS::Div('chat')
    ->addclass('linediv')
    ->rowbox()
    ->add(LTS::Div('chatcapt')
        ->capt('Чат: '))
    ->add($chatinput)
    ->add(LTS::Button('.timebut')
        ->capt('>')
        ->click('LTS(events).chat($(chatinput).val())'));
    
$timerdiv = LTS::Div('timerdiv');
$secretword = LTS::Div('secretword');
$secretbutton = LTS::Button('.timebut')
        ->capt('<')
        ->click(
<<<JS
            if(isgolos) 
                if(spyid == 0)
                    alert('Необходимо кого-то выбрать или воздержаться от голосования!');
                else
                    LTS(events).sendresult(spyid); 
            else
            {
                $(secretword).text(issecretshow ? '' : secret); 
                issecretshow = ! issecretshow;
            }
JS
        );
$secretbox = LTS::Div('secretbox')
    ->addclass('linediv')
    ->rowbox()
    ->add($timerdiv)
    ->add($secretword)
    ->add($secretbutton);
$secretbox->css()->add('display', 'none');

$maindiv = LTS::Div('gamediv')
    ->columnbox()
    ->content('start')
    ->add($gamevars)
    ->add($ErrWindow)
    ->add($hellobox)
    ->add($chat)
    ->add($secretbox)
    ->add($messagesbox)
    ->add(LTS::Div('headdiv')->click('jQuery(this).hide()'));

$maindiv->css()->add('index.css')
    ->add('visibility', 'hidden');
$maindiv->js()->add('scrolldiv.js')
    ->add('LTS:game.js');
$maindiv->js('ready')->add(
<<<JS
    setInterval(function () {
        if(timermessage > 0 && --timermessage == 0) LTS(ErrWindow).close(); 
        LTS.post('messages', 'messages.php', { gameid : {$gameid}, mesid : messageid, userid : {$gamerid}});
    }, 500)
JS
);

function getRandomSentence($filename) {
    global $gamevars;

    // Проверяем, существует ли файл
    if (!file_exists($filename)) {
        return "Файл не найден.";
    }

    // Читаем файл и разбиваем его на строки
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Проверяем, есть ли строки в файле
    if (empty($lines)) {
        return "Файл пуст.";
    }
    
    // Индекс последней выбранной строки
    $bylo = $gamevars->get('bylo');

    // Выбираем случайную строку
    do 
    {
        $randomIndex = array_rand($lines);
    }
    while($bylo !== null && $bylo == $randomIndex);
    $gamevars->set('bylo', $randomIndex);

    return $lines[$randomIndex];
}

$events = LTS::Events($maindiv);
$events->client('chat(mes)', "if(result != 'ok') alert(result); else $(chatinput).val('')")
    ->client('creategoloschat()', "if(result != 'no') creategolosovanie(result)")
    ->client('sendresult(id)', "if(result != 'ok') alert(result);")
    ->client('itogi()', '');
$events->server('chat', function ($args) {
        global $messages; 
        global $gameid;
        global $gamerid;
        global $gamername;

        $mes = $args['mes'];
        if($mes == '')
            return 'ok';

        $messages->value('parent_games', $gameid)
            ->value('parent_clients', $gamerid)
            ->value('type', 4)
            ->value('message', "{$gamername}: {$mes}")
            ->insert();    

        return 'ok';
    })
    ->server('creategoloschat', function ($args) {
        global $clients;
        global $gameid;
        global $messages;
        global $gamerid;

        $all = $messages->all(array('parent_games' => $gameid, 'WHERE' => "`type` = 11 OR (`type` = 10 AND `parent_clients` = {$gamerid})"));
        if($all !== false && count($all) > 0)
            return 'no';

        $all = $clients->all('parent_games', $gameid);
        return $all;       
    })
    ->server('sendresult', function ($args) {
        global $gamerid;
        global $gamername;
        global $clients;
        global $gameid;
        global $messages;

        $id = $args['id'];
        if($id == 0)
            return 'Необходимо кого-то выбрать или воздержаться от голосования!';

        $ob = $clients->get($id);
        if($ob === false)
            return 'Очень странная ошибка. Если ты видишь этот текст, значит, ты обманул самого гениального в мире программиста!';

        $kol = $ob->kol + 1;

        $clients->value('kol', $kol)
            ->set($id);
        $clients->value('golos', $id)
            ->set($gamerid);

        $messages->value('parent_games', $gameid)
            ->value('parent_clients', $gamerid)
            ->value('type', 10)
            ->value('message', 'Проголосовал ' . $gamername)
            ->insert();  

        return 'ok';       
    })
    ->server('itogi', function ($args) { 
        global $clients;
        global $gameid;
        global $messages;
        global $games;

        $all = $messages->all(array('parent_games' => $gameid, 'type' => 12));
        if($all !== false && count($all) > 0)
            return 'ok';

        $messages->value('parent_games', $gameid)
            ->value('type', 12)
            ->value('message', 'Подведение итогов ...')
            ->insert();

        $game = $games->get($gameid);
        $spyuser = $clients->get($game->spy);   

        $all = $clients->all(array('parent_games' => $gameid, 'ORDER' => '-kol'));

        $poiman = false;
        if($all === false || count($all) < 3)
            $mes = 'Какая-то легендарная ошибка! Программист - говнокодер.';
        else
        {
            if($all[0]->kol == 0)
                $mes = 'Участники воздержались от голосования. Шпиону удалось уйти!';
            else
            if($all[0]->kol == $all[1]->kol)
                $mes = 'Голоса разделились. Шпиону удалось уйти!';
            else
            if($game->spy == $all[0]->id)
            {
                $mes = 'Шпиону был пойман!';
                $poiman = true;
            }
            else
                $mes = 'Шпион не был обнаружен!';                
        }

        $messages->value('parent_games', $gameid)
            ->value('parent_clients', $poiman ? $game->spy : 0)
            ->value('type', 13)
            ->value('message', $mes)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 14)
            ->value('message', 'С Е К Р Е Т: ' . $game->secret)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 15)
            ->value('message', 'Ш П И О Н: ' . $spyuser->name)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 16)
            ->value('message',  '*** Совет от Мерлина: ' . getRandomSentence('spy.d'))
            ->insert();

        return 'ok';
    });

LTS::scriptversion('1');
LTS::Space()->build($maindiv);
?>
