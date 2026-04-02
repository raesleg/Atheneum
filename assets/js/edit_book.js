document.getElementById('cover-input').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    let preview = document.getElementById('new-cover-preview');
    if (!preview) {
        preview = document.createElement('img');
        preview.id = 'new-cover-preview';
        preview.alt = 'New cover preview';
        preview.style.cssText = 'display:none;max-height:140px;border-radius:6px;margin-top:10px;object-fit:contain;';
        document.getElementById('upload-area').after(preview);
    }

    const reader = new FileReader();
    reader.onload = function (ev) {
        preview.src = ev.target.result;
        preview.style.display = 'block';
        // Update upload area label to show chosen file
        document.getElementById('upload-area').querySelector('p').innerHTML =
            '<strong>' + file.name + '</strong><br><small>Click to choose a different file</small>';
    };
    reader.readAsDataURL(file);
});
