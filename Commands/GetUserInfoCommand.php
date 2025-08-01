<?php

namespace Famiq\ActiveDirectoryUser\Commands;

use Illuminate\Console\Command;
use Famiq\ActiveDirectoryUser\ActiveDirectoryUser;

class GetUserInfoCommand extends Command
{
    protected $signature = 'FamiqADUser:info {mail}';

    protected $description = 'Display information about an Active Directory user';

    public function handle()
    {
        $mail = $this->argument('mail');
        $user = ActiveDirectoryUser::findByMail($mail);

        if (! $user) {
            $this->error('User not found.');
            return Command::FAILURE;
        }

        $this->info('Name: ' . ($user->getCommonName() ?? 'N/A'));
        $this->info('Department: ' . ($user->getDepartamento() ?? 'N/A'));
        $this->info('Phone: ' . ($user->getPhoneNumber() ?? 'N/A'));

        $manager = $user->getManager();
        if ($manager) {
            $this->info('Manager: ' . $manager->getCommonName());
        }

        return Command::SUCCESS;
    }
}
