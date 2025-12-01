<div class="flex-1 overflow-y-auto" id="chatList">
<?php 
$usuarios_lista->data_seek(0);
while($usuario = $usuarios_lista->fetch_assoc()): 
    $query_ultimo = "SELECT * FROM mensajes_chat 
                     WHERE (Id_Emisor = $Usuario_Id AND Id_Receptor = {$usuario['Id']})
                        OR (Id_Emisor = {$usuario['Id']} AND Id_Receptor = $Usuario_Id)
                     ORDER BY Fecha_Mensaje DESC LIMIT 1";
    $resultado_ultimo = $conexion->query($query_ultimo);
    $ultimo_mensaje = $resultado_ultimo->fetch_assoc();
    
    $activo = ($chat_con == $usuario['Id']) ? 'bg-primary/10 dark:bg-[#232f48]' : 'hover:bg-slate-100 dark:hover:bg-slate-800';
?>
<a href="?chat=<?php echo $usuario['Id']; ?>" class="flex cursor-pointer justify-between gap-4 <?php echo $activo; ?> px-4 py-3 chat-item" data-nombre="<?php echo strtolower($usuario['Nombre'].' '.$usuario['Apellido']); ?>">
<div class="flex items-start gap-4">
<div class="relative">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-14" style="background-image: url('<?php echo $usuario['Perfil']; ?>');"></div>
<span class="absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full border-2 border-background-light dark:border-[#232f48] bg-green-500"></span>
</div>
<div class="flex flex-1 flex-col justify-center">
<p class="text-base font-semibold text-slate-900 dark:text-white"><?php echo $usuario['Nombre'].' '.$usuario['Apellido']; ?></p>
<p class="text-sm text-slate-500 dark:text-[#92a4c9] truncate">
<?php 
if ($ultimo_mensaje) {
    echo substr($ultimo_mensaje['Mensaje'], 0, 30) . (strlen($ultimo_mensaje['Mensaje']) > 30 ? '...' : '');
} else {
    echo 'Iniciar conversación';
}
?>
</p>
</div>
</div>
<div class="shrink-0 text-right">
<p class="text-xs text-slate-500 dark:text-[#92a4c9]">
<?php 
if ($ultimo_mensaje) {
    $fecha = strtotime($ultimo_mensaje['Fecha_Mensaje']);
    $hoy = strtotime('today');
    echo ($fecha >= $hoy) ? date('g:i A', $fecha) : date('d/m', $fecha);
}
?>
</p>
</div>
</a>
<?php endwhile; ?>
</div>
