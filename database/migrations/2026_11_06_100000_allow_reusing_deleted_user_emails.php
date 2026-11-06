<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const EMAIL_INDEX = 'users_email_unique';

    public function up(): void
    {
        switch (DB::getDriverName()) {
            case 'mysql':
                DB::statement('ALTER TABLE users DROP INDEX ' . self::EMAIL_INDEX);
                DB::statement(
                    'ALTER TABLE users ADD COLUMN active_email VARCHAR(255) '
                    . 'GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN email ELSE NULL END) VIRTUAL'
                );
                DB::statement('CREATE UNIQUE INDEX ' . self::EMAIL_INDEX . ' ON users (active_email)');
                break;
            case 'pgsql':
            case 'sqlite':
                DB::statement('DROP INDEX IF EXISTS ' . self::EMAIL_INDEX);
                DB::statement(
                    'CREATE UNIQUE INDEX ' . self::EMAIL_INDEX . ' ON users (email) WHERE deleted_at IS NULL'
                );
                break;
            default:
                throw new RuntimeException('Active-only user email uniqueness is not supported for this database driver.');
        }
    }

    public function down(): void
    {
        $duplicateEmails = DB::table('users')
            ->select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateEmails) {
            throw new RuntimeException(
                'Cannot restore global email uniqueness while multiple user records share an email address.'
            );
        }

        switch (DB::getDriverName()) {
            case 'mysql':
                DB::statement('DROP INDEX ' . self::EMAIL_INDEX . ' ON users');
                DB::statement('ALTER TABLE users DROP COLUMN active_email');
                DB::statement('CREATE UNIQUE INDEX ' . self::EMAIL_INDEX . ' ON users (email)');
                break;
            case 'pgsql':
            case 'sqlite':
                DB::statement('DROP INDEX IF EXISTS ' . self::EMAIL_INDEX);
                DB::statement('CREATE UNIQUE INDEX ' . self::EMAIL_INDEX . ' ON users (email)');
                break;
            default:
                throw new RuntimeException('Active-only user email uniqueness is not supported for this database driver.');
        }
    }
};
