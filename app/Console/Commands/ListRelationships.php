<?php

// app/Console/Commands/ListRelationships.php
namespace App\Console\Commands;

use Spatie\ModelInfo\ModelInfo;
use Spatie\ModelInfo\ModelFinder;

// returns a `Illuminate\Support\Collection` containing all
// the class names of all your models.

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListRelationships extends Command
{


    protected $signature = 'xx:list_models';
    protected $description = 'List all model relationships';


    public function handle()
    {
        //$modelsPath = app_path('Models');
        //$modelFiles = File::allFiles($modelsPath);
        $models = ModelFinder::all();

        //dd($models);
        foreach ($models as $model) {
            echo "Model: $model\n";
            $modelInfo = ModelInfo::forModel($model);

            dump($modelInfo->relations);

            continue;


            if (class_exists($modelClass)) {
                $model = new $modelClass;
                dump(get_class_methods($model));
                $this->info("Relationships for {$modelClass}:");
                foreach (get_class_methods($model) as $method) {
                    if ($this->isRelationshipMethod($model, $method)) {
                        $this->line(" - {$method}");
                    }
                }
            }
        }
    }

    protected function isRelationshipMethod($model, $method)
    {
        $reflection = new \ReflectionMethod($model, $method);



        // Check if the method has a return type
        $returnType = $reflection->getReturnType();
        if (!$returnType) {
            return false; // If there's no return type, it can't be a relationship
        }


        dump($returnType);

        return $reflection->class === get_class($model)

            && in_array($reflection->getReturnType()->getName(), [
                'Illuminate\Database\Eloquent\Relations\HasOne',
                'Illuminate\Database\Eloquent\Relations\HasMany',
                'Illuminate\Database\Eloquent\Relations\BelongsTo',
                'Illuminate\Database\Eloquent\Relations\BelongsToMany',
                'Illuminate\Database\Eloquent\Relations\MorphTo',
                'Illuminate\Database\Eloquent\Relations\MorphMany',
            ]);
    }
}
