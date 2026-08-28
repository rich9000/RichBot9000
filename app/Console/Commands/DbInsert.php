<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DbInsert extends Command
{
    protected $signature = 'db:insert {table} {--data=* : Data to insert in format key=value}';
    protected $description = 'Insert data into a database table';

    public function handle()
    {
        $table = $this->argument('table');
        $data = $this->option('data');

        // Check if table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            return 1;
        }

        // Parse data into array
        $insertData = [];
        foreach ($data as $item) {
            if (strpos($item, '=') === false) {
                $this->error("Invalid data format: {$item}. Use key=value format.");
                return 1;
            }
            list($key, $value) = explode('=', $item, 2);
            $insertData[$key] = $value;
        }

        // Add timestamps if they exist in the table
        if (Schema::hasColumn($table, 'created_at')) {
            $insertData['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $insertData['updated_at'] = now();
        }

        try {
            $id = DB::table($table)->insertGetId($insertData);
            $this->info("Successfully inserted data into {$table} with ID: {$id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error inserting data: " . $e->getMessage());
            return 1;
        }
    }
} 