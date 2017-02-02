<?php

namespace Karakhanyans\Seeder\Traits;

use Illuminate\Support\Facades\File;

trait SeederTrait
{
    private $models = [];
    private $folder;
    private $path;
    private $globalSeeders = [];
    private $recursive = false;

    public function setFolder($folder)
    {
        $this->folder = $folder;
        $this->setPath();
    }

    public function setRecursive()
    {
        $this->recursive = true;
    }

    public function setPath()
    {
        $path = base_path('app');
        if (!empty($this->folder)) {
            $path .= '\\' . $this->folder;
        }
        return $this->path = $path;
    }

    private function listFolderFiles($dir)
    {
        $files = ($this->recursive)?File::allFiles($dir):File::files($dir);
        foreach ($files as $file){
            array_push($this->models, $this->cleanFilePath($file));
            array_push($this->globalSeeders, $this->seederFileName($file));
        }
    }

    public function seed()
    {
        $this->listFolderFiles($this->path);
        foreach ($this->models as $model) {
            $this->getData($model);
        }
        $this->makeGlobalSeederFile();
    }

    private function getData($model)
    {
        try{
            if(method_exists($model,'all')){
                $data = $model::all()->toArray();
                $this->makeSeederFile($model, $data);
                $plur = last(explode('\\',$model)).'Seeder';
                echo $plur." generated \n";
            }
        }catch (\Exception $e){
            $m = new $model;
            echo "\"".$m->getTable()."\""." table not found in your database\n";
        }
    }

    private function makeSeederFile($model, $data)
    {
        $txt = $this->seederText($model, $data);

        File::put($this->setNewPath($model),$txt);
        return $this;
    }

    private function makeGlobalSeederFile()
    {
        $txt = $this->globalSeederText();
        File::put($this->globalSeederFileName(),$txt);
        return $this;
    }

    private function globalSeederText()
    {
        $phpOpenTag = "<?php\n\n";
        $nameSpace = 'use Illuminate\Database\Seeder;' . "\n\n";
        $traitDeclaration = "class GlobalSeeder extends Seeder\n" . "{";
        $declaration = "\n\n\t/**\n\t* Run the database seeds.\n\t*\n\t* @return void\n\t*/";
        $protectedVariable = "\n\n\t protected $" . 'models' . ";";
        $setFunction = "\n\n\t public function setModels(){\n\t\t" . '$this->models' . " = '" . serialize($this->globalSeeders) . "';\n\t }";
        $runFunction = "\n\n\t public function run(){\n\t\t " . '$this->setModels()' . ";\n";
        $foreach = "\t\t " . 'foreach(unserialize($this->models' . ') as $item){' . "\n";
        $insert = "\t\t\t " . '$this->call($item);';
        $endforeach = "\n\t\t }";
        $closeRunFunction = "\n\t }";
        $closeClass = "\n\n}";
        return $phpOpenTag . $nameSpace . $traitDeclaration . $declaration . $protectedVariable . $setFunction . $runFunction . $foreach . $insert . $endforeach . $closeRunFunction . $closeClass;
    }

    private function seederText($model, $data)
    {
        $plur = last(explode('\\',$model));
        $phpOpenTag = "<?php\n\n";
        $nameSpace = 'use Illuminate\Database\Seeder;' . "\n\n";
        $DB = 'use Illuminate\Support\Facades\DB;' . "\n\n";
        $traitDeclaration = "class " . $plur . "Seeder" . " extends Seeder\n" . "{";
        $declaration = "\n\n\t/**\n\t* Run the database seeds.\n\t*\n\t* @return void\n\t*/";
        $protectedVariable = "\n\n\t protected $" . strtolower(str_plural($plur)) . ";";
        $setFunction = "\n\n\t public function set" . ucfirst(str_plural($plur)) . "(){\n\t\t" . '$this->' . strtolower(str_plural($plur)) . " = '" . serialize($data) . "';\n\t }";
        $runFunction = "\n\n\t public function run(){\n\t\t " . '$this->set' . ucfirst(str_plural($plur)) . "();\n";
        $foreach = "\t\t " . 'foreach(unserialize($this->' . strtolower(str_plural($plur)) . ') as $item){' . "\n";
        $try = "\t\t\t try{ \n";
        $insert = "\t\t\t\t ".$model."::create((array)" . '$item' . ");\n";
        $catch = "\t\t\t".'}catch (\Exception $e){'."\n";
        $catchContent = "\t\t\t\t ". '\Illuminate\Support\Facades\Log::info(\'Duplicate entry for user: \'.$item[\'id\']);'."\n";
        $closeCatch = "\t\t\t }";
        $endforeach = "\n\t\t }";
        $closeRunFunction = "\n\t }";
        $closeClass = "\n\n}";
        return $phpOpenTag . $nameSpace . $DB . $traitDeclaration . $declaration . $protectedVariable . $setFunction . $runFunction . $foreach .$try . $insert . $catch . $catchContent . $closeCatch . $endforeach . $closeRunFunction . $closeClass;
    }

    private function setNewPath($model)
    {
        $seed = last(explode('\\',$model)).'Seeder.php';
        return database_path('seeds\\' . $seed);
    }

    private function cleanFilePath($file){
        return '\\'.ucfirst(str_replace('/','\\',substr($file,strpos($file,'app'),-4)));
    }
    private function seederFileName($file){
        $file = $this->cleanFilePath($file);
        return last(explode('\\',$file)).'Seeder';
    }
    private function globalSeederFileName(){
        return database_path('seeds\GlobalSeeder.php');
    }
}