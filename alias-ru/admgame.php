<?php
include_once 'sdb.php';

$gamevars = LTS::Vars();
$gameid = $gamevars->get('game');
$gamerid = $gamevars->get('gamer');
$gamername = $gamevars->get('name');
$isadmin = $gamevars->get('admin');
$oldgame = $gamevars->get('oldgame');

if($gameid === null || ! $isadmin)
{
    include_once 'index.php';
    exit();
}

$lendiv = LTS::Div('.code')
    ->capt($gamevars->get('len'));

$divmin = LTS::Div('.codecapt')
    ->capt('минуты');

$hellobox = LTS::Div('name')->rowbox()->capt('Привет, ' . $gamevars->get('name') . '! Ты создал новую игру!');
$messbox = LTS::Div('codemes')->rowbox()->capt('Сообщи код игры всем участникам, чтобы они могли ввести его и подключиться. После того, как все участники подключатся, нажми кнопку "Старт".');

$lenbox = LTS::Div('.linediv')
    ->rowbox()
    ->add(LTS::Div('.codecapt')
        ->capt('Время игры:'))
    ->add($lendiv)
    ->add($divmin)
    ->add(LTS::Button('.timebut')->capt('+')->click('addlen(1)'))
    ->add(LTS::Button('.timebut')->capt('-')->click('addlen(-1)'))
    ->add(LTS::Button('.timebut')->capt('Старт')->width('250px')->click('LTS(events).startgame()'));

$gamersdiv = LTS::Div('.code')
    ->capt('1');
$gamersbox = LTS::Div('.linediv')
    ->rowbox()
    ->add(LTS::Div('.codecapt')
        ->capt('Присоединилось игроков:'))
    ->add($gamersdiv);
$messagesbox = LTS::Div('messagesbox')
    ->addclass('messagesbox');

$golosbutton = LTS::Button('.timebut')
        ->capt('Начать голосование')
        ->width('800px')
        ->click('if(endgame) LTS(events).newgame(); else if(isgolos || progolosoval) LTS(events).golosend(); else LTS(events).golosbegin()');
$golosbox = LTS::Div('golosbox')
    ->rowbox()
    ->addclass('.linediv')
    ->add($golosbutton);

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
    ->add(LTS::Div('.linediv')
        ->rowbox()
        ->add(LTS::Div('.codecapt')
            ->capt('Код игры:'))
        ->add(LTS::Div('.code')
            ->capt($gamevars->get('code'))))
    ->add($messbox)
    ->add($lenbox)
    ->add($gamersbox)
    ->add($golosbox)
    ->add($chat)
    ->add($secretbox)
    ->add($messagesbox)
    ->add(LTS::Div('headdiv')->click('jQuery(this).hide()'));

$maindiv->css()->add('index.css')
    ->add('visibility', 'hidden');
$maindiv->js()->add('scrolldiv.js')
    ->add("LTS:admgame.js");
$maindiv->js('ready')->add(
<<<JS
    setInterval(function () {
        if(timermessage > 0 && --timermessage == 0) LTS(ErrWindow).close(); 
        LTS.post('messages', 'messages.php', { gameid : {$gameid}, mesid : messageid, userid : {$gamerid}});
    }, 500);
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
$events->client('startgame()', "if(result != 'ok') alert(result)")
    ->client('chat(mes)', "if(result != 'ok') alert(result); else $(chatinput).val('')")
    ->client('golosbegin()', '')
    ->client('creategoloschat()', "if(result != 'no') creategolosovanie(result)")
    ->client('sendresult(id)', "if(result != 'ok') alert(result);")
    ->client('golosend()', '')
    ->client('itogi()', '')
    ->client('vopr()', '')
    ->client('newgame()', '');
$events->server('startgame', function ($args) {
    global $gamevars;
    global $gameid;
    global $games;
    global $clients;
    global $messages;

    $all = $clients->all('parent_games', $gameid);
    if($all === false || count($all) < 3)
        return 'Необходимо, чтобы в игре присутствовало хотя бы три участника!';

    $secret = getRandomSentence('secrets.d');
    $len = $gamevars->get('len');
    $beg = date('Y-m-d H:i:s');
    $end = date('Y-m-d H:i:s', strtotime("+{$len} minutes", strtotime($beg)));
    $randomIndex = array_rand($all);
    $spy = $all[$randomIndex]->id;

    $clients//->value('spy', 0)
        ->value('golos', 0)
        ->value('kol', 0)
        ->setall('parent_games', $gameid);
    //$clients->value('spy', 1)
    //    ->set($spy);

    $games->value('len', $len)
        ->value('begin', $beg)
        //->value('end', $end)
        ->value('secret', $secret)
        ->value('spy', $spy)
        //->value('status', 3)
        ->set($gameid);

    //$messages->delall('parent_games', $gameid);
    $messages->value('parent_games', $gameid)
        ->value('type', 98)
        ->value('message', $end)
        ->insert();
    $messages->value('parent_games', $gameid)
        ->value('parent_clients', $spy)
        ->value('type', 5)
        ->value('message', 'Шпион назначен!')
        ->insert();
    $messages->value('parent_games', $gameid)
        ->value('type', 6)
        ->value('message', $secret)
        ->insert();
    $messages->value('parent_games', $gameid)
        ->value('type', 99)
        ->value('message', '0:00')
        ->insert();
    $messages->value('parent_games', $gameid)
        ->value('type', 3)
        ->value('message', 'Поехали!')
        ->insert();

    //$gamevars->set('end', $end);
            
    return 'ok';
})
    ->server('chat', function ($args) {
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
    ->server('golosbegin', function ($args) {
        global $messages; 
        global $gameid;

        $messages->value('parent_games', $gameid)
            ->value('type', 9)
            ->value('message', 'Начато голосование')
            ->insert();    
        $messages->value('parent_games', $gameid)
            ->value('type', 96)
            ->value('message', date("Y-m-d H:i:s", strtotime("+1 minute")))
            ->insert();    
        $messages->value('parent_games', $gameid)
            ->value('type', 97)
            ->value('message', '0:00')
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
    ->server('golosend', function ($args) {
        global $gameid;
        global $messages;
        
        $messages->value('parent_games', $gameid)
            ->value('type', 11)
            ->value('message', 'Окончание голосования')
            ->insert();  

        $all = $messages->all(array('parent_games' => $gameid, 'WHERE' => '`type` = 96 OR `type` = 97'));
        $d = date('Y-m-d H:i:s');
        foreach($all as $obj)
            $messages->value('message', $obj->type == 96 ? $d : '0:00')
                ->set($obj->id);
                
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
                $mes = 'Шпион был пойман!';
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
    })
    ->server('vopr', function ($args) {
        global $messages;
        global $gameid;
        $vopr = getRandomSentence('vopr.d');
        $messages->value('parent_games', $gameid)
            ->value('parent_clients', 0)
            ->value('type', 4)
            ->value('message', " *** МЕРЛИН: {$vopr}")
            ->insert();    
        return true;
    })
    ->server('newgame', function ($args) { 
        global $gameid;
        global $messages;
        global $gamevars;

        $messages->delall('parent_games', $gameid);
        $messages->value('parent_games', $gameid)
            ->value('type', 1)
            ->value('message', "Ждем админа...")
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 95)
            ->value('message', '1')
            ->insert();

        $gamevars->set('oldgame', true);

        return 'ok';
    });

    //$all = $clients->all(array('parent_games' => $gameid, 'COUNT' => 'id as kolusers', 'FIELDS' => ''));
    //$all = $messages->all(array('WHERE' => "`parent_games` = {$gameid} AND (`type` = 11 OR (`type` = 10 AND `parent_clients` = {$gamerid}))"));
      //  $all = $clients->all(array('parent_games' => $gameid, 'MAX' => 'kol as maxkol', 'GROUP' => 'id,name', 'FIELDS' => 'id,name'));
    //echo $clients->str_sql_query;
LTS::scriptversion('1');
LTS::Space()->build($maindiv);
?>
