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

        $sql = "SELECT wp_posts.ID AS wp_id,
                IFNULL(users.id, 1) AS user_id,
                post_date AS created_at,
                post_content AS content,
                post_title AS title,
                post_excerpt AS excerpt,
                post_name AS slug,
                post_modified AS updated_at, 
                CASE WHEN comment_status = 'open' THEN 1 ELSE 0 END allow_comments, 
                CASE post_status
                    WHEN 'publish' THEN 'public'
                    WHEN 'pending' THEN 'public' 
                    WHEN 'auto-draft' THEN 'draft'
                    ELSE post_status 
                END status,
                CASE WHEN pm.meta_value THEN pm.meta_value
                    ELSE REPLACE(temp.old_thumb, 'http://www.lida.info/wp-content/uploads/', '')
                END thumbnail
            FROM wp_posts
            LEFT JOIN users ON wp_posts.post_author = users.wp_id
            INNER JOIN (
                SELECT post_id, 
                    MAX(CASE WHEN meta_key = '_thumbnail_id' THEN meta_value END) AS 'thumb_id', 
                    MAX(CASE WHEN meta_key = 'thumbnail' THEN meta_value END) AS 'old_thumb'
                FROM wp_postmeta
                WHERE (meta_key = '_thumbnail_id' OR meta_key = 'thumbnail')
                GROUP BY post_id
            ) temp ON temp.post_id = wp_posts.ID
            LEFT JOIN wp_postmeta pm ON pm.post_id = temp.thumb_id AND pm.meta_key = '_wp_attached_file'
            WHERE post_type = 'post'";

        $posts = DB::select(DB::raw($sql));

        foreach ($posts as $post) {
            DB::table('posts')->insert((array)$post);
        }
        
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
