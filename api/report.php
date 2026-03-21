<?php
// api/report.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Env vars ────────────────────────────────────────────────────────────────
$supabaseUrl   = getenv('SUPABASE_URL')      ?: null;
$supabaseKey   = getenv('SUPABASE_ANON_KEY') ?: null;
$brevoApiKey   = getenv('BREVO_API_KEY')     ?: null;

if (!$supabaseUrl || !$supabaseKey || !$brevoApiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing environment variables']);
    exit;
}

$fromEmail = 'lolanamol@gmail.com';

// ── Parse input ────────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userId  = $input['userId']  ?? null;
$emailTo = $input['email']   ?? null;
$period  = $input['period']  ?? 'last-30-days';
$jwt     = $input['accessToken'] ?? $supabaseKey; // Use user JWT if available

if (!$userId || !$emailTo) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing userId or email']);
    exit;
}

// ── Fetch data from Supabase ──────────────────────────────────────────────────
function fetchSpendingData($userId, $period) {
    global $supabaseUrl, $supabaseKey, $jwt;

    $today = new DateTime();
    $start = clone $today;
    $end   = clone $today;

    $end->modify('+1 day');
    if ($period === 'last-7-days') $start->modify('-7 days');
    elseif ($period === 'last-30-days') $start->modify('-30 days');
    elseif ($period === 'this-month') $start->modify('first day of this month');
    elseif ($period === 'last-month') {
        $start->modify('first day of last month');
        $end->modify('last day of last month');
        $end->modify('+1 day');
    }

    $startStr = $start->format('Y-m-d');
    $endStr   = $end->format('Y-m-d');

    $url = $supabaseUrl . "/rest/v1/ENTRY?"
    . "select=entry_id,e_type,e_amount,e_date,CATEGORY(c_name),LOG!inner(user_id)"
    . "&LOG.user_id=eq." . urlencode($userId)
    . "&e_date=gte." . urlencode($startStr)
    . "&e_date=lte." . urlencode($endStr)
    . "&order=e_date.asc";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $jwt,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $entries = json_decode($response, true) ?: [];

    // --- NEW AGGREGATION LOGIC ---
    $byCategory = ['income' => [], 'expenses' => []];
    $daily = [];
    $totalIncome = 0;
    $totalExpenses = 0;

    foreach ($entries as $e) {
        $cat  = $e['CATEGORY']['c_name'] ?? 'Other';
        $amt  = (float)($e['e_amount'] ?? 0);
        $date = substr($e['e_date'] ?? '', 0, 10);
        $type = strtoupper($e['e_type'] ?? '');

        if (!isset($daily[$date])) {
            $daily[$date] = ['I' => 0, 'E' => 0];
        }

        if ($type === 'I') {
            $byCategory['income'][$cat] = ($byCategory['income'][$cat] ?? 0) + $amt;
            $totalIncome += $amt;
            $daily[$date]['I'] += $amt;
        } elseif ($type === 'E') {
            $byCategory['expenses'][$cat] = ($byCategory['expenses'][$cat] ?? 0) + $amt;
            $totalExpenses += $amt;
            $daily[$date]['E'] += $amt;
        }
    }

    ksort($daily);

    // Calculate Running Balance
    $runningBalance = 0;
    foreach ($daily as $date => &$vals) {
        $runningBalance += ($vals['I'] - $vals['E']);
        $vals['balance'] = $runningBalance;
    }

    return [
        'period'         => "$startStr to $endStr",
        'totalIncome'    => $totalIncome,
        'totalExpenses'  => $totalExpenses,
        'balance'        => $totalIncome - $totalExpenses,
        'categoriesExp'  => $byCategory['expenses'],
        'dailyMetrics'   => $daily
    ];
}

$data = fetchSpendingData($userId, $period);
if (!$data) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data']);
    exit;
}

/// ── QuickChart Configuration ──────────────────────────────────────────────────
function quickChartUrl($config) {
    return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
}

$pieConfig = [
    'type' => 'pie',
    'data' => [
        'labels' => array_keys($data['categoriesExp']),
        'datasets' => [['data' => array_values($data['categoriesExp']), 'backgroundColor' => ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']]]
    ]
];

$dates = array_keys($data['dailyMetrics']);
$lineConfig = [
    'type' => 'line',
    'data' => [
        'labels' => $dates,
        'datasets' => [
            [
                'label' => 'Income',
                'data' => array_map(fn($d) => $data['dailyMetrics'][$d]['I'], $dates),
                'borderColor' => 'rgb(123, 122, 77)',
                'fill' => false
            ],
            [
                'label' => 'Expenses',
                'data' => array_map(fn($d) => $data['dailyMetrics'][$d]['E'], $dates),
                'borderColor' => 'rgb(55, 66, 77)',
                'fill' => false
            ],
            [
                'label' => 'Balance',
                'data' => array_map(fn($d) => $data['dailyMetrics'][$d]['balance'], $dates),
                'borderColor' => 'rgb(75, 192, 192)',
                'fill' => false,
                'borderDash' => [5, 5]
            ]
        ]
    ],
    'options' => ['plugins' => ['title' => ['display' => true, 'text' => 'Financial Trends Over Time']]]
];

$pieUrl  = quickChartUrl($pieConfig);
$lineUrl = quickChartUrl($lineConfig);

// ── Build HTML email ──────────────────────────────────────────────────────────
$incomeFormatted   = number_format($data['totalIncome']   ?? 0, 2);
$expensesFormatted = number_format($data['totalExpenses'] ?? 0, 2);
$balanceFormatted  = number_format($data['balance']       ?? 0, 2);
$balanceColor      = ($data['balance'] ?? 0) >= 0 ? 'green' : 'red';
$balanceStatus     = ($data['balance'] ?? 0) >= 0 ? '▲ Surplus' : '▼ Deficit';

// ── Build HTML email ──────────────────────────────────────────────────────────
$incomeF   = number_format($data['totalIncome'], 2);
$expensesF = number_format($data['totalExpenses'], 2);
$balanceF  = number_format($data['balance'], 2);
$color     = $data['balance'] >= 0 ? 'green' : 'red';

$html = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;color:#333;padding:20px;">
    <h1 style="color:#1a73e8;">Financial Summary</h1>
    <p>Period: <strong>{$data['period']}</strong></p>
    <div style="background:#eee;padding:15px;border-radius:8px;">
        <p><strong>Total Income:</strong> ₱ {$incomeF}</p>
        <p><strong>Total Expenses:</strong> ₱ {$expensesF}</p>
        <p><strong>Net Balance:</strong> <span style="color:{$color};font-weight:bold;">₱ {$balanceF}</span></p>
    </div>
    
    <h2>Financial Trends</h2>
    <img src="{$lineUrl}" style="max-width:100%;height:auto;border:1px solid #ddd;">
    <p style="text-align:center;font-size:12px;color:#666;">
        Legend: <span style="color:rgb(123, 122, 77)">━ Income</span> | 
        <span style="color:rgb(55, 66, 77)">━ Expenses</span> | 
        <span style="color:rgb(75, 192, 192)">--- Balance</span>
    </p>

    <h2>Expenses by Category</h2>
    <img src="{$pieUrl}" style="max-width:100%;height:auto;">
</body>
</html>
HTML;

// ── Send via Brevo ─────────────────────────────────────────────────────────────
$payload = [
    'sender' => ['email' => $fromEmail, 'name' => 'Finance Tracker'],
    'to'     => [['email' => $emailTo]],
    'subject'=> 'Your Financial Summary: ' . $data['period'],
    'htmlContent' => $html
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $brevoApiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Email sent']);
} else {
    http_response_code($httpCode ?: 500);
    echo json_encode([
        'error' => 'Failed to send email',
        'status' => $httpCode,
        'brevo_response' => json_decode($response, true) ?: $response,
        'curl_error' => $curlErr
    ]);
}