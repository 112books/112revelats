<?php
// Copia aquest fitxer com a /home/112books/webforms/config.php i canvia els valors.
return [
    'recipient'        => 'hola@112books.eu',
    'sender'           => 'formulari@112books.eu',
    'sender_name'      => 'Formulari 112 Revelats',
    'allowed_origins'  => [
        'https://112revelats.112books.eu',
        'https://112revelats.org',
        'https://www.112revelats.org',
    ],
    'admin_user'       => 'form',
    'admin_password'   => 'CANVIA_AQUESTA_CONTRASENYA_LLARGA',
    'db_path'          => __DIR__ . '/data/forms.sqlite',
    'email_image'      => __DIR__ . '/assets/email-header.jpg',
    'submissions_path' => __DIR__ . '/submissions',
    'rate_per_hour'    => 30,
    'send_confirmation' => true,
];
