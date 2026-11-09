(function () {
    const textarea = document.getElementById('content');
    const format = document.getElementById('content-format');
    const error = document.getElementById('blog-editor-error');
    if (!textarea || !format || !error) return;

    function showError(message) {
        error.textContent = message;
        error.hidden = false;
    }

    if (!window.jQuery || !window.jQuery.fn.summernote) {
        showError('The rich text editor could not load. You can still edit the article in the text field.');
        return;
    }

    const original = textarea.value;
    try {
        if (format.value !== 'html') {
            const escaped = document.createElement('div');
            escaped.textContent = original;
            textarea.value = escaped.innerHTML.replace(/\r\n|\r|\n/g, '<br>');
        }
        const editor = window.jQuery(textarea);
        editor.summernote({
            height: 360,
            toolbar: [
                ['history', ['undo', 'redo']],
                ['style', ['style', 'bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['font', ['fontname', 'fontsize', 'color']],
                ['paragraph', ['ul', 'ol', 'paragraph', 'height']],
                ['insert', ['link', 'table', 'hr']],
            ],
            callbacks: {
                onImageUpload: function () {
                    showError('Use the cover image field to add an image to this post.');
                },
            },
        });
        textarea.removeAttribute('required');
        format.value = 'html';
        textarea.form.addEventListener('submit', function () {
            textarea.value = editor.summernote('code');
        });
    } catch (exception) {
        textarea.value = original;
        console.error('Unable to initialize the blog editor:', exception);
        showError('The rich text editor could not load. You can still edit the article in the text field.');
    }
})();
