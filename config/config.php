<?php
/*
 * Main settings. Edit the database and mail sections to match your machine.
 * With XAMPP the defaults below work as-is (user "root", empty password).
 */
return [
    'app' => [
        'name'      => 'CineMax',
        'timezone'  => 'Africa/Tripoli',
        'currency'  => 'LYD',
        // Minutes an unpaid booking keeps its seats before they are released.
        'hold_minutes' => 10,
        // Bookings can be cancelled up to this many hours before the screening.
        'cancel_hours' => 2,
        // VIP seats cost this many times the normal ticket price.
        'vip_multiplier' => 1.5,
        'debug' => true,
    ],

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'cinema_db',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // Email (PHPMailer). While 'enabled' is false, emails are written to
    // storage/logs/mail.log instead of being sent, so you can still see them.
    'mail' => [
        'enabled'    => false,
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'your-address@gmail.com',
        'password'   => 'your-app-password',
        'from_email' => 'your-address@gmail.com',
        'from_name'  => 'CineMax Cinema',
    ],
];
