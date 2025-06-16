<?php

use Illuminate\Database\Seeder;

use App\Main; // Указать модель обработки 

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class); // пользовательский класс

        // DB::insert('sql qwery', [data]); // 1 способ

        // DB::table('nameTable')->isetr([[data],[data]]); // 2 способ

        // Main::create([data]); // 3 способ

        

        Main::create(['text'=>'new']);
    }
}
