# Architecture Diagram: Video Seeker Blocker System

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     INTERN DASHBOARD                            │
│                   (intern-dashboard.php)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    VIDEO PLAYER COMPONENT                       │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  <video id="protected-player">                            │ │
│  │    - controls enabled                                     │ │
│  │    - controlsList="nodownload"                           │ │
│  │    - oncontextmenu="return false"                        │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   JAVASCRIPT CONTROLLER                         │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  Variables:                                               │ │
│  │  • maxWatchedTime (furthest point reached)              │ │
│  │  • isCompleted (98%+ watched)                           │ │
│  │  • isSeeking (prevent updates during seek)              │ │
│  │  • lastValidTime (last valid position)                  │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  Event Listeners:                                         │ │
│  │  • loadedmetadata → Resume from saved position           │ │
│  │  • seeking → Block forward seeks                         │ │
│  │  • seeked → Clear seeking flag                           │ │
│  │  • timeupdate → Update progress UI                       │ │
│  │  • pause → Sync progress immediately                     │ │
│  │  • ended → Mark as complete                              │ │
│  │  • beforeunload → Save on exit                           │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SEEKER BLOCKER LOGIC                         │
│                                                                 │
│  User seeks forward?                                            │
│         │                                                       │
│         ├─ Yes → Is video completed?                           │
│         │         │                                             │
│         │         ├─ Yes → Allow seek ✓                        │
│         │         │                                             │
│         │         └─ No → Is seeking beyond maxWatchedTime+2?  │
│         │                   │                                   │
│         │                   ├─ Yes → BLOCK SEEK ✗              │
│         │                   │        • Reset to maxWatchedTime │
│         │                   │        • Show message            │
│         │                   │                                   │
│         │                   └─ No → Allow seek ✓               │
│         │                                                       │
│         └─ No (seeking backward) → Always allow ✓              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   PROGRESS SYNC SYSTEM                          │
│                                                                 │
│  Triggers:                                                      │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ 1. Every 5 seconds (setInterval)                        │  │
│  │ 2. On pause (immediate)                                 │  │
│  │ 3. On video end (immediate)                             │  │
│  │ 4. On page exit (sendBeacon)                            │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Sync Function:                                                 │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ async function syncProgress() {                         │  │
│  │   fetch('backend/api/update_watch_progress.php', {      │  │
│  │     method: 'POST',                                     │  │
│  │     body: JSON.stringify({                              │  │
│  │       video_id: videoId,                                │  │
│  │       seconds: Math.floor(maxWatchedTime),              │  │
│  │       total: Math.floor(video.duration)                 │  │
│  │     })                                                   │  │
│  │   })                                                     │  │
│  │ }                                                        │  │
│  └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      API ENDPOINT                               │
│           (backend/api/update_watch_progress.php)               │
│                                                                 │
│  1. Validate session                                            │
│  2. Parse JSON data                                             │
│  3. Verify video ownership                                      │
│  4. Update database:                                            │
│     ┌─────────────────────────────────────────────────────┐   │
│     │ INSERT INTO intern_video_progress                   │   │
│     │ VALUES (intern_id, video_id, seconds, total)        │   │
│     │ ON DUPLICATE KEY UPDATE                             │   │
│     │   seconds_watched = GREATEST(                       │   │
│     │     seconds_watched,                                │   │
│     │     VALUES(seconds_watched)                         │   │
│     │   ),                                                 │   │
│     │   total_seconds = VALUES(total_seconds),            │   │
│     │   updated_at = CURRENT_TIMESTAMP                    │   │
│     └─────────────────────────────────────────────────────┘   │
│  5. Check if 98%+ complete                                      │
│  6. Return status                                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATABASE                                │
│                  (intern_video_progress)                        │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ id | intern_id | video_id | seconds_watched | total_seconds│ │
│  │────┼───────────┼──────────┼─────────────────┼──────────────│ │
│  │ 1  │    42     │    15    │      245        │     600      │ │
│  │ 2  │    42     │    16    │      120        │     480      │ │
│  │ 3  │    43     │    15    │      590        │     600      │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  Additional Columns:                                            │
│  • is_completed (TINYINT) - 1 if 98%+ watched                  │
│  • watched_at (TIMESTAMP) - First watch time                   │
│  • updated_at (TIMESTAMP) - Last progress update               │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────┐
│  INTERN  │
│  LOGS IN │
└────┬─────┘
     │
     ▼
┌─────────────────────┐
│ Load Dashboard      │
│ • Get enrollment    │
│ • Get videos        │
│ • Get progress      │
└────┬────────────────┘
     │
     ▼
┌─────────────────────┐
│ Select Video        │
│ • Load from DB      │
│ • Get saved seconds │
└────┬────────────────┘
     │
     ▼
┌─────────────────────┐
│ Initialize Player   │
│ • Set maxWatchedTime│
│ • Seek to position  │
│ • Attach listeners  │
└────┬────────────────┘
     │
     ▼
┌─────────────────────┐
│ User Watches        │
│ • Update UI         │
│ • Track max time    │
└────┬────────────────┘
     │
     ├─────────────────┐
     │                 │
     ▼                 ▼
┌──────────┐    ┌──────────┐
│ Seeks    │    │ Pauses   │
│ Forward? │    │ Video?   │
└────┬─────┘    └────┬─────┘
     │               │
     ▼               ▼
┌──────────┐    ┌──────────┐
│ Check    │    │ Sync     │
│ Position │    │ Progress │
└────┬─────┘    └────┬─────┘
     │               │
     ├─ Allowed ─────┤
     │               │
     └─ Blocked      │
         │           │
         ▼           ▼
    ┌─────────┐ ┌─────────┐
    │ Show    │ │ Save to │
    │ Message │ │ Database│
    └─────────┘ └─────────┘
```

## Component Interaction

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (Browser)                       │
│                                                             │
│  ┌──────────────┐         ┌──────────────┐                │
│  │ Video Player │◄────────┤ JS Controller│                │
│  └──────┬───────┘         └──────┬───────┘                │
│         │                        │                         │
│         │ Events                 │ API Calls               │
│         │                        │                         │
└─────────┼────────────────────────┼─────────────────────────┘
          │                        │
          │                        │ AJAX/Fetch
          │                        │
┌─────────┼────────────────────────┼─────────────────────────┐
│         │                        ▼                         │
│         │              ┌──────────────────┐               │
│         │              │ update_watch_    │               │
│         │              │ progress.php     │               │
│         │              └────────┬─────────┘               │
│         │                       │                         │
│         │                       │ SQL Query               │
│         │                       │                         │
│         │              ┌────────▼─────────┐               │
│         │              │ Database         │               │
│         │              │ (MySQL)          │               │
│         │              └──────────────────┘               │
│         │                                                 │
│         │                    BACKEND (Server)             │
└─────────┼─────────────────────────────────────────────────┘
          │
          │ On Page Load
          │
          ▼
    ┌──────────┐
    │ Resume   │
    │ Position │
    └──────────┘
```

## State Machine

```
┌─────────────┐
│   INITIAL   │
│   STATE     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Load Video  │
│ Metadata    │
└──────┬──────┘
       │
       ▼
┌─────────────┐     Seek Forward
│  PLAYING    │────────────────┐
│             │                │
└──────┬──────┘                │
       │                       ▼
       │              ┌─────────────────┐
       │              │ Check Position  │
       │              └────────┬────────┘
       │                       │
       │              ┌────────┴────────┐
       │              │                 │
       │              ▼                 ▼
       │         ┌─────────┐      ┌─────────┐
       │         │ ALLOWED │      │ BLOCKED │
       │         └────┬────┘      └────┬────┘
       │              │                 │
       │              │                 ▼
       │              │         ┌──────────────┐
       │              │         │ Show Message │
       │              │         │ Reset Time   │
       │              │         └──────┬───────┘
       │              │                │
       │              └────────┬───────┘
       │                       │
       ▼                       ▼
┌─────────────┐         ┌─────────────┐
│   PAUSED    │         │  SEEKING    │
└──────┬──────┘         └──────┬──────┘
       │                       │
       │ Sync Progress         │
       │                       │
       ▼                       ▼
┌─────────────┐         ┌─────────────┐
│ Save to DB  │         │  SEEKED     │
└─────────────┘         └──────┬──────┘
                               │
                               ▼
                        ┌─────────────┐
                        │  PLAYING    │
                        └─────────────┘
```

## Security Flow

```
┌──────────────┐
│ User Request │
└──────┬───────┘
       │
       ▼
┌──────────────────┐
│ Check Session    │
│ isset(intern_id)?│
└──────┬───────────┘
       │
       ├─ No ──► 403 Forbidden
       │
       ▼ Yes
┌──────────────────┐
│ Validate Data    │
│ JSON valid?      │
└──────┬───────────┘
       │
       ├─ No ──► 400 Bad Request
       │
       ▼ Yes
┌──────────────────┐
│ Check Ownership  │
│ Video belongs to │
│ intern's program?│
└──────┬───────────┘
       │
       ├─ No ──► 403 Access Denied
       │
       ▼ Yes
┌──────────────────┐
│ Sanitize Input   │
│ • Cast to int    │
│ • Prepare stmt   │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Update Database  │
│ • Use GREATEST() │
│ • Prevent regress│
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Return Response  │
│ JSON with status │
└──────────────────┘
```

## Migration Flow

```
┌──────────────────┐
│ Admin Runs       │
│ Migration Script │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Check Columns    │
│ SHOW COLUMNS     │
└────────┬─────────┘
         │
         ├─ Exists ──► Skip
         │
         ▼ Missing
┌──────────────────┐
│ ALTER TABLE      │
│ ADD COLUMN       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Update Existing  │
│ Records          │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Show Success     │
│ Message          │
└──────────────────┘
```

---

**Legend:**
- `┌─┐` = Component/Process
- `│` = Data flow
- `▼` = Direction
- `◄─►` = Bidirectional
- `├─` = Decision branch
- `✓` = Success
- `✗` = Blocked/Failed
