<?php
use App\Models\User;
use App\Models\BranchHeadProfile;
use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get a super admin user for authorization testing
$superAdmin = User::where('admin_type', 'super_admin')->first();
echo "Super Admin: " . $superAdmin->name . " (" . $superAdmin->email . ")\n";
echo str_repeat("-", 80) . "\n\n";

// Get a branch to assign
$branch = Branch::first();
echo "Branch: " . $branch->name . " (ID: " . $branch->id . ")\n";
echo str_repeat("-", 80) . "\n\n";

// Simulate branch head account creation (like the controller would do)
$email = 'branchhead.test.' . time() . '@mtcgs.local';
$tempPassword = Str::random(12);
$tempPasswordHashed = Hash::make($tempPassword);

echo "Creating Branch Head Account:\n";
echo "Email: $email\n";
echo "Temp Password (plain): $tempPassword\n";
echo str_repeat("-", 80) . "\n";

try {
    // Create User
    $user = User::create([
        'name' => 'Test Branch Head',
        'email' => $email,
        'password' => $tempPasswordHashed,
        'role' => 'branch_head',
        'admin_type' => null,
        'profile_type' => BranchHeadProfile::class,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    echo "✓ User created (ID: {$user->id})\n";

    // Create BranchHeadProfile
    $profile = BranchHeadProfile::create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'employee_number' => 'BH-' . time(),
        'first_name' => 'Test',
        'last_name' => 'BranchHead',
        'middle_name' => 'Account',
        'position' => 'Branch Head',
        'department' => 'Administration',
        'contact_number' => '09123456789',
        'address' => 'Test Address',
        'date_hired' => now()->toDateString(),
        'is_fingerprint_registered' => false,
    ]);
    echo "✓ BranchHeadProfile created (ID: {$profile->id})\n";

    // Verify User-Profile relationship
    $user->refresh();
    $user->profile_id = $profile->id;
    $user->save();
    echo "✓ User-Profile relationship set\n";

    echo "\nVerification:\n";
    echo str_repeat("-", 80) . "\n";
    
    // Check database
    $dbUser = User::findOrFail($user->id);
    echo "✓ User found in DB: {$dbUser->email} (role: {$dbUser->role})\n";
    
    $dbProfile = BranchHeadProfile::findOrFail($profile->id);
    echo "✓ BranchHeadProfile found in DB: {$dbProfile->first_name} {$dbProfile->last_name}\n";
    
    $profileViaRelation = $user->getBranchHeadProfile();
    if ($profileViaRelation) {
        echo "✓ User->getBranchHeadProfile() works: {$profileViaRelation->first_name}\n";
    }

    // Count total
    $totalBranchHeads = BranchHeadProfile::count();
    $totalUsers = User::where('role', 'branch_head')->count();
    echo "\nDatabase Summary:\n";
    echo str_repeat("-", 80) . "\n";
    echo "Total BranchHeadProfiles: $totalBranchHeads\n";
    echo "Total Branch Head Users: $totalUsers\n";

    echo "\n✅ BRANCH HEAD ACCOUNT CREATION SUCCESSFUL!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
