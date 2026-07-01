<?php

use App\Models\Member;

use App\Models\UserDeviceDetails;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Schema;

if (!function_exists('authMember')) {
    /**

     * Get the currently logged-in member (from session).

     *

     * @return \App\Models\Member|null

     */

    function authMember(array $select = ['*'])
    {
        $sessionMember = session('member');

        if (!$sessionMember || !isset($sessionMember['member_id'], $sessionMember['mobile'])) {
            return null;
        }

        return Member::where('member_number', $sessionMember['member_id'])

            ->where('phone_number', $sessionMember['mobile'])

            ->select($select)

            ->first();
    }
}

if (!function_exists('getLedgerYears')) {
    function getLedgerYears()
    {
        $startYear = 2021;

        $currentDate = new DateTime();

        $currentYear = (int) $currentDate->format('Y');

        $currentMonth = (int) $currentDate->format('n');

        // If month is Jan, Feb, or Mar (1, 2, 3),

        // the "Start" of the current FY is actually the previous calendar year.

        if ($currentMonth <= 3) {
            $maxFYStart = $currentYear - 1;
        } else {
            $maxFYStart = $currentYear;
        }

        $years = [];

        // Loop from the latest valid FY start year down to your base year

        for ($y = $maxFYStart; $y >= $startYear; $y--) {
            $years[] = $y . '_' . ($y + 1); // Using underscore to match your table format cgr_YYYY_YYYY
        }

        return $years;
    }
}

function getCGRDeposit($memberNumber, $year = 'cgr_2021_2022')
{
    $data = [];

    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)

            ->where('membernumber', $memberNumber)

            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        foreach ($rows as $r) {
            $months = [
                'April' => ['amount' => $r->april, 'mode' => $r->aprilmode, 'date' => $r->aprildate],

                'May' => ['amount' => $r->may, 'mode' => $r->maymode, 'date' => $r->maydate],

                'June' => ['amount' => $r->june, 'mode' => $r->junemode, 'date' => $r->junedate],

                'July' => ['amount' => $r->july, 'mode' => $r->julymode, 'date' => $r->julydate],

                'August' => ['amount' => $r->aug, 'mode' => $r->augmode, 'date' => $r->augdate],

                'September' => ['amount' => $r->sep, 'mode' => $r->sepmode, 'date' => $r->sepdate],

                'October' => ['amount' => $r->oct, 'mode' => $r->octmode, 'date' => $r->octdate],

                'November' => ['amount' => $r->nov, 'mode' => $r->novmode, 'date' => $r->novdate],

                'December' => ['amount' => $r->dece, 'mode' => $r->decmode, 'date' => $r->decdate],

                'January' => ['amount' => $r->jan, 'mode' => $r->janmode, 'date' => $r->jandate],

                'February' => ['amount' => $r->feb, 'mode' => $r->febmode, 'date' => $r->febdate],

                'March' => ['amount' => $r->march, 'mode' => $r->marchmode, 'date' => $r->marchdate],
            ];

            $data[] = [
                'opening_amount' => round(floatval($r->opening_amount)),

                'months' => $months,
            ];
        }
    }

    return $data;
}

function getTotalDeposit($memberNumber, $year = 'cgr_2021_2022')
{
    $total = 0;

    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)->where('membernumber', $memberNumber)->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        foreach ($rows as $r) {
            $total += round(floatval($r->april)) + round(floatval($r->may)) + round(floatval($r->june)) + round(floatval($r->july)) + round(floatval($r->aug)) + round(floatval($r->sep)) + round(floatval($r->oct)) + round(floatval($r->nov)) + round(floatval($r->dece)) + round(floatval($r->jan)) + round(floatval($r->feb)) + round(floatval($r->march));
        }
    }

    return $total;
}

function getTotalInterest2($memberNumber, $year = 'cgr_2021_2022')
{
    $total = 0;

    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)->where('membernumber', $memberNumber)->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $rate = 0.08;

        $total = 0;

        foreach ($rows as $r) {
            // Monthly interest contributions

            $total += round((($rate * floatval($r->april)) / 12) * 12) + round((($rate * floatval($r->may)) / 12) * 11) + round((($rate * floatval($r->june)) / 12) * 10) + round((($rate * floatval($r->july)) / 12) * 9) + round((($rate * floatval($r->aug)) / 12) * 8) + round((($rate * floatval($r->sep)) / 12) * 7) + round((($rate * floatval($r->oct)) / 12) * 6) + round((($rate * floatval($r->nov)) / 12) * 5) + round((($rate * floatval($r->dece)) / 12) * 4) + round((($rate * floatval($r->jan)) / 12) * 3) + round((($rate * floatval($r->feb)) / 12) * 2) + round((($rate * floatval($r->march)) / 12) * 1);

            // Withdraw logic

            if (!empty(trim($r->withdrawamount))) {
                $month = date('m', strtotime(str_replace('/', '-', $r->withdrawdate)));

                $opening = floatval($r->opening_amount) - floatval($r->withdrawamount);

                $monthNumber = intval($month);

                $monthsElapsed = $monthNumber - 3; // April = 4, so April = 1st month

                $monthsRemaining = 12 - $monthsElapsed;

                $previousAmount = round((($rate * floatval($r->opening_amount)) / 12) * max($monthsElapsed, 0));

                $newAmount = round((($rate * floatval($opening)) / 12) * max($monthsRemaining, 0));

                $total += $previousAmount + $newAmount;
            } else {
                $total += round((($rate * floatval($r->opening_amount)) / 12) * 12);
            }
        }
    }

    return $total;
}
function getTotalInterest($memberNumber, $year = 'cgr_2021_2022')
{
    $total = 0;
    $currentDate = new DateTime();
    $currentYear = (int)$currentDate->format('Y');
    $currentMonth = (int)$currentDate->format('n');

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;
    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    preg_match('/\d{4}/', $year, $matches);
    $inputYearInt = isset($matches[0]) ? (int)$matches[0] : 0;

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)->where('membernumber', $memberNumber)->get();
        if ($rows->isEmpty()) return 0;

        $rate = 0.08;

        foreach ($rows as $r) {
            // Monthly interest
            $total += round((((float)$rate * (float)$r->april) / 12) * 12, 2)
                + round((((float)$rate * (float)$r->may) / 12) * 11, 2)
                + round((((float)$rate * (float)$r->june) / 12) * 10, 2)
                + round((((float)$rate * (float)$r->july) / 12) * 9, 2)
                + round((((float)$rate * (float)$r->aug) / 12) * 8, 2)
                + round((((float)$rate * (float)$r->sep) / 12) * 7, 2)
                + round((((float)$rate * (float)$r->oct) / 12) * 6, 2)
                + round((((float)$rate * (float)$r->nov) / 12) * 5, 2)
                + round((((float)$rate * (float)$r->dece) / 12) * 4, 2)
                + round((((float)$rate * (float)$r->jan) / 12) * 3, 2)
                + round((((float)$rate * (float)$r->feb) / 12) * 2, 2)
                + round((((float)$rate * (float)$r->march) / 12) * 1, 2);

            // Withdrawal logic 
            if (!empty($r->withdrawamount)) {

                $withdrawMonth = (int)date('m', strtotime(str_replace('/', '-', $r->withdrawdate)));

                $openingAmount = floatval($r->opening_amount);
                $openingAfter = $openingAmount - floatval($r->withdrawamount);

                // Financial year order
                $financialMonths = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
                $index = array_search($withdrawMonth, $financialMonths);

                if ($index !== false) {
                    $previousMonths = $index;
                    $remainingMonths = 12 - $index;

                    $previousAmount = round((($rate * $openingAmount) / 12) * $previousMonths, 2);
                    $newAmount = round((($rate * $openingAfter) / 12) * $remainingMonths, 2);

                    $total += $previousAmount + $newAmount;
                }
            } else {
                $total += round((($rate * floatval($r->opening_amount)) / 12) * 12, 2);
            }
        }
    }

    return $total;
}

function getCGRDepositInterest2($memberNumber, $year = 'cgr_2021_2022')
{
    $interestData = [];

    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)

            ->where('membernumber', $memberNumber)

            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $rate = 0.08;

        foreach ($rows as $r) {
            $openingAmount = floatval($r->opening_amount);

            // Monthly interest calculations

            $monthFields = [
                'April' => 'april',

                'May' => 'may',

                'June' => 'june',

                'July' => 'july',

                'August' => 'aug',

                'September' => 'sep',

                'October' => 'oct',

                'November' => 'nov',

                'December' => 'dece',

                'January' => 'jan',

                'February' => 'feb',

                'March' => 'march',
            ];

            $monthlyInterest = [];

            $monthIndex = 0;

            foreach ($monthFields as $name => $field) {
                $amount = floatval($r->$field ?? 0);

                $monthlyInterest[$name] = round((($rate * $amount) / 12) * (12 - $monthIndex));

                $monthIndex++;
            }

            $openingInterest = round((($rate * $openingAmount) / 12) * 12);

            $interestData[] = [
                'opening_interest' => $openingInterest,

                'months' => $monthlyInterest,
            ];
        }
    }

    return $interestData;
}
function getCGRDepositInterest($memberNumber, $year = 'cgr_2021_2022')
{
    $interestData = [];

    $currentDate = new DateTime();
    $currentYear = (int) $currentDate->format('Y');
    $currentMonth = (int) $currentDate->format('n');

    // Current Financial Year
    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;
    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // Extract year from input
    preg_match('/\d{4}/', $year, $matches);
    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    if (Schema::hasTable($year)) {
        $rows = DB::table($year)
            ->where('membernumber', $memberNumber)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $rate = 0.08;

        foreach ($rows as $r) {
            $openingAmount = floatval($r->opening_amount ?? 0);
            $withdrawAmount = floatval($r->withdrawamount ?? 0);

            // ---------- MATCHED OLD LOGIC ----------
            $totalInterest = 0;

            if (!empty($withdrawAmount) && !empty($r->withdrawdate)) {

                $withdrawMonth = (int) date("m", strtotime(str_replace('/', '-', $r->withdrawdate)));

                $openingAfterWithdraw = $openingAmount - $withdrawAmount;

                // Financial year months mapping (April → March)
                $financialMonths = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];

                $index = array_search($withdrawMonth, $financialMonths);

                if ($index !== false) {
                    $previousMonths = $index;
                    $remainingMonths = 12 - $index;

                    $previousInterest = (($rate * $openingAmount) / 12) * $previousMonths;
                    $newInterest = (($rate * $openingAfterWithdraw) / 12) * $remainingMonths;

                    $totalInterest = round($previousInterest + $newInterest, 2);
                }
            } else {
                $totalInterest = round((($rate * $openingAmount) / 12) * 12, 2);
            }

            // ---------- MONTHLY INTEREST  ----------
            $monthFields = [
                'April' => 'april',
                'May' => 'may',
                'June' => 'june',
                'July' => 'july',
                'August' => 'aug',
                'September' => 'sep',
                'October' => 'oct',
                'November' => 'nov',
                'December' => 'dece',
                'January' => 'jan',
                'February' => 'feb',
                'March' => 'march',
            ];

            $monthlyInterest = [];
            $monthIndex = 0;

            foreach ($monthFields as $name => $field) {
                $amount = floatval($r->$field ?? 0);
                $monthlyInterest[$name] = round((($rate * $amount) / 12) * (12 - $monthIndex));
                $monthIndex++;
            }

            $interestData[] = [
                'opening_interest' => $totalInterest,
                'months' => $monthlyInterest,
            ];
        }
    }
    return $interestData;
}

function getTotalShareAmount($sno)
{
    $record = DB::table('share_amount')

        ->where('member_number', $sno)

        ->first();

    return $record ? $record->share_value : 'Not Available';
}

function getTotalShareDetails($sno)
{
    $record = DB::table('share_amount')

        ->where('member_number', $sno)

        ->first();

    return $record ? $record->share_details : 'Not Available';
}

function remainingOpeningAmount($sno, $year = 'cgr_2021_2022')
{
    $total = 0;

    if (Schema::hasTable($year)) {
        // Ensure $year is a valid table name (optional safety check)

        $record = DB::table($year)

            ->where('membernumber', $sno)

            ->first();

        if ($record) {
            $total = floatval($record->opening_amount) - floatval($record->withdrawamount);
        }
    }

    return $total;
}

function getClosingAmount($sno, $year = 'cgr_2021_2022')
{
    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    $totalDeposit = floatval(getTotalDeposit($sno, $year));

    $totalInterest = floatval(getTotalInterest($sno, $year));

    $remaining = floatval(remainingOpeningAmount($sno, $year));

    $amount = round($totalDeposit + $totalInterest + $remaining);
    return $amount;
}

function getTotalWithdrawal($sno, $year = 'cgr_2021_2022')
{
    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    // Optional: safety check for table name

    if (!preg_match('/^cgr_\d{4}_\d{4}$/', $year)) {
        throw new InvalidArgumentException('Invalid year table name.');
    }

    $withdrawAmount = 0;

    $withdrawDate = null;

    if (Schema::hasTable($year)) {
        $record = DB::table($year)

            ->where('membernumber', $sno)

            ->first();

        if (!$record) {
            return '0';
        }

        $withdrawAmount = floatval($record->withdrawamount ?? 0);

        $withdrawDate = $record->withdrawdate ?? null;

        if ($withdrawAmount == 0) {
            return '0';
        }
    }

    return round($withdrawAmount) . ' On - ' . $withdrawDate;
}

function getCGRYearSummary($memberNumber, $year)
{
    $currentDate = new DateTime();

    $currentYear = (int) $currentDate->format('Y');

    $currentMonth = (int) $currentDate->format('n');

    // 1. Calculate the current financial year start

    $currentFYStart = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;

    $currentFYTable = 'cgr_' . $currentFYStart . '_' . ($currentFYStart + 1);

    // 2. Extract the year from the input $year (e.g., "cgr_2026_2027")

    // We grab the first 4 digits to compare

    preg_match('/\d{4}/', $year, $matches);

    $inputYearInt = isset($matches[0]) ? (int) $matches[0] : 0;

    // 3. If the input is greater than the current FY, reset it

    if ($inputYearInt > $currentFYStart) {
        $year = $currentFYTable;
    }

    $deposit = getCGRDeposit($memberNumber, $year);

    $interest = getCGRDepositInterest($memberNumber, $year);
    $totalDeposit = getTotalDeposit($memberNumber, $year);

    $totalInterest = getTotalInterest($memberNumber, $year);

    $closing = getClosingAmount($memberNumber, $year);

    $withdrawal = getTotalWithdrawal($memberNumber, $year);

    return [
        'deposit' => $deposit,

        'interest' => $interest,

        'totalDeposit' => $totalDeposit,

        'totalInterest' => $totalInterest,

        'closing' => $closing,

        'totalWithdrawal' => $withdrawal,
    ];
}

function getCGRAllYearSummary($memberNumber)
{
    $years = getLedgerYears(); // returns array of years

    $summary = [];

    foreach ($years as $year) {
        $yearTable = 'cgr_' . str_replace('-', '_', $year);

        $data = getCGRYearSummary($memberNumber, $yearTable);

        $summary[$year] = $data;
    }

    return $summary;
}

function getDashboardSummary($memberNumber)
{
    $years = getLedgerYears();

    // Get the current (latest) ledger year

    $year = $years[0];

    $yearTable = 'cgr_' . str_replace('-', '_', $year);

    $totalDeposit = getTotalDeposit($memberNumber, $yearTable);

    $totalInterest = getTotalInterest($memberNumber, $yearTable);

    $closing = getClosingAmount($memberNumber, $yearTable);

    $withdrawal = getTotalWithdrawal($memberNumber, $yearTable);
    return [
        'totalDeposit' => $totalDeposit,

        'totalInterest' => $totalInterest,

        'closing' => $closing,

        'totalWithdrawal' => $withdrawal,
    ];
}


if (!function_exists('fcmTokenGet')) {
    /**

     * Get the currently logged-in member (from session).

     *

     * @return \App\Models\Member|null

     */

    function fcmTokenGet()
    {
        $auth_member = authMember(['member_number']);

        if (!$auth_member) {
            return null;
        }

        return UserDeviceDetails::where('member_number', $auth_member?->member_number)
            ->value('fcm_token');
    }
}