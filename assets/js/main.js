document.addEventListener("DOMContentLoaded", function() {
    const alertBoxes = document.getElementsByClassName("alert");
    for (let i = 0; i < alertBoxes.length; i++) {
        const alertBox = alertBoxes[i];
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertBox);
            bsAlert.close();
        }, 2000); //close after 2 sec
    }

    const input = document.getElementById("profile_pic");
    if (input) {
        input.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Use whichever preview img exists
            const imgPreview = document.getElementById("imgPreview") || document.getElementById("profileImg");
            if (imgPreview) {
                imgPreview.src = URL.createObjectURL(file);
            }
        });
    }
});

// document.getElementById("profile_pic").addEventListener("change", function(e) {
//     const file = e.target.files[0];
//     if (file) {
//         document.getElementById("imgPreview").src = URL.createObjectURL(file);
//     }
// });
