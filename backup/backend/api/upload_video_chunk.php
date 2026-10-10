<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

$targetDir = "../../uploads/internship/videos/temp/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$fileName = $_POST['fileName'] ?? '';
$index = (int) ($_POST['index'] ?? 0);
$totalChunks = (int) ($_POST['totalChunks'] ?? 0);
$fileId = $_POST['fileId'] ?? ''; // Unique ID for this upload session

if (!$fileName || !$fileId) {
    die(json_encode(['error' => 'Invalid parameters']));
}

$chunkFile = $targetDir . $fileId . "_" . $index;

if (move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile)) {
    // Check if all chunks are uploaded
    $uploadedCount = 0;
    for ($i = 0; $i < $totalChunks; $i++) {
        if (file_exists($targetDir . $fileId . "_" . $i)) {
            $uploadedCount++;
        }
    }

    if ($uploadedCount === $totalChunks) {
        // Finalize upload
        $finalDir = "../../uploads/internship/videos/";
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('video_') . "." . $extension;
        $finalPath = $finalDir . $newFileName;

        $dest = fopen($finalPath, 'ab');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $targetDir . $fileId . "_" . $i;
            $source = fopen($chunkPath, 'rb');
            stream_copy_to_stream($source, $dest);
            fclose($source);
            unlink($chunkPath); // Delete chunk
        }
        fclose($dest);

        die(json_encode([
            'status' => 'completed',
            'path' => 'uploads/internship/videos/' . $newFileName,
            'fileName' => $newFileName
        ]));
    }

    die(json_encode(['status' => 'chunk_saved', 'index' => $index]));
} else {
    die(json_encode(['error' => 'Failed to move uploaded file']));
}
?>