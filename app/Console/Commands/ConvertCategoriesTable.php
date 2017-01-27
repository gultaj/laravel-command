<?php

namespace App\Console\Commands;

use \DB;
use Illuminate\Console\Command;

class ConvertCategoriesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command wp_terms to categories table';

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
        $this->info('Begin convertation categories table');

        $this->convertCategories();

        $this->info('Finished convertation categories table');

        $this->info('Begin convertation post_categories table');

        $this->convertPostCategories();

        $this->info('Finished convertation post_categories table');
    }

    private function convertCategories()
    {
        $table_name = 'categories';
        
        $sql = "term_taxonomy_id as wp_id,
	            name,
	            slug,
	            description,
                parent";

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        DB::table('wp_term_taxonomy')
            ->select(DB::raw($sql))
            ->leftJoin('wp_terms', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
            ->where('wp_term_taxonomy.taxonomy', 'category')
            ->chunk(100, function($categories) use ($table_name) {
                DB::table($table_name)->insert($categories->map(function($category) {
                    return (array)$category;
                })->toArray());
            });
    }

    private function convertPostCategories()
    {
        $table_name = 'category_post';

        $sql = "posts.id as post_id,
	        categories.id as category_id";
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('wp_term_relationships')
            ->select(\DB::raw($sql))
            ->join('posts', 'posts.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('categories', 'categories.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($post_categories) use ($table_name) {
                DB::table($table_name)->insert($post_categories->map(function($pc) {
                    return (array)$pc;
                })->toArray());
            });
    }
}
