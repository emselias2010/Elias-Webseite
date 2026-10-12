<?php
/**
 * Endurance section: photos, Strava screenshots and statistics.
 * Everything here is maintained by hand. There is no Strava API, no OAuth
 * and no automatic import.
 *
 * ADDING A PHOTO
 * 1. Upload the file to /assets/images/sport/ (JPG or WebP, about 1600 px wide).
 * 2. Set 'image' => ['src' => '/assets/images/sport/your-file.jpg', 'width' => 1600, 'height' => 2000]
 *    (the real pixel size of the file) and write an 'alt' text describing the picture.
 *
 * BEFORE YOU UPLOAD A STRAVA SCREENSHOT
 * Crop out anything private: home address, start and end points near home,
 * full maps of regular routes, other people's names.
 *
 * STATISTICS: only numbers you have checked. The numbers below come from
 * Strava (activities since January 2026, as of 12 October 2026).
 */
declare(strict_types=1);

const SPORT_INTRO = [
    'title' => 'Beyond the finish line.',
    'text' => "Endurance is the part of my week that can't be faked. Running, the road bike, the pool and the gym: different sessions, same idea. Show up, go a little further, write it down.",
    // Set large between the intro and the statistics.
    'quote' => 'I didn’t come this far to come this far.',
    // Shown under "Strava in numbers". Update it together with the numbers.
    'strava_as_of' => 'As of 12 October 2026',
    // Shown under the gallery when a Strava link is set in profile.php.
    'strava_note' => 'Training log on Strava',
];

const SPORT_CATEGORIES = ['Running', 'Cycling', 'Triathlon'];

/** Example: ['label' => 'Marathon', 'value' => '3:45:10', 'note' => 'Event name, year'] */
const SPORT_STATS = [
    ['label' => 'First marathon', 'value' => '42.2 km', 'note' => 'July 2026, 4:46:34 moving time'],
    ['label' => 'Longest ride', 'value' => '101 km', 'note' => 'September 2026, 1,134 m climbing'],
    ['label' => 'Fastest 5 km', 'value' => '21:58', 'note' => 'October 2026, 4:23 /km'],
    ['label' => 'Road bike', 'value' => 'Cube Agree C:62'],
];

/**
 * "Strava in numbers": large numbers that count up when they scroll into view.
 * Copy the totals from Strava. Empty = section hidden.
 * Example: ['value' => 1234.5, 'decimals' => 1, 'unit' => 'km', 'label' => 'Running', 'note' => 'All time']
 */
const STRAVA_STATS = [
    ['value' => 117, 'label' => 'Activities', 'note' => 'Since January 2026'],
    ['value' => 401.9, 'decimals' => 1, 'unit' => 'km', 'label' => 'Running', 'note' => '51 runs'],
    ['value' => 408.7, 'decimals' => 1, 'unit' => 'km', 'label' => 'Cycling', 'note' => '13 rides, road and mountain bike'],
    ['value' => 7781, 'unit' => 'm', 'label' => 'Elevation gain', 'note' => 'All sports together'],
    ['value' => 126, 'unit' => 'h', 'label' => 'Moving time', 'note' => 'All sports together'],
];

/**
 * Best activities as separate tiles. Empty = hidden.
 * The best ones are currently shown in the gallery below instead.
 * Example: ['sport' => 'Run', 'title' => 'City Marathon', 'date' => 'October 2026',
 *           'stats' => ['Distance' => '42.2 km', 'Time' => '3:59:12', 'Pace' => '5:40 /km']]
 */
const STRAVA_HIGHLIGHTS = [];

/**
 * Gallery. Without a photo, 'stat' is shown large in the frame instead.
 * 'route' is the shape of the GPS track as an SVG path (viewBox 0 0 200 200),
 * north up, without map and coordinates. The first and last 600 m (at least 5 %)
 * are cut off, like a Strava privacy zone.
 * 'facts' are short numbers shown under the title.
 */
const GALLERY_ITEMS = [
    ['category' => 'Running', 'title' => 'My first marathon', 'date' => 'July 2026', 'route' => 'M16 129L17 125L18 121L20 117L23 113L24 109L26 106L29 103L30 99L30 94L34 91L38 90L41 87L44 83L47 80L50 77L54 75L57 73L56 70L59 69L64 69L68 69L72 69L77 68L81 69L83 73L87 73L92 74L96 74L100 75L105 76L108 77L113 77L117 76L121 76L124 76L129 76L133 77L137 80L140 77L144 77L146 76L150 76L153 78L157 78L161 78L164 79L168 80L171 83L173 87L175 91L177 95L179 99L183 100L184 102L181 99L177 97L176 93L174 90L173 87L172 84L169 81L165 79L161 78L157 79L153 78L150 76L146 76L144 77L140 77L137 80L134 77L130 76L125 76L121 77L119 76L114 77L110 77L107 76L103 76L98 75L94 74L90 73L85 73L81 72L81 69L77 68L73 69L69 69L64 68L60 69L56 69L57 73L53 75L50 76L47 80L44 83L41 86L38 89L34 91L30 94L30 98L30 102L27 105L24 109L23 114L19 117L18 122L17 127L16 132',
        'stat' => ['value' => '42.2', 'unit' => 'km'],
        'facts' => ['4:46:34 moving time', '6:47 /km', '458 m climbing'],
        'description' => 'The longest run so far. Every kilometre after 30 was new territory.', 'alt' => '', 'image' => null],
    ['category' => 'Running', 'title' => 'Fastest 5 km', 'date' => 'October 2026', 'route' => 'M16 149L20 150L24 150L27 151L31 150L35 150L39 148L42 147L45 145L48 143L52 141L55 138L58 136L60 133L63 130L65 127L67 124L69 120L71 117L72 114L74 110L75 107L77 104L79 100L81 98L83 94L86 92L88 89L91 86L93 84L96 82L99 80L101 78L104 76L106 74L109 73L112 72L115 71L119 70L122 69L125 68L128 67L131 66L135 65L138 64L141 63L144 62L147 61L151 60L154 60L157 60L161 60L164 60L167 60L171 61L174 62L177 64L179 65L181 65L182 63L183 60L184 57L183 55L180 53L176 52L173 51L169 50L166 50L162 49L158 49L155 50L152 50L148 50L145 51L142 52L139 52L135 53L132 55L129 56L126 57L123 58L120 59L116 61L113 62L110 63L107 65L104 66L101 68L98 69L96 70L93 71L90 73L88 76L85 78L83 81L80 83L78 86L76 88L72 93L69 95L67 98L64 101L62 103L60 106L58 109L56 112L55 115L53 118L51 121L49 124',
        'stat' => ['value' => '21:58', 'unit' => ''],
        'facts' => ['5.01 km', '4:23 /km', 'Personal record'],
        'description' => 'A new personal best over 5 km, after a very fast first kilometre and a half.', 'alt' => '', 'image' => null],
    ['category' => 'Running', 'title' => '30 km long run', 'date' => 'May 2026', 'route' => 'M103 101L116 86L134 77L153 71L172 70L184 67L167 61L149 63L132 69L115 77L101 91L88 105L77 121L59 127L44 119L47 122L68 126L83 114L94 98L107 84L124 73L142 65L161 61L181 64L177 71L158 69L140 75L122 81L108 94L97 111L86 128L70 138L51 138L34 127L22 110L29 122L44 134L63 140L81 132L94 117L104 100L117 85L135 77L154 71L174 70L183 65L165 61L145 64L126 72L110 81L97 95L85 110L72 124L53 125L40 116L57 126L75 123L87 107L99 92L112 79L129 70L146 64L165 61L183 65L176 71L158 69L140 75L133 76L127 79L110 91L99 107L90 123L76 135L58 139L41 132L28 119L23 112L36 128L53 138L73 137L89 125L99 109L107 95L120 83L137 76L156 70L176 71L184 65L166 60L148 63L130 70L112 78L99 92L87 107L76 122L58 126L42 118L30 111L22 96L16 84L18 90L27 107L39 116L56 126L74 124L86 109L98 94L95 97L85 111L72 125',
        'stat' => ['value' => '30', 'unit' => 'km'],
        'facts' => ['2:56:58 moving time', '5:54 /km'],
        'description' => 'The longest training run before the marathon.', 'alt' => '', 'image' => null],
    ['category' => 'Cycling', 'title' => 'The first 100 km', 'date' => 'September 2026', 'route' => 'M164 25L167 28L169 32L167 39L162 44L157 46L155 51L153 55L155 60L157 67L157 73L155 78L154 82L150 90L148 96L138 123L139 129L140 133L142 137L142 141L145 144L149 148L153 154L154 162L154 167L156 169L158 175L161 179L162 184L158 184L158 183L160 183L159 178L158 175L156 172L154 168L154 162L154 157L151 149L145 145L143 142L142 138L140 135L139 134L137 134L139 130L138 128L138 123L137 116L138 110L140 107L142 103L145 99L149 93L150 86L154 83L157 72L157 67L155 60L153 55L149 52L145 48L142 45L138 42L134 38L129 36L126 30L125 23L124 19L112 20L105 17L97 17L94 16L90 17L86 19L83 18L81 17L77 16L69 18L67 20L62 21L59 23L53 30L47 33L43 32L37 31L33 31L31 32L32 32L35 32L38 31L45 33L50 31L56 28L58 26L60 23L65 22L69 20L72 17L77 17L81 17L82 18L84 18L85 19L89 17L94 16L99 17L104 17L111 19L119 20',
        'stat' => ['value' => '101', 'unit' => 'km'],
        'facts' => ['4:00:30 moving time', '25.2 km/h', '1,134 m climbing'],
        'description' => 'A relaxed Sunday ride on the road bike that grew into my first 100 kilometres.', 'alt' => '', 'image' => null],
    ['category' => 'Cycling', 'title' => 'Top speed', 'date' => 'May 2026', 'route' => 'M63 184L67 184L67 181L70 180L69 176L72 177L72 174L73 173L75 172L79 170L72 169L74 167L71 165L73 165L75 163L75 157L73 152L73 150L73 146L73 143L67 139L71 138L72 135L73 133L75 131L71 127L70 127L73 119L77 124L82 132L84 139L83 138L86 131L84 127L87 135L92 141L101 144L102 144L102 145L103 147L102 156L103 166L104 164L107 169L106 160L108 160L112 166L115 165L114 161L115 158L118 156L117 149L118 145L120 144L124 147L125 143L126 139L127 139L128 135L130 132L132 129L134 124L130 121L128 121L124 120L124 119L131 117L129 115L132 115L130 112L137 115L135 111L130 108L123 109L121 104L122 101L126 102L129 100L129 99L127 96L126 93L126 91L122 87L119 80L115 79L113 77L109 71L108 67L116 68L121 67L119 63L118 62L120 60L113 56L109 53L110 49L100 47L95 46L87 45L83 43L84 41L79 39L76 36L79 29L80 21L73 16',
        'stat' => ['value' => '76', 'unit' => 'km/h'],
        'facts' => ['12.4 km ride'],
        'description' => 'The highest speed recorded on the bike so far.', 'alt' => '', 'image' => null],
    ['category' => 'Cycling', 'title' => 'Mountain bike climb', 'date' => 'May 2026', 'route' => 'M107 29L105 33L106 37L108 39L111 40L113 42L113 46L119 48L125 52L127 55L131 58L137 61L140 64L145 67L150 71L155 74L156 76L157 80L161 81L158 83L156 87L153 86L150 84L151 88L151 92L154 96L158 94L161 93L166 92L170 93L174 95L176 99L180 101L184 104L183 107L183 112L184 117L183 120L180 121L177 123L174 125L172 128L172 125L173 121L175 118L175 114L171 112L167 112L166 113L167 117L165 121L163 125L161 129L159 132L159 137L161 140L162 145L160 149L160 154L158 158L159 155L158 151L157 147L154 143L151 140L148 138L145 134L144 136L146 140L146 143L147 148L147 151L147 156L148 160L149 164L148 166L147 162L145 161L144 165L143 169L142 170L140 168L137 168L135 169L134 170L133 171L130 169L129 169L127 166L116 165L108 159L101 161L85 159L75 157L72 153L67 148L49 138L41 137L27 133L16 125L22 112L30 99L29 83L33 79L42 75L50 69L58 66L67 53L81 40L106 31',
        'stat' => ['value' => '714', 'unit' => 'm'],
        'facts' => ['14.4 km', '1:43:53 moving time'],
        'description' => 'More than 700 metres of climbing in just 14 kilometres.', 'alt' => '', 'image' => null],
    ['category' => 'Triathlon', 'title' => 'Triathlon morning', 'date' => 'September 2026', 'route' => 'M46 64L52 68L56 70L61 74L69 80L79 82L81 85L87 91L93 96L100 103L106 109L108 115L111 120L113 124L120 130L124 135L127 139L133 145L142 151L148 156L155 159L160 161L166 166L172 170L177 174L184 179L183 178L177 172L168 165L164 161L157 155L151 150L143 143L138 138L130 130L124 124L119 119L111 112L108 108L103 103L96 96L90 91L86 87L83 83L75 76L69 70L64 67L62 63L56 58L50 52L44 46L35 37L28 30L22 25L18 22L16 21L17 22L19 28L17 32L18 36L23 41L27 46L34 53L41 59L46 64L51 68L56 69L66 78L74 81L79 82L82 85L86 90L93 95L99 101L101 104L106 110L108 116L112 123L116 128L123 134L131 144L138 148L146 154L154 159L161 161L167 166L174 172L182 179L184 179L181 175L174 169L164 162L156 154L148 147L140 140L134 134L127 127L119 119L111 111L105 105L101 101L95 95L86 87L78 79L72 73L68 69L63 67L62 65L58 59L50 51',
        'stat' => ['value' => '3', 'unit' => 'sports'],
        'facts' => ['Swim 250 m', 'Bike 18.5 km', 'Run 4.8 km'],
        'description' => 'Swim, bike and run in one morning, one straight after the other.', 'alt' => '', 'image' => null],
    ['category' => 'Triathlon', 'title' => 'In the pool', 'date' => 'September 2026', 'stat' => ['value' => '1.5', 'unit' => 'km'],
        'facts' => ['36:54 moving time'],
        'description' => 'The longest swim session so far.', 'alt' => '', 'image' => null],
];
