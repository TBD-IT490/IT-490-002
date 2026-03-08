<?php
// ── DUMMY DATA ──────────────────────────────────────────────
// Replace with API calls later

$books = [
    [
        'id' => 1,
        'title' => 'The Secret History',
        'author' => 'Donna Tartt',
        'cover' => 'https://covers.openlibrary.org/b/id/8233546-L.jpg',
        'genre' => ['Literary Fiction', 'Mystery'],
        'year' => 1992,
        'pages' => 524,
        'rating' => 4.3,
        'reviews' => 1842,
        'description' => 'A small group of classics students at an elite Vermont college murder one of their own. A dark academic masterpiece exploring beauty, obsession, and the danger of moral detachment.',
        'isbn' => '978-0-14-017107-3',
    ],
    [
        'id' => 2,
        'title' => 'Piranesi',
        'author' => 'Susanna Clarke',
        'cover' => 'https://covers.openlibrary.org/b/id/10471185-L.jpg',
        'genre' => ['Fantasy', 'Mystery'],
        'year' => 2020,
        'pages' => 272,
        'rating' => 4.6,
        'reviews' => 3421,
        'description' => 'Piranesi lives in the House. The House is beautiful. Its halls are filled with statues, its tides are vast, and somewhere within it lie the bones of thirteen people. But who is Piranesi?',
        'isbn' => '978-1-63557-563-5',
    ],
    [
        'id' => 3,
        'title' => 'Jonathan Strange & Mr Norrell',
        'author' => 'Susanna Clarke',
        'cover' => 'https://covers.openlibrary.org/b/id/8302059-L.jpg',
        'genre' => ['Fantasy', 'Historical Fiction'],
        'year' => 2004,
        'pages' => 846,
        'rating' => 4.1,
        'reviews' => 987,
        'description' => 'Two magicians attempt to restore magic to England in the early nineteenth century. Clarke\'s debut novel is an endlessly inventive work of staggering depth.',
        'isbn' => '978-0-7653-2527-3',
    ],
    [
        'id' => 4,
        'title' => 'The Name of the Rose',
        'author' => 'Umberto Eco',
        'cover' => 'https://covers.openlibrary.org/b/id/12003547-L.jpg',
        'genre' => ['Historical Fiction', 'Mystery'],
        'year' => 1980,
        'pages' => 502,
        'rating' => 4.2,
        'reviews' => 1123,
        'description' => 'In 1327, a Franciscan friar investigates a series of mysterious deaths at an Italian abbey, uncovering a vast conspiracy around a forbidden book.',
        'isbn' => '978-0-15-144647-6',
    ],
    [
        'id' => 5,
        'title' => 'Middlemarch',
        'author' => 'George Eliot',
        'cover' => 'https://covers.openlibrary.org/b/id/9261091-L.jpg',
        'genre' => ['Classic', 'Literary Fiction'],
        'year' => 1872,
        'pages' => 904,
        'rating' => 4.4,
        'reviews' => 2241,
        'description' => 'A sweeping portrait of English provincial life, following the interweaving stories of idealistic Dorothea Brooke and the ambitious young doctor Tertius Lydgate.',
        'isbn' => '978-0-14-043388-9',
    ],
    [
        'id' => 6,
        'title' => 'House of Leaves',
        'author' => 'Mark Z. Danielewski',
        'cover' => 'https://covers.openlibrary.org/b/id/8371508-L.jpg',
        'genre' => ['Horror', 'Literary Fiction'],
        'year' => 2000,
        'pages' => 709,
        'rating' => 4.1,
        'reviews' => 1554,
        'description' => 'A young family moves into a house on Ash Tree Lane where they discover something is terribly wrong. A genre-shattering debut that challenges the very form of the novel.',
        'isbn' => '978-0-375-70376-4',
    ],
    [
        'id' => 7,
        'title' => 'Rebecca',
        'author' => 'Daphne du Maurier',
        'cover' => 'https://covers.openlibrary.org/b/id/9254057-L.jpg',
        'genre' => ['Gothic', 'Mystery'],
        'year' => 1938,
        'pages' => 449,
        'rating' => 4.5,
        'reviews' => 2876,
        'description' => '"Last night I dreamt I went to Manderley again." A nameless narrator marries a widower and is haunted by his first wife\'s enigmatic presence.',
        'isbn' => '978-0-380-73040-9',
    ],
    [
        'id' => 8,
        'title' => 'The Shadow of the Wind',
        'author' => 'Carlos Ruiz Zafón',
        'cover' => 'https://covers.openlibrary.org/b/id/8258644-L.jpg',
        'genre' => ['Mystery', 'Historical Fiction'],
        'year' => 2001,
        'pages' => 487,
        'rating' => 4.4,
        'reviews' => 2104,
        'description' => 'A boy discovers a mysterious book in post-war Barcelona\'s Cemetery of Forgotten Books, then finds someone is destroying every copy of the author\'s work.',
        'isbn' => '978-0-14-303490-3',
    ],
];

$genres = ['Literary Fiction', 'Mystery', 'Fantasy', 'Historical Fiction', 'Classic', 'Horror', 'Gothic'];

$groups = [
    [
        'id' => 1,
        'name' => 'The Obscurists',
        'description' => 'A circle devoted to the labyrinthine, the gothic, and the delightfully strange. We read what others fear to finish.',
        'members' => ['eleanor_voss', 'thomas_blackwood', 'iris_vale', 'dorian_marsh'],
        'member_count' => 4,
        'current_book_id' => 7,
        'invite_code' => 'OBS-7X2K',
        'created' => '2024-09-15',
    ],
    [
        'id' => 2,
        'name' => 'Marginalia Society',
        'description' => 'We annotate. We argue. We read slowly and we read well. Literary fiction only.',
        'members' => ['eleanor_voss', 'constance_grey', 'walter_penn'],
        'member_count' => 3,
        'current_book_id' => 5,
        'invite_code' => 'MAR-9P1R',
        'created' => '2024-11-02',
    ],
    [
        'id' => 3,
        'name' => 'The Cartographers',
        'description' => 'Fantasy and speculative fiction. We draw maps of worlds that don\'t exist — yet.',
        'members' => ['thomas_blackwood', 'iris_vale', 'sebastien_noir'],
        'member_count' => 3,
        'current_book_id' => 2,
        'invite_code' => 'CART-3Z5W',
        'created' => '2025-01-10',
    ],
];

$schedule = [
    [
        'id' => 1,
        'group_id' => 1,
        'book_id' => 7,
        'title' => 'Rebecca — Chapters I–XIV',
        'date' => '2025-08-14',
        'time' => '19:00',
        'notes' => 'Focus on the narrator\'s unreliable perspective and Mrs Danvers.',
        'format' => 'Online (Discord)',
    ],
    [
        'id' => 2,
        'group_id' => 2,
        'book_id' => 5,
        'title' => 'Middlemarch — Book I',
        'date' => '2025-08-20',
        'time' => '20:00',
        'notes' => 'Dorothea\'s idealism and its limits. Come with annotated passages.',
        'format' => 'In Person — The Owl & Pen Café',
    ],
    [
        'id' => 3,
        'group_id' => 1,
        'book_id' => 7,
        'title' => 'Rebecca — Full Novel Discussion',
        'date' => '2025-09-04',
        'time' => '19:00',
        'notes' => 'Full discussion with spoilers. What does Manderley represent?',
        'format' => 'Online (Discord)',
    ],
    [
        'id' => 4,
        'group_id' => 3,
        'book_id' => 2,
        'title' => 'Piranesi — Complete',
        'date' => '2025-08-28',
        'time' => '18:30',
        'notes' => 'The House as metaphor. Knowledge, solitude, and identity.',
        'format' => 'Online (Zoom)',
    ],
];

$discussions = [
    [
        'id' => 1,
        'book_id' => 7,
        'group_id' => 1,
        'author' => 'thomas_blackwood',
        'content' => 'The second Mrs de Winter never receives a name. I think this erasure is the entire point — she has no identity of her own, only an absence where Rebecca was.',
        'created' => '2025-07-28 14:32',
        'replies' => [
            ['author'=>'iris_vale','content'=>'Yes, and Mrs Danvers actively enforces this erasure. She\'s a monument to someone who no longer exists.','created'=>'2025-07-28 15:01'],
            ['author'=>'eleanor_voss','content'=>'What strikes me is how the novel itself participates — we only know Rebecca through others\' desire for her.','created'=>'2025-07-28 15:44'],
        ],
    ],
    [
        'id' => 2,
        'book_id' => 5,
        'group_id' => 2,
        'author' => 'constance_grey',
        'content' => 'Dorothea\'s desire to be useful, to build cottages, to matter — it reads almost painfully contemporary. The ambition with no sanctioned outlet.',
        'created' => '2025-07-30 10:15',
        'replies' => [
            ['author'=>'walter_penn','content'=>'Eliot makes it structural, not personal. The world cannot accommodate a St Theresa.','created'=>'2025-07-30 11:02'],
        ],
    ],
];

$user_ratings = [
    // user_id => [book_id => rating]
    1 => [1 => 5, 2 => 4, 7 => 5, 3 => 3, 8 => 4],
];

$user_reviews = [
    [
        'id' => 1,
        'user' => 'eleanor_voss',
        'book_id' => 1,
        'rating' => 5,
        'title' => 'A dark mirror held up to beauty',
        'body' => 'Tartt writes sentences that feel like entering a candlelit room. The moral rot at the center of the novel is all the more disturbing for how seductive it is. I have read this four times.',
        'created' => '2025-06-12',
    ],
    [
        'id' => 2,
        'user' => 'thomas_blackwood',
        'book_id' => 7,
        'rating' => 5,
        'title' => 'The most Gothic of all Gothics',
        'body' => 'Du Maurier understood that the true horror is the past — not the dead, but the living\'s inability to release them. Rebecca never appears and she dominates every page.',
        'created' => '2025-07-01',
    ],
    [
        'id' => 3,
        'user' => 'iris_vale',
        'book_id' => 2,
        'rating' => 5,
        'title' => 'A cathedral of strangeness',
        'body' => 'Piranesi is everything I want fiction to do: to make a world so strange you forget to breathe while reading it, then mourn it when it ends.',
        'created' => '2025-07-18',
    ],
];

function getBookById($id) {
    global $books;
    foreach ($books as $b) { if ($b['id'] == $id) return $b; }
    return null;
}

function getGroupById($id) {
    global $groups;
    foreach ($groups as $g) { if ($g['id'] == $id) return $g; }
    return null;
}

function renderStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half?1:0);
    $out = '<span class="stars">';
    for ($i=0;$i<$full;$i++) $out .= '<i class="bi bi-star-fill filled"></i>';
    if ($half) $out .= '<i class="bi bi-star-half filled"></i>';
    for ($i=0;$i<$empty;$i++) $out .= '<i class="bi bi-star"></i>';
    $out .= '</span>';
    return $out;
}