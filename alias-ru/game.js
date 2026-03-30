var perviy = true;

var messageid = 0;
var issecretshow = false;
var secret = '';
var isgolos = false;
var progolosoval = false;
var spyid = 0;
var spyname = 'Шпион не выбран';
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
    spyname = 'Шпион не выбран';
    imspy = false;
    endgame = false;
    $(messagesbox).empty();
    $(secretbox).css('display', 'none');
    $(secretbutton).css('visibility', 'visible');
    $(chat).css('display', 'flex');
}

function getRandomLightColor() {
    // Генерируем случайные значения для красного, зеленого и синего компонентов
    const r = 10 * Math.floor(Math.random() * 10) + 150; 
    const g = 10 * Math.floor(Math.random() * 10) + 150; 
    const b = 10 * Math.floor(Math.random() * 10) + 150; 

    // Преобразуем в шестнадцатеричный формат с использованием суммирования строк
    const color = '#' + 
        ((r < 16 ? '0' : '') + r.toString(16)) + 
        ((g < 16 ? '0' : '') + g.toString(16)) + 
        ((b < 16 ? '0' : '') + b.toString(16));

    return color;
}

function setmessage(obj, color, _class)
{
    var mes = obj.type == 4 ? '> ' + obj.message : obj.message;
    var newmes = '<div style="color:' + color + '"' + (_class ? ' class = "' + _class + '">' : '>') + mes + '</div>';
    $(messagesbox).prepend(newmes);
}

function creategolosovanie(arr)
{
    if(! typeof arr == 'array')
        return;
    arr.forEach((obj) => {
        var newmes = '<div id=\"golos' + obj.id + '" class="golosuser" user="' + obj.id + '">' + obj.name + '</div>';
        $(messagesbox).prepend(newmes);
    });
    $(messagesbox).prepend('<div style="padding:30px;">Выбери шпиона, нажми "<"... </div>');
}

document.addEventListener("DOMContentLoaded", function () {
    setscrolldiv(document.getElementById('messagesbox'));

    if(LTS.vars().get('oldgame'))
    {
        $(hellobox).css('display', 'none');
    }

    jQuery(document).on('click', '.golosuser', function () {
        // Получаем атрибут user и содержимое текущего элемента

        // Убираем класс selected у всех других элементов с классом .golosuser
        jQuery('.golosuser').not(this).removeClass('selected');

        // Проверяем, есть ли класс selected у текущего элемента
        if (jQuery(this).hasClass('selected')) {
            jQuery(this).removeClass('selected'); // Удаляем класс, если он есть
            spyid = 0;
            spyname = 'Шпион не выбран';
        } else {
            jQuery(this).addClass('selected'); // Добавляем класс, если его нет
            spyid = jQuery(this).attr('user');
            spyname = 'Шпион - ' + jQuery(this).text() + '?';
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
                case 3: // старт игры
                    $(messagesbox).empty();
                    issecretshow = true;
                    $(hellobox).css('display', 'none');
                    $(secretword).text(secret);
                    $(secretbox).css('display', 'flex');
                    break;
                case 4: // чат
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
                case 5: // шпион
                    if(obj.parent_clients == {$gamerid})
                    {
                        secret = 'Ты - Ш П И О Н!';
                        imspy = true;
                    }
                    else
                        imspy = false;
                    break;
                case 6: // секрет
                    if(! imspy)
                        secret = obj.message;
                    return;
                case 9: // предложить голосование
                    $(chat).css('display', 'none');
                    $(messagesbox).empty();
                    spyname = 'Шпион не выбран';
                    isgolos = true;
                    spyid = 0;
                    $(secretword).text(spyname);
                    LTS(events).creategoloschat();
                    return;
                case 10: // проголосовал
                    if(isgolos && LTS.vars().get('gamer') == obj.parent_clients)
                    {
                        isgolos = false;
                        progolosoval = true;
                        messageid = obj.id;
                        $(messagesbox).empty();
                        $(secretbutton).css('visibility', 'hidden');
                        $(secretword).text('Ожидание результатов ...');
                    }
                    break;
                case 11: // окончание голосования
                    isgolos = false;
                    messageid = obj.id;
                    $(messagesbox).empty();
                    $(secretbutton).css('visibility', 'hidden');
                    $(secretword).text('Ожидание результатов ...');
                    LTS(events).itogi();
                    break;
                case 13:
                    $(messagesbox).empty();
                    if(imspy)
                        if(obj.parent_clients == {$gamerid})
                            $(secretword).text('Кажется, тебя поймали ...');
                        else
                            $(secretword).text('Тебе удалось улизнуть!');
                    else
                        $(secretword).text('Готово!');
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
                case 98: // енд дата
                    return;
                case 97:
                case 99: // тек время
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