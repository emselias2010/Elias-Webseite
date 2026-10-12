<?php
/**
 * Projects shown in the portfolio and on the detail pages (/projects/<slug>).
 *
 * To add a project: copy one entry, change the slug, fill in what is true.
 * - 'status' is optional (Concept, In Development, Prototype, Finished, Live, Discontinued). Only set it when it is correct.
 * - 'implemented' = already works, 'planned' = ideas, 'scope' = areas where the split is still open.
 * - Leave links empty until they exist. No invented repositories or demos.
 * - 'cover' / 'screenshots': ['src' => '/assets/images/projects/file.jpg', 'alt' => '...', 'width' => 1600, 'height' => 900]
 * - 'published_at': 'YYYY-MM-DD', only when the project really was published.
 * - Never put credentials, internal IP addresses or private config in here.
 */
declare(strict_types=1);

const PROJECTS = [
    [
        'slug' => 'domain-monitor',
        'title' => 'Domain Monitor',
        'tagline' => 'Spotting look-alike domains before they fool anyone',
        'summary' => 'A web app that scans for look-alike versions of a domain and scores how dangerous each one is as a possible phishing site. A PHP frontend manages users and domains, a Python backend runs the scans.',
        'description' => [
            'Domain Monitor takes a domain and generates look-alike versions of it: small changes like swapped or missing letters, extra characters, a hyphen or a different ending, the kind of names someone could register to imitate the original.',
            'For each one it checks whether it is really registered and online, whether it shows a login form or the brand name, and where it redirects. From that it works out a danger score for how likely the domain is to be a phishing site. A PHP frontend manages accounts, the watched domains and settings; a Python backend runs the scans, with a guest scan, scheduled automatic scans and email alerts.',
            'It is a defensive tool: the point is to spot a fake domain early, before it is used to trick someone.',
        ],
        'technologies' => ['PHP', 'Python', 'MySQL', 'DNS'],
        'status' => 'Finished',
        'implemented' => ['Finding look-alike domains (typosquatting)', 'A danger score for each domain', 'Guest scan without an account', 'Scheduled automatic scans', 'Email alerts'],
        'github_url' => 'https://github.com/emselias2010/Domain-Monitor',
        'live_url' => '',
    ],
    [
        'slug' => 'scoreboard',
        'title' => 'Scoreboard',
        'tagline' => 'A live scoreboard for volleyball',
        'summary' => 'A digital scoreboard for volleyball: a public display shows the score live, and a password-protected control panel handles points, sets, timeouts and the look of the board.',
        'description' => [
            'Scoreboard is a digital scoreboard for volleyball games. One page is the public display that shows the current score in real time; a separate, password-protected control panel is what you use to run the match.',
            'From the panel you add points, win sets, call timeouts and switch sides, set team names, colours and logos, give the match a title, and undo the last action. There is a countdown for timeouts and breaks, and the display can be installed on a device as an app.',
            'I built it with PHP and MySQL. The display keeps asking the server for the score, and changes from the panel are saved through a protected endpoint.',
        ],
        'technologies' => ['PHP', 'MySQL', 'JavaScript', 'PWA'],
        'status' => 'Finished',
        'implemented' => ['Live score display', 'Control panel for points, sets and timeouts', 'Team names, colours and logos', 'Undo the last action', 'Installable as an app (PWA)'],
        'github_url' => 'https://github.com/emselias2010/Scoreboard',
        'live_url' => '',
    ],
    [
        'slug' => 'reverse-shell-lab',
        'title' => 'Reverse Shell Lab',
        'tagline' => 'Understanding an attack in order to defend against it',
        'summary' => 'An ethical-hacking exercise in my own isolated lab: an HTTP reverse shell that also got past a virus scanner. Built only to learn how such attacks work and how to detect them.',
        'description' => [
            'Cybersecurity makes the most sense to me when I understand both sides. For this exercise I built an HTTP reverse shell in a closed test environment: a controlled lab with my own machines, separated from any real network.',
            'As part of it I also looked at how such a tool can slip past a virus scanner, so I could see for myself which techniques detection has to catch.',
            'This was done only to learn, on systems I am allowed to test. Nothing was aimed at anyone else, and nothing illegal was done or intended. The point is defensive: knowing how an attack works is what lets you recognise and stop it.',
        ],
        'technologies' => ['Ethical hacking', 'Networking', 'Lab environment'],
        'status' => 'Finished',
        'implemented' => ['HTTP reverse shell in an isolated lab', 'Study of antivirus detection', 'Defensive analysis of how the attack works'],
        'github_url' => '',
        'live_url' => '',
    ],
    [
        'slug' => 'nexus',
        'title' => 'NEXUS',
        'tagline' => 'A personal AI assistant',
        'summary' => 'My own assistant instead of another chat clone: one interface that connects a language model with tasks, a calendar and tools I write myself.',
        'description' => [
            "NEXUS started with a simple question: what would an assistant look like if I built one for myself instead of using someone else's? The goal is one place where a language model, my tasks and my calendar work together.",
            'I\'m building it as a personal development project. It has its own chat interface and backend, talks to local models through Ollama, and is designed around small tools the assistant can call.',
            'It is a project in progress. The list below shows what the project covers and, separately, which ideas are still ahead.',
        ],
        'technologies' => ['Ollama', 'Local AI models', 'Backend API', 'Database', 'Web interface'],
        'status' => 'In Development',
        // TODO (Elias): move the points that already work into 'implemented'.
        'scope' => [
            'Own chat interface',
            'Backend architecture',
            'Local AI models through Ollama',
            'Connection to a language model',
            'Database for selected information',
            'Task management',
            'Calendar functions',
            'Modular tool and executor architecture',
            'Mobile interface',
        ],
        'implemented' => [],
        'planned' => ['Hardware extensions', 'Integration with the MagicMirror'],
        'github_url' => '',
        'live_url' => '',
    ],
    [
        'slug' => 'magicmirror',
        'title' => 'MagicMirror',
        'tagline' => 'A mirror that shows the day ahead',
        'summary' => 'A Raspberry Pi running MagicMirror², with modules for calendar, tasks and training stats.',
        'description' => [
            'The MagicMirror is where hardware meets software for me: a Raspberry Pi running MagicMirror², with modules I configure and adapt myself.',
            'The long-term idea is an elegant, dark mirror with a wooden look that shows a few useful things at a glance.',
        ],
        'technologies' => ['Raspberry Pi', 'MagicMirror²'],
        'status' => 'In Development',
        // TODO (Elias): split 'scope' into implemented / planned.
        'scope' => [
            'Modules for calendar and tasks',
            'Sport and Garmin statistics',
            'Motivational quotes',
            'Customised interface',
            'Own background services and data integration',
        ],
        'planned' => ['Dark mirror in a wooden frame'],
        'github_url' => '',
        'live_url' => '',
    ],
    [
        'slug' => 'cybermind',
        'title' => 'CyberMind',
        'tagline' => 'A learning platform for IT security',
        'summary' => 'A platform where users work through IT-security courses, do exercises and quizzes, and track their points in a dashboard. It had security holes and was hacked, which taught me to take security seriously. I no longer work on it.',
        'description' => [
            'CyberMind is a platform for learning IT security. Users work through courses on topics like passwords, phishing, malware and social engineering, do interactive exercises and a quiz for each one, and see their points and progress in a dashboard.',
            'I built the whole thing with PHP and MySQL: accounts for single users, businesses and schools, invite codes, login with hashed passwords, roles, and PayPal subscriptions for the paid plans.',
            'It also taught me security the hard way. The site had vulnerabilities and was hacked. That showed me in practice why security has to be built in from the start instead of added later.',
            "I'm no longer working on CyberMind, but that lesson went straight into later projects, including this website, which I built to be secure from the ground up.",
        ],
        'technologies' => ['PHP', 'MySQL', 'JavaScript', 'PayPal API'],
        'status' => 'Discontinued',
        'scope' => [
            'Courses with theory, exercises and quizzes',
            'Points and progress dashboard',
            'Solo, business and school accounts with invite codes',
            'Login with hashed passwords and roles',
            'PayPal subscriptions',
        ],
        'github_url' => 'https://github.com/emselias2010/Cybermind',
        'live_url' => '',
    ],
];

function find_project(string $slug): ?array
{
    foreach (PROJECTS as $project) {
        if ($project['slug'] === $slug) {
            return $project;
        }
    }
    return null;
}

/** Only https links are rendered; empty or malformed values are ignored. */
function safe_external_url(?string $url): ?string
{
    $value = trim((string) $url);
    return preg_match('~^https://\S+$~i', $value) ? $value : null;
}
