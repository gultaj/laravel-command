<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConvertEventsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //
    }

    private function convertEvents()
    {

    }

    private function convertPlaceEvents()
    {

    }

    private function convertCategoryEvents()
    {
        
    }
}
