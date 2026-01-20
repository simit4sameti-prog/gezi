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

// 1. Check if file_uploads is enabled in php.ini
if (!ini_get('file_uploads')) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Configuration Error: file_uploads is disabled in php.ini']);
    exit;
}

// 2. Check for post_max_size violation
// If content length is present but $_POST and $_FILES are empty, it implies the request exceeded post_max_size
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $postMaxSize = ini_get('post_max_size');
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    http_response_code(413); // Payload Too Large
    echo json_encode([
        'error' => "Upload failed: File size exceeds server limit.",
        'details' => "Request body size (" . $_SERVER['CONTENT_LENGTH'] . " bytes) exceeded post_max_size ($postMaxSize). upload_max_filesize is $uploadMaxFilesize."
    ]);
    exit;
}

// 3. Check if 'file' key exists in $_FILES
if (!isset($_FILES['file'])) {
    $receivedKeys = implode(', ', array_keys($_FILES));
    http_response_code(400);
    echo json_encode([
        'error' => "No file was uploaded.",
        'details' => "Expected key 'file' not found in request. Received keys: [" . ($receivedKeys ?: 'NONE') . "]. Check if FormData.append('file', ...) is correct."
    ]);
    exit;
}

$file = $_FILES['file'];

// 4. Check for PHP upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Unknown upload error';
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:   $errorMsg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
        case UPLOAD_ERR_FORM_SIZE:  $errorMsg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
        case UPLOAD_ERR_PARTIAL:    $errorMsg = 'The uploaded file was only partially uploaded'; break;
        case UPLOAD_ERR_NO_FILE:    $errorMsg = 'No file was uploaded'; break;
        case UPLOAD_ERR_NO_TMP_DIR: $errorMsg = 'Missing a temporary folder'; break;
        case UPLOAD_ERR_CANT_WRITE: $errorMsg = 'Failed to write file to disk'; break;
        case UPLOAD_ERR_EXTENSION:  $errorMsg = 'File upload stopped by extension'; break;
    }

    http_response_code(400);
    echo json_encode(['error' => $errorMsg, 'code' => $file['error']]);
    exit;
}

// 5. Ensure uploads directory exists and is writable
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    // Try to create with 0777 permissions
    if (!mkdir($uploadDir, 0777, true)) {
        $lastError = error_get_last();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create uploads directory.', 'details' => $lastError ? $lastError['message'] : 'Unknown error']);
        exit;
    }
    // Explicitly chmod just in case mkdir didn't set it (umask issues)
    chmod($uploadDir, 0777);
}

// Validate file type
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($fileExt, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type.', 'allowed' => $allowed, 'received' => $fileExt]);
    exit;
}

// Generate unique name
$newFileName = uniqid('img_', true) . "." . $fileExt;
$fileDestination = $uploadDir . $newFileName;

// Move uploaded file
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
    $lastError = error_get_last();
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to move uploaded file.',
        'details' => 'Check directory permissions for ' . $uploadDir,
        'system_error' => $lastError ? $lastError['message'] : ''
    ]);
}
?>