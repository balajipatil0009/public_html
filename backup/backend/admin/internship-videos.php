<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

if (!isset($_GET['internship_id'])) {
    header('Location: internships.php');
    exit();
}

$internship_id = (int) $_GET['internship_id'];

// Get internship details
$stmt = $db->prepare("SELECT title FROM internships WHERE id = :id");
$stmt->execute(['id' => $internship_id]);
$internship = $stmt->fetch();

if (!$internship) {
    header('Location: internships.php');
    exit();
}

$pageTitle = 'Manage Videos - ' . $internship['title'];

// Handle delete video
if (isset($_GET['delete'])) {
    $v_id = (int) $_GET['delete'];

    // Get file path if local
    $stmt = $db->prepare("SELECT local_path FROM internship_videos WHERE id = :id");
    $stmt->execute(['id' => $v_id]);
    $v_data = $stmt->fetch();

    if ($v_data && $v_data['local_path'] && file_exists('../../' . $v_data['local_path'])) {
        unlink('../../' . $v_data['local_path']);
    }

    $stmt = $db->prepare("DELETE FROM internship_videos WHERE id = :id AND internship_id = :i_id");
    if ($stmt->execute(['id' => $v_id, 'i_id' => $internship_id])) {
        setSuccessMessage('Video deleted successfully');
    }
    header("Location: internship-videos.php?internship_id=$internship_id");
    exit();
}

// Handle add/edit video (DB part, upload is handled via API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_video'])) {
    $v_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : null;
    $v_title = sanitize($_POST['v_title']);
    $v_description = sanitize($_POST['v_description']);
    $v_type = 'local'; // Force local only
    $v_url = null;
    $v_path = sanitize($_POST['local_path']);
    $v_duration = sanitize($_POST['v_duration']);
    $v_order = (int) $_POST['v_order'];

    if ($v_id) {
        $query = "UPDATE internship_videos SET title = :title, description = :description, video_type = :type, 
                  video_url = :video_url, local_path = :local_path, duration = :duration, display_order = :display_order 
                  WHERE id = :id AND internship_id = :i_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $v_id);
    } else {
        $query = "INSERT INTO internship_videos (internship_id, title, description, video_type, video_url, local_path, duration, display_order) 
                  VALUES (:i_id, :title, :description, :type, :video_url, :local_path, :duration, :display_order)";
        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(':i_id', $internship_id);
    $stmt->bindParam(':title', $v_title);
    $stmt->bindParam(':description', $v_description);
    $stmt->bindParam(':type', $v_type);
    $stmt->bindParam(':video_url', $v_url);
    $stmt->bindParam(':local_path', $v_path);
    $stmt->bindParam(':duration', $v_duration);
    $stmt->bindParam(':display_order', $v_order);

    if ($stmt->execute()) {
        setSuccessMessage($v_id ? 'Video updated' : 'Video added');
    } else {
        setErrorMessage('Failed to save video');
    }
    header("Location: internship-videos.php?internship_id=$internship_id");
    exit();
}

// Get videos
$stmt = $db->prepare("SELECT * FROM internship_videos WHERE internship_id = :id ORDER BY display_order ASC");
$stmt->execute(['id' => $internship_id]);
$videos = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="internships.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">&larr;
                Back to Internships</a>
            <h2 style="margin-top: 0.5rem;">Videos for <?php echo htmlspecialchars($internship['title']); ?></h2>
        </div>
        <button onclick="openVideoModal()" class="btn btn-primary">+ Add Video</button>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Quiz</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($videos) > 0): ?>
                    <?php foreach ($videos as $video):
                        $qStmt = $db->prepare("SELECT id FROM internship_quizzes WHERE video_id = :vid");
                        $qStmt->execute(['vid' => $video['id']]);
                        $quiz = $qStmt->fetch();
                        ?>
                        <tr>
                            <td><?php echo $video['display_order']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($video['title']); ?></strong><br>
                                <small style="color: #6b7280;"><?php echo truncateText($video['description'], 50); ?></small>
                            </td>
                            <td>
                                <span class="badge"
                                    style="background: <?php echo $video['video_type'] === 'local' ? '#ecfdf5; color: #059669;' : '#fef2f2; color: #ef4444;'; ?>">
                                    <?php echo strtoupper($video['video_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($video['duration']); ?></td>
                            <td>
                                <a href="internship-quizzes.php?video_id=<?php echo $video['id']; ?>"
                                    class="btn btn-sm <?php echo $quiz ? 'btn-success' : 'btn-outline'; ?>">
                                    <?php echo $quiz ? '✅ Edit Quiz' : '➕ Add Quiz'; ?>
                                </a>
                            </td>
                            <td class="action-buttons">
                                <button onclick='openVideoModal(<?php echo json_encode($video); ?>)'
                                    class="btn btn-sm btn-primary">Edit</button>
                                <a href="?internship_id=<?php echo $internship_id; ?>&delete=<?php echo $video['id']; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure? This will also delete the video file.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No videos found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div
        style="background: white; padding: 2.5rem; border-radius: 16px; width: 600px; max-width: 95%; max-height: 90vh; overflow-y: auto;">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem; font-size: 1.5rem;">Add Video</h3>
        <form id="videoForm" method="POST" action="">
            <input type="hidden" id="video_id" name="video_id">
            <input type="hidden" id="local_path" name="local_path">

            <div class="form-group">
                <label>Video Title *</label>
                <input type="text" id="v_title" name="v_title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="v_description" name="v_description" class="form-control" rows="3"></textarea>
            </div>

            <div id="local_input" class="form-group">
                <label>Upload Video File (MP4) *</label>
                <input type="file" id="video_file" class="form-control" accept="video/mp4"
                    onchange="handleFileUpload(this)">
                <div id="upload_progress_container" style="display: none; margin-top: 1rem;">
                    <div style="height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden;">
                        <div id="upload_bar"
                            style="height: 100%; width: 0%; background: var(--primary); transition: width 0.3s;"></div>
                    </div>
                    <small id="upload_status" style="display: block; margin-top: 0.5rem; color: #64748b;">Uploading:
                        0%</small>
                </div>
                <div id="file_ready" style="display: none; margin-top: 0.5rem; color: #059669; font-weight: 600;">
                    ✓ Video uploaded successfully.
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Duration (Time)</label>
                    <input type="text" id="v_duration" name="v_duration" class="form-control" placeholder="e.g., 45:00">
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" id="v_order" name="v_order" class="form-control" value="0">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" name="save_video" id="submitBtn" class="btn btn-primary">Save Video</button>
                <button type="button" onclick="closeVideoModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>

    async function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;

        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const fileId = Date.now() + "_" + Math.random().toString(36).substr(2, 9);
        const fileName = file.name;

        const progressContainer = document.getElementById('upload_progress_container');
        const progressBar = document.getElementById('upload_bar');
        const statusText = document.getElementById('upload_status');
        const submitBtn = document.getElementById('submitBtn');
        const fileReady = document.getElementById('file_ready');

        progressContainer.style.display = 'block';
        fileReady.style.display = 'none';
        submitBtn.disabled = true;

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('fileName', fileName);
            formData.append('index', i);
            formData.append('totalChunks', totalChunks);
            formData.append('fileId', fileId);

            try {
                const response = await fetch('../api/upload_video_chunk.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'completed') {
                    document.getElementById('local_path').value = result.path;
                    progressBar.style.width = '100%';
                    statusText.innerText = 'Upload complete!';
                    fileReady.style.display = 'block';
                    submitBtn.disabled = false;
                    break;
                } else if (result.status === 'chunk_saved') {
                    const percent = Math.round(((i + 1) / totalChunks) * 100);
                    progressBar.style.width = percent + '%';
                    statusText.innerText = `Uploading: ${percent}%`;
                } else {
                    alert('Upload failed: ' + (result.error || 'Unknown error'));
                    submitBtn.disabled = false;
                    return;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during upload.');
                submitBtn.disabled = false;
                return;
            }
        }
    }

    function openVideoModal(video = null) {
        const modal = document.getElementById('videoModal');
        const title = document.getElementById('modalTitle');
        const idInput = document.getElementById('video_id');
        const titleInput = document.getElementById('v_title');
        const descInput = document.getElementById('v_description');
        const pathInput = document.getElementById('local_path');
        const durInput = document.getElementById('v_duration');
        const orderInput = document.getElementById('v_order');
        const fileReady = document.getElementById('file_ready');
        const progressContainer = document.getElementById('upload_progress_container');

        if (video) {
            title.innerText = 'Edit Video';
            idInput.value = video.id;
            titleInput.value = video.title;
            descInput.value = video.description;
            pathInput.value = video.local_path || '';
            durInput.value = video.duration;
            orderInput.value = video.display_order;
            fileReady.style.display = video.local_path ? 'block' : 'none';
        } else {
            title.innerText = 'Add New Video';
            idInput.value = '';
            titleInput.value = '';
            descInput.value = '';
            pathInput.value = '';
            durInput.value = '';
            orderInput.value = 0;
            fileReady.style.display = 'none';
        }
        progressContainer.style.display = 'none';
        modal.style.display = 'flex';
    }

    function closeVideoModal() {
        document.getElementById('videoModal').style.display = 'none';
    }
</script>

<?php include 'includes/footer.php'; ?>