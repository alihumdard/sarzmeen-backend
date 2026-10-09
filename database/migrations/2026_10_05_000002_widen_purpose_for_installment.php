<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 'installment' is 11 chars; the column was sized for 'sale'/'rent'.
    public function up(): void
    {
        Schema::getConnection()->statement(
            'ALTER TABLE properties ALTER COLUMN purpose TYPE varchar(20)'
        );
    }

    public function down(): void
    {
        Schema::getConnection()->statement(
            'ALTER TABLE properties ALTER COLUMN purpose TYPE varchar(10)'
        );
    }
};
