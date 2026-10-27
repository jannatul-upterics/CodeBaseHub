<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {--name= : The name of the administrator}
                            {--email= : The email address of the administrator}
                            {--password= : The administrator password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely create or update an administrator user account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name');
        if (! $name) {
            $name = $this->input->isInteractive()
                ? text(
                    label: 'Administrator Name',
                    default: 'Admin User',
                    required: true
                )
                : 'Admin User';
        }

        $email = $this->option('email');
        if (! $email) {
            if ($this->input->isInteractive()) {
                $email = text(
                    label: 'Administrator Email',
                    placeholder: 'admin@codebasehub.local',
                    required: true
                );
            } else {
                $this->error('The --email option is required when running in non-interactive mode.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email']]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $password = $this->option('password');
        if (! $password) {
            if ($this->input->isInteractive()) {
                $password = password(
                    label: 'Password (min 8 characters)',
                    required: true
                );
            } else {
                $this->error('The --password option is required when running in non-interactive mode.');

                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            $this->info("Administrator [{$email}] was updated successfully.");

            return self::SUCCESS;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Administrator [{$email}] created successfully.");

        return self::SUCCESS;
    }
}
