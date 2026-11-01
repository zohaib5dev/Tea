<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'admin:make {email : Email of an existing user} {--remove : Take admin rights away instead}';

    protected $description = 'Give (or remove) admin rights for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('remove')])->save();
        $this->info($user->name . ($this->option('remove') ? ' is no longer an admin.' : ' is now an admin.'));

        return self::SUCCESS;
    }
}
