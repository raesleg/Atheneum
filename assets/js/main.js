document.addEventListener("DOMContentLoaded", function() {
    const alertBoxes = document.getElementsByClassName("alert");
    for (let i = 0; i < alertBoxes.length; i++) {
        const alertBox = alertBoxes[i];
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertBox);
            bsAlert.close();
        }, 2000); //close after 2 sec
    }
});