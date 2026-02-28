<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Define log file
$logFile = 'upload_debug.log';

// Helper to log messages
function logDebug($message) {
    global $logFile;
    $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

logDebug("--- New Request ---");
logDebug("Method: " . $_SERVER['REQUEST_METHOD']);
logDebug("Headers: " . print_r(getallheaders(), true));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Error: Method not allowed");
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

logDebug("POST Data: " . print_r($_POST, true));
logDebug("FILES Data: " . print_r($_FILES, true));

// Check if 'file' key exists in $_FILES
if (!isset($_FILES['file'])) {
    logDebug("Error: 'file' key missing in \$_FILES");
    http_response_code(400);
    echo json_encode([
        'error' => 'No file sent',
        'debug_files_keys' => array_keys($_FILES),
        'debug_post_keys' => array_keys($_POST)
    ]);
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    logDebug("Error: Upload error code " . $file['error']);
    http_response_code(500);
    echo json_encode(['error' => 'File upload error code: ' . $file['error']]);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    logDebug("Created upload directory: " . $uploadDir);
}

// Secure filename
$filename = uniqid() . '_' . basename($file['name']);
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    logDebug("Success: File moved to " . $targetPath);
    // Return full URL if possible, or relative path
    // For this environment, relative path is safer or construct full URL based on HTTP_HOST
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    // Assuming script is at root
    $url = $protocol . "://" . $host . "/" . $targetPath;

    echo json_encode([
        'url' => $url,
        'message' => 'File uploaded successfully'
    ]);
} else {
    logDebug("Error: Failed to move uploaded file from " . $file['tmp_name'] . " to " . $targetPath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
}
?>
