<?php
declare(strict_types=1);

namespace Nexus\Database\ORM\Mapping;

use Nexus\Database\ORM\Attributes\BelongsTo;
use Nexus\Database\ORM\Attributes\BelongsToMany;
use Nexus\Database\ORM\Attributes\Column;
use Nexus\Database\ORM\Attributes\ConcurrencyToken;
use Nexus\Database\ORM\Attributes\ForeignKey;
use Nexus\Database\ORM\Attributes\HasMany;
use Nexus\Database\ORM\Attributes\HasOne;
use Nexus\Database\ORM\Attributes\Key;
use Nexus\Database\ORM\Attributes\SoftDeletes;
use Nexus\Database\ORM\Attributes\Table;
use Nexus\Support\Str;

class PropertyMetadata
{
    public function __construct(
        public string $propertyName,
        public string $columnName,
        public ?string $phpType,
        public bool $isKey = false,
        public bool $autoIncrement = false,
        public bool $isConcurrencyToken = false,
        public bool $nullable = false,
        public mixed $default = null,
        public ?array $relation = null
    ) {}
}

class EntityMetadata
{
    /** @var array<string, PropertyMetadata> Keyed by property name */
    public array $properties = [];
    /** @var array<string, PropertyMetadata> Keyed by column name */
    public array $columnToProperty = [];

    public function __construct(
        public string $className,
        public string $tableName,
        public string $primaryKeyProperty = 'id',
        public string $primaryKeyColumn = 'id',
        public bool $autoIncrement = true,
        public ?string $concurrencyTokenProperty = null,
        public ?string $softDeleteColumn = null
    ) {}

    public function addProperty(PropertyMetadata $prop): void
    {
        $this->properties[$prop->propertyName] = $prop;
        $this->columnToProperty[$prop->columnName] = $prop;
    }
}

class MetadataFactory
{
    private static array $cache = [];

    public static function getMetadata(string $class): EntityMetadata
    {
        if (isset(self::$cache[$class])) {
            return self::$cache[$class];
        }

        $ref = new \ReflectionClass($class);
        $tableName = Str::snake(Str::plural(basename(str_replace('\\', '/', $class))));

        $tableAttr = $ref->getAttributes(Table::class);
        if (!empty($tableAttr)) {
            $tableName = $tableAttr[0]->newInstance()->name;
        }

        $metadata = new EntityMetadata($class, $tableName);

        foreach ($ref->getProperties() as $prop) {
            $propName = $prop->getName();
            $colName = Str::snake($propName);
            $isKey = false;
            $autoIncrement = false;
            $isConcurrencyToken = false;
            $nullable = $prop->getType()?->allowsNull() ?? true;
            $phpType = $prop->getType() instanceof \ReflectionNamedType ? $prop->getType()->getName() : null;
            $relation = null;

            if ($propName === 'id') {
                $isKey = true;
                $autoIncrement = true;
            }

            foreach ($prop->getAttributes() as $attr) {
                $inst = $attr->newInstance();
                if ($inst instanceof Key) {
                    $isKey = true;
                    $autoIncrement = $inst->autoIncrement;
                } elseif ($inst instanceof Column) {
                    if ($inst->name !== null) $colName = $inst->name;
                    if ($inst->nullable) $nullable = true;
                } elseif ($inst instanceof ConcurrencyToken) {
                    $isConcurrencyToken = true;
                    $metadata->concurrencyTokenProperty = $propName;
                } elseif ($inst instanceof HasOne) {
                    $relation = ['type' => 'hasOne', 'target' => $inst->targetEntity, 'foreignKey' => $inst->foreignKey ?? Str::snake(basename(str_replace('\\', '/', $class))) . '_id'];
                } elseif ($inst instanceof HasMany) {
                    $relation = ['type' => 'hasMany', 'target' => $inst->targetEntity, 'foreignKey' => $inst->foreignKey ?? Str::snake(basename(str_replace('\\', '/', $class))) . '_id'];
                } elseif ($inst instanceof BelongsTo) {
                    $relation = ['type' => 'belongsTo', 'target' => $inst->targetEntity, 'foreignKey' => $inst->foreignKey ?? Str::snake(basename(str_replace('\\', '/', $inst->targetEntity))) . '_id'];
                } elseif ($inst instanceof BelongsToMany) {
                    $relation = [
                        'type' => 'belongsToMany',
                        'target' => $inst->targetEntity,
                        'pivotTable' => $inst->pivotTable ?? Str::snake(basename(str_replace('\\', '/', $class))) . '_' . Str::snake(basename(str_replace('\\', '/', $inst->targetEntity))),
                        'foreignKey' => $inst->foreignKey ?? Str::snake(basename(str_replace('\\', '/', $class))) . '_id',
                        'relatedKey' => $inst->relatedKey ?? Str::snake(basename(str_replace('\\', '/', $inst->targetEntity))) . '_id',
                    ];
                } elseif ($inst instanceof SoftDeletes) {
                    $metadata->softDeleteColumn = $inst->column;
                }
            }

            if ($relation !== null) {
                continue;
            }

            $propMeta = new PropertyMetadata(
                propertyName: $propName,
                columnName: $colName,
                phpType: $phpType,
                isKey: $isKey,
                autoIncrement: $autoIncrement,
                isConcurrencyToken: $isConcurrencyToken,
                nullable: $nullable,
                relation: $relation
            );

            if ($isKey) {
                $metadata->primaryKeyProperty = $propName;
                $metadata->primaryKeyColumn = $colName;
                $metadata->autoIncrement = $autoIncrement;
            }

            $metadata->addProperty($propMeta);
        }

        self::$cache[$class] = $metadata;
        return $metadata;
    }
}
