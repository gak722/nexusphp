<?php
declare(strict_types=1);

namespace Nexus\Database\Grammar;

interface GrammarInterface
{
    public function wrapIdentifier(string $value): string;
    public function compileSelect(array $queryParts): string;
    public function compileInsert(string $table, array $columns): string;
    public function compileUpdate(string $table, array $columns, string $whereSql): string;
    public function compileDelete(string $table, string $whereSql): string;
    public function compileCreateTable(array $tableBlueprint): string;
    public function compileDropTable(string $table, bool $ifExists = true): string;
    public function supportsSavepoints(): bool;
    public function supportsTransactionalDdl(): bool;
}
