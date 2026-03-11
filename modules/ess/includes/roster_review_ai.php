<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/auth_hr_manager.php';
require_once __DIR__ . '/../../../config/config.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request payload.'
    ]);
    exit;
}

$roster = $data['roster'] ?? [];
$coverage = $data['coverage'] ?? [];
$validation = $data['validation'] ?? [];

$critical = $validation['critical'] ?? [];
$warnings = $validation['warnings'] ?? [];

$prompt = "You are reviewing an employee roster for an HR Manager.\n\n";
$prompt .= "Department: " . ($roster['department_name'] ?? 'Unknown') . "\n";
$prompt .= "Period: " . ($roster['period_label'] ?? 'Unknown') . "\n";
$prompt .= "Total Employees: " . ($coverage['total_employees'] ?? 0) . "\n";
$prompt .= "AM Count: " . ($coverage['am_count'] ?? 0) . "\n";
$prompt .= "MD Count: " . ($coverage['md_count'] ?? 0) . "\n";
$prompt .= "GY Count: " . ($coverage['gy_count'] ?? 0) . "\n";
$prompt .= "Off Count: " . ($coverage['off_count'] ?? 0) . "\n\n";

$prompt .= "Critical Conflicts:\n";
if (!empty($critical)) {
    foreach ($critical as $c) {
        $prompt .= "- {$c}\n";
    }
} else {
    $prompt .= "- None\n";
}

$prompt .= "\nWarnings:\n";
if (!empty($warnings)) {
    foreach ($warnings as $w) {
        $prompt .= "- {$w}\n";
    }
} else {
    $prompt .= "- None\n";
}

$prompt .= "\nGive a short HR-friendly review summary in 3 to 5 sentences. ";
$prompt .= "State whether the roster looks okay to approve or should be returned for correction.";

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';

if (!$groqApiKey || $groqApiKey === 'GROQ_API_KEY') {
    echo json_encode([
        'success' => true,
        'summary' => fallbackSummary($critical, $warnings)
    ]);
    exit;
}

$payload = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        ["role" => "system", "content" => "You are an HR roster review assistant."],
        ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.3
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $groqApiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error || $httpCode >= 400) {
    echo json_encode([
        'success' => true,
        'summary' => fallbackSummary($critical, $warnings)
    ]);
    exit;
}

$result = json_decode($response, true);
$summary = $result['choices'][0]['message']['content'] ?? fallbackSummary($critical, $warnings);

echo json_encode([
    'success' => true,
    'summary' => trim($summary)
]);
exit;

function fallbackSummary(array $critical, array $warnings): string
{
    $criticalCount = count($critical);
    $warningCount = count($warnings);

    if ($criticalCount === 0 && $warningCount === 0) {
        return "No critical conflicts or warnings were detected in this roster. Based on current checks, the schedule appears clean and may be approved if all assignments are correct.";
    }

    if ($criticalCount > 0) {
        return "This roster has {$criticalCount} critical conflict(s) and {$warningCount} warning(s). It is recommended to return this roster to the officer for correction before approval.";
    }

    return "This roster has no critical conflicts but has {$warningCount} warning(s). It may still be reviewed for approval, but the HR Manager should check the flagged work pattern carefully.";
}