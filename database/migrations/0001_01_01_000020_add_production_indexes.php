<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'CREATE INDEX IF NOT EXISTS properties_owner_id_index ON properties (owner_id)',
            'CREATE INDEX IF NOT EXISTS properties_status_location_id_index ON properties (status, location_id)',
            'CREATE INDEX IF NOT EXISTS properties_status_property_type_id_index ON properties (status, property_type_id)',
            'CREATE INDEX IF NOT EXISTS inquiries_property_id_index ON inquiries (property_id)',
            'CREATE INDEX IF NOT EXISTS inquiries_user_id_index ON inquiries (user_id)',
            'CREATE INDEX IF NOT EXISTS inquiries_created_at_index ON inquiries (created_at)',
            'CREATE INDEX IF NOT EXISTS payments_created_at_index ON payments (created_at)',
            'CREATE INDEX IF NOT EXISTS messages_sender_id_index ON messages (sender_id)',
            'CREATE INDEX IF NOT EXISTS blogs_author_id_index ON blogs (author_id)',
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        $indexes = [
            'DROP INDEX IF EXISTS properties_owner_id_index',
            'DROP INDEX IF EXISTS properties_status_location_id_index',
            'DROP INDEX IF EXISTS properties_status_property_type_id_index',
            'DROP INDEX IF EXISTS inquiries_property_id_index',
            'DROP INDEX IF EXISTS inquiries_user_id_index',
            'DROP INDEX IF EXISTS inquiries_created_at_index',
            'DROP INDEX IF EXISTS payments_created_at_index',
            'DROP INDEX IF EXISTS messages_sender_id_index',
            'DROP INDEX IF EXISTS blogs_author_id_index',
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }
};
