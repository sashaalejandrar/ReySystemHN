#!/usr/bin/env python3
"""
Script para reemplazar el menú lateral duplicado con include en múltiples archivos PHP
"""
import re
import os

# Lista de archivos a procesar
archivos = [
    'apertura_caja.php',
    'cierre_caja.php',
    'arqueo_caja.php',
    'caja_al_dia.php',
    'reporte_ventas.php',
    'reportes_caja.php',
    'lista_proveedores.php',
    'crear_usuarios.php',
    'crear_proveedor.php',
    'creacion_de_producto.php',
    'configuracion.php',
    'consulta_precios.php',
    'consulta_edicion_precios.php',
    'chequeo_stock.php',
    'clientes.php',
    'lista_deudas.php',
    
]

# Directorio base
base_dir = '/opt/lampp/htdocs/ReySystemDemo'

# Patrón para encontrar el aside completo
patron_aside = re.compile(
    r'<!-- SideNavBar -->.*?</aside>',
    re.DOTALL
)

# Reemplazo
reemplazo = '<!-- SideNavBar -->\n<?php include \'menu_lateral.php\'; ?>'

archivos_procesados = []
archivos_no_encontrados = []
archivos_sin_menu = []

for archivo in archivos:
    ruta_completa = os.path.join(base_dir, archivo)
    
    if not os.path.exists(ruta_completa):
        archivos_no_encontrados.append(archivo)
        continue
    
    try:
        with open(ruta_completa, 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Verificar si tiene el aside
        if '<aside' not in contenido:
            archivos_sin_menu.append(archivo)
            continue
        
        # Reemplazar
        nuevo_contenido = patron_aside.sub(reemplazo, contenido)
        
        # Guardar
        with open(ruta_completa, 'w', encoding='utf-8') as f:
            f.write(nuevo_contenido)
        
        archivos_procesados.append(archivo)
        print(f"✓ {archivo}")
        
    except Exception as e:
        print(f"✗ Error en {archivo}: {e}")

print(f"\n=== RESUMEN ===")
print(f"Procesados: {len(archivos_procesados)}")
print(f"Sin menú lateral: {len(archivos_sin_menu)}")
print(f"No encontrados: {len(archivos_no_encontrados)}")

if archivos_sin_menu:
    print(f"\nArchivos sin menú lateral:")
    for a in archivos_sin_menu:
        print(f"  - {a}")

if archivos_no_encontrados:
    print(f"\nArchivos no encontrados:")
    for a in archivos_no_encontrados:
        print(f"  - {a}")
