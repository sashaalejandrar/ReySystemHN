<?php
// Forzar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Crear un objeto DateTime para mayor precisión
$datetime = new DateTime();

echo "Zona Horaria: " . date_default_timezone_get() . "<br>";
echo "Hora Actual de PHP (H:i:s): " . $datetime->format('H:i:s') . "<br>";
echo "Fecha Actual de PHP (Y-m-d): " . $datetime->format('Y-m-d') . "<br>";
?>