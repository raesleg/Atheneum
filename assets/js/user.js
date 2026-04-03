document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("profile_pic");
    const preview = document.getElementById("profileImg") || document.getElementById("imgPreview");
    const placeholder = document.getElementById("avatarPlaceholder");
    if (!input || !preview) return;

    // Original image fallback
    const originalSrc = preview.getAttribute("data-original") || "";

    input.addEventListener("change", function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const allowedTypes = ["image/jpeg", "image/png"];
        const allowedExtensions = ["jpg", "jpeg", "png"];
        const fileName = file.name.toLowerCase();
        const fileExt = fileName.split('.').pop();

        if (allowedTypes.includes(file.type) && allowedExtensions.includes(fileExt)) {
            // Valid image, show preview
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.style.display = "block";
                if (placeholder) placeholder.style.display = "none";
            };
            reader.readAsDataURL(file);
        } else {
            // Invalid image, keep original/fallback
            preview.src = originalSrc;
            preview.style.display = originalSrc ? "block" : "none";
            if (placeholder) placeholder.style.display = "flex";
        }
    });
});