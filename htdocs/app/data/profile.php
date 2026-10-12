<?php
/**
 * Central profile configuration: name, texts, social links, navigation.
 *
 * RULES FOR THIS FILE
 * - Only real, confirmed information. No invented facts, results or accounts.
 * - No age or date of birth, anywhere.
 */
declare(strict_types=1);

const PROFILE = [
    'name' => 'Elias Ems',
    // Shown next to the name at the top of the page.
    'role' => 'Professional Vibecoder',
    'site_title' => 'Elias — Beyond the Profile',
    // Three verbs, set large in the hero.
    'headline' => ['Building.', 'Moving.', 'Exploring.'],
    'intro' => "I run long distances, build my own software and learn how things work by making them myself. This is the part that doesn't fit in a bio.",
    'description' => 'Personal site of Elias Ems: endurance sport, cybersecurity, programming and the projects in between.',

    // Optional round portrait in the hero. null = no picture.
    // Example: ['src' => '/assets/images/hero.jpg', 'alt' => 'Elias on a morning run', 'width' => 800, 'height' => 800]
    'hero_image' => null,

    'about' => [
        'title' => 'A little about what drives me.',
        // The first paragraph lights up word by word while scrolling.
        'paragraphs' => [
            "I like goals that are slightly too big. A marathon, which I finished in 2026. Triathlon training with swimming, the road bike and transition runs straight off the bike. An AI assistant I write myself, with local models and its own tools. Ski instructor training in Salzburg, because I want to pass on what I love about skiing. The interesting part is everything between the idea and the day it works.",
            'After four years at BG/BRG Judenburg I started at HTL Villach in 2025. My focus there is networks and cybersecurity, and in ethical hacking labs I have built things like reverse shells to understand how attacks work. In summer 2026 I did my first internship at Windberger IT Solutions. Outside of school I learn by doing: I set things up on Linux, write code in Python and PHP, break it, and fix it again.',
            'Sport and tech feel like the same habit to me. Both reward patience, both show honest numbers, and both get better when you keep showing up.',
        ],
        'facts' => [
            ['label' => 'School', 'value' => 'HTL in Villach, Austria'],
            ['label' => 'Focus', 'value' => 'Networks and cybersecurity'],
            ['label' => 'On the snow', 'value' => 'Trained ski instructor (SBSSV)'],
            ['label' => 'Building', 'value' => 'NEXUS, a personal AI assistant'],
        ],
        'interests' => [
            'Running and marathon',
            'Triathlon and road cycling',
            'Strength training and calisthenics',
            'Volleyball and beach volleyball',
            'Skiing, snowboarding and the mountains',
            'Cybersecurity and networks',
            'Programming and web development',
            'AI and personal assistants',
            'Vibecoding',
            'Linux and hardware',
            'Photo and video editing',
            'Entrepreneurship',
            'Travel',
        ],
    ],
];

/**
 * Social links. Replace the empty strings with your real links.
 * While 'url' is empty the page shows "Not linked yet".
 * For e-mail just the address is enough.
 */
const SOCIAL_LINKS = [
    ['platform' => 'instagram', 'label' => 'Instagram', 'note' => 'Training, trips and everyday life', 'url' => 'https://www.instagram.com/eliasems2010/', 'handle' => '@eliasems2010'],
    ['platform' => 'github', 'label' => 'GitHub', 'note' => 'Code and experiments', 'url' => 'https://github.com/emselias2010', 'handle' => 'emselias2010'],
    ['platform' => 'strava', 'label' => 'Strava', 'note' => 'Runs, rides and swims', 'url' => 'https://www.strava.com/athletes/190203526', 'handle' => ''],
    ['platform' => 'email', 'label' => 'E-mail', 'note' => 'The direct way', 'url' => 'elias.ems@hotmail.com', 'handle' => 'elias.ems@hotmail.com'],
];

const NAV_ITEMS = [
    ['href' => '/#about', 'label' => 'About'],
    ['href' => '/#journey', 'label' => 'Journey'],
    ['href' => '/#endurance', 'label' => 'Endurance'],
    ['href' => '/#projects', 'label' => 'Projects'],
    ['href' => '/#ask', 'label' => 'Ask Elias'],
    ['href' => '/#contact', 'label' => 'Contact'],
    ['href' => '/#guestbook', 'label' => 'Guestbook'],
];

/** Turns a social link into an href, or null when it is not set (or not https). */
function social_href(array $link): ?string
{
    $value = trim((string) $link['url']);
    if ($value === '') {
        return null;
    }
    if ($link['platform'] === 'email') {
        $address = preg_replace('/^mailto:/i', '', $value);
        return filter_var($address, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $address : null;
    }
    return preg_match('~^https://\S+$~i', $value) ? $value : null;
}
