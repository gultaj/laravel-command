<?php

namespace App\Console\Commands;

use \DB;
use Illuminate\Console\Command;

class ConvertTagsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:tags';

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

        $this->info('Begin convertation tags table');

        $this->convertTags();

        $this->info('Finished convertation tags table');

        $this->info('Begin convertation tag_post table');

        $this->convertPostTags();

        $this->info('Finished convertation tag_post table');
    }

     private function convertTags()
    {
        $table_name = 'tags';
        
        $sql = "term_taxonomy_id as wp_id,
	            name,
	            slug";

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        DB::table('wp_term_taxonomy')
            ->select(DB::raw($sql))
            ->leftJoin('wp_terms', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
            ->where('wp_term_taxonomy.taxonomy', 'post_tag')
            ->chunk(100, function($tags) use ($table_name) {
                DB::table($table_name)->insert($tags->map(function($tag) {
                    return (array)$tag;
                })->toArray());
            });
    }

    private function convertPostTags()
    {
        $table_name = 'tag_post';

        $sql = "posts.id as post_id,
	        tags.id as tag_id";
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('wp_term_relationships')
            ->select(\DB::raw($sql))
            ->join('posts', 'posts.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('tags', 'tags.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($post_tags) use ($table_name) {
                DB::table($table_name)->insert($post_tags->map(function($pt) {
                    return (array)$pt;
                })->toArray());
            });
    }
}
