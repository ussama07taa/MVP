<?php
// Quick test script - run with: php test_attendance.php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emp = \App\Models\Employee::first();
if (!$emp) { echo "No employee found\n"; exit; }

$year  = 2026;
$month = 5;

$pDays  = $emp->attendances()->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'present')->count();
$hDays  = $emp->attendances()->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'half_day')->count();
$worked = $pDays + ($hDays * 0.5);
$wageSum = $emp->attendances()->where('is_paid', false)->sum('wage_earned');

echo "Employee      : " . $emp->name . "\n";
echo "Daily Salary  : " . $emp->daily_salary . " DH\n";
echo "Present Days  : " . $pDays . "\n";
echo "Half Days     : " . $hDays . "\n";
echo "Worked Days   : " . $worked . "\n";
echo "Earned Salary : " . round($worked * (float)$emp->daily_salary, 2) . " DH\n";
echo "Unpaid Wages  : " . $wageSum . " DH\n";
echo "Total Advances: " . $emp->total_advances . " DH\n";
echo "Net to Pay    : " . round(max(0, $wageSum - (float)$emp->total_advances), 2) . " DH\n";
