 <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::table('events', function (Blueprint $table) {
                if (!Schema::hasColumn('events', 'public_registration')) {
                    $table->boolean('public_registration')
                        ->default(false);
                }

                if (!Schema::hasColumn('events', 'require_approval')) {
                    $table->boolean('require_approval')
                        ->default(false);
                }

                if (!Schema::hasColumn('events', 'waitlist_enabled')) {
                    $table->boolean('waitlist_enabled')
                        ->default(false);
                }

                if (!Schema::hasColumn('events', 'contact_name')) {
                    $table->string('contact_name')
                        ->nullable();
                }

                if (!Schema::hasColumn('events', 'contact_email')) {
                    $table->string('contact_email')
                        ->nullable();
                }

                if (!Schema::hasColumn('events', 'contact_phone')) {
                    $table->string('contact_phone')
                        ->nullable();
                }
            });
        }

        public function down(): void
        {
            Schema::table('events', function (Blueprint $table) {
                $columns = [];

                if (Schema::hasColumn('events', 'public_registration')) {
                    $columns[] = 'public_registration';
                }

                if (Schema::hasColumn('events', 'require_approval')) {
                    $columns[] = 'require_approval';
                }

                if (Schema::hasColumn('events', 'waitlist_enabled')) {
                    $columns[] = 'waitlist_enabled';
                }

                if (Schema::hasColumn('events', 'contact_name')) {
                    $columns[] = 'contact_name';
                }

                if (Schema::hasColumn('events', 'contact_email')) {
                    $columns[] = 'contact_email';
                }

                if (Schema::hasColumn('events', 'contact_phone')) {
                    $columns[] = 'contact_phone';
                }

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    };
