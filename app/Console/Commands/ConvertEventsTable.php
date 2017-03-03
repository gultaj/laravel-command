<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \DB;

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
        $this->info("Events");

        $this->convertEventTypes();
        $this->info("\tConverted event_types table");

        $this->convertEventPlaces();
        $this->info("\tConverted event_places table");

        $this->convertEvents();
        $this->info("\tConverted events table");

        //$this->updateEvents();
        // $this->info("\tUpdated events table");
    }

    private function convertEvents()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('events')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // $sql = "wp_posts.ID AS wp_id,
        //     IFNULL(users.id, 1) AS user_id,
        //     post_date AS created_at,
        //     post_content AS content,
        //     post_title AS title,
        //     post_modified AS updated_at";

        // DB::table('wp_posts')
        //     ->select(DB::raw($sql))
        //     ->leftJoin('users', 'wp_posts.post_author', '=',  'users.wp_id')
        //     ->where('post_type', 'afisha')
        //     ->chunk(100, function($events) {
        //         DB::table('events')->insert($events->map(function($event) {
        //             return (array)$event;
        //         })->toArray());
        // });

        $sql = "INSERT INTO events (wp_id, user_id, event_type_id, event_place_id, title, content, startDate, endDate, status, created_at, updated_at)
            SELECT wp_posts.ID AS wp_id,
            IFNULL(users.id, 1) AS user_id,
            IFNULL(types.event_type_id, 1) AS event_type_id,
            IFNULL(places.event_place_id, 1) AS event_place_id,
            post_title AS title,
            post_content AS content,
            DATE(FROM_UNIXTIME(dates.startDate)) AS startDate,
            DATE(FROM_UNIXTIME(dates.endDate)) AS endDate,
            CASE post_status
                WHEN 'publish' THEN 'public'
                WHEN 'pending' THEN 'public' 
                WHEN 'auto-draft' THEN 'draft'
                ELSE post_status 
            END status,
            post_date AS created_at,
            post_modified AS updated_at
            FROM wp_posts
            LEFT JOIN users ON wp_posts.post_author = users.wp_id
            LEFT JOIN (
                SELECT 
                    post_id,
                    MAX(CASE WHEN meta_key = 'from_date' THEN meta_value END) AS startDate,
                    MAX(CASE WHEN meta_key = 'to_date' THEN meta_value END) AS endDate
                FROM wp_postmeta
                WHERE (meta_key = 'from_date' OR meta_key = 'to_date')
                GROUP BY post_id
            ) AS dates ON dates.post_id = wp_posts.ID
            LEFT JOIN (
                SELECT wp_posts.id as id, event_types.id as event_type_id
                FROM wp_term_relationships
                JOIN wp_posts ON wp_posts.id = wp_term_relationships.object_id
                JOIN event_types ON event_types.wp_id = wp_term_relationships.term_taxonomy_id
                WHERE wp_posts.post_type = 'afisha'
            ) AS types ON types. id = wp_posts.id
            LEFT JOIN (
                SELECT wp_posts.id as id, event_places.id as event_place_id
                FROM wp_term_relationships
                JOIN wp_posts ON wp_posts.id = wp_term_relationships.object_id
                JOIN event_places ON event_places.wp_id = wp_term_relationships.term_taxonomy_id
                WHERE wp_posts.post_type = 'afisha'
            ) AS places ON places.id = wp_posts.ID
            WHERE post_type ='afisha'";

        DB::insert(DB::raw($sql));
    }

    private function convertEventPlaces()
    {
         $table_name = 'event_places';

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('event_places')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "SELECT term_taxonomy_id as wp_id,
                name,
                slug,
                description,
                wp_place_afishameta.meta_value as meta
            FROM wp_term_taxonomy
            LEFT JOIN wp_terms ON wp_terms.term_id = wp_term_taxonomy.term_id
            JOIN wp_place_afishameta ON wp_place_afishameta.place_afisha_id = wp_term_taxonomy.term_id
            WHERE wp_term_taxonomy.taxonomy = 'place_afisha'";
        
        $eventPlaces = DB::select(DB::raw($sql));

        foreach ($eventPlaces as $eventPlace) {
            $meta = unserialize($eventPlace->meta);
            unset($eventPlace->meta);
            $eventPlace->address = empty($meta['address']) ? null : $meta['address'];
            $eventPlace->phone = empty($meta['phone']) ? null : $meta['phone'];
            DB::table('event_places')->insert((array)$eventPlace);
        }
    }

    private function convertEventTypes()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('event_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "INSERT INTO event_types (wp_id, name, slug, description)
            SELECT term_taxonomy_id as wp_id, name, slug, description
            FROM wp_term_taxonomy
            LEFT JOIN wp_terms ON wp_terms.term_id = wp_term_taxonomy.term_id
            WHERE wp_term_taxonomy.taxonomy = 'section_afisha'";
        
        DB::insert(DB::raw($sql));
    }

    private function updateEvents()
    {
        // $sql = "events.id as id,
	    //     event_types.id as event_type_id";

        // DB::table('wp_term_relationships')
        //     ->select(DB::raw($sql))
        //     ->join('events', 'events.wp_id', '=', 'wp_term_relationships.object_id')
        //     ->join('event_types', 'event_types.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
        //     ->chunk(100, function($events) {
        //         $events->each(function($event) {
        //             DB::table('events')->where('id', $event->id)->update(['event_type_id' => $event->event_type_id]);
        //         });
        //     });

        // $sql = "events.id as id,
	    //     event_places.id as event_place_id";

        // DB::table('wp_term_relationships')
        //     ->select(DB::raw($sql))
        //     ->join('events', 'events.wp_id', '=', 'wp_term_relationships.object_id')
        //     ->join('event_places', 'event_places.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
        //     ->chunk(100, function($events) {
        //         $events->each(function($event) {
        //             DB::table('events')->where('id', $event->id)->update(['event_place_id' => $event->event_place_id]);
        //         });
        //     });
    }

    // private function convertEventTypes()
    // {
    //     $table_name = 'category_post';

    //     $sql = "posts.id as post_id,
	//         categories.id as category_id";
        
    //     DB::statement('SET FOREIGN_KEY_CHECKS=0');
    //     DB::table($table_name)->truncate();
    //     DB::statement('SET FOREIGN_KEY_CHECKS=1');

    //     DB::table('wp_term_relationships')
    //         ->select(DB::raw($sql))
    //         ->join('posts', 'posts.wp_id', '=', 'wp_term_relationships.object_id')
    //         ->join('categories', 'categories.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
    //         ->chunk(100, function($post_categories) use ($table_name) {
    //             DB::table($table_name)->insert($post_categories->map(function($pc) {
    //                 return (array)$pc;
    //             })->toArray());
    //         });
    // }
}
