<?php
session_start();
session_unset();
session_destroy();

$redirect_url = 'index.php'; // Repli par défaut

if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer_url = $_SERVER['HTTP_REFERER'];
    $parsed_url = parse_url($referer_url);

    $query_params = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    
    // Ajouter ou mettre à jour le paramètre showLogin
    $query_params['showLogin'] = 'true';

    $new_query_string = http_build_query($query_params);
    
    // Reconstruire l'URL
    $redirect_url = (isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '') .
                    (isset($parsed_url['host']) ? $parsed_url['host'] : '') .
                    (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '') .
                    (isset($parsed_url['path']) ? $parsed_url['path'] : '') .
                    (empty($new_query_string) ? '' : '?' . $new_query_string);
}

header('Location: ' . 'index.php');
exit();
