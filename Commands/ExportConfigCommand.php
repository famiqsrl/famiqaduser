<?php
declare(strict_types=1);

namespace Famiq\ActiveDirectoryUser\Commands;

use Illuminate\Console\Command;

/**
 * Comando para exportar el archivo de configuración LDAP del paquete.
 */
class ExportConfigCommand extends Command
{
    protected $signature = 'FamiqADUser:export';

    protected $description = 'Export the FamiqADUser configuration file';

    /**
     * Ejecuta el proceso de exportación.
     */
    public function handle(): int
    {
        $source = __DIR__.'/../ldap.php';
        $destination = config_path('ldap.php');

        if (!file_exists($source)) {
            $this->error('Source configuration not found.');
            return Command::FAILURE;
        }

        if (file_exists($destination) && ! $this->confirm('config/ldap.php exists. Overwrite?', false)) {
            $this->info('Export aborted.');
            return Command::SUCCESS;
        }

        if (! copy($source, $destination)) {
            $this->error('Failed to copy configuration file.');
            return Command::FAILURE;
        }

        $this->info('Configuration exported to config/ldap.php');
        return Command::SUCCESS;
    }
}
