// Array of images and quotes
const images = [
    {src: "images/w1.jpg", quote: "“Education is the key to unlocking the world.”"},
    {src: "images/w2.jpg", quote: "“Science is a way of thinking, much more than it is a body of knowledge.”"},
    {src: "images/w3.jpg", quote: "“Learning never exhausts the mind.”"},
    // Add more images and quotes as needed
];

let currentIndex = 0;

// Function to rotate images and quotes
function rotateImage() {
    // Get the image and quote elements in the slider
    const sliderImage = document.querySelector('.slider img');
    const quoteText = document.querySelector('.slider .quote');

    // Update image source and quote text
    currentIndex = (currentIndex + 1) % images.length;
    sliderImage.src = images[currentIndex].src;
    quoteText.textContent = images[currentIndex].quote;
}

// Rotate every 5 seconds
setInterval(rotateImage, 5000);
