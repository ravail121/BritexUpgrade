<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(EmailTemplateTableSeeder::class);
        // $this->call(SystemGlobalSettingTableSeeder::class);
        // $this->call(DefaultImeiTableSeeder::class);
        // $this->call(StaffTableSeeder::class);
        $this->call(EduSeeder::class);
    }
}
