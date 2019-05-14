<?php

use App\Model\Category;
use Illuminate\Database\Seeder;

class FaqCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = $this->getArray();
        foreach ($names as $name) {
            Category::create([
                'name' => $name, 
            ]);
        }
    }

    protected function getArray()
    {
        return [
            'Pre-sale',
            'Features',
            'Porting',
            'Troubleshooting',
            'Roaming',
            'Data',
            'My Account'
        ];
    }
}
