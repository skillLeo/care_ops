<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('email_templates')->insert([
            [
                'key' => 'intake',
                'name' => 'Intake Email',
                'subject' => 'A Client has been added.',
                'body' => "Client Name: [last_name], [first_name]\n" .
                    "MRN: [mrn]\n" .
                    "DOB: [dob]\n" .
                    "Medicaid ID: [medicaid_id]\n" .
                    "Carelon ID: [carelon_id]\n" .
                    "Starting Date: [starting_date]\n" .
                    "LOC: [current_level_of_care]\n" .
                    "Counselor: [counselor_name]\n" .
                    "Peer: [peer_name]\n\n" .
                    'Please Update Attendances (if necessary), create Icanotes, make sure all the notes are done up until today, Complete the assessments, Complete consents. If any of the details are wrong or missing, please update.',
                'to' => null,
                'cc' => null,
                'bcc' => 'fawzan@snbllc.org',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'discharge',
                'name' => 'Discharge Email',
                'subject' => 'A client has been discharged',
                'body' => "Client Name: [last_name], [first_name]\n" .
                    "MRN: [mrn]\n" .
                    "DOB: [dob]\n" .
                    "Carelon ID: [carelon_id]\n" .
                    "Medicaid ID: [medicaid_id]\n" .
                    "LOC: [last_level_of_care]\n" .
                    "Discharge Date: [discharge_date]\n\n" .
                    'Please update necessary information and discharge the following [last_level_of_care] authorization(s) in Carelon: [authorization_numbers]' . "\n" .
                    'Please make sure all the notes are complete before making the icanotes account inactive.',
                'to' => null,
                'cc' => null,
                'bcc' => 'fawzan@snbllc.org',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'transition',
                'name' => 'Transition Email',
                'subject' => 'A client has transitioned',
                'body' => "Client Name: [last_name], [first_name]\n" .
                    "MRN: [mrn]\n" .
                    "DOB: [dob]\n" .
                    "Carelon ID: [carelon_id]\n" .
                    "Medicaid ID: [medicaid_id]\n" .
                    "Previous LOC: [previous_level_of_care] ([previous_level_of_care_start] - [previous_level_of_care_end])\n" .
                    "New LOC: [new_level_of_care] ([new_level_of_care_start] - Ongoing)\n\n" .
                    'Please update necessary information and discharge the following [previous_level_of_care] authorization(s) in Carelon: [authorization_numbers]',
                'to' => null,
                'cc' => null,
                'bcc' => 'fawzan@snbllc.org',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'reactivation',
                'name' => 'Reactivation Email',
                'subject' => 'A client has been reactivated',
                'body' => "Client Name: [last_name], [first_name]\n" .
                    "MRN: [mrn]\n" .
                    "DOB: [dob]\n" .
                    "Carelon ID: [carelon_id]\n" .
                    "Medicaid ID: [medicaid_id]\n" .
                    "Reactivation Date: [reactivation_date]\n" .
                    "LOC: [current_level_of_care]\n\n" .
                    'Account has been reactivated.',
                'to' => null,
                'cc' => null,
                'bcc' => 'fawzan@snbllc.org',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
