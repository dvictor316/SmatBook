<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('super_admin_user_bucket_overrides')) {
            Schema::create('super_admin_user_bucket_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('bucket', 40)->index();
                $table->string('note')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'bucket']);
            });
        }

        if (!Schema::hasTable('users')) {
            return;
        }

        $this->seedOverride(['thomas ogbodo'], 'state_manager', 'Live override: keep Thomas under state managers.');
        $this->seedOverride(['dauda uche'], 'state_manager', 'Live override: keep Dauda under state managers.');
        $this->seedOverride(['duke ogbodo', 'ogbodo duke'], 'registered_business', 'Live override: keep Duke under registered businesses.');
        $this->seedOverride(['mrs. eze florence', 'eze florence', 'ndeze2@gmail.com'], 'registered_business', 'Live override: keep Mrs. Eze Florence under registered businesses.');
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_user_bucket_overrides');
    }

    private function seedOverride(array $names, string $bucket, string $note): void
    {
        $userIds = DB::table('users')
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $normalized = strtolower(trim($name));
                    $pattern = '%' . str_replace(' ', '%', $normalized) . '%';

                    $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(TRIM(email)) LIKE ?', [$pattern]);
                }
            })
            ->pluck('id')
            ->all();

        foreach ($userIds as $userId) {
            DB::table('super_admin_user_bucket_overrides')->updateOrInsert(
                ['user_id' => $userId, 'bucket' => $bucket],
                ['note' => $note, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
};
