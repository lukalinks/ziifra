#!/usr/bin/env bash
# Reset platform super admin password from SUPER_ADMIN_* in .env
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "${APP_DIR}"

php artisan config:clear

php artisan ziifra:grant-super-admin --create

php artisan tinker --execute='
$email = config("admin.default_super_admin_email");
$pass = config("admin.default_super_admin_password");
if ($pass === "" || $pass === null) {
    throw new RuntimeException("SUPER_ADMIN_PASSWORD is empty in .env");
}
$user = App\Models\User::query()->where("email", $email)->first();
if ($user === null) {
    throw new RuntimeException("User not found: ".$email);
}
$user->update([
    "password" => Illuminate\Support\Facades\Hash::make($pass),
    "is_super_admin" => true,
    "email_verified_at" => $user->email_verified_at ?? now(),
]);
echo "Password synced for {$email}\n";
'

php artisan config:cache

echo "Login at $(grep '^APP_URL=' .env | cut -d= -f2)/login"
