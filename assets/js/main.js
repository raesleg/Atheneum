document.addEventListener("DOMContentLoaded", function() {
    // alert popup
    const alertBoxes = document.getElementsByClassName("alert");
    for (let i = 0; i < alertBoxes.length; i++) {
        const alertBox = alertBoxes[i];
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertBox);
            bsAlert.close();
        }, 3000); //close after 3 sec
    }             
});