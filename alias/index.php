<?php
include_once 'sdb.php';

$gamevars = LTS::Vars();

//$games->delall();
//$clients->delall();

function creategame($args)
{
    global $games;
    global $clients;
    global $messages;
    global $gamevars;

    $name = $args['name'];
    if($name == '')
        return 'Name cannot be empty!';

    $m1 = 10; $m2 = 99; 
    do {
        $randomNumber = rand($m1, $m2);
        $res = $games->all('code', $randomNumber); 
        if($res !== false)
        {
            $m1*= 10;
            $m2*= 10;
            $m2+= 9;
            $randomNumber = 0;
        }    
    }
    while($randomNumber == 0);

    $id = $games->value('code', $randomNumber)
        ->value('begin', date('Y-m-d H:i:s'))
        ->value('secret', '')
        ->value('level', 0)
        ->value('len', 3)
        ->value('spy', 0)
        //->value('status', 1)
        ->insert();

    $gid = $clients->value('parent_games', $id)
        ->value('admin', 1)
        //->value('spy', 0)
        ->value('name', $name)
        ->value('golos', 0)
        ->value('kol', 0)
        ->value('tik', date('Y-m-d H:i:s'))
        ->insert();

    $messages->value('parent_games', $id)
        ->value('type', 1)
        ->value('message', "Waiting for players...")
        ->insert();
    $messages->value('parent_games', $id)
        ->value('type', 95)
        ->value('message', "1")
        ->insert();
    $messages->value('parent_games', $id)
        ->value('parent_clients', $gid)
        ->value('type', 100)
        ->value('message', "Hello, {$name}!")
        ->insert();

    $gamevars->set('game', $id)
        ->set('code', $randomNumber)
        ->set('gamer', $gid)
        ->set('admin', 1)
        ->set('oldgame', false)
        //->set('status', 1) // waiting
        ->set('len', 3)
        //->set('end', date('Y-m-d H:i:s'))
        ->set('name', $name);

    return 'ok';
}

function connectgame($args)
{
    global $games;
    global $clients;
    global $gamevars;
    global $messages;

    $name = $args['name'];
    if($name == '')
        return 'Name cannot be empty!';

    $code = $args['code'];
    if($code == '')
        return 'Game connection code cannot be empty!';

    $all = $games->all('code', $code);
    if($all == false)
        return 'Game with the entered code not found!';

    $id = $all[0]->id;
    $len = $all[0]->len;
    //$end = $all[0]->end;
    //$status = $all[0]->status;

    $all = $clients->all(array('parent_games' => $id, 'name' => $name));
    if($all !== false)
        return 'A player with this name has already joined the game!';

    $gid = $clients->value('name', $name)
        ->value('parent_games', $id)
        ->value('admin', 0)
        //->value('spy', 0)
        ->value('golos', 0)
        ->value('kol', 0)
        ->value('tik', date('Y-m-d H:i:s'))
        ->insert();

    $messages->value('parent_games', $id)
        ->value('parent_clients', $gid)
        ->value('type', 100)
        ->value('message', "Hello, {$name}!")
        ->insert();

    $gamevars->set('game', $id)
        ->set('code', $code)
        ->set('gamer', $gid)
        ->set('admin', 0)
        ->set('oldgame', false)
        //->set('status', $status)
        ->set('len', $len)
        //->set('end', $end)
        ->set('name', $name);

    return 'ok';
}

//echo connectgame(array('name' => 'XXX', 'code' => 76));

//$games->delall();
//$clients->delall();
//$messages->delall();

$overlay = LTS::Div('overlay')->click("$(description).fadeOut();$(overlay).fadeOut();");
$description = LTS::Div('description')
    ->add(LTS::Button('closeDescription')
    ->addclass('close-btn')
    ->capt('Close')
    ->click("$(description).fadeOut();$(overlay).fadeOut();"))
    ->add(LTS::Div()->capt(file_get_contents('description.html')));

$maindiv = LTS::Div('maindiv')  
    ->columnbox()
    ->wrap()
    ->add($overlay)
    ->add($description)
    ->add($gamevars)
    ->add($ErrWindow)
    ->add(LTS::Div('headdiv'));
$maindiv->css()->add('index.css')
    ->add('visibility', 'hidden');

$maindiv->js()->add(
<<<JS
    var scriptversion = localStorage.getItem('scriptversion');
    if(! scriptversion || scriptversion != '1.0')
    {
        localStorage.setItem('scriptversion', '1.0');
        location.reload(true);    
    }
    var timermessage = 0; 
    function alert(mess) { $(ErrorMessage).html(mess); LTS(ErrWindow).open(); timermessage = 30; };
JS
);
$maindiv->js('ready')->add(
<<<JS
    setInterval(function () {
        if(timermessage > 0 && --timermessage == 0) LTS(ErrWindow).close(); 
    }, 500);
    $(maindiv).css('visibility', 'visible');
JS
);

$myname = LTS::Html('input')->addclass('inputs')->attr('type', 'text');
$myname->css()->add('font-size', '40px');

$gmname = LTS::Html('input')->addclass('inputs')->attr('type', 'text');
$gmname->css()->add('font-size', '40px');
$gmcode = LTS::Html('input')->addclass('inputs')->attr('type', 'text');
$gmcode->css()->add('font-size', '40px');

$newdlg = LTS::Dialog($maindiv)
    ->add(LTS::Div()
        ->columnbox()
        ->wrap()
        ->add(LTS::Html('h3')->capt('What is your name?'))
        ->add($myname))
    ->width(800)
    ->height(400)
    ->autoopen(false)
    ->option('modal', 'true')
    ->button('Start Game', 'var myname = $(myname).val(); LTS(events).creategame(myname);')
    ->button('Cancel', 'LTS(newdlg).close();');

$connectdlg = LTS::Dialog($maindiv)
    ->add(LTS::Div()
        ->columnbox()
        ->wrap()
        ->add(LTS::Html('h3')->capt('What is your name?'))
        ->add($gmname)
        ->add(LTS::Html('h3')->capt('Enter game code'))
        ->add($gmcode))
    ->width(800)
    ->height(600)
    ->autoopen(false)
    ->option('modal', 'true')
    ->button('Join Game', 'var gmname = $(gmname).val(); var gmcode = $(gmcode).val(); LTS(events).connectgame(gmname, gmcode);')
    ->button('Cancel', 'LTS(connectdlg).close();');

$events = LTS::Events($maindiv);
$events->client('creategame(name)', 'if(result == "ok") { LTS.vars().localsave(); LTS.page("admgame.php"); } else alert(result)');
$events->server('creategame', function ($args) {
    return creategame($args);    
});
$events->client('connectgame(name, code)', 'if(result == "ok") { LTS.vars().localsave(); LTS.page("game.php"); } else alert(result)');
$events->server('connectgame', function ($args) {
    return connectgame($args);    
});

LTS::Button($maindiv)->addclass('mainbuttons')->capt('Create Game')->click('LTS(newdlg).open()');
LTS::Button($maindiv)->addclass('mainbuttons')->capt('Join Game')->click('LTS(connectdlg).open()');
LTS::Button($maindiv)->addclass('mainbuttons')->capt('Game Rules')->click("$(description).fadeIn(); $(overlay).fadeIn();");

// Restoring last connection
$maindiv->js('create')->add(
<<<JS
    if(LTS.vars().localload()) { 
        var gameid = LTS.vars().get('game');
        if(gameid !== null)
            LTS(events).replay(gameid);
    }
JS
);

$events->client('replay(gameid)', 
<<<JS
    if(result) {
       $(repleyhello).text("Hello, " + LTS.vars().get("name") + "! Do you want to connect to your previous game?"); 
       LTS(replaydialog).open();
   }
JS
);
$events->server('replay', function ($args) {
    global $games;
    $id = $args['gameid'];
    return $games->get($id) !== false;
});

$repleyhello = LTS::Html('h3');
$replaydialog = LTS::Dialog($maindiv)
       ->add(LTS::Div()
           ->columnbox()
           ->wrap()
           ->add($repleyhello))
       ->width(800)
       ->height(500)
       ->autoopen(false)
       ->option('modal', 'true')
       ->button('Restore', 'LTS.vars().get("admin") ? LTS.page("admgame.php") : LTS.page("game.php")')
       ->button('Cancel', 'LTS(replaydialog).close();');            

LTS::scriptversion('1');
LTS::Space()->build($maindiv);
?>