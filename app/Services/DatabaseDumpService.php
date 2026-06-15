<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class DatabaseDumpService
{
    public function dump(string $destination): void
    {
        $connection = $this->connection();
        $handle = fopen($destination, 'wb');
        chmod($destination, 0600);
        $process = new Process([
            $this->binary(['mariadb-dump', 'mysqldump']),
            ...$this->connectionArguments($connection),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--no-tablespaces',
            '--add-drop-table',
            $connection['database'],
        ], env: $this->environment($connection));
        $process->setTimeout(3600);
        $process->run(function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });
        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($destination);
            throw new RuntimeException('Der Datenbankdump konnte nicht erstellt werden: '.$process->getErrorOutput());
        }
    }

    public function restore(string $source): void
    {
        $connection = $this->connection();
        $handle = fopen($source, 'rb');
        $process = new Process([
            $this->binary(['mariadb', 'mysql']),
            ...$this->connectionArguments($connection),
            '--default-character-set=utf8mb4',
            $connection['database'],
        ], env: $this->environment($connection), input: $handle);
        $process->setTimeout(3600);
        $process->run();
        fclose($handle);

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Die Datenbank konnte nicht wiederhergestellt werden: '.$process->getErrorOutput());
        }
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string, socket: ?string}
     */
    private function connection(): array
    {
        $name = config('database.default');
        $connection = config("database.connections.{$name}");

        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backup und Restore unterstützen derzeit ausschließlich MariaDB/MySQL.');
        }

        return [
            'host' => (string) ($connection['host'] ?? '127.0.0.1'),
            'port' => (int) ($connection['port'] ?? 3306),
            'database' => (string) $connection['database'],
            'username' => (string) $connection['username'],
            'password' => (string) ($connection['password'] ?? ''),
            'socket' => $connection['unix_socket'] ?: null,
        ];
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string, socket: ?string}  $connection
     * @return list<string>
     */
    private function connectionArguments(array $connection): array
    {
        $arguments = ["--user={$connection['username']}"];

        if ($connection['socket']) {
            $arguments[] = "--socket={$connection['socket']}";
        } else {
            $arguments[] = "--host={$connection['host']}";
            $arguments[] = "--port={$connection['port']}";
        }

        return $arguments;
    }

    /**
     * @param  array{password: string}  $connection
     * @return array<string, string>
     */
    private function environment(array $connection): array
    {
        return [
            ...getenv(),
            'MYSQL_PWD' => $connection['password'],
        ];
    }

    /**
     * @param  list<string>  $candidates
     */
    private function binary(array $candidates): string
    {
        $finder = new ExecutableFinder;

        foreach ($candidates as $candidate) {
            $path = $finder->find($candidate);

            if ($path !== null) {
                return $path;
            }
        }

        throw new RuntimeException('Das benötigte MariaDB-Kommandozeilenprogramm ist nicht installiert.');
    }
}
