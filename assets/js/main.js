document.addEventListener("DOMContentLoaded", function() {
    const alertBoxes = document.getElementsByClassName("alert");
    for (let i = 0; i < alertBoxes.length; i++) {
        const alertBox = alertBoxes[i];
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertBox);
            bsAlert.close();
        }, 2000); //close after 2 sec
    }

    const nextButton = document.getElementById('nextBtn');
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            const user = document.getElementById('username')?.value;
            const email = document.getElementById('email')?.value;
            const pwd = document.getElementById('pwd')?.value;
            const confirm = document.getElementById('pwd_confirm')?.value;

            if (!user || !email || !pwd || !confirm) return false;
            if (pwd !== confirm) return false;

            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            return true;
        });
    }

    const backButton = document.getElementById('backBtn');
    if (backButton) {
        backButton.addEventListener('click', function() {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
        });
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
