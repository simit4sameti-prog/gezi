<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Hata raporlamayı aç (Debug için)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST request required']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$fileName = uniqid() . '_' . basename($file['name']);
// Dosya adındaki türkçe karakterleri ve boşlukları temizle
$fileName = preg_replace('/[^a-zA-Z0-9_.]/', '', $fileName);

$target_file = $target_dir . $fileName;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
$check = getimagesize($file["tmp_name"]);
if($check === false) {
    echo json_encode(['error' => 'File is not an image.']);
    exit;
}

// Allow certain file formats
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if(!in_array($imageFileType, $allowed)) {
    echo json_encode(['error' => 'Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.']);
    exit;
}

if (move_uploaded_file($file["tmp_name"], $target_file)) {
    // Return the path relative to the domain root
    // Eğer script root'ta ise "uploads/filename.jpg" döner.
    echo json_encode(['url' => $target_file]);
} else {
    echo json_encode(['error' => 'Sorry, there was an error uploading your file.']);
}
?>
