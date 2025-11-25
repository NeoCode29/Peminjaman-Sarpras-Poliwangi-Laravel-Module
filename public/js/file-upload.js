/**
 * File Upload Utility
 * Vanilla JavaScript implementation untuk file upload dengan progress bar
 * 
 * Usage:
 * <input type="file" id="myFile" accept="image/*">
 * <button onclick="uploadFile()">Upload</button>
 * <div id="uploadProgress"></div>
 * 
 * function uploadFile() {
 *     const fileInput = document.getElementById('myFile');
 *     const file = fileInput.files[0];
 *     
 *     FileUpload.upload(file, {
 *         url: '/api/upload/sarpras-image',
 *         type: 'image',
 *         onProgress: (percent) => console.log(`Progress: ${percent}%`),
 *         onSuccess: (data) => console.log('Success:', data),
 *         onError: (error) => console.error('Error:', error)
 *     });
 * }
 */

const FileUpload = {
    /**
     * Upload single file dengan progress tracking
     */
    upload: function(file, options = {}) {
        const {
            url = '/api/upload',
            type = 'image',
            category = null,
            onProgress = null,
            onSuccess = null,
            onError = null,
            additionalData = {}
        } = options;

        // Validasi file
        if (!file) {
            if (onError) onError('Tidak ada file yang dipilih');
            return;
        }

        // Validasi ukuran file (default 5MB)
        const maxSize = this.getMaxSize(type);
        if (file.size > maxSize) {
            const maxSizeMB = (maxSize / 1024 / 1024).toFixed(2);
            if (onError) onError(`Ukuran file maksimal ${maxSizeMB}MB`);
            return;
        }

        // Validasi tipe file
        const allowedTypes = this.getAllowedTypes(type);
        if (!this.validateFileType(file, allowedTypes)) {
            if (onError) onError(`Tipe file tidak diizinkan. Hanya menerima: ${allowedTypes.join(', ')}`);
            return;
        }

        // Prepare FormData
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);
        if (category) formData.append('category', category);

        // Append additional data
        for (const [key, value] of Object.entries(additionalData)) {
            formData.append(key, value);
        }

        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();

        // Progress event
        if (onProgress) {
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            });
        }

        // Load event (success)
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (onSuccess) onSuccess(response);
            } else {
                const error = JSON.parse(xhr.responseText);
                if (onError) onError(error.message || 'Upload gagal');
            }
        });

        // Error event
        xhr.addEventListener('error', () => {
            if (onError) onError('Terjadi kesalahan saat upload');
        });

        // Abort event
        xhr.addEventListener('abort', () => {
            if (onError) onError('Upload dibatalkan');
        });

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Send request
        xhr.open('POST', url);
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }
        xhr.send(formData);

        // Return xhr untuk bisa di-abort
        return xhr;
    },

    /**
     * Upload multiple files
     */
    uploadMultiple: function(files, options = {}) {
        const {
            url = '/api/upload/multiple',
            type = 'image',
            onProgress = null,
            onSuccess = null,
            onError = null,
            additionalData = {}
        } = options;

        if (!files || files.length === 0) {
            if (onError) onError('Tidak ada file yang dipilih');
            return;
        }

        const formData = new FormData();
        
        // Append all files
        Array.from(files).forEach((file) => {
            formData.append('files[]', file);
        });

        formData.append('type', type);

        // Append additional data
        for (const [key, value] of Object.entries(additionalData)) {
            formData.append(key, value);
        }

        // Use same upload logic
        return this.sendRequest(url, formData, { onProgress, onSuccess, onError });
    },

    /**
     * Preview image sebelum upload
     */
    previewImage: function(file, targetElement) {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            if (typeof targetElement === 'string') {
                targetElement = document.querySelector(targetElement);
            }
            if (targetElement) {
                targetElement.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    },

    /**
     * Format file size untuk display
     */
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    /**
     * Get max file size berdasarkan type (dalam bytes)
     */
    getMaxSize: function(type) {
        const sizes = {
            'image': 5 * 1024 * 1024,      // 5MB
            'document': 10 * 1024 * 1024,  // 10MB
            'identity': 2 * 1024 * 1024,   // 2MB
            'avatar': 1 * 1024 * 1024,     // 1MB
        };
        return sizes[type] || 5 * 1024 * 1024;
    },

    /**
     * Get allowed file types berdasarkan type
     */
    getAllowedTypes: function(type) {
        const types = {
            'image': ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'document': ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'identity': ['jpg', 'jpeg', 'png', 'pdf'],
            'avatar': ['jpg', 'jpeg', 'png', 'webp'],
        };
        return types[type] || types['image'];
    },

    /**
     * Validasi tipe file
     */
    validateFileType: function(file, allowedExtensions) {
        const extension = file.name.split('.').pop().toLowerCase();
        return allowedExtensions.includes(extension);
    },

    /**
     * Send request helper
     */
    sendRequest: function(url, formData, callbacks = {}) {
        const { onProgress, onSuccess, onError } = callbacks;

        const xhr = new XMLHttpRequest();

        if (onProgress) {
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            });
        }

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (onSuccess) onSuccess(response);
            } else {
                const error = JSON.parse(xhr.responseText);
                if (onError) onError(error.message || 'Upload gagal');
            }
        });

        xhr.addEventListener('error', () => {
            if (onError) onError('Terjadi kesalahan saat upload');
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        xhr.open('POST', url);
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }
        xhr.send(formData);

        return xhr;
    },

    /**
     * Create progress bar element
     */
    createProgressBar: function(containerId) {
        const container = typeof containerId === 'string' 
            ? document.getElementById(containerId) 
            : containerId;

        if (!container) return null;

        container.innerHTML = `
            <div style="width: 100%; background-color: #e0e0e0; border-radius: 4px; overflow: hidden;">
                <div id="progress-bar-fill" style="width: 0%; height: 24px; background-color: #4CAF50; text-align: center; line-height: 24px; color: white; transition: width 0.3s;">
                    <span id="progress-text">0%</span>
                </div>
            </div>
        `;

        return {
            update: function(percent) {
                const fill = document.getElementById('progress-bar-fill');
                const text = document.getElementById('progress-text');
                if (fill && text) {
                    fill.style.width = percent + '%';
                    text.textContent = percent + '%';
                }
            },
            reset: function() {
                this.update(0);
            },
            complete: function() {
                this.update(100);
                const fill = document.getElementById('progress-bar-fill');
                if (fill) {
                    fill.style.backgroundColor = '#4CAF50';
                }
            },
            error: function() {
                const fill = document.getElementById('progress-bar-fill');
                if (fill) {
                    fill.style.backgroundColor = '#f44336';
                }
            }
        };
    }
};

// Export untuk module jika diperlukan
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FileUpload;
}
