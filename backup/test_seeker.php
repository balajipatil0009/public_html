<?php
// Simple test file to verify video seeking capabilities on the current server
$video_path = isset($_GET['file']) ? $_GET['file'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Seek Test</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f0f0f0;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 800px;
            width: 100%;
        }

        video {
            width: 100%;
            margin-top: 1rem;
            border-radius: 4px;
            background: #000;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Server Seek Test</h1>
        <p>This page contains a raw HTML5 video player with <strong>NO JavaScript logic</strong>.</p>
        <p>Try to seek (jump forward/backward) in the video.</p>

        <?php if ($video_path): ?>
            <video controls>
                <source src="<?php echo htmlspecialchars($video_path); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div style="margin-top: 1rem; text-align: left; background: #eee; padding: 1rem; border-radius: 4px;">
                <strong>Test Results:</strong>
                <ul>
                    <li>If you <strong>CANNOT</strong> seek here (video snaps back), the issue is 100% the server
                        (<code>php -S</code>).</li>
                    <li>If you <strong>CAN</strong> seek here, the issue is in the dashboard code.</li>
                </ul>
            </div>
        <?php else: ?>
            <p style="color: red;">No video file specified. Usage: <code>test_seeker.php?file=path/to/video.mp4</code></p>
        <?php endif; ?>
    </div>
</body>

</html>