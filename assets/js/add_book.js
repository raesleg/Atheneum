document.getElementById('cover-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const preview = document.getElementById('cover-preview');
    const area = document.getElementById('upload-area');
    const reader = new FileReader();
    reader.onload = function(ev) {
        preview.src = ev.target.result;
        preview.style.display = 'block';
        area.querySelector('p').textContent = file.name;
    };
    reader.readAsDataURL(file);
});

