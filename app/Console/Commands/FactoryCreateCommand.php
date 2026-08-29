<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionMethod;
use Throwable;

#[Signature('factory:create
    {model : Model name}
    {--count=1 : Number of records to create}
    {--state=* : Factory state}
    {--set=* : Attribute in key=value format}
')]
#[Description('Create a record using model factory')]
class FactoryCreateCommand extends Command
{
    public function handle(): int
    {
        $modelName = (string) $this->argument('model');
        $modelNamespace = (string) config('factory-create.model_namespace',
            'App\\Models'
        );
        $modelClass = $modelNamespace . '\\' .$modelName;

        $attributeOptions = (array) $this->option('set');

        $count = (int) $this->option('count');
        $states = (array) $this->option('state');
        $maxCount = (int) config('factory-create.max_count', 20);

        if ($count < 1 || $count > config('factory-create.max_count', 20)) {
            $this->error('Count must be between 1 and ' . $maxCount . '.');

            return self::FAILURE;
        }

        if (!class_exists($modelClass)) {
            $this->error("Model '{$modelName}' does not exist.");

            return self::FAILURE;
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            $this->error("Class '{$modelClass}' is not an Eloquent model.");

            return self::FAILURE;
        }

        if (!method_exists($modelClass, 'factory')) {
            $this->error("Model '{$modelName}' must have a factory.");

            return self::FAILURE;
        }

        try {
            $attributes = $this->parseAttributes($attributeOptions);

            $factory = $modelClass::factory();

            foreach ($states as $state) {
                if (!$this->isAllowedFactoryState($factory, $state)) {
                    $this->error("Factory state '{$state}' is not found for model '{$modelName}'.");

                    return self::FAILURE;
                }

                $result = $factory->{$state}();

                if (!$result instanceof Factory) {
                    $this->error("Factory state '{$state}' must return a factory instance.");

                    return self::FAILURE;
                }
                $factory = $result;
            }

            $models = $factory
                ->count($count)
                ->create($attributes);

            $this->displayCreatedModels($models);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Created {$models->count()} {$modelName} record" . ($models->count() === 1 ? '.' : 's.'));

        return self::SUCCESS;
    }

    private function isAllowedFactoryState(Factory $factory, string $state): bool
    {
        if (!method_exists($factory, $state)) {
            return false;
        }

        $method = new ReflectionMethod($factory, $state);

        return
            $method->isPublic()
            && !$method->isStatic()
            && $method->getNumberOfRequiredParameters() === 0
            && $method->getDeclaringClass()->getName() === $factory::class;
    }

    /** @return array<string, mixed> */
    private function parseAttributes(array $options): array
    {
        $attributes = [];

        foreach ($options as $option) {
            if (!str_contains($option, '=')) {
                throw new InvalidArgumentException(
                    "Attribute '{$option}' must be of a key=value format."
                );
            }
            [$key, $value] = explode('=', $option, 2);

            $key = trim($key);

            if ($key === '') {
                throw new InvalidArgumentException(
                    'Attribute must not be empty.'
                );
            }
            if (array_key_exists($key, $attributes)) {
                throw new InvalidArgumentException(
                    "Attribute '{$key}' was provided more than once."
                );
            }

            $attributes[$key] = $this->parseAttributeValue($value);
        }

        return $attributes;
    }

    private function parseAttributeValue(string $value): mixed
    {
        return match (strtolower(trim($value))) {
            'null' => null,
            'true' => true,
            'false' => false,
            default => $value
        };
    }

    /**@param Collection<int, Model> $models */
    private function displayCreatedModels(Collection $models)
    {
        $rows = $models->values()
            ->map(function (Model $model, int $index): array {
                return [
                    $index + 1,
                    class_basename($model),
                    $model->getKey(),
                    $model->getAttribute('uuid') ?? '-',
                ];
            })->all();

        $this->table(
            ['№', 'Model', 'Key', 'UUID'],
            $rows
        );
    }
}
