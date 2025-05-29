<?php
date_default_timezone_set('America/Mexico_City'); // O la zona horaria correcta para ti, ej: 'America/Monterrey'

if (!function_exists('formatTimeAgo')) {
    function formatTimeAgo($datetime, $full = false) {
        
        // Si tu columna 'created_at' de MySQL ya está en la zona horaria local, entonces:
        $ago = new DateTime($datetime); // Simplemente crea el objeto DateTime

        $now = new DateTime(); // 'now' siempre estará en la zona horaria de PHP
        $diff = $now->diff($ago);

        // ... (el resto de la función formatTimeAgo sigue igual)
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'año',
            'm' => 'mes',
            'w' => 'semana',
            'd' => 'día',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        $time_ago = $string ? implode(', ', $string) . '' : 'justo ahora';

        if ($time_ago === 'justo ahora') {
            return $time_ago;
        } else {
            // Evitar "Hace Hace" si la función ya devuelve "Hace ..."
            if (strpos(strtolower($time_ago), 'hace') === 0) {
                 return $time_ago;
            }
            return 'Hace ' . $time_ago;
        }
    }
}

?>