<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ResetUserPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

final readonly class ResetUserPassword
{
    public function handle(User $actor, User $target, ResetUserPasswordData $data): void
    {
        DB::transaction(function () use ($actor, $target, $data): void {
            $target->update([
                'password' => $data->password,
            ]);

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            Activity::causedBy($actor)
                ->performedOn($target)
                ->withProperties(['event' => 'password.reset'])
                ->log('User password reset.');
        });
    }
}
