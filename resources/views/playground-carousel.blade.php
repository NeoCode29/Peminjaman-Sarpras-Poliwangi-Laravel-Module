<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Playground Carousel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/js/app.js')
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }
        .playground-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .playground-card {
            width: 100%;
            max-width: 960px;
            background: #020617;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.35);
        }
        .playground-header {
            margin-bottom: 16px;
        }
        .playground-header h1 {
            font-size: 1.25rem;
            margin: 0 0 4px;
        }
        .playground-header p {
            margin: 0;
            font-size: 0.9rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
<div class="playground-wrapper">
    <div class="playground-card">
        <div class="playground-header">
            <h1>Playground: Carousel Component</h1>
            <p>Contoh penggunaan <code>&lt;x-carousel&gt;</code> dengan beberapa slide gambar.</p>
        </div>

        <x-carousel :autoplay="true" :interval="4000" class="u-mb-4">
            <div class="c-carousel__slide">
                <img src="https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="Ruang rapat" loading="lazy">
                <div class="c-carousel__slide-content">
                    <strong>Ruang Rapat Utama</strong>
                    <div>Ideal untuk pertemuan pimpinan dan kegiatan resmi kampus.</div>
                </div>
            </div>

            <div class="c-carousel__slide">
                <img src="https://images.pexels.com/photos/256395/pexels-photo-256395.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="Aula" loading="lazy">
                <div class="c-carousel__slide-content">
                    <strong>Aula Serbaguna</strong>
                    <div>Cocok untuk seminar, wisuda, dan kegiatan skala besar.</div>
                </div>
            </div>

            <div class="c-carousel__slide">
                <img src="https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="Lab komputer" loading="lazy">
                <div class="c-carousel__slide-content">
                    <strong>Laboratorium Komputer</strong>
                    <div>Dilengkapi perangkat modern untuk praktikum dan pelatihan.</div>
                </div>
            </div>
        </x-carousel>
    </div>
</div>
</body>
</html>
