<?php

use App\Model\FaqCategory;
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
            FaqCategory::create([
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
