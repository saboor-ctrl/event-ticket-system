<?php

function sts_get_screenings() {
    // Try to get screenings from database
    $saved_screenings = get_option('sts_screenings');
    
    if ($saved_screenings && is_array($saved_screenings)) {
        return $saved_screenings;
    }
    
    // Fallback to hardcoded default screenings
    return [
        'wd' => [
            'name' => 'Where Darkness Dwells',
            'date' => 'Apr 10, 7PM',
            'location' => 'Hall A'
        ],
        'kc' => [
            'name' => 'Killing of Connor',
            'date' => 'Apr 11, 6PM',
            'location' => 'Hall B'
        ],
        'sf1' => [
            'name' => 'Short Film Showcase 1',
            'date' => 'Apr 12, 5PM',
            'location' => 'Hall C'
        ],
        'sf2' => [
            'name' => 'Short Film Showcase 2',
            'date' => 'Apr 13, 4PM',
            'location' => 'Hall D'
        ],
    ];
}

