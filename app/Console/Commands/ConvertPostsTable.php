<?php

namespace App\Console\Commands;

use \DB;
use Illuminate\Console\Command;

class ConvertPostsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert wp_posts table to posts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Start convert post table');
        $this->convertPosts();
        $this->info('End convert post table');

        $this->info('Begin convertation tags table');
        $this->convertTags();
        $this->info('Finished convertation tags table');

        $this->info('Begin convertation tag_post table');
        $this->convertPostTags();
        $this->info('Finished convertation tag_post table');

        $this->info('Begin convertation categories table');
        $this->convertCategories();
        $this->info('Finished convertation categories table');

        $this->info('Begin convertation post_categories table');
        $this->convertPostCategories();
        $this->info('Finished convertation post_categories table');
    }

    public function convertPosts()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('posts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "wp_posts.ID AS wp_id,
            IFNULL(users.id, 1) AS user_id,
            post_date AS created_at,
            post_content AS content,
            post_title AS title,
            post_excerpt AS excerpt,
            post_name AS slug,
            post_modified AS updated_at, 
            CASE WHEN comment_status = 'open' THEN 1 ELSE 0 END allow_comments, 
            CASE WHEN post_status = 'publish' OR post_status = 'pending' THEN 'public' WHEN post_status = 'auto-draft' THEN 'draft' ELSE post_status END status,
            wp_postmeta.meta_value as thumbnail";

        DB::table('wp_posts')
            ->select(DB::raw($sql))
            ->leftJoin('wp_postmeta', function($join) {
                $join->on('wp_postmeta.post_id', '=', 'wp_posts.ID')
                    ->where('wp_postmeta.meta_key', '=', 'thumbnail');
            })
            ->leftJoin('users', 'wp_posts.post_author', '=',  'users.wp_id')
            ->where('post_type', 'post')
            ->chunk(100, function($posts) {
                DB::table('posts')->insert($posts->map(function($post) {
                    return (array)$post;
                })->toArray());
        });
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
            ->select(DB::raw($sql))
            ->join('posts', 'posts.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('tags', 'tags.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($post_tags) use ($table_name) {
                DB::table($table_name)->insert($post_tags->map(function($pt) {
                    return (array)$pt;
                })->toArray());
            });
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
            ->select(DB::raw($sql))
            ->join('posts', 'posts.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('categories', 'categories.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($post_categories) use ($table_name) {
                DB::table($table_name)->insert($post_categories->map(function($pc) {
                    return (array)$pc;
                })->toArray());
            });
    }
}
