<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extends existing web profile tables and creates new tables
     * to match frontend CMS data structures.
     */
    public function up(): void
    {
        // ──────────────────────────────────────────────────────────
        // 1. Extend `settings` table
        // ──────────────────────────────────────────────────────────
        Schema::table('settings', function (Blueprint $table) {
            $table->string('floating_card_title')->nullable()->after('link_maps');
            $table->string('floating_card_desc')->nullable()->after('floating_card_title');
            $table->integer('tahun_berdiri')->nullable()->after('floating_card_desc');
            $table->integer('jamaah_aktif')->nullable()->after('tahun_berdiri');
            $table->json('hero_images')->nullable()->after('jamaah_aktif');
            $table->string('history_image')->nullable()->after('hero_images');
            $table->text('committee_description')->nullable()->after('history_image');
            $table->string('link_facebook')->nullable()->after('link_instagram');
            $table->string('link_youtube')->nullable()->after('link_facebook');
            $table->string('link_twitter')->nullable()->after('link_youtube');
            $table->string('link_tiktok')->nullable()->after('link_twitter');
            $table->string('email')->nullable()->after('no_whatsapp');
            $table->string('telepon_kantor', 30)->nullable()->after('email');
            $table->text('alamat_lengkap')->nullable()->after('telepon_kantor');
            $table->string('kota')->nullable()->after('alamat_lengkap');
            $table->string('kodepos', 10)->nullable()->after('kota');
            $table->longText('maps_iframe')->nullable()->after('link_maps');
        });

        // ──────────────────────────────────────────────────────────
        // 2. Extend `services` table
        // ──────────────────────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            $table->string('category')->nullable()->after('icon');
            $table->string('badge')->nullable()->after('category');
            $table->string('bg_image')->nullable()->after('badge');
            $table->json('details')->nullable()->after('description');
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        // ──────────────────────────────────────────────────────────
        // 3. Extend `galleries` table
        // ──────────────────────────────────────────────────────────
        Schema::table('galleries', function (Blueprint $table) {
            $table->text('subcaption')->nullable()->after('caption');
            $table->string('tag')->nullable()->after('subcaption');
            $table->string('category')->nullable()->after('tag');
            $table->string('icon_name', 50)->nullable()->after('category');
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        // ──────────────────────────────────────────────────────────
        // 4. Extend `events` table
        // ──────────────────────────────────────────────────────────
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->string('badge')->nullable()->after('category');
            $table->string('image')->nullable()->after('badge');
            $table->string('location')->nullable()->after('image');
            $table->string('author')->nullable()->after('location');
            $table->longText('content')->nullable()->after('description');
            $table->integer('hits')->default(0)->after('is_active');
        });

        // ──────────────────────────────────────────────────────────
        // 5. Create `whatsapp_contacts` table
        // ──────────────────────────────────────────────────────────
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number', 20);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────
        // 6. Create `committee_divisions` table
        // ──────────────────────────────────────────────────────────
        Schema::create('committee_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────
        // 7. Create `committee_members` table
        // ──────────────────────────────────────────────────────────
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->string('group'); // dewan_penasihat, pengurus_harian, divisi
            $table->foreignId('division_id')->nullable()->constrained('committee_divisions')->nullOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────
        // 8. Create `cta_settings` table
        // ──────────────────────────────────────────────────────────
        Schema::create('cta_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('quote')->nullable();
            $table->string('quote_source')->nullable();
            $table->integer('total_donors')->default(0);
            $table->json('slider_images')->nullable();
            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────
        // 9. Create `cta_programs` table
        // ──────────────────────────────────────────────────────────
        Schema::create('cta_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cta_setting_id')->constrained('cta_settings')->cascadeOnDelete();
            $table->string('name');
            $table->integer('progress')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────
        // 10. Create `master_categories` table
        // ──────────────────────────────────────────────────────────
        Schema::create('master_categories', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // kategori, tipe_berita, label, status
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon_name', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_categories');
        Schema::dropIfExists('cta_programs');
        Schema::dropIfExists('cta_settings');
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('committee_divisions');
        Schema::dropIfExists('whatsapp_contacts');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['category', 'badge', 'image', 'location', 'author', 'content', 'hits']);
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn(['subcaption', 'tag', 'category', 'icon_name', 'sort_order']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['category', 'badge', 'bg_image', 'details', 'sort_order']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'floating_card_title', 'floating_card_desc', 'tahun_berdiri', 'jamaah_aktif',
                'hero_images', 'history_image', 'committee_description',
                'link_facebook', 'link_youtube', 'link_twitter', 'link_tiktok',
                'email', 'telepon_kantor', 'alamat_lengkap', 'kota', 'kodepos', 'maps_iframe',
            ]);
        });
    }
};
