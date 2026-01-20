<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Check for upload errors
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No file uploaded';
    $errorMsg = 'Upload error: ' . $error;

    // Map common error codes
    switch ($error) {
        case UPLOAD_ERR_INI_SIZE: $errorMsg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
        case UPLOAD_ERR_FORM_SIZE: $errorMsg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
        case UPLOAD_ERR_PARTIAL: $errorMsg = 'The uploaded file was only partially uploaded'; break;
        case UPLOAD_ERR_NO_FILE: $errorMsg = 'No file was uploaded'; break;
        case UPLOAD_ERR_NO_TMP_DIR: $errorMsg = 'Missing a temporary folder'; break;
        case UPLOAD_ERR_CANT_WRITE: $errorMsg = 'Failed to write file to disk'; break;
        case UPLOAD_ERR_EXTENSION: $errorMsg = 'File upload stopped by extension'; break;
    }

    http_response_code(400);
    echo json_encode(['error' => $errorMsg]);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    // Try to create with 0777 permissions
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create uploads directory. Check permissions.']);
        exit;
    }
    // Explicitly chmod just in case mkdir didn't set it (umask issues)
    chmod($uploadDir, 0777);
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];

// Validate type
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($fileExt, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp']);
    exit;
}

// Generate unique name
$newFileName = uniqid('img_', true) . "." . $fileExt;
$fileDestination = $uploadDir . $newFileName;

if (move_uploaded_file($fileTmpName, $fileDestination)) {
    // Determine protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];

    // Correctly handle script path to avoid assuming root
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    // Clean up slashes
    $basePath = rtrim($scriptPath, '/\\');

    $url = $protocol . $domainName . $basePath . '/' . $fileDestination;

    echo json_encode(['url' => $url]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file. Check directory permissions.']);
}
?>