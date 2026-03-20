<?php
// api/report.php

header('Content-Type: application/json');

// ────────────────────────────────────────────────
//  ENVIRONMENT VARIABLES (set in Vercel dashboard)
// ────────────────────────────────────────────────
$supabaseUrl      = getenv('SUPABASE_URL')      ?: 'https://your-project.supabase.co';
$supabaseKey      = getenv('SUPABASE_ANON_KEY') ?: die('Missing SUPABASE_ANON_KEY');
$resendApiKey     = getenv('RESEND_API_KEY')    ?: die('Missing RESEND_API_KEY');
$fromEmail        = 'reports@yourdomain.com';   // verified sender in Resend

// ────────────────────────────────────────────────
//  INPUT (expects JSON POST from your frontend)
// ────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

$userId    = $input['userId']    ?? null;
$emailTo   = $input['email']     ?? null;
$period    = $input['period']    ?? 'last-30-days'; // e.g. last-7-days, monthly-2026-02, etc.

if (!$userId || !$emailTo) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing userId or email']);
    exit;
}

// ────────────────────────────────────────────────
//  FETCH DATA FROM SUPABASE (your ENTRY table)
// ────────────────────────────────────────────────
function fetchSpendingData($userId, $period) {
    global $supabaseUrl, $supabaseKey;

    // Simple date range logic — improve as needed
    $today = new DateTime();
    if ($period === 'last-7-days') {
        $start = (clone $today)->modify('-7 days')->format('Y-m-d');
    } elseif ($period === 'last-30-days') {
        $start = (clone $today)->modify('-30 days')->format('Y-m-d');
    } else {
        $start = (clone $today)->modify('-30 days')->format('Y-m-d'); // fallback
    }
    $end = $today->format('Y-m-d');

    $url = "$supabaseUrl/rest/v1/ENTRY?"
         . "select=entry_id,e_type,e_amount,e_date,CATEGORY(c_name)"
         . "&LOG.user_id=eq.$userId"
         . "&e_date=gte.$start"
         . "&e_date=lte.$end"
         . "&order=e_date.desc";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=minimal'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $entries = json_decode($response, true) ?: [];

    // Aggregate
    $byCategory = ['income' => [], 'expenses' => []];
    $daily = [];
    $totalIncome = 0;
    $totalExpenses = 0;

    foreach ($entries as $e) {
        $cat   = $e['CATEGORY']['c_name'] ?? 'Other';
        $amt   = (float)$e['e_amount'];
        $date  = substr($e['e_date'], 0, 10);
        $type  = strtoupper($e['e_type'] ?? '');

        if ($type === 'I') {
            $byCategory['income'][$cat] = ($byCategory['income'][$cat] ?? 0) + $amt;
            $totalIncome += $amt;
        } elseif ($type === 'E') {
            $byCategory['expenses'][$cat] = ($byCategory['expenses'][$cat] ?? 0) + $amt;
            $totalExpenses += $amt;

            // Daily for line chart
            $daily[$date] = ($daily[$date] ?? 0) + $amt;
        }
    }

    ksort($daily); // chronological

    return [
        'period'         => "$start to $end",
        'totalIncome'    => $totalIncome,
        'totalExpenses'  => $totalExpenses,
        'balance'        => $totalIncome - $totalExpenses,
        'categoriesExp'  => $byCategory['expenses'],
        'categoriesInc'  => $byCategory['income'],
        'dailyExpenses'  => $daily
    ];
}

$data = fetchSpendingData($userId, $period);

// ────────────────────────────────────────────────
//  GENERATE CHART URLs via QuickChart.io
// ────────────────────────────────────────────────
function quickChartUrl($config) {
    return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
}

$pieConfig = [
    'type' => 'pie',
    'data' => [
        'labels' => array_keys($data['categoriesExp']),
        'datasets' => [[
            'data' => array_values($data['categoriesExp']),
            'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
        ]]
    ],
    'options' => [
        'plugins' => ['title' => ['display' => true, 'text' => 'Expenses by Category']]
    ]
];

$lineConfig = [
    'type' => 'line',
    'data' => [
        'labels' => array_keys($data['dailyExpenses']),
        'datasets' => [[
            'label' => 'Daily Expenses',
            'data' => array_values($data['dailyExpenses']),
            'borderColor' => '#FF6384',
            'fill' => false
        ]]
    ],
    'options' => [
        'plugins' => ['title' => ['display' => true, 'text' => 'Daily Spending Trend']]
    ]
];

$pieUrl  = quickChartUrl($pieConfig);
$lineUrl = quickChartUrl($lineConfig);

// ────────────────────────────────────────────────
//  BUILD EMAIL HTML
// ────────────────────────────────────────────────
$html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h1 style="color: #1a73e8;">Your Financial Report</h1>
    <p>Period: <strong>{$data['period']}</strong></p>

    <h2>Summary</h2>
    <ul>
        <li><strong>Income:</strong> ₱ {$data['totalIncomeFormatted']}</li>
        <li><strong>Expenses:</strong> ₱ {$data['expensesFormatted']}</li>
        <li><strong>Balance:</strong> ₱ {$data['balanceFormatted']} 
            <span style="color:{$color};">{$status}</span></li>
    </ul>

    <h2>Expenses by Category</h2>
    <img src="$pieUrl" alt="Expenses Pie Chart" style="max-width: 100%; height: auto;">

    <h2>Daily Spending Trend</h2>
    <img src="$lineUrl" alt="Daily Expenses Line Chart" style="max-width: 100%; height: auto;">

    <p style="margin-top: 2rem; font-size: 0.9em; color: #666;">
        This is an automated report from your finance tracker.<br>
        View full history: <a href="https://your-app.com/dashboard">Dashboard</a>
    </p>
</body>
</html>
HTML;

// ────────────────────────────────────────────────
//  SEND VIA RESEND API
// ────────────────────────────────────────────────
$payload = [
    'from'    => $fromEmail,
    'to'      => $emailTo,
    'subject' => 'Your ' . ucfirst(str_replace('-', ' ', $period)) . ' Financial Summary',
    'html'    => $html
];

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $resendApiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Report sent to ' . $emailTo]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Email failed',
        'resend_response' => $response
    ]);
}