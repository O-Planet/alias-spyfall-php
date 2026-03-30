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
    ->capt('minutes');

$hellobox = LTS::Div('name')->rowbox()->capt('Hello, ' . $gamevars->get('name') . '! You have created a new game!');
$messbox = LTS::Div('codemes')->rowbox()->capt('Share the game code with all participants so they can enter it and connect. Once all participants have connected, click the "Start" button.');

$lenbox = LTS::Div('.linediv')
    ->rowbox()
    ->add(LTS::Div('.codecapt')
        ->capt('Game time:'))
    ->add($lendiv)
    ->add($divmin)
    ->add(LTS::Button('.timebut')->capt('+')->click('addlen(1)'))
    ->add(LTS::Button('.timebut')->capt('-')->click('addlen(-1)'))
    ->add(LTS::Button('.timebut')->capt('Start')->width('250px')->click('LTS(events).startgame()'));

$gamersdiv = LTS::Div('.code')
    ->capt('1');
$gamersbox = LTS::Div('.linediv')
    ->rowbox()
    ->add(LTS::Div('.codecapt')
        ->capt('Players joined:'))
    ->add($gamersdiv);
$messagesbox = LTS::Div('messagesbox')
    ->addclass('messagesbox');

$golosbutton = LTS::Button('.timebut')
        ->capt('Start voting')
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
        ->capt('Chat: '))
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
                    alert('You need to choose someone or abstain from voting!');
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
            ->capt('Game code:'))
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

    // Check if file exists
    if (!file_exists($filename)) {
        return "File not found.";
    }

    // Read file and split into lines
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Check if file has any lines
    if (empty($lines)) {
        return "File is empty.";
    }
    
    // Index of last selected line
    $bylo = $gamevars->get('bylo');

    // Select random line
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
        return 'At least three participants must be present in the game!';

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
        ->value('message', 'Spy assigned!')
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
        ->value('message', 'Let\'s go!')
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
            ->value('message', 'Voting started')
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
            return 'You need to choose someone or abstain from voting!';

        $ob = $clients->get($id);
        if($ob === false)
            return 'Very strange error. If you see this text, it means you have outsmarted the most genius programmer in the world!';

        $kol = $ob->kol + 1;

        $clients->value('kol', $kol)
            ->set($id);
        $clients->value('golos', $id)
            ->set($gamerid);

        $messages->value('parent_games', $gameid)
            ->value('parent_clients', $gamerid)
            ->value('type', 10)
            ->value('message', 'Voted ' . $gamername)
            ->insert();  

        return 'ok';       
    })
    ->server('golosend', function ($args) {
        global $gameid;
        global $messages;
        
        $messages->value('parent_games', $gameid)
            ->value('type', 11)
            ->value('message', 'Voting ended')
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
            ->value('message', 'Summing up results ...')
            ->insert();

        $game = $games->get($gameid);
        $spyuser = $clients->get($game->spy);   

        $all = $clients->all(array('parent_games' => $gameid, 'ORDER' => '-kol'));

        $poiman = false;
        if($all === false || count($all) < 3)
            $mes = 'Some legendary error! The programmer is a bad coder.';
        else
        {
            if($all[0]->kol == 0)
                $mes = 'Participants abstained from voting. The spy managed to get away!';
            else
            if($all[0]->kol == $all[1]->kol)
                $mes = 'Votes were split. The spy managed to get away!';
            else
            if($game->spy == $all[0]->id)
            {
                $mes = 'The spy was caught!';
                $poiman = true;
            }
            else
                $mes = 'The spy was not detected!';                
        }

        $messages->value('parent_games', $gameid)
            ->value('parent_clients', $poiman ? $game->spy : 0)
            ->value('type', 13)
            ->value('message', $mes)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 14)
            ->value('message', 'S E C R E T: ' . $game->secret)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 15)
            ->value('message', 'S P Y: ' . $spyuser->name)
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 16)
            ->value('message',  '*** Advice from Merlin: ' . getRandomSentence('spy.d'))
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
            ->value('message', " *** MERLIN: {$vopr}")
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
            ->value('message', "Waiting for admin...")
            ->insert();
        $messages->value('parent_games', $gameid)
            ->value('type', 95)
            ->value('message', '1')
            ->insert();

        $gamevars->set('oldgame', true);

        return 'ok';
    });

LTS::scriptversion('1');
LTS::Space()->build($maindiv);
?>