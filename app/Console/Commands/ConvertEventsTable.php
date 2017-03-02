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

        $this->updateEvents();
        $this->info("\tUpdated events table");
    }

    private function convertEvents()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('events')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "wp_posts.ID AS wp_id,
            IFNULL(users.id, 1) AS user_id,
            post_date AS created_at,
            post_content AS content,
            post_title AS title,
            post_modified AS updated_at";

        DB::table('wp_posts')
            ->select(DB::raw($sql))
            ->leftJoin('users', 'wp_posts.post_author', '=',  'users.wp_id')
            ->where('post_type', 'afisha')
            ->chunk(100, function($events) {
                DB::table('events')->insert($events->map(function($event) {
                    return (array)$event;
                })->toArray());
        });
    }

    private function convertEventPlaces()
    {
         $table_name = 'event_places';
        
        $sql = "term_taxonomy_id as wp_id,
	            name,
	            slug,
	            description,
                wp_place_afishameta.meta_value as meta";

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        DB::table('wp_term_taxonomy')
            ->select(DB::raw($sql))
            ->leftJoin('wp_terms', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
            ->join('wp_place_afishameta', 'wp_place_afishameta.place_afisha_id', '=', 'wp_term_taxonomy.term_id')
            ->where('wp_term_taxonomy.taxonomy', 'place_afisha')
            ->chunk(100, function($eventPlaces) use ($table_name) {
                DB::table($table_name)->insert($eventPlaces->map(function($eventPlace) {
                    $meta = unserialize($eventPlace->meta);
                    unset($eventPlace->meta);
                    $eventPlace->address = empty($meta['address']) ? null : $meta['address'];
                    $eventPlace->phone = empty($meta['phone']) ? null : $meta['phone'];
                    return (array)$eventPlace;
                })->toArray());
            });
    }

    private function convertEventTypes()
    {
        $table_name = 'event_types';
        
        $sql = "term_taxonomy_id as wp_id,
	            name,
	            slug,
	            description";

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table_name)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        DB::table('wp_term_taxonomy')
            ->select(DB::raw($sql))
            ->leftJoin('wp_terms', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
            ->where('wp_term_taxonomy.taxonomy', 'section_afisha')
            ->chunk(100, function($eventTypes) use ($table_name) {
                DB::table($table_name)->insert($eventTypes->map(function($eventType) {
                    return (array)$eventType;
                })->toArray());
            });
    }

    private function updateEvents()
    {
        $sql = "events.id as id,
	        event_types.id as event_type_id";

        DB::table('wp_term_relationships')
            ->select(DB::raw($sql))
            ->join('events', 'events.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('event_types', 'event_types.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($events) {
                $events->each(function($event) {
                    DB::table('events')->where('id', $event->id)->update(['event_type_id' => $event->event_type_id]);
                });
            });

        $sql = "events.id as id,
	        event_places.id as event_place_id";

        DB::table('wp_term_relationships')
            ->select(DB::raw($sql))
            ->join('events', 'events.wp_id', '=', 'wp_term_relationships.object_id')
            ->join('event_places', 'event_places.wp_id', '=', 'wp_term_relationships.term_taxonomy_id')
            ->chunk(100, function($events) {
                $events->each(function($event) {
                    DB::table('events')->where('id', $event->id)->update(['event_place_id' => $event->event_place_id]);
                });
            });
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
