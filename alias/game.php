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
    ->capt('Hello, ' . $gamevars->get('name'));

$messagesbox = LTS::Div('messagesbox')
    ->addclass('messagesbox');

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
    });

LTS::scriptversion('1');
LTS::Space()->build($maindiv);
?>