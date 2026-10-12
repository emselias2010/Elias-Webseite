<?php
/** @var string $platform  Logo of a social platform as inline SVG (decorative). */
?>
<svg class="contact-tile__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
<?php switch ($platform):
    case 'instagram': ?>
    <rect x="3" y="3" width="18" height="18" rx="5.2" fill="none" stroke="currentColor" stroke-width="1.9"/>
    <circle cx="12" cy="12" r="4.1" fill="none" stroke="currentColor" stroke-width="1.9"/>
    <circle cx="17.3" cy="6.7" r="1.25" fill="currentColor"/>
<?php break;
    case 'github': ?>
    <path fill="currentColor" d="M12 .5C5.65.5.5 5.65.5 12.02c0 5.08 3.29 9.39 7.86 10.91.58.1.79-.25.79-.56v-1.97c-3.2.7-3.87-1.54-3.87-1.54-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.19 1.76 1.19 1.03 1.76 2.69 1.25 3.34.96.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a11 11 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.69 5.39-5.26 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.53 11.53 0 0 0 23.5 12.02C23.5 5.65 18.35.5 12 .5Z"/>
<?php break;
    case 'strava': ?>
    <path fill="currentColor" d="M15.39 17.94 13.3 13.8h-3.07l5.16 10.2 5.15-10.2h-3.07ZM10.2 7.6l2.84 5.59h4.17L10.2 0 3.2 13.19h4.17Z"/>
<?php break;
    case 'email': ?>
    <rect x="2.5" y="4.5" width="19" height="15" rx="3" fill="none" stroke="currentColor" stroke-width="1.9"/>
    <path d="m3.5 6.5 8.5 6.5 8.5-6.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
<?php break;
    default: ?>
    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.9"/>
<?php endswitch; ?>
</svg>
