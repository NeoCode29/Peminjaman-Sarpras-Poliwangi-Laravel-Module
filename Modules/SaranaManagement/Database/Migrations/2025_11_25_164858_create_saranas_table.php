<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaranasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saranas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sarana')->unique()->comment('Kode unik sarana, contoh: SRN-001');
            $table->string('nama');
            $table->foreignId('kategori_id')->constrained('kategori_saranas')->onDelete('restrict');
            $table->string('merk')->nullable();
            $table->text('spesifikasi')->nullable();
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'dalam_perbaikan'])->default('baik');
            $table->enum('status_ketersediaan', ['tersedia', 'dipinjam', 'dalam_perbaikan', 'tidak_tersedia'])->default('tersedia');

            // Tipe sarana: pooled (qty) atau serialized (per unit)
            $table->enum('type', ['pooled', 'serialized'])->default('pooled');

            // Statistik jumlah unit
            $table->integer('jumlah_total')->default(1)->comment('Jumlah unit total');
            $table->integer('jumlah_tersedia')->default(0)->comment('Unit tersedia');
            $table->integer('jumlah_rusak')->default(0)->comment('Unit rusak');
            $table->integer('jumlah_maintenance')->default(0)->comment('Unit dalam maintenance');
            $table->integer('jumlah_hilang')->default(0)->comment('Unit hilang');

            $table->integer('tahun_perolehan')->nullable();
            $table->decimal('nilai_perolehan', 15, 2)->nullable()->comment('Harga saat perolehan');
            $table->string('lokasi_penyimpanan')->nullable();
            $table->string('foto')->nullable()->comment('Path ke file foto');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('kode_sarana');
            $table->index('kategori_id');
            $table->index('kondisi');
            $table->index('status_ketersediaan');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saranas');
    }
}
