<?php
// Mock session for demo - in real app this would be set by login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noetic — <?php echo ucfirst($current_page); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --deep: #202030;
            --card: #39304A;
            --blush: #DCBCCE;
            --moss: #242E0F;
            --umber: #86715B;
            --dim: #2d2840;
            --text-muted: #a89aac;
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--deep);
            color: var(--blush);
            font-family: 'Crimson Text', Georgia, serif;
            font-size: 1.05rem;
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(57,48,74,0.4) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(36,46,15,0.2) 0%, transparent 50%);
        }

        /* ── NAVBAR ── */
        .noetic-nav {
            background: rgba(32,32,48,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(134,113,91,0.25);
            padding: 0.6rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand {
            font-family: 'IM Fell English', serif;
            font-size: 1.6rem;
            color: var(--blush) !important;
            letter-spacing: 0.04em;
            text-decoration: none;
        }
        .nav-brand span { color: var(--umber); }

        .noetic-nav .nav-link {
            color: var(--text-muted) !important;
            font-family: 'Crimson Text', serif;
            font-size: 1rem;
            letter-spacing: 0.05em;
            padding: 0.4rem 1rem !important;
            transition: color 0.2s;
            text-transform: uppercase;
            font-size: 0.82rem;
        }
        .noetic-nav .nav-link:hover,
        .noetic-nav .nav-link.active {
            color: var(--blush) !important;
        }
        .noetic-nav .nav-link.active {
            border-bottom: 1px solid var(--umber);
        }

        /* ── CARDS ── */
        .n-card {
            background: var(--card);
            border: 1px solid rgba(134,113,91,0.2);
            border-radius: 4px;
            transition: border-color 0.2s, transform 0.2s;
        }
        .n-card:hover { border-color: rgba(134,113,91,0.5); }

        /* ── BUTTONS ── */
        .btn-n {
            background: var(--moss);
            color: var(--blush);
            border: 1px solid rgba(134,113,91,0.3);
            font-family: 'Crimson Text', serif;
            font-size: 0.95rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-radius: 2px;
            padding: 0.45rem 1.4rem;
            transition: background 0.2s, color 0.2s;
        }
        .btn-n:hover { background: var(--umber); color: var(--deep); }
        .btn-n-outline {
            background: transparent;
            color: var(--blush);
            border: 1px solid var(--umber);
            font-family: 'Crimson Text', serif;
            font-size: 0.95rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-radius: 2px;
            padding: 0.45rem 1.4rem;
            transition: background 0.2s, color 0.2s;
        }
        .btn-n-outline:hover { background: var(--umber); color: var(--deep); }

        /* ── FORM CONTROLS ── */
        .form-control, .form-select {
            background: var(--deep);
            border: 1px solid rgba(134,113,91,0.35);
            color: var(--blush);
            border-radius: 2px;
            font-family: 'Crimson Text', serif;
        }
        .form-control:focus, .form-select:focus {
            background: var(--dim);
            color: var(--blush);
            border-color: var(--umber);
            box-shadow: 0 0 0 2px rgba(134,113,91,0.2);
        }
        .form-control::placeholder { color: var(--text-muted); }
        .form-label { color: var(--text-muted); font-size: 0.82rem; letter-spacing: 0.1em; text-transform: uppercase; }

        /* ── STARS ── */
        .stars { color: var(--umber); letter-spacing: 2px; }
        .stars .filled { color: #c9a87c; }

        /* ── PAGE HEADING ── */
        .page-heading {
            font-family: 'IM Fell English', serif;
            font-size: 2.2rem;
            color: var(--blush);
            border-bottom: 1px solid rgba(134,113,91,0.3);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* ── BADGE ── */
        .n-badge {
            background: rgba(134,113,91,0.25);
            color: var(--blush);
            border: 1px solid rgba(134,113,91,0.4);
            border-radius: 2px;
            padding: 0.15rem 0.6rem;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* ── DIVIDER ── */
        .ornament {
            text-align: center;
            color: var(--umber);
            letter-spacing: 0.5rem;
            margin: 1rem 0;
            font-size: 0.8rem;
        }

        /* ── ALERT ── */
        .n-alert {
            background: rgba(134,113,91,0.2);
            border: 1px solid var(--umber);
            color: var(--blush);
            border-radius: 2px;
            padding: 0.75rem 1rem;
        }

        /* ── MAIN WRAP ── */
        .main-wrap { padding: 2.5rem 0 4rem; }

        /* ── AVATAR ── */
        .avatar-ring {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 1px solid var(--umber);
            background: var(--moss);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 0.9rem;
            color: var(--blush);
            flex-shrink: 0;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--deep); }
        ::-webkit-scrollbar-thumb { background: var(--umber); border-radius: 3px; }
    </style>
</head>
<body>

<nav class="noetic-nav">
    <div class="container d-flex align-items-center gap-4">
        <a href="dashboard.php" class="nav-brand">Noetic<span>.</span></a>
        <div class="d-flex align-items-center gap-1 ms-2 flex-grow-1">
            <a href="books.php" class="nav-link <?php echo $current_page==='books'?'active':''; ?>">Library</a>
            <a href="groups.php" class="nav-link <?php echo $current_page==='groups'?'active':''; ?>">Circles</a>
            <a href="schedule.php" class="nav-link <?php echo $current_page==='schedule'?'active':''; ?>">Gatherings</a>
            <a href="recommendations.php" class="nav-link <?php echo $current_page==='recommendations'?'active':''; ?>">Discoveries</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="profile.php" class="nav-link <?php echo $current_page==='profile'?'active':''; ?> d-flex align-items-center gap-2">
                <div class="avatar-ring"><?php echo strtoupper(substr($_SESSION['username'],0,1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </a>
        </div>
    </div>
</nav>

<div class="main-wrap">
<div class="container">