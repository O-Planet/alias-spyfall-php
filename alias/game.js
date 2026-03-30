var perviy = true;

var messageid = 0;
var issecretshow = false;
var secret = '';
var isgolos = false;
var progolosoval = false;
var spyid = 0;
var spyname = 'Spy not chosen';
var imspy = false;
var endgame = false;
var gameusers = {};
var userscolors = ['#dbd2ff'];

var timermessage = 0; 
function alert(mess) { $(ErrorMessage).html(mess); LTS(ErrWindow).open(); timermessage = 30; };

function start()
{
    messageid = 0;
    issecretshow = false;
    secret = '';
    isgolos = false;
    progolosoval = false;
    spyid = 0;
    spyname = 'Spy not chosen';
    imspy = false;
    endgame = false;
    $(messagesbox).empty();
    $(secretbox).css('display', 'none');
    $(secretbutton).css('visibility', 'visible');
    $(chat).css('display', 'flex');
}

function getRandomLightColor() {
    // Generate random values for red, green, and blue components
    const r = 10 * Math.floor(Math.random() * 10) + 150; 
    const g = 10 * Math.floor(Math.random() * 10) + 150; 
    const b = 10 * Math.floor(Math.random() * 10) + 150; 

    // Convert to hexadecimal format using string concatenation
    const color = '#' + 
        ((r < 16 ? '0' : '') + r.toString(16)) + 
        ((g < 16 ? '0' : '') + g.toString(16)) + 
        ((b < 16 ? '0' : '') + b.toString(16));

    return color;
}

function setmessage(obj, color, _class)
{
    var mes = obj.type == 4 ? '> ' + obj.message : obj.message;
    var newmes = '<div style=\"color:' + color + '\"' + (_class ? ' class = \"' + _class + '\">' : '>') + mes + '</div>';
    $(messagesbox).prepend(newmes);
}

function creategolosovanie(arr)
{
    if(! typeof arr == 'array')
        return;
    arr.forEach((obj) => {
        var newmes = '<div id=\\\"golos' + obj.id + '\" class=\"golosuser\" user=\"' + obj.id + '\">' + obj.name + '</div>';
        $(messagesbox).prepend(newmes);
    });
    $(messagesbox).prepend('<div style=\"padding:30px;\">Choose the spy, press "<" ... </div>');
}

document.addEventListener("DOMContentLoaded", function () {
    setscrolldiv(document.getElementById('messagesbox'));

    if(LTS.vars().get('oldgame'))
    {
        $(hellobox).css('display', 'none');
    }

    jQuery(document).on('click', '.golosuser', function () {
        // Get the user attribute and the content of the current element

        // Remove the selected class from all other elements with the .golosuser class
        jQuery('.golosuser').not(this).removeClass('selected');

        // Check if the current element has the selected class
        if (jQuery(this).hasClass('selected')) {
            jQuery(this).removeClass('selected'); // Remove the class if it exists
            spyid = 0;
            spyname = 'Spy not chosen';
        } else {
            jQuery(this).addClass('selected'); // Add the class if it doesn't exist
            spyid = jQuery(this).attr('user');
            spyname = 'Spy - ' + jQuery(this).text() + '?';
        }

        $(secretword).text(spyname);
    });

    LTS.request('messages', function (result) {
        if(result == 'no' || result == 'Error')
            return; 

        result.forEach((obj) => {
            var color = '#dbd2ff';
            var _class = null;
            if(obj.id > messageid && ! isgolos)
                messageid = obj.id;
            switch(obj.type)
            {
                case 1:
                    start();
                    break;
                case 3: // game start
                    $(messagesbox).empty();
                    issecretshow = true;
                    $(hellobox).css('display', 'none');
                    $(secretword).text(secret);
                    $(secretbox).css('display', 'flex');
                    break;
                case 4: // chat
                    var userkey = 'user' + obj.parent_users;
                    if(!(userkey in gameusers))
                    {
                        do {
                            color = getRandomLightColor();
                        } while(userscolors.includes(color));
                        gameusers[userkey] = color;
                        userscolors.push(color);
                    }
                    else
                        color = gameusers[userkey]; 
                    break;
                case 5: // spy
                    if(obj.parent_clients == {$gamerid})
                    {
                        secret = "You are A S P Y!";
                        imspy = true;
                    }
                    else
                        imspy = false;
                    break;
                case 6: // secret
                    if(! imspy)
                        secret = obj.message;
                    return;
                case 9: // propose voting
                    $(chat).css('display', 'none');
                    $(messagesbox).empty();
                    spyname = 'Spy not chosen';
                    isgolos = true;
                    spyid = 0;
                    $(secretword).text(spyname);
                    LTS(events).creategoloschat();
                    return;
                case 10: // voted
                    if(isgolos && LTS.vars().get('gamer') == obj.parent_clients)
                    {
                        isgolos = false;
                        progolosoval = true;
                        messageid = obj.id;
                        $(messagesbox).empty();
                        $(secretbutton).css('visibility', 'hidden');
                        $(secretword).text('Waiting for results ...');
                    }
                    break;
                case 11: // end of voting
                    isgolos = false;
                    messageid = obj.id;
                    $(messagesbox).empty();
                    $(secretbutton).css('visibility', 'hidden');
                    $(secretword).text('Waiting for results ...');
                    LTS(events).itogi();
                    break;
                case 13:
                    $(messagesbox).empty();
                    if(imspy)
                        if(obj.parent_clients == {$gamerid})
                            $(secretword).text('Seems like you got caught ...');
                        else
                            $(secretword).text('You managed to escape!');
                    else
                        $(secretword).text('Done!');
                    break;
                case 14:
                    _class = 'spymess';
                    color = '#f1c40f';
                    break;
                case 15:
                    _class = 'spymess';
                    color = '#e74c3c';
                    break;
                case 16:
                    endgame = true;
                    if(! imspy)
                        return;
                    _class = 'spymess';
                    color = '#9b59b6';
                    break;
                case 12:
                case 95:
                case 96:
                case 98: // end date
                    return;
                case 97:
                case 99: // current time
                     $(timerdiv).text(obj.message);
                     return;
            }

            if(! isgolos)
                setmessage(obj, color, _class);
        });

        if(perviy)
        {
            perviy = false;
            $(maindiv).css('visibility', 'visible');
        }
    });
});