// Form validation helper
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// File upload preview
function setupFilePreview(inputSelector, previewSelector) {
    const input = document.querySelector(inputSelector);
    const preview = document.querySelector(previewSelector);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function() {
        preview.innerHTML = '';
        
        if (this.files) {
            Array.from(this.files).forEach(file => {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'file-preview-item';
                
                const icon = document.createElement('i');
                icon.className = 'fas ' + getFileIcon(file.type);
                
                const nameSpan = document.createElement('span');
                nameSpan.textContent = file.name;
                
                const sizeSpan = document.createElement('span');
                sizeSpan.textContent = formatFileSize(file.size);
                
                fileDiv.appendChild(icon);
                fileDiv.appendChild(nameSpan);
                fileDiv.appendChild(sizeSpan);
                preview.appendChild(fileDiv);
            });
        }
    });
}

function getFileIcon(mimeType) {
    const icons = {
        'application/pdf': 'fa-file-pdf',
        'application/msword': 'fa-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fa-file-word',
        'image/jpeg': 'fa-file-image',
        'image/png': 'fa-file-image',
        'default': 'fa-file'
    };
    return icons[mimeType] || icons['default'];
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Initialize components when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // File previews
    setupFilePreview('#file-upload', '#file-preview');
    
    // Tooltips
    tippy('[data-tippy-content]', {
        arrow: true,
        animation: 'shift-away'
    });
});