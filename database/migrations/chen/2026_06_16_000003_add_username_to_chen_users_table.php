<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddUsernameToChenUsersTable extends Migration
{
    public function up()
    {
        Schema::table('chen_users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill a username for any existing account (login credential is now username).
        foreach (DB::table('chen_users')->whereNull('username')->get() as $u) {
            $base = $u->email ? Str::before($u->email, '@') : ('user' . $u->id);
            $username = $base;
            $i = 1;
            while (DB::table('chen_users')->where('username', $username)->where('id', '!=', $u->id)->exists()) {
                $username = $base . $i++;
            }
            DB::table('chen_users')->where('id', $u->id)->update(['username' => $username]);
        }

        // Email is no longer required for login.
        Schema::table('chen_users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('chen_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
}
