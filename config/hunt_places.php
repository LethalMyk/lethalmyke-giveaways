<?php

return [
    'review_stale_days' => (int) env('HUNT_PLACES_REVIEW_STALE_DAYS', 120),
    'per_page' => (int) env('HUNT_PLACES_PER_PAGE', 12),
];