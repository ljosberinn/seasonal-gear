<?php

namespace App\Gear;

use Generator;
use RuntimeException;

/**
 * Reads DB2 CSV exports and game tables downloaded from wago.tools. Rows are streamed because
 * ItemSparse alone is ~50 MB.
 */
class Db2Directory
{
    public function __construct(public readonly string $path) {}

    public static function tablePath(string $directory, string $table): string
    {
        return $directory.DIRECTORY_SEPARATOR.$table.'.csv';
    }

    public static function gameTablePath(string $directory, string $name): string
    {
        return $directory.DIRECTORY_SEPARATOR.'gt_'.$name.'.txt';
    }

    /**
     * @return Generator<int, array<string, string>>
     */
    public function rows(string $table): Generator
    {
        $handle = $this->open(self::tablePath($this->path, $table));

        try {
            $header = fgetcsv($handle, null, ',', '"', '');

            if ($header === false) {
                return;
            }

            $width = count($header);

            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                yield array_combine($header, array_pad($row, $width, ''));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  (callable(array<string, string>): bool)|null  $filter
     * @return array<string, array<string, string>>
     */
    public function keyed(string $table, string $key = 'ID', ?callable $filter = null): array
    {
        $result = [];

        foreach ($this->rows($table) as $row) {
            if ($filter === null || $filter($row)) {
                $result[$row[$key]] = $row;
            }
        }

        return $result;
    }

    /**
     * @param  (callable(array<string, string>): bool)|null  $filter
     * @return array<string, list<array<string, string>>>
     */
    public function grouped(string $table, string $key, ?callable $filter = null): array
    {
        $result = [];

        foreach ($this->rows($table) as $row) {
            if ($filter === null || $filter($row)) {
                $result[$row[$key]][] = $row;
            }
        }

        return $result;
    }

    /**
     * Tab-separated game table keyed by its first column (item level).
     *
     * @return array<int, array<string, float>>
     */
    public function gameTable(string $name): array
    {
        $lines = preg_split('/\r?\n/', trim((string) file_get_contents(self::gameTablePath($this->path, $name))));
        $header = explode("\t", (string) array_shift($lines));
        $result = [];

        foreach ($lines as $line) {
            $values = array_map(floatval(...), explode("\t", $line));
            $result[(int) $values[0]] = array_combine($header, $values);
        }

        return $result;
    }

    /**
     * @return resource
     */
    private function open(string $file)
    {
        $handle = is_file($file) ? fopen($file, 'r') : false;

        if ($handle === false) {
            throw new RuntimeException("Missing DB2 export: {$file}");
        }

        return $handle;
    }
}
