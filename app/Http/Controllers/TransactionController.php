<?php

namespace App\Http\Controllers;

use App\Helpers\JWT;
use App\Models\Billing;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {

        $transactions = Transaction::with(['campaign', 'zakat', 'infak', 'wakaf'])
        ->orderBy('transaction_date', 'desc')
        ->paginate(20);

        return response()->json($transactions);
    }

    public function getTransactionsByCategory($category)
    {
        $transactions = Transaction::where('category', $category)
            ->with(['campaign', 'zakat', 'infak', 'wakaf'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function getTransactionsByCampaignId($campaignId)
    {
        $transactions = Transaction::where('campaign_id', $campaignId)
            ->with(['campaign', 'zakat', 'infak', 'wakaf'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);
    
        return response()->json($transactions);
    }
    

    public function getUserTransactions(Request $request)
    {
        $authHeader = $request->header('Authorization');
        $userId = null;
    
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $key = env('JWT_SECRET', 'your-secret-key');
    
            try {
                $decoded = JWT::decode($token, $key, ['HS256']);
                $userId = $decoded->sub;
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid token'], 401);
            }
        }
    
        if (!$userId) {
            return response()->json(['error' => 'User not logged in'], 401);
        }

        $transactions = Transaction::where('user_id', $userId)
            ->leftJoin('campaigns', 'transactions.campaign_id', '=', 'campaigns.id')
            ->leftJoin('zakats', 'transactions.zakat_id', '=', 'zakats.id')
            ->leftJoin('infaks', 'transactions.infak_id', '=', 'infaks.id')
            ->leftJoin('wakafs', 'transactions.wakaf_id', '=', 'wakafs.id')
            ->select(
                'transactions.*',
                'campaigns.campaign_name',
                'zakats.category_name as zakat_name',
                'infaks.category_name as infak_name',
                'wakafs.category_name as wakaf_name'
            )
            ->get();
    
        $transactions->map(function ($transaction) {
            if ($transaction->campaign_id) {
                $transaction->category_name = $transaction->campaign_name;
            } elseif ($transaction->zakat_id) {
                $transaction->category_name = $transaction->zakat_name;
            } elseif ($transaction->infak_id) {
                $transaction->category_name = $transaction->infak_name;
            } elseif ($transaction->wakaf_id) {
                $transaction->category_name = $transaction->wakaf_name;
            }
            unset($transaction->campaign_name, $transaction->zakat_name, $transaction->infak_name, $transaction->wakaf_name);
            return $transaction;
        });
    
        return response()->json($transactions, 200);
    }
    

    public function getTransactionSummary(Request $request)
    {
        $authHeader = $request->header('Authorization');
        $userId = null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $key = env('JWT_SECRET', 'your-secret-key');

            try {
                $decoded = JWT::decode($token, $key, ['HS256']);
                $userId = $decoded->sub;
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid token'], 401);
            }
        }

        if (!$userId) {
            return response()->json(['error' => 'User not logged in'], 401);
        }

        $totalCampaign = Transaction::where('user_id', $userId)
            ->where('category', 'campaign')
            ->sum('transaction_amount');

        $totalZakat = Transaction::where('user_id', $userId)
            ->where('category', 'zakat')
            ->sum('transaction_amount');

        $totalInfak = Transaction::where('user_id', $userId)
            ->where('category', 'infak')
            ->sum('transaction_amount');

        $totalWakaf = Transaction::where('user_id', $userId)
            ->where('category', 'wakaf')
            ->sum('transaction_amount');
            
        $totalAll = Transaction::where('user_id', $userId)->sum('transaction_amount');

        return response()->json([
            'total_campaign' => $totalCampaign,
            'total_zakat' => $totalZakat,
            'total_infak' => $totalInfak,
            'total_wakaf' => $totalWakaf,
            'total_all' => $totalAll,
        ], 200);
    }

    public function summary(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', '2000-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now('Asia/Jakarta')->toDateString()))->endOfDay();
        
 
        $userPhones = User::whereBetween('created_at', [$startDate, $endDate])
            ->pluck('phone_number')
            ->toArray();
        
        $transactionPhones = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->pluck('phone_number')
            ->unique()
            ->toArray();
    
        $uniquePhones = array_merge($userPhones, $transactionPhones);
    
        return response()->json([
            'total_transaction' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('transaction_amount'),
    
            'total_transaction_count' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->count('transaction_id'),
    
            'total_billing_count' => Billing::where('success', false)
                ->whereBetween('billing_date', [$startDate, $endDate])
                ->count('billing_id'),
    
            'total_donatur' => count($uniquePhones),
    
            'total_migration' => Transaction::where('asal_transaksi', 'MIGRASI')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('transaction_amount'),

            'total_qris' => Transaction::where('asal_transaksi', 'QRIS ICT')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('transaction_amount'),
    
            'total_for_ict' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('for_ict'),
        ]);
    }
    
    

}