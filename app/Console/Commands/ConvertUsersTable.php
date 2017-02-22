<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \DB;

class ConvertUsersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:users';

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
        $this->info('Start convert users table');
        $this->convertUsers();
        $this->info('End convert users table');
    }

    private function convertUsers()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "ID AS wp_id,
            user_login AS name,
            user_email AS email,
            user_registered AS created_at,
            user_registered AS updated_at";

        DB::table('wp_users')
            ->select(DB::raw($sql))
            ->chunk(100, function($users) {
                DB::table('users')->insert($users->map(function($x) {
                    $x->password = bcrypt($x->name . '_' . \Carbon\Carbon::parse($x->created_at));
                    return (array)$x;
                })->toArray());
        });
    }
}