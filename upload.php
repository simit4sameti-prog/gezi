<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileError = $file['error'];

if ($fileError === 0) {
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($fileExt, $allowed)) {
        // Generate unique name
        $newFileName = uniqid('', true) . "." . $fileExt;
        $fileDestination = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            // Getting the base URL protocol and host
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $domainName = $_SERVER['HTTP_HOST'];

            // Construct the full URL
            $url = $protocol . $domainName . '/' . $fileDestination;

            echo json_encode(['url' => $url]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Upload error code: ' . $fileError]);
}
?>