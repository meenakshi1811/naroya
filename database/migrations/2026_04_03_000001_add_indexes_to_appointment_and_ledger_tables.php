<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexes('appointments', [
            ['doctor_id'],
            ['status'],
            ['patient_id'],
            ['appointment_date'],
            ['created_at'],
        ]);

        $this->addIndexes('appointment', [
            ['dr_id'],
            ['patient_id'],
            ['varAppointment'],
            ['created_at'],
            ['chrIsAccepted', 'chrIsRejected', 'chrIsCanceled'],
        ]);

        $this->addIndexes('ledger', [
            ['doctor_id'],
            ['status'],
            ['patient_id'],
            ['appointment_date'],
            ['created_at'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexes('appointments', [
            ['doctor_id'],
            ['status'],
            ['patient_id'],
            ['appointment_date'],
            ['created_at'],
        ]);

        $this->dropIndexes('appointment', [
            ['dr_id'],
            ['patient_id'],
            ['varAppointment'],
            ['created_at'],
            ['chrIsAccepted', 'chrIsRejected', 'chrIsCanceled'],
        ]);

        $this->dropIndexes('ledger', [
            ['doctor_id'],
            ['status'],
            ['patient_id'],
            ['appointment_date'],
            ['created_at'],
        ]);
    }

    /**
     * @param  array<int, array<int, string>>  $indexes
     */
    private function addIndexes(string $table, array $indexes): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes): void {
            foreach ($indexes as $columns) {
                if (! $this->hasColumns($table, $columns)) {
                    continue;
                }

                $indexName = $this->indexName($table, $columns);

                if (! $this->indexExists($table, $indexName)) {
                    $blueprint->index($columns, $indexName);
                }
            }
        });
    }

    /**
     * @param  array<int, array<int, string>>  $indexes
     */
    private function dropIndexes(string $table, array $indexes): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes): void {
            foreach ($indexes as $columns) {
                if (! $this->hasColumns($table, $columns)) {
                    continue;
                }

                $indexName = $this->indexName($table, $columns);

                if ($this->indexExists($table, $indexName)) {
                    $blueprint->dropIndex($indexName);
                }
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function indexName(string $table, array $columns): string
    {
        return sprintf('%s_%s_index', $table, implode('_', $columns));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);

        return array_key_exists(strtolower($indexName), $indexes);
    }
};
