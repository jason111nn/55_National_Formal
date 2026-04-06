<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Publishers 
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            $table->string('isbn_code', 10)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Contacts
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained('publishers')->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->timestamps();
        });

        // Admins (超級管理員與出版社管理員共用表)
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->enum('role', ['super', 'publisher']);
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        // Books
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('isbn', 20)->unique()->index();
            $table->string('title');
            $table->text('description');
            $table->string('author');
            $table->foreignId('publisher_id')->constrained('publishers')->onDelete('cascade');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        // Book Images
        Schema::create('book_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->string('image_url');
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_images');
        Schema::dropIfExists('books');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('publishers');
    }
};
