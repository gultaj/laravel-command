<?php

namespace App\Console\Commands;

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

        \DB::table('categories')->delete();

        $this->convertCategories();

        $this->info('Finished convertation categories table');

         $this->info('Begin convertation post_categories table');

        \DB::table('post_categories')->delete();

        $this->convertPostCategories();

        $this->info('Finished convertation post_categories table');
    }

    private function convertCategories()
    {
         $sql = "term_taxonomy_id as wp_id,
	            name,
	            slug,
	            description,
                parent";

        \DB::table('wp_term_taxonomy')
            ->select(\DB::raw($sql))
            ->leftJoin('wp_terms', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
            ->where('wp_term_taxonomy.taxonomy', 'category')
            ->chunk(100, function($categories) {
                \DB::table('categories')->insert($categories->map(function($category) {
                    return (array)$category;
                })->toArray());
            });
    }

    private function convertPostCategories()
    {
        $sql = "object_id as wp_post_id,
            categories.id as category_id";

        \DB::table('wp_term_relationships')
            ->select(\DB::raw($sql))
            ->join('categories', 'categories.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($post_categories) {
                \DB::table('post_categories')->insert($post_categories->map(function($pc) {
                    return (array)$pc;
                })->toArray());
            });
    }
}
