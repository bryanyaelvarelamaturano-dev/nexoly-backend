<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Core Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración principal de Cloudinary.
    | Usamos variables separadas para evitar errores de null en producción.
    |
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),

    'api_key' => env('CLOUDINARY_API_KEY'),

    'api_secret' => env('CLOUDINARY_API_SECRET'),

    'secure' => true,

    /*
    |--------------------------------------------------------------------------
    | Optional Configuration
    |--------------------------------------------------------------------------
    */

    // Webhook (opcional)
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    // Upload preset (solo si usas presets desde el dashboard)
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    // Opcionales (no los estás usando, pero no estorban)
    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE'),
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION'),
];
