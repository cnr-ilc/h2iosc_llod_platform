<?php

namespace Database\Seeders;

use App\Enums\ResourceStatusImport;
use App\Enums\ResourceStatusSubmission;
use App\Jobs\CreateProjectSemanticTurkey;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectSkosImportTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test.import.skos@pllod.dev'],
            [
                'name' => 'test_import_skos',
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        $resource = [
            'name' => 'test_import',
            'handle_id' => 'http://hdl.handle.net/test-0234',
            'internal_id' => 123,
            'type' => 'rdfs',
            'description' => 'questo è per un test',
            'authors' => 'test user',
            'item_identifier' => 'test-072',
            'issued_date' => '23-02-24',
            'publisher' => 'test publisher',
            'status_submission' => ResourceStatusSubmission::NOT_SUBMITTED->value,
            'status_import' => ResourceStatusImport::PENDING->value,
        ];

        $createdResource = (new Resource)->create($resource);

        $createdResource->users()->attach($user->id);

        $resource_file = [
            'resource_id' => $createdResource->id,
            'path' => 'disciplines.rdf',
            'name' => 'triple',
            'size' => 2514908,
            'mime_type' => 'xml/rdf',
            'description' => 'ontologia',
            'hash_md5' => 'ehbwehfgwoeufhuoefh38734',
        ];

        (new ResourceFile)->create($resource_file);

        CreateProjectSemanticTurkey::dispatchSync($createdResource);

    }
}
