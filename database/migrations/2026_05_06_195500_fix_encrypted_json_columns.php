<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE entities MODIFY vies_payload LONGTEXT NULL');
        DB::statement('ALTER TABLE proposals MODIFY line_items LONGTEXT NOT NULL');
        DB::statement('ALTER TABLE proposals MODIFY totals LONGTEXT NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY line_items LONGTEXT NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY totals LONGTEXT NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE entities MODIFY vies_payload JSON NULL');
        DB::statement('ALTER TABLE proposals MODIFY line_items JSON NOT NULL');
        DB::statement('ALTER TABLE proposals MODIFY totals JSON NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY line_items JSON NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY totals JSON NOT NULL');
    }
};
