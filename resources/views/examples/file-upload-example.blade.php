<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Upload Example</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 10px; color: #333; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .upload-section { margin-bottom: 30px; padding: 20px; border: 2px dashed #ddd; border-radius: 8px; }
        .upload-section h2 { font-size: 18px; margin-bottom: 15px; color: #444; }
        .file-input-wrapper { position: relative; display: inline-block; margin-bottom: 10px; }
        input[type="file"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #45a049; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .preview { margin-top: 15px; max-width: 300px; border-radius: 4px; }
        .message { padding: 12px; margin-top: 10px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .file-info { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>File Upload Examples</h1>
        <p class="subtitle">Contoh implementasi file upload dengan vanilla JavaScript</p>

        <!-- Example 1: Upload Image dengan Preview -->
        <div class="upload-section">
            <h2>1. Upload Gambar Sarpras (dengan Preview)</h2>
            <input type="file" id="imageFile" accept="image/*">
            <button onclick="uploadImage()">Upload Gambar</button>
            <div id="imageProgress"></div>
            <img id="imagePreview" class="preview" style="display: none;">
            <div id="imageMessage"></div>
        </div>

        <!-- Example 2: Upload Document -->
        <div class="upload-section">
            <h2>2. Upload Dokumen (Private)</h2>
            <input type="file" id="documentFile" accept=".pdf,.doc,.docx">
            <button onclick="uploadDocument()">Upload Dokumen</button>
            <div id="documentProgress"></div>
            <div id="documentMessage"></div>
        </div>

        <!-- Example 3: Upload Avatar -->
        <div class="upload-section">
            <h2>3. Upload Avatar</h2>
            <input type="file" id="avatarFile" accept="image/*">
            <button onclick="uploadAvatar()">Upload Avatar</button>
            <div id="avatarProgress"></div>
            <img id="avatarPreview" class="preview" style="display: none;">
            <div id="avatarMessage"></div>
        </div>

        <!-- Example 4: Upload Multiple -->
        <div class="upload-section">
            <h2>4. Upload Multiple Files</h2>
            <input type="file" id="multipleFiles" multiple accept="image/*,.pdf">
            <button onclick="uploadMultiple()">Upload Semua</button>
            <div id="multipleProgress"></div>
            <div id="multipleMessage"></div>
        </div>
    </div>

    <script src="/js/file-upload.js"></script>
    <script>
        // Example 1: Upload Image dengan Preview
        function uploadImage() {
            const fileInput = document.getElementById('imageFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('imageMessage', 'Pilih file terlebih dahulu', 'error');
                return;
            }

            // Preview image
            FileUpload.previewImage(file, '#imagePreview');
            document.getElementById('imagePreview').style.display = 'block';

            // Show file info
            showMessage('imageMessage', `File: ${file.name} (${FileUpload.formatFileSize(file.size)})`, 'info');

            // Create progress bar
            const progressBar = FileUpload.createProgressBar('imageProgress');

            // Upload
            FileUpload.upload(file, {
                url: '/api/upload/sarpras-image',
                type: 'image',
                onProgress: (percent) => {
                    progressBar.update(percent);
                },
                onSuccess: (response) => {
                    progressBar.complete();
                    showMessage('imageMessage', 'Gambar berhasil diupload!', 'success');
                    console.log('Response:', response);
                },
                onError: (error) => {
                    progressBar.error();
                    showMessage('imageMessage', 'Error: ' + error, 'error');
                }
            });
        }

        // Example 2: Upload Document
        function uploadDocument() {
            const fileInput = document.getElementById('documentFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('documentMessage', 'Pilih file terlebih dahulu', 'error');
                return;
            }

            showMessage('documentMessage', `File: ${file.name} (${FileUpload.formatFileSize(file.size)})`, 'info');

            const progressBar = FileUpload.createProgressBar('documentProgress');

            FileUpload.upload(file, {
                url: '/api/upload/document',
                type: 'document',
                additionalData: {
                    model_type: 'App\\Models\\Peminjaman',
                    model_id: 1 // Example ID
                },
                onProgress: (percent) => {
                    progressBar.update(percent);
                },
                onSuccess: (response) => {
                    progressBar.complete();
                    showMessage('documentMessage', 'Dokumen berhasil diupload!', 'success');
                    console.log('Response:', response);
                },
                onError: (error) => {
                    progressBar.error();
                    showMessage('documentMessage', 'Error: ' + error, 'error');
                }
            });
        }

        // Example 3: Upload Avatar
        function uploadAvatar() {
            const fileInput = document.getElementById('avatarFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('avatarMessage', 'Pilih file terlebih dahulu', 'error');
                return;
            }

            FileUpload.previewImage(file, '#avatarPreview');
            document.getElementById('avatarPreview').style.display = 'block';

            showMessage('avatarMessage', `File: ${file.name} (${FileUpload.formatFileSize(file.size)})`, 'info');

            const progressBar = FileUpload.createProgressBar('avatarProgress');

            FileUpload.upload(file, {
                url: '/api/upload/avatar',
                type: 'avatar',
                onProgress: (percent) => {
                    progressBar.update(percent);
                },
                onSuccess: (response) => {
                    progressBar.complete();
                    showMessage('avatarMessage', 'Avatar berhasil diupload!', 'success');
                    console.log('Response:', response);
                },
                onError: (error) => {
                    progressBar.error();
                    showMessage('avatarMessage', 'Error: ' + error, 'error');
                }
            });
        }

        // Example 4: Upload Multiple
        function uploadMultiple() {
            const fileInput = document.getElementById('multipleFiles');
            const files = fileInput.files;
            
            if (files.length === 0) {
                showMessage('multipleMessage', 'Pilih file terlebih dahulu', 'error');
                return;
            }

            showMessage('multipleMessage', `${files.length} file dipilih`, 'info');

            const progressBar = FileUpload.createProgressBar('multipleProgress');

            FileUpload.uploadMultiple(files, {
                url: '/api/upload/multiple',
                type: 'document',
                additionalData: {
                    model_type: 'App\\Models\\Peminjaman',
                    model_id: 1
                },
                onProgress: (percent) => {
                    progressBar.update(percent);
                },
                onSuccess: (response) => {
                    progressBar.complete();
                    showMessage('multipleMessage', `${files.length} file berhasil diupload!`, 'success');
                    console.log('Response:', response);
                },
                onError: (error) => {
                    progressBar.error();
                    showMessage('multipleMessage', 'Error: ' + error, 'error');
                }
            });
        }

        // Helper function
        function showMessage(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.innerHTML = `<div class="message ${type}">${message}</div>`;
        }

        // Preview on file select
        document.getElementById('imageFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                FileUpload.previewImage(file, '#imagePreview');
                document.getElementById('imagePreview').style.display = 'block';
            }
        });

        document.getElementById('avatarFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                FileUpload.previewImage(file, '#avatarPreview');
                document.getElementById('avatarPreview').style.display = 'block';
            }
        });
    </script>
</body>
</html>
