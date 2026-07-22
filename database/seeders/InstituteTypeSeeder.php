<?php

namespace Database\Seeders;

use App\Models\InstituteType;
use App\Models\Module;
use Illuminate\Database\Seeder;

class InstituteTypeSeeder extends Seeder
{
    /**
     * Starting set of institute types. Every type gets the full module set
     * for now — per-institute customization (enabling/disabling a module,
     * renaming its label) happens later at the institute_modules level, not
     * here. This template is only ever read at institute-creation time.
     */
    public function run(): void
    {
        $allModuleIds = Module::pluck('id')->all();

        $types = [
            'Coaching Institute',
            'Dance Academy',
            'Music Academy',
            'Computer Institute',
            'Spoken English Institute',
        ];

        foreach ($types as $name) {
            $type = InstituteType::updateOrCreate(['name' => $name]);

            $type->modules()->sync($allModuleIds);
        }
    }
}
