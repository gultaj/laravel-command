<?php

namespace App\Console\Commands;

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Begin');

        \DB::table('posts')->truncate();

        $sql = "ID AS wp_id,
            post_author AS user_id,
            post_date AS created_at,
            post_content AS content,
            post_title AS title,
            post_excerpt AS excerpt,
            post_name AS wp_slug,
            post_modified AS updated_at, 
            CASE WHEN comment_status = 'open' THEN 1 ELSE 0 END allow_comments, 
            CASE WHEN post_status = 'publish' OR post_status = 'pending' THEN 'public' WHEN post_status = 'auto-draft' THEN 'draft' ELSE post_status END status,
            wp_postmeta.meta_value as thumbnail";

        \DB::table('wp_posts')
            ->select(\DB::raw($sql))
            ->leftJoin('wp_postmeta', function($join) {
                $join->on('wp_postmeta.post_id', '=', 'wp_posts.ID')
                    ->where('wp_postmeta.meta_key', '=', 'thumbnail');
            })
            ->where('post_type', 'post')->limit(100)
            ->chunk(100, function($posts) {
                \DB::table('posts')->insert($posts->map(function($x) {
                    //$this->info(serialize((array)$x));
                    return (array)$x;
                })->toArray());
        });
            
        $this->info('End');
    }
}
