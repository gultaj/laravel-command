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
        $this->info('Posts');

        $this->convertPosts();
        $this->info("\tConverted posts table");

        $this->convertTags();
        $this->info("\tConverted tags table");

        $this->convertPostTags();
        $this->info("\tConverted tag_post table");

        $this->convertCategories();
        $this->info("\tConverted categories table");

        $this->convertPostCategories();
        $this->info("\tConverted post_categories table");
    }

    public function convertPosts()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('posts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT 
                INTO posts (wp_id, user_id, created_at, content, title, excerpt, slug, updated_at, allow_comments, `status`, thumbnail)
            SELECT wp_posts.ID AS wp_id,
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

        DB::insert(DB::raw($sql));
        
    }

    private function convertTags()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('tags')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT INTO tags (wp_id, name, slug)
            SELECT term_taxonomy_id as wp_id,
                name,
                slug
            FROM wp_term_taxonomy
            LEFT JOIN wp_terms ON wp_terms.term_id = wp_term_taxonomy.term_id
            WHERE wp_term_taxonomy.taxonomy = 'post_tag'";

        DB::insert(DB::raw($sql));
    }

    private function convertPostTags()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('tag_post')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT INTO tag_post (post_id, tag_id)
            SELECT posts.id as post_id, tags.id as tag_id
            FROM wp_term_relationships
            JOIN posts ON posts.wp_id = wp_term_relationships.object_id
            JOIN tags ON tags.wp_id = wp_term_relationships.term_taxonomy_id";

        DB::insert(DB::raw($sql));
    }

    
    private function convertCategories()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT INTO categories (wp_id, name, slug, description, parent)
            SELECT term_taxonomy_id as wp_id, name, slug, description, parent
            FROM wp_term_taxonomy
            LEFT JOIN wp_terms ON wp_terms.term_id = wp_term_taxonomy.term_id
            WHERE wp_term_taxonomy.taxonomy = 'category'";

        DB::insert(DB::raw($sql));
    }

    private function convertPostCategories()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('category_post')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT INTO category_post (post_id, category_id)
            SELECT posts.id as post_id, categories.id as category_id
            FROM wp_term_relationships
            JOIN posts ON posts.wp_id = wp_term_relationships.object_id
            JOIN categories ON categories.wp_id = wp_term_relationships.term_taxonomy_id";

        DB::insert(DB::raw($sql));
    }
}
