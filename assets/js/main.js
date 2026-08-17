// instead of using use-mobile hook
function checkDevice() {
    if (window.innerWidth < 768) {
        console.log("Mobile view active");
    }
}
window.addEventListener('resize', checkDevice);
