<?php
// ─── FIX 1: Revertir api_config.dart a producción ───
$apiPath = 'C:\\movilapps\\proyectos_personales\\devubi_system\\mobile\\lib\\core\\network\\api_config.dart';
$c = file_get_contents($apiPath);

// Cambiar activeEnvironment de development a production
$c = str_replace(
  "static const AppEnvironment activeEnvironment = AppEnvironment.development;",
  "static const AppEnvironment activeEnvironment = AppEnvironment.production;",
  $c,
  $count1
);

// Revertir baseUrl a _prodUrl en development (por si acaso alguien cambia la env)
$c = str_replace(
  "        // Para DISPOSITIVO F\u00cdSICO en la red local (IP del PC)\n        return _devPhysicalUrl;",
  "        // Para emulador: _devUrl\n        // Para dispositivo f\u00edsico: cambiar a _devPhysicalUrl\n        return _devUrl;",
  $c,
  $count1b
);

file_put_contents($apiPath, $c);
echo "[FIX 1] api_config.dart -> production mode: " . ($count1 > 0 ? "OK" : "SKIP (ya estaba)") . "\n";

// ─── FIX 2: Reducir timeout en dio_client.dart ───
$dioPath = 'C:\\movilapps\\proyectos_personales\\devubi_system\\mobile\\lib\\core\\network\\dio_client.dart';
$d = file_get_contents($dioPath);

// Reducir timeouts de 10s a 8s (balance entre cold start de Railway y respuesta r\u00e1pida)
$d = str_replace(
  "connectTimeout: const Duration(seconds: 10),",
  "connectTimeout: const Duration(seconds: 8),",
  $d,
  $count2
);
$d = str_replace(
  "receiveTimeout: const Duration(seconds: 10),",
  "receiveTimeout: const Duration(seconds: 8),",
  $d,
  $count2b
);

file_put_contents($dioPath, $d);
echo "[FIX 2] dio_client.dart -> timeouts 8s: " . ($count2 > 0 || $count2b > 0 ? "OK" : "SKIP") . "\n";

echo "\nDone.\n";
