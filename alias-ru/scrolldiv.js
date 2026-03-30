// Переменные для отслеживания касаний и перетаскивания
let startY;
let scrollTop;
let isDragging = false;
let scrollableDiv;

function setscrolldiv(div)
{
    scrollableDiv = div;

    // Обработка прокрутки колесиком мыши
    scrollableDiv.addEventListener('wheel', (event) => {
        event.preventDefault(); // Предотвращаем стандартное поведение прокрутки
        scrollableDiv.scrollTop += event.deltaY; // Прокручиваем содержимое
    });

    // Обработка касания на сенсорных экранах
    scrollableDiv.addEventListener('touchstart', (event) => {
        startY = event.touches[0].clientY; // Запоминаем начальную позицию касания
        scrollTop = scrollableDiv.scrollTop; // Запоминаем текущее положение прокрутки
    });

    scrollableDiv.addEventListener('touchmove', (event) => {
        const touchY = event.touches[0].clientY; // Получаем текущую позицию касания
        const distance = touchY - startY; // Вычисляем расстояние перемещения

        // Прокручиваем содержимое
        scrollableDiv.scrollTop = scrollTop - distance;
        event.preventDefault(); // Предотвращаем стандартное поведение прокрутки
    });

    // Обработка перетаскивания мышью
    scrollableDiv.addEventListener('mousedown', (event) => {
        isDragging = true; // Устанавливаем флаг перетаскивания
        startY = event.clientY; // Запоминаем начальную позицию
        scrollTop = scrollableDiv.scrollTop; // Запоминаем текущее положение прокрутки
    });

    scrollableDiv.addEventListener('mousemove', (event) => {
        if (isDragging) {
            const distance = event.clientY - startY; // Вычисляем расстояние перемещения
            scrollableDiv.scrollTop = scrollTop - distance; // Прокручиваем содержимое
        }
    });

    scrollableDiv.addEventListener('mouseup', () => {
        isDragging = false; // Сбрасываем флаг перетаскивания
    });

    scrollableDiv.addEventListener('mouseleave', () => {
        isDragging = false; // Сбрасываем флаг при выходе мыши из контейнера
    });
}