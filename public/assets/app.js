(() => {
    const form = document.querySelector('.upload-form');
    if (!form) return;

    const input = form.querySelector('input[type="file"]');
    const zone = form.querySelector('.file-drop');
    const title = form.querySelector('.file-drop-title');
    const caption = form.querySelector('.file-drop-caption');
    const pickLabel = form.querySelector('.file-pick-label');
    const submit = form.querySelector('button[type="submit"]');
    const submitLabel = submit.textContent;

    const showFile = () => {
        const file = input.files[0];
        zone.classList.toggle('has-file', Boolean(file));
        title.textContent = file ? file.name : 'Thả file Excel vào đây';
        caption.textContent = file
            ? `${file.size >= 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(file.size / 1024)) + ' KB'} · Đã chọn, sẵn sàng kiểm tra`
            : 'Chỉ nhận Excel XLSX · Tối đa 10 MB';
        pickLabel.textContent = file ? 'Chọn file khác' : 'Hoặc chọn file từ máy';
    };

    input.addEventListener('change', showFile);
    zone.addEventListener('dragover', (event) => {
        event.preventDefault();
        zone.classList.add('is-dragging');
    });
    zone.addEventListener('dragleave', (event) => {
        if (!zone.contains(event.relatedTarget)) zone.classList.remove('is-dragging');
    });
    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('is-dragging');
        if (event.dataTransfer.files.length === 1) {
            input.files = event.dataTransfer.files;
            showFile();
        } else if (event.dataTransfer.files.length > 1) {
            caption.textContent = 'Vui lòng chọn một file mỗi lần.';
        }
    });
    form.addEventListener('submit', () => {
        form.setAttribute('aria-busy', 'true');
        submit.disabled = true;
        submit.textContent = 'Đang đọc file…';
    });
    window.addEventListener('pageshow', () => {
        form.removeAttribute('aria-busy');
        submit.disabled = false;
        submit.textContent = submitLabel;
        showFile();
    });
})();
