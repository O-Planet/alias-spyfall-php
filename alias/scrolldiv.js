// Variables for tracking touches and dragging
let startY;
let scrollTop;
let isDragging = false;
let scrollableDiv;

function setscrolldiv(div)
{
    scrollableDiv = div;

    // Handling mouse wheel scrolling
    scrollableDiv.addEventListener('wheel', (event) => {
        event.preventDefault(); // Prevent default scrolling behavior
        scrollableDiv.scrollTop += event.deltaY; // Scroll the content
    });

    // Handling touch events on touchscreens
    scrollableDiv.addEventListener('touchstart', (event) => {
        startY = event.touches[0].clientY; // Remember the initial touch position
        scrollTop = scrollableDiv.scrollTop; // Remember the current scroll position
    });

    scrollableDiv.addEventListener('touchmove', (event) => {
        const touchY = event.touches[0].clientY; // Get the current touch position
        const distance = touchY - startY; // Calculate the movement distance

        // Scroll the content
        scrollableDiv.scrollTop = scrollTop - distance;
        event.preventDefault(); // Prevent default scrolling behavior
    });

    // Handling mouse dragging
    scrollableDiv.addEventListener('mousedown', (event) => {
        isDragging = true; // Set the dragging flag
        startY = event.clientY; // Remember the initial position
        scrollTop = scrollableDiv.scrollTop; // Remember the current scroll position
    });

    scrollableDiv.addEventListener('mousemove', (event) => {
        if (isDragging) {
            const distance = event.clientY - startY; // Calculate the movement distance
            scrollableDiv.scrollTop = scrollTop - distance; // Scroll the content
        }
    });

    scrollableDiv.addEventListener('mouseup', () => {
        isDragging = false; // Reset the dragging flag
    });

    scrollableDiv.addEventListener('mouseleave', () => {
        isDragging = false; // Reset the flag when mouse leaves the container
    });
}