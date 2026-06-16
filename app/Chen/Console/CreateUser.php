<?php

namespace App\Chen\Console;

use App\Chen\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'chen:user {email}';
    protected $description = 'Create a Chen platform user (login-only, full access)';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("A Chen user with email {$email} already exists.");
            return 1;
        }

        $name = $this->ask('Name');
        $password = $this->secret('Password') ?: $this->ask('Password');
        if (empty($password)) {
            $this->error('Password cannot be empty.');
            return 1;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Chen user {$email} created.");
        return 0;
    }
}
