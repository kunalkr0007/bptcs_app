<?php

namespace App\Http\Controllers;

use App\Models\AccountDetail;
use App\Models\Deposit;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use InvalidArgumentException;
use Throwable;

class MainController extends Controller
{


    private function sumMonthEntries($entriesList)
    {
        $result = [];

        foreach ($entriesList as $entry) {
            foreach (($entry['months'] ?? []) as $monthName => $monthData) {

                if (!isset($result[$monthName])) {
                    $result[$monthName] = 0;
                }

                // normalize structure
                $items = isset($monthData[0]) ? $monthData : [$monthData];

                foreach ($items as $item) {
                    if (!empty($item['amount'])) {
                        $result[$monthName] += (float) $item['amount'];
                    }
                }
            }
        }

        return $result;
    }

    public function home(Request $request)
    {
        $member = authMember();

        $memberNumber = $member->member_number;
        $cacheKey = "dashboard_summary_{$memberNumber}";

        // Data change after 1hr ,refresh it from setting to reset
        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($memberNumber) {
            $rawSummary = getCGRAllYearSummary($memberNumber);
            $share_amount = getTotalShareAmount($memberNumber);
            $share_details = getTotalShareDetails($memberNumber);

            $yearlySummary = [];
            // foreach ($rawSummary as $financialYear => $data) {
            //     $depositMonths = $data['deposit'][0]['months'] ?? [];
            //     $interestMonths = $data['interest'][0]['months'] ?? [];

            //     $months = [];
            //     foreach ($depositMonths as $monthName => $monthData) {
            //         $months[] = [
            //             'month' => $monthName,
            //             'deposit' => floatval($monthData['amount'] ?? 0),
            //             'interest' => floatval($interestMonths[$monthName] ?? 0),
            //         ];
            //     }

            //     $yearlySummary[] = [
            //         'year' => $financialYear,
            //         'totalDeposit' => floatval($data['totalDeposit'] ?? 0),
            //         'totalWithdrawal' => floatval($data['totalWithdrawal'] ?? 0),
            //         'totalInterest' => floatval($data['totalInterest'] ?? 0),
            //         'closing' => floatval($data['closing'] ?? 0),
            //         'months' => $months,
            //     ];
            // }
            foreach ($rawSummary as $financialYear => $data) {

                $depositTotals = $this->sumMonthEntries($data['deposit'] ?? []);
                $interestTotals = $this->sumMonthEntries($data['interest'] ?? []);

                $months = [];

                $allMonths = array_unique(array_merge(
                    array_keys($depositTotals),
                    array_keys($interestTotals)
                ));

                foreach ($allMonths as $monthName) {
                    $months[] = [
                        'month' => $monthName,
                        'deposit' => $depositTotals[$monthName] ?? 0,
                        'interest' => $interestTotals[$monthName] ?? 0,
                    ];
                }

                $yearlySummary[] = [
                    'year' => $financialYear,
                    'totalDeposit' => (float) ($data['totalDeposit'] ?? 0),
                    'totalWithdrawal' => (float) ($data['totalWithdrawal'] ?? 0),
                    'totalInterest' => (float) ($data['totalInterest'] ?? 0),
                    'closing' => (float) ($data['closing'] ?? 0),
                    'months' => $months,
                ];
            }

            usort($yearlySummary, fn($a, $b) => strcmp($b['year'], $a['year']));
            //To get current financial year stats
            $dashboardStats = getDashboardSummary($memberNumber);
            $totals = [
                'deposit' => $dashboardStats['totalDeposit'],
                'withdrawal' => $dashboardStats['totalWithdrawal'],
                'interest' => $dashboardStats['totalInterest'],
                'closing' => $dashboardStats['closing'],
                'share_amount' => $share_amount,
                'share_details' => $share_details,
            ];

            return [
                'yearlySummary' => $yearlySummary,
                'totals' => $totals,
                'cached_at' => now(), // record the cache generation time
            ];
        });

        return Inertia::render('Home', [
            'member' => $member,
            'yearlySummary' => $data['yearlySummary'],
            'totals' => $data['totals'],
            // show user exactly when cache was generated
            'lastUpdated' => \Carbon\Carbon::parse($data['cached_at'])->format('d M Y, h:i A'),
            'logged_in_page' => session()->pull('logged_in_from_login', false),
        ]);
    }

    public function profile(Request $request)
    {
        $member = authMember([
            'name',
            'cgr_number',
            'member_number',
            'fathers_name',
            'date_of_birth',
            'phone_number',
            'permanent_address',
            'permanent_city',
            'permanent_state',
            'nominee_name',
            'nominee_relation',
            'designation',
            'date_of_appointment',
            'date_of_retirement',
            'office_address',
            'office_city',
            'office_state',
            'zone',
            'district'
        ]);
        if (!$member) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Please login to continue.']);
        }

        return Inertia::render('Profile', [
            'member' => $member,
        ]);
    }
    public function office(Request $request)
    {
        $member = authMember([
            'name',
            'designation',
            'date_of_appointment',
            'date_of_retirement',
            'office_address',
            'office_city',
            'office_state',
            'zone',
            'district'
        ]);
        if (!$member) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Please login to continue.']);
        }

        return Inertia::render('Office', [
            'member' => $member,
        ]);
    }

    public function ledgerYears()
    {
        $years = getLedgerYears();

        return Inertia::render('LedgerYearSelect', [
            'years' => $years,
        ]);
    }
    // public function ledger($year)
    // {
    //     $memberNumber = authMember()->member_number;
    //     $yearTable = 'cgr_' . str_replace('-', '_', $year);

    //     $summary = getCGRYearSummary($memberNumber, $yearTable);
    //     $deposit = $summary['deposit'] ?? [];
    //     $interest = $summary['interest'] ?? [];
    //     $months = array_keys($deposit[0]['months'] ?? []);


    //     $ledgerData = [];
    //     foreach ($months as $month) {
    //         $ledgerData[] = [
    //             'month' => ucfirst($month),
    //             'cgr' => [
    //                 'amount' => round(floatval($deposit[0]['months'][$month]['amount'] ?? 0)),
    //                 'mode' => $deposit[0]['months'][$month]['mode'] ?? null,
    //                 'date' => $deposit[0]['months'][$month]['date'] ?? null,
    //             ],
    //             'interest' => round(floatval($interest[0]['months'][$month] ?? 0)),
    //         ];
    //     }

    //     return Inertia::render('Ledger', [
    //         'year' => $year,
    //         'openingBalance' => $deposit[0]['opening_amount'] ?? 0,
    //         'closingBalance' => ($deposit[0]['opening_amount'] ?? 0) + $summary['totalDeposit'] + $summary['totalInterest'],
    //         'totalDeposit' => $summary['totalDeposit'],
    //         'totalInterest' => $summary['totalInterest'],
    //         'totalWithdrawal' => $summary['totalWithdrawal'],
    //         'closing' => $summary['closing'],
    //         'ledgerData' => $ledgerData,
    //     ]);
    // }

    public function ledger($year)
    {
        $memberNumber = authMember()->member_number;
        $yearTable = 'cgr_' . str_replace('-', '_', $year);

        $summary = getCGRYearSummary($memberNumber, $yearTable);
        $deposit = $summary['deposit'] ?? [];
        $interest = $summary['interest'] ?? [];
        // -------------------------------
        // GROUP MULTIPLE ENTRIES BY MONTH
        // -------------------------------

        $groupedLedger = [];
        $i = 1;

        foreach ($deposit as $entryIndex => $entry) {
            foreach ($entry['months'] as $month => $data) {
                // Skip if no data present
                if (floatval($data['amount']) == 0 && empty($data['mode']) && $i > 1) {

                    continue;
                }

                if (!isset($groupedLedger[$month])) {
                    $groupedLedger[$month] = [
                        'month' => ucfirst($month),
                        'rows' => [],
                        'total_interest' => 0,
                    ];
                }

                // Add row
                $groupedLedger[$month]['rows'][] = [
                    'amount'   => floatval($data['amount']),
                    'mode'     => $data['mode'],
                    'date'     => $data['date'],
                    'interest' => floatval($interest[$entryIndex]['months'][$month] ?? 0),
                ];
                // Sum interest
                $groupedLedger[$month]['total_interest'] += floatval($interest[$entryIndex]['months'][$month] ?? 0);
            }
            $i++;
        }
        // Convert associative → indexed array
        $ledgerData = array_values($groupedLedger);
        $startYear = (int) explode('_', $year)[0];
        $currentDate = now();

        // Show interest only after the financial year has been completed (from April onwards).
        $interestFinalized = $currentDate->year > $startYear + 1
            || ($currentDate->year == $startYear + 1 && $currentDate->month >= 4);
        return Inertia::render('Ledger', [
            'year' => $year,
            'openingBalance' => $deposit[0]['opening_amount'] ?? 0,
            'closingBalance' => ($deposit[0]['opening_amount'] ?? 0)
                + $summary['totalDeposit']
                + $summary['totalInterest'],
            'opening_interest' => $interest[0]['opening_interest'] ?? 0,

            'totalDeposit' => $summary['totalDeposit'],
            'totalInterest' => $summary['totalInterest'],
            'totalWithdrawal' => $summary['totalWithdrawal'],
            'closing' => $summary['closing'],

            // send grouped data
            'ledgerData' => $ledgerData,
            'interestFinalized' => $interestFinalized,
        ]);
    }

    public function notifications()
    {
        $id = authMember(['member_number'])->member_number;
        $notifications = Notification::orderBy('id', 'desc')->whereIn('user_id', ['0', $id])->where('status', '1')->get();

        return Inertia::render('Notification', [
            'notifications' => $notifications,
        ]);
    }

    public function help_support(Request $request)
    {
        return Inertia::render('HelpSupport');
    }

    public function security(Request $request)
    {
        return Inertia::render('Security');
    }

    public function accounts_index(Request $request)
    {
        try {
            $memberNumber = authMember()->member_number;

            $fdAccountIds = AccountDetail::where('member_sno', $memberNumber)
                ->where('account_type', 'fd')
                ->pluck('id');

            $rdAccountIds = AccountDetail::where('member_sno', $memberNumber)
                ->where('account_type', 'rd')
                ->pluck('id');

            $savingAccountIds = AccountDetail::where('member_sno', $memberNumber)
                ->where('account_type', 'saving')
                ->pluck('id');

            $fd = Deposit::with('account')->whereIn('account_id', $fdAccountIds)
                ->where('type', 'fd')
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($deposit) {
                    return array_merge(
                        $deposit->toArray(),
                        [
                            'transaction' => $deposit->fd_amount
                                ? $deposit->fd_amount->toArray()
                                : null
                        ]
                    );
                })
                ->toArray();
            $rd = Deposit::with(['account', 'getAmount'])
                ->whereIn('account_id', $rdAccountIds)
                ->where('type', 'rd')
                ->get()
                ->groupBy('account_id')
                ->map(function ($deposits) {
                    $firstDeposit = $deposits->first();
                    return [
                        'id' => $firstDeposit->account_id,
                        'account' => [
                            'account_number' => $firstDeposit->account->account_number ?? 'Deposit Account',
                            'status' => $firstDeposit->account->status
                        ],
                        'transaction' => [
                            'reference' => optional($firstDeposit->fd_amount)->reference ?? '-',
                        ],
                        'start_date' => $firstDeposit->start_date,
                        'status' => $firstDeposit->status,
                        'amount' => $deposits->sum(fn($deposit) => optional($deposit->fd_amount)->amount ?? 0),
                    ];
                })
                ->values()
                ->toArray();

            $savings = Deposit::with(['account', 'getAmount', 'transactions'])
                ->whereIn('account_id', $savingAccountIds)
                ->where('type', 'saving')
                ->get()
                ->groupBy('account_id')
                ->map(function ($deposits) {
                    $firstDeposit = $deposits->first();
                    $lastBalance = $firstDeposit->account->transactions
                        ->sortByDesc('created_at')
                        ->first()
                        ?->balance_after ?? 0;
                    return [
                        'id' => $firstDeposit->account->id,
                        'account' => [
                            'account_number' => $firstDeposit->account->account_number ?? 'Deposit Account',
                        ],
                        'transaction' => [
                            'reference' => $firstDeposit->fd_amount->reference,
                        ],
                        'start_date' => $firstDeposit->start_date,
                        'amount' => $lastBalance,
                        'status' => $firstDeposit->status,

                    ];
                })
                ->values()
                ->toArray();
            $dashboardStats = getDashboardSummary($memberNumber);
            $totals = [
                'deposit' => $dashboardStats['totalDeposit'],
                'withdrawal' => $dashboardStats['totalWithdrawal'],
                'interest' => $dashboardStats['totalInterest'],
                'closing' => $dashboardStats['closing'],
            ];
            return Inertia::render('Accounts/Index', [
                'fd' => $fd,
                'rd' => $rd,
                'savings' => $savings,
                'totals' => $totals
            ]);
        } catch (Throwable $e) {
            // Log the exception if needed
            // logger($e->getMessage());
            return redirect()->route('home')->with(['error' => 'Something went wrong.']);
        }
    }

    public function fd_show(Request $request)
    {
        try {
            $id = $request->id ?? null;
            if (!$id) {
                return redirect()->route('accounts.index');
            }
            $deposit = Deposit::with([
                'getAmount',
                'account',
                'account.memberDetail',
                'transactions' => function ($query) {
                    $query->orderBy('id', 'desc'); // or created_at
                }
            ])->findOrFail($id);
            if (!$deposit) {
                return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
            }

            return Inertia::render('Accounts/FdShow', [
                'deposit' => $deposit,
            ]);
        } catch (Throwable $e) {
            return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
        }
    }
    public function rd_show(Request $request)
    {
        try {
            $id = $request->id ?? null;
            if (!$id) {
                return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
            }

            $deposit = Deposit::with([
                'getAmount',
                'account',
                'transactions' => function ($query) {
                    $query->select(
                        'transactions.*',
                        'deposits.deposit_date as deposit_date'
                    )
                        ->leftJoin('deposits', function ($join) {
                            $join->on(
                                DB::raw("REPLACE(transactions.reference, 'DEP-', '')"),
                                '=',
                                'deposits.id'
                            );
                        })
                        ->orderBy('transactions.created_at', 'desc');
                }
            ])
                ->where('account_id', $id)
                ->first()
                ->toArray();

            if (!$deposit) {
                return redirect()->route('accounts.index');
            }

            return Inertia::render('Accounts/RdShow', [
                'rd' => $deposit,
            ]);
        } catch (Throwable $e) {
            return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
        }
    }
    public function saving_show(Request $request)
    {
        try {
            $id = $request->id ?? null;
            if (!$id) {
                return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
            }
            $account = AccountDetail::findOrFail($id);

            $transactions = $account->transactions()
                ->select('transactions.*', 'deposits.deposit_date as deposit_date')
                ->leftJoin('deposits', function ($join) {
                    $join->on(
                        DB::raw("REPLACE(transactions.reference, 'DEP-', '')"),
                        '=',
                        'deposits.id'
                    );
                })
                ->orderBy('transactions.created_at', 'desc')
                ->paginate(50);

            return Inertia::render('Accounts/SavingShow', [
                'saving' => $account,
                'transactions' => $transactions,
            ]);
        } catch (Throwable $e) {
            return redirect()->route('accounts.index')->with(['error' => 'Something went wrong.']);
        }
    }
    public function member_dashboard(Request $request)
    {
        try {
            $memberNumber = request('member');
            if (!empty($memberNumber)) {
                $member = Member::where(function ($q) use ($memberNumber) {
                    $q->where('member_number', $memberNumber);
                })->first();
            } else {
                $member = null; // or handle no input case
            }
            if (!empty($member)) {
                $fdAccountIds = AccountDetail::where('member_sno', $memberNumber)
                    ->where('account_type', 'fd')
                    ->pluck('id');

                $rdAccountIds = AccountDetail::where('member_sno', $memberNumber)
                    ->where('account_type', 'rd')
                    ->pluck('id');

                $savingAccountIds = AccountDetail::where('member_sno', $memberNumber)
                    ->where('account_type', 'saving')
                    ->pluck('id');
            } else {
                // If $memberNumber is empty, return empty collections
                $fdAccountIds = collect();
                $rdAccountIds = collect();
                $savingAccountIds = collect();
            }

            $fd = Deposit::with('account')->whereIn('account_id', $fdAccountIds)
                ->where('type', 'fd')
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($deposit) {
                    return array_merge(
                        $deposit->toArray(),
                        [
                            'transaction' => $deposit->fd_amount
                                ? $deposit->fd_amount->toArray()
                                : null
                        ]
                    );
                })
                ->toArray();
            $rd = Deposit::with(['account', 'getAmount'])
                ->whereIn('account_id', $rdAccountIds)
                ->where('type', 'rd')
                ->get()
                ->groupBy('account_id')
                ->map(function ($deposits) {
                    $firstDeposit = $deposits->first();
                    return [
                        'id' => $firstDeposit->account_id,
                        'account' => [
                            'account_number' => $firstDeposit->account->account_number ?? 'Deposit Account',
                            'status' => $firstDeposit->account->status
                        ],
                        'transaction' => [
                            'reference' => optional($firstDeposit->fd_amount)->reference ?? '-',
                        ],
                        'start_date' => $firstDeposit->start_date,
                        'status' => $firstDeposit->status,
                        'amount' => $deposits->sum(fn($deposit) => optional($deposit->fd_amount)->amount ?? 0),
                    ];
                })
                ->values()
                ->toArray();

            $savings = Deposit::with(['account', 'getAmount', 'transactions'])
                ->whereIn('account_id', $savingAccountIds)
                ->where('type', 'saving')
                ->get()
                ->groupBy('account_id')
                ->map(function ($deposits) {
                    $firstDeposit = $deposits->first();
                    $lastBalance = $firstDeposit->account->transactions
                        ->sortByDesc('created_at')
                        ->first()
                        ?->balance_after ?? 0;
                    return [
                        'id' => $firstDeposit->account->id,
                        'account' => [
                            'account_number' => $firstDeposit->account->account_number ?? 'Deposit Account',
                        ],
                        'transaction' => [
                            'reference' => $firstDeposit->fd_amount->reference,
                        ],
                        'start_date' => $firstDeposit->start_date,
                        'amount' => $lastBalance,
                        'status' => $firstDeposit->status,

                    ];
                })
                ->values()
                ->toArray();
            $dashboardStats = getDashboardSummary($memberNumber);

            $totals = [
                'deposit' => $dashboardStats['totalDeposit'],
                'withdrawal' => $dashboardStats['totalWithdrawal'],
                'interest' => $dashboardStats['totalInterest'],
                'closing' => $dashboardStats['closing'],
            ];
            return view('test', [
                'fd' => $fd,
                'rd' => $rd,
                'savings' => $savings,
                'totals' => $totals,
                'member' => $member
            ]);
        } catch (Throwable $e) {
            return redirect()->route('home')->with(['error' => 'Something went wrong.']);
        }
    }
}