<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectDatabase extends Command
{
    protected $signature = 'db:inspect {table?} {--list : List all tables} {--data : Show table data} {--columns : Show column details}';
    protected $description = 'Inspect database table structures';

    public function handle()
    {
        if ($this->option('list')) {
            $this->listTables();
            return;
        }

        $table = $this->argument('table');
        if (!$table) {
            $this->error('Please specify a table name or use --list to see all tables');
            return 1;
        }

        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist");
            return 1;
        }

        if ($this->option('data')) {
            $this->inspectTableData($table);
            return;
        }

        $this->inspectTable($table);
    }

    protected function listTables()
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function($table) {
            return reset($table);
        }, $tables);

        $this->table(['Table Name'], array_map(function($name) {
            return [$name];
        }, $tableNames));
    }

    protected function inspectTable($table)
    {
        $columns = Schema::getColumnListing($table);
        $tableInfo = [];

        foreach ($columns as $column) {
            $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0];
            
            $tableInfo[] = [
                'Column' => $column,
                'Type' => $columnInfo->Type,
                'Null' => $columnInfo->Null,
                'Key' => $columnInfo->Key,
                'Default' => $columnInfo->Default,
                'Extra' => $columnInfo->Extra
            ];
        }

        $this->info("Table Structure: {$table}");
        $this->table(
            ['Column', 'Type', 'Null', 'Key', 'Default', 'Extra'],
            $tableInfo
        );

        // Show foreign key constraints
        $foreignKeys = DB::select("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = ? 
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [config('database.connections.mysql.database'), $table]);

        if (!empty($foreignKeys)) {
            $this->info("\nForeign Key Constraints:");
            $this->table(
                ['Column', 'References Table', 'References Column'],
                array_map(function($fk) {
                    return [
                        $fk->COLUMN_NAME,
                        $fk->REFERENCED_TABLE_NAME,
                        $fk->REFERENCED_COLUMN_NAME
                    ];
                }, $foreignKeys)
            );
        }
    }

    protected function inspectTableData($table)
    {
        $data = DB::table($table)->get();
        $this->table(['ID', 'Data'], $data->map(function($row) {
            return [$row->id, json_encode($row)];
        }));
    }
} 