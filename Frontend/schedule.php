<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

$msg = '';
$filter_group = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_event'])) {
    $gid = (int)($_POST['group_id'] ?? 0);
    $grp = getGroupById($gid);
    if ($grp) {
        $msg = "success:Gathering scheduled for <em>" . htmlspecialchars($_POST['event_date'] ?? '') . "</em> in <em>{$grp['name']}</em>.";
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['',''];

// My groups for dropdown
$my_groups = array_filter($groups, fn($g) => in_array($_SESSION['username'], $g['members']));

// Filter schedule
$filtered_schedule = $filter_group
    ? array_filter($schedule, fn($s) => $s['group_id'] == $filter_group)
    : $schedule;

// Sort by date
usort($filtered_schedule, fn($a,$b) => strtotime($a['date']) - strtotime($b['date']));
?>

<style>
.event-month-header {
    font-family: 'IM Fell English', serif;
    font-size: 1rem;
    color: var(--text-muted);
    letter-spacing: 0.15em;
    text-transform: uppercase;
    margin: 1.5rem 0 0.8rem;
    border-bottom: 1px solid rgba(134,113,91,0.2);
    padding-bottom: 0.4rem;
}
.event-row {
    display:flex; gap:1.2rem; align-items:flex-start;
    padding:1rem 0; border-bottom:1px solid rgba(134,113,91,0.12);
}
.event-date-box {
    min-width:54px; text-align:center;
    background:rgba(36,46,15,0.4); border:1px solid rgba(134,113,91,0.25);
    border-radius:2px; padding:0.4rem 0.2rem;
}
.event-day { font-family:'IM Fell English',serif; font-size:1.8rem; line-height:1; color:var(--blush); }
.event-month-sm { font-size:0.65rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); }
</style>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Gatherings</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#scheduleModal">
        <i class="bi bi-calendar-plus"></i> Schedule
    </button>
</div>

<?php if ($msg_text): ?>
<div class="n-alert mb-4"><?php echo $msg_text; ?></div>
<?php endif; ?>

<!-- Filter row -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="schedule.php" class="btn-n-outline btn btn-sm <?php echo !$filter_group?'btn-n':'' ; ?>">All Circles</a>
    <?php foreach ($my_groups as $g): ?>
    <a href="schedule.php?group_id=<?php echo $g['id']; ?>"
       class="btn-n-outline btn btn-sm <?php echo $filter_group==$g['id']?'btn-n':''; ?>">
       <?php echo htmlspecialchars($g['name']); ?>
    </a>
    <?php endforeach; ?>
</div>

<?php
$months_seen = [];
foreach ($filtered_schedule as $ev):
    $month_key = date('F Y', strtotime($ev['date']));
    $evb = getBookById($ev['book_id']);
    $evg = getGroupById($ev['group_id']);
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
                <a href="groups.php?id=<?php echo $ev['group_id']; ?>" style="color:var(--umber); text-decoration:none;">
                    <?php echo htmlspecialchars($evg['name']); ?>
                </a>
                &nbsp;·&nbsp; <i class="bi bi-clock"></i> <?php echo $ev['time']; ?>
                &nbsp;·&nbsp; <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ev['format']); ?>
            </div>
            <?php if ($ev['notes']): ?>
            <div style="font-size:0.9rem; font-style:italic; color:var(--text-muted);"><?php echo htmlspecialchars($ev['notes']); ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-column align-items-center gap-2" style="flex-shrink:0;">
            <img src="<?php echo $evb['cover']; ?>"
                 style="width:36px;height:54px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;"
                 alt="<?php echo htmlspecialchars($evb['title']); ?>">
            <a href="books.php?id=<?php echo $evb['id']; ?>" style="font-size:0.7rem; color:var(--text-muted); text-decoration:none; text-align:center; max-width:60px; line-height:1.2;">
                <?php echo htmlspecialchars($evb['title']); ?>
            </a>
        </div>
    </div>

<?php endforeach; ?>

<?php if (empty($filtered_schedule)): ?>
<div style="text-align:center; padding:3rem; color:var(--text-muted); font-style:italic;">
    No gatherings scheduled yet. Create one to begin.
</div>
<?php endif; ?>

<!-- Schedule modal -->
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
                            <option value="<?php echo $g['id']; ?>" <?php echo $filter_group==$g['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($g['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <select class="form-select" name="book_id" required>
                            <?php foreach ($books as $b): ?>
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

<?php require_once 'includes/footer.php'; ?>