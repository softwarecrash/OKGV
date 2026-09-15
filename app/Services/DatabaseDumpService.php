<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseDumpService
{
    public function dump(string $destination): void
    {
        try {
            $this->dumpWithClient($destination);
        } catch (Throwable $clientException) {
            try {
                $this->dumpWithPdo($destination);
            } catch (Throwable $pdoException) {
                throw new RuntimeException(
                    'Der Datenbankdump konnte weder über das MariaDB-Programm noch über die PHP-Datenbankverbindung erstellt werden. '
                    .'MariaDB: '.$clientException->getMessage().' PHP: '.$pdoException->getMessage(),
                    previous: $pdoException,
                );
            }
        }
    }

    private function dumpWithClient(string $destination): void
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

    /**
     * Shared hosting may permit PHP's database connection but not external
     * processes. OKGV does not rely on database routines or triggers, so a
     * schema and data dump through PDO is a portable fallback.
     */
    private function dumpWithPdo(string $destination): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($destination, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Die temporäre Dump-Datei konnte nicht geöffnet werden.');
        }

        chmod($destination, 0600);

        try {
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->beginTransaction();
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $quotedTable = $this->quoteIdentifier((string) $table);
                $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
                $definition = is_array($create) ? array_values($create)[1] ?? null : null;

                if (! is_string($definition)) {
                    throw new RuntimeException("Das Tabellenschema für {$table} konnte nicht gelesen werden.");
                }

                fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n{$definition};\n");
                $statement = $pdo->query("SELECT * FROM {$quotedTable}");
                $columns = null;

                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $columns ??= array_keys($row);
                    $values = array_map(
                        fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        array_values($row),
                    );
                    fwrite(
                        $handle,
                        'INSERT INTO '.$quotedTable.' ('
                        .implode(', ', array_map($this->quoteIdentifier(...), $columns)).') VALUES ('
                        .implode(', ', $values).");\n",
                    );
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink($destination);

            throw $exception;
        } finally {
            fclose($handle);
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
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
