<?php

namespace Karakhanyans\Seeder;

use Illuminate\Console\Command;
use Karakhanyans\Seeder\Traits\SeederTrait;

class Seeder extends Command
{
    use SeederTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:seed {folder?} {--r|recursive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds from Database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $folder = $this->argument('folder');
        $recursive = $this->option('recursive');
        if($recursive){
            $this->setRecursive();
        }
        $this->setFolder($folder);
        $this->seed();
    }
}
