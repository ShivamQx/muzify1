<?php
session_start();
include 'connection.php';

// Step 0: Create songs table if it doesn't exist
$tableCheck = "SHOW TABLES LIKE 'songs'";
$tableResult = $conn->query($tableCheck);
if ($tableResult->num_rows === 0) {
    $createTableSQL = "
        CREATE TABLE songs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            artist VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            thumbnail VARCHAR(255) DEFAULT 'public/default.jpg',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if (!$conn->query($createTableSQL)) {
        die("Error creating songs table: " . $conn->error);
    }
}

// Handle file upload
if(isset($_POST['upload'])){
    $songFile = $_FILES['song_file'];
    $thumbFile = $_FILES['thumbnail'];

    $songName = $songFile['name'];
    $songTmp = $songFile['tmp_name'];
    $songPath = "uploads/" . basename($songName);

    $thumbPath = "public/default.jpg"; // default thumbnail
    if($thumbFile['name'] != ""){
        $thumbName = $thumbFile['name'];
        $thumbTmp = $thumbFile['tmp_name'];
        $thumbPath = "uploads/" . basename($thumbName);
        move_uploaded_file($thumbTmp, $thumbPath);
    }

    if(move_uploaded_file($songTmp, $songPath)){
        $title = pathinfo($songName, PATHINFO_FILENAME);
        $artist = $_POST['artist'] ?? "Unknown Artist";

        $stmt = $conn->prepare("INSERT INTO songs (title, artist, file_path, thumbnail) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $artist, $songPath, $thumbPath);
        $stmt->execute();
        $stmt->close();
        header("Location: home.php");
        exit;
    } else {
        $uploadError = "Failed to upload song.";
    }
}

// Fetch songs
$songs = [];
$sql = "SELECT id, title, artist, file_path, thumbnail FROM songs ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        if(!file_exists($row['thumbnail'])){
            $row['thumbnail'] = "public/default.jpg";
        }
        $songs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Muzify - Home</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {margin:0;font-family:'Poppins',sans-serif;background:#0f0f1a;color:#fff;display:flex;flex-direction:column;align-items:center;min-height:100vh;}
header{width:100%;padding:20px;text-align:center;background:rgba(255,255,255,0.05);backdrop-filter:blur(10px);}
header h1{margin:0;font-size:28px;font-weight:600;}
.buttons{margin:15px;display:flex;gap:15px;justify-content:center;}
.buttons a, .buttons button{padding:10px 20px;border:none;border-radius:10px;background:#1db954;color:#fff;font-weight:600;text-decoration:none;cursor:pointer;transition:0.3s;}
.buttons a:hover, .buttons button:hover{background:#1ed760;}
.song-list{margin:30px auto 140px;width:90%;max-width:1000px;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;}
.song-card{background:rgba(255,255,255,0.08);border-radius:18px;padding:15px;text-align:center;cursor:pointer;transition:transform 0.2s ease,background 0.3s ease;display:flex;flex-direction:column;align-items:center;}
.song-card:hover{transform:translateY(-6px) scale(1.02);background:rgba(255,255,255,0.12);}
.song-card img{width:100%;height:160px;object-fit:cover;border-radius:14px;margin-bottom:12px;box-shadow:0 6px 15px rgba(0,0,0,0.4);}
.song-card h3{margin:0;font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;}
.song-card p{font-size:13px;opacity:0.8;margin:4px 0 0;}

/* Music Player */
.player{position:fixed;bottom:0;left:0;width:100%;padding:15px 25px;background:rgba(15,15,25,0.95);backdrop-filter:blur(15px);display:flex;justify-content:space-between;align-items:center;box-shadow:0 -4px 20px rgba(0,0,0,0.6);z-index:1000;}
.player-left{display:flex;align-items:center;gap:15px;}
.player-left img{width:55px;height:55px;border-radius:10px;object-fit:cover;box-shadow:0 4px 10px rgba(0,0,0,0.4);}
.track-info{font-size:14px;line-height:1.2;}
.track-info .title{font-weight:600;}
.track-info .artist{font-size:12px;opacity:0.7;}
.controls{display:flex;gap:18px;align-items:center;}
.controls button{background:#2a2a40;border:none;padding:12px;border-radius:50%;color:#fff;cursor:pointer;font-size:18px;transition:background 0.3s ease, transform 0.2s ease;}
.controls button:hover{background:#444466;transform:scale(1.1);}
.progress-container{flex-grow:1;margin:0 20px;display:flex;align-items:center;gap:10px;}
.progress{flex-grow:1;appearance:none;height:5px;border-radius:5px;background:#333;cursor:pointer;}
.progress::-webkit-slider-thumb{appearance:none;height:14px;width:14px;background:#1db954;border-radius:50%;cursor:pointer;}
.volume{width:100px;}
</style>
</head>
<body>

<header>
  <h1>🎶 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
</header>

<div class="buttons">
    <a href="browse.php">Browse Music</a>
    <button onclick="document.getElementById('uploadModal').style.display='flex'">Upload Song</button>
</div>

<div class="song-list">
<?php foreach($songs as $song): ?>
    <div class="song-card" onclick="playSong('<?php echo $song['file_path']; ?>','<?php echo addslashes($song['title']); ?>','<?php echo addslashes($song['artist']); ?>','<?php echo $song['thumbnail']; ?>')">
        <img src="<?php echo $song['thumbnail']; ?>" alt="cover">
        <h3><?php echo $song['title']; ?></h3>
        <p><?php echo $song['artist']; ?></p>
    </div>
<?php endforeach; ?>
</div>

<!-- Upload Modal -->
<div id="uploadModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);justify-content:center;align-items:center;">
  <div style="background:#1a1a2f;padding:30px;border-radius:15px;position:relative;width:300px;">
    <span style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:18px;" onclick="document.getElementById('uploadModal').style.display='none'">&times;</span>
    <h3 style="text-align:center;">Upload Song</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="artist" placeholder="Artist Name" style="width:100%;margin:8px 0;padding:8px;border-radius:8px;border:none;">
        <input type="file" name="song_file" required style="width:100%;margin:8px 0;">
        <input type="file" name="thumbnail" style="width:100%;margin:8px 0;">
        <button type="submit" name="upload" style="width:100%;padding:10px;border:none;border-radius:10px;background:#1db954;color:#fff;font-weight:600;margin-top:10px;">Upload</button>
    </form>
    <?php if(isset($uploadError)){ echo "<p style='color:red;'>$uploadError</p>"; } ?>
  </div>
</div>

<div class="player">
  <div class="player-left">
    <img src="public/default.jpg" id="trackThumb" alt="cover">
    <div class="track-info">
        <div class="title" id="trackTitle">No song selected</div>
        <div class="artist" id="trackArtist"></div>
    </div>
  </div>
  <div class="progress-container">
    <span id="currentTime">0:00</span>
    <input type="range" id="progress" class="progress" value="0" min="0" max="100">
    <span id="duration">0:00</span>
  </div>
  <div class="controls">
    <button onclick="prevSong()">⏮</button>
    <button onclick="togglePlay()" id="playPause">▶️</button>
    <button onclick="nextSong()">⏭</button>
    <input type="range" id="volume" class="volume" min="0" max="1" step="0.01" value="1">
  </div>
</div>

<audio id="audioPlayer"></audio>

<script>
let audio = document.getElementById('audioPlayer');
let playPauseBtn = document.getElementById('playPause');
let progress = document.getElementById('progress');
let currentTimeEl = document.getElementById('currentTime');
let durationEl = document.getElementById('duration');
let trackTitle = document.getElementById('trackTitle');
let trackArtist = document.getElementById('trackArtist');
let trackThumb = document.getElementById('trackThumb');
let volume = document.getElementById('volume');
let currentSongIndex = -1;
let songs = <?php echo json_encode($songs); ?>;

function playSong(path, title, artist, thumb){
    audio.src = path;
    audio.play();
    playPauseBtn.textContent = "⏸";
    trackTitle.textContent = title;
    trackArtist.textContent = artist;
    trackThumb.src = thumb || "public/default.jpg";
    currentSongIndex = songs.findIndex(s => s.file_path === path);
}

function togglePlay(){
    if(audio.paused){audio.play(); playPauseBtn.textContent="⏸";}
    else{audio.pause(); playPauseBtn.textContent="▶️";}
}

function prevSong(){if(currentSongIndex>0){let s=songs[--currentSongIndex];playSong(s.file_path,s.title,s.artist,s.thumbnail);}}
function nextSong(){if(currentSongIndex<songs.length-1){let s=songs[++currentSongIndex];playSong(s.file_path,s.title,s.artist,s.thumbnail);}}

audio.addEventListener('timeupdate',()=>{
    progress.value=(audio.currentTime/audio.duration)*100||0;
    currentTimeEl.textContent=formatTime(audio.currentTime);
    durationEl.textContent=formatTime(audio.duration);
});

progress.addEventListener('input',()=>{audio.currentTime=(progress.value/100)*audio.duration;});
volume.addEventListener('input',()=>{audio.volume=volume.value;});

function formatTime(seconds){
    if(isNaN(seconds)) return "0:00";
    let min=Math.floor(seconds/60);
    let sec=Math.floor(seconds%60);
    return `${min}:${sec<10?'0':''}${sec}`;
}
</script>
</body>
</html>
