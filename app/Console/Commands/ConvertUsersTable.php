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
        $this->info('Users');
        
        $this->convertUsers();
        $this->info("\tConverted users table");
    }

    private function convertUsers()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $sql = "SELECT ID AS wp_id,
                user_login AS name,
                user_email AS email,
                user_registered AS created_at,
                user_registered AS updated_at
            FROM wp_users";

        $users = DB::select(DB::raw($sql));

        foreach ($users as $user) {
            $user->password = bcrypt($user->name . '_' . \Carbon\Carbon::parse($user->created_at));
            DB::table('users')->insert((array)$user);
        }
    }
}
