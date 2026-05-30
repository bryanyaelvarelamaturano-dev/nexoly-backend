<?php
/**
 * Este archivo redirige el tráfico a la carpeta public
 * para que Render/Docker encuentren Laravel correctamente.
 */
require_once __DIR__ . '/public/index.php';