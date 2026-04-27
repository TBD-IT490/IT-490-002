<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'includes/data.php';
require_once 'includes/header.php';

$filter_group = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$msg          = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_event'])) {

    $result = rmq_rpc('schedule.create', [
         'group_id' => (int)($_POST['group_id'] ?? 0),
         'book_id' => (int)($_POST['book_id'] ?? 0),
         'title' => trim($_POST['event_title'] ?? ''),
         'date' => $_POST['event_date'] ?? '',
         'time' => $_POST['event_time'] ?? '',
         'format' => trim($_POST['event_format'] ?? ''),
         'notes' => trim($_POST['event_notes']  ?? ''),
     ]);
     if ($result['success'] ?? false) {
         $date_fmt = date('F j, Y', strtotime($_POST['event_date'] ?? ''));
         $msg      = "success:Gathering scheduled for <em>$date_fmt</em>.";
     } else {
         $msg = 'error:Could not save gathering. Please try again.';
     }
 }

 list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

 $bselect_res      = rmq_rpc('book.list', ['fields' => ['id', 'title']]); //'id,title'
 $books_for_select = $bselect_res['books'] ?? [];

 //showing member circles - pls work :c - update IT WORKS! (im tired its 2:30 ;-;)
 $gselect_res = rmq_rpc('group.list', ['username' => $_SESSION['username']]);
 $my_groups   = $gselect_res['groups'] ?? [];

 //showing meetings for all circles
 $mselect_res = rmq_rpc('schedule.list', [
    'username' => $_SESSION['username'],
    'group_id' => $filter_group
    ]);
 $filtered_schedule = $mselect_res['meetings'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Schedule</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Gatherings</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#scheduleModal">
        <i class="bi bi-calendar-plus"></i> Schedule
    </button>
</div>

<?php if ($msg_text): ?>
<div class="n-alert mb-4 <?php echo $msg_type === 'error' ? 'border-danger' : ''; ?>">
    <?php echo $msg_text; ?>
</div>
<?php endif; ?>

<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="schedule.php" class="btn btn-sm <?php echo !$filter_group ? 'btn-n' : 'btn-n-outline'; ?>">All Circles</a>
    <?php foreach ($my_groups as $g): ?>
    <a href="schedule.php?group_id=<?php echo $g['id']; ?>"
       class="btn btn-sm <?php echo $filter_group == $g['id'] ? 'btn-n' : 'btn-n-outline'; ?>">
        <?php echo htmlspecialchars($g['name']); ?>
    </a>
    <?php endforeach; ?>
</div>

<?php
$months_seen = [];
foreach ($filtered_schedule as $ev):
    $month_key = date('F Y', strtotime($ev['date']));
    $evb = getBookById((int)$ev['book_id']);
    $evg  = getGroupById((int)$ev['group_id']);
?>
    <?php if (!in_array($month_key, $months_seen)):
        $months_seen[] = $month_key; ?>
    <div class="event-month-header"><?php echo $month_key; ?></div>
    <?php endif; ?>

    <div class="event-row">
        <div class="event-date-box">
            <div class="event-day"><?php echo date('d', strtotime($ev['date'])); ?></div>
            <div class="event-month-sm"><?php echo date('M', strtotime($ev['date'])); ?></div>
        </div>
        <div class="flex-grow-1">
            <div style="font-family:'Cormorant Garamond',serif; font-size:1.15rem; color:var(--blush); margin-bottom:0.2rem;">
                <?php echo htmlspecialchars($ev['title']); ?>
            </div>
            <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:0.4rem;">
                <?php if ($evg): ?>
                <a href="groups.php?id=<?php echo $ev['group_id']; ?>" style="color:var(--umber); text-decoration:none;">
                    <?php echo htmlspecialchars($evg['name']); ?>
                </a>
                &nbsp;·&nbsp;
                <?php endif; ?>
                <i class="bi bi-clock"></i> <?php echo $ev['time']; ?>
                &nbsp;·&nbsp;
                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ev['format']); ?>
            </div>
            <?php if (!empty($ev['notes'])): ?>
            <div style="font-size:0.9rem; font-style:italic; color:var(--text-muted);"><?php echo htmlspecialchars($ev['notes']); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($evb): ?>
        <div class="d-flex flex-column align-items-center gap-2" style="flex-shrink:0;">
            <img src="<?php echo htmlspecialchars($evb['cover']); ?>"
                 style="width:36px;height:54px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;" alt="">
            <a href="books.php?id=<?php echo $evb['id']; ?>"
               style="font-size:0.7rem; color:var(--text-muted); text-decoration:none; text-align:center; max-width:60px; line-height:1.2;">
                <?php echo htmlspecialchars($evb['title']); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

<?php endforeach; ?>

<?php if (empty($filtered_schedule)): ?>
<div style="text-align:center; padding:3rem; color:var(--text-muted); font-style:italic;">
    No gatherings scheduled yet. Create one to begin.
</div>
<?php endif; ?>

<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card); border:1px solid rgba(134,113,91,0.3); border-radius:4px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(134,113,91,0.2);">
                <h5 class="modal-title" style="font-family:'IM Fell English',serif; color:var(--blush);">Schedule a Gathering</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="schedule_event" value="1">
                    <div class="mb-3">
                        <label class="form-label">Circle</label>
                        <select class="form-select" name="group_id" required>
                            <?php foreach ($my_groups as $g): ?>
                            <option value="<?php echo $g['id']; ?>"
                                    <?php echo $filter_group == $g['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <select class="form-select" name="book_id" required>
                            <?php foreach ($books_for_select as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="event_date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Time</label>
                            <input type="time" class="form-control" name="event_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Session Title</label>
                        <input type="text" class="form-control" name="event_title" placeholder="e.g. Rebecca — Chapters I–XIV">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Format / Location</label>
                        <input type="text" class="form-control" name="event_format" placeholder="e.g. Online (Zoom), The Blue Café…">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discussion Notes</label>
                        <textarea class="form-control" name="event_notes" rows="2" placeholder="Focus topics, readings to prepare…"></textarea>
                    </div>
                    <button type="submit" class="btn-n btn w-100">Save Gathering</button>
                </form>
            </div>
        </div>
    </div>
</div>
</html>

<?php require_once 'includes/footer.php'; ?>