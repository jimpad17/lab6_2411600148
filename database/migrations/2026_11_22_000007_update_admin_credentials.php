<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /** The new admin login shown on the login screen. */
    private const NEW_EMAIL = 'jimpadillo@gmail.com';

    private const OLD_EMAIL = 'admin@jimtrack.local';

    private const PASSWORD = 'JimTrack2026!';

    public function up(): void
    {
        $values = [
            'name' => 'Jim Padillo',
            'email' => self::NEW_EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ];

        $newAdmin = DB::table('users')->where('email', self::NEW_EMAIL)->first();

        if ($newAdmin) {
            DB::table('users')->where('id', $newAdmin->id)->update($values);

            // Keep the old account as a non-protected historical row, if it exists.
            DB::table('users')
                ->where('email', self::OLD_EMAIL)
                ->where('id', '!=', $newAdmin->id)
                ->update(['email' => 'legacy-admin@jimtrack.local']);

            return;
        }

        $oldAdmin = DB::table('users')->where('email', self::OLD_EMAIL)->first();

        if ($oldAdmin) {
            DB::table('users')->where('id', $oldAdmin->id)->update($values);
        }

        // Fresh installs get the account from UserSeeder instead. Inserting a
        // user here would duplicate seeded/test admins on migrate:fresh.
    }

    public function down(): void
    {
        // Credentials are intentionally not reverted: this is a one-way
        // configuration change and down() keeps the data intact.
    }
};
