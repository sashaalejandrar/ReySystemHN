#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Método 2: Async Requests + BeautifulSoup + Mistral AI
Usa requests asíncronos para mayor velocidad
"""

import sys
import json
import asyncio
import aiohttp
from bs4 import BeautifulSoup
import re
from urllib.parse import urljoin, quote

MISTRAL_API_KEY = "w9euutg7ruv8K2F8oi2iVEnHDCRI1s2X"
MISTRAL_MODEL = "mistral-large-latest"

async def fetch_url(session, url):
    """Obtiene el contenido de una URL de forma asíncrona"""
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'es-HN,es;q=0.9,en;q=0.8'
    }
    
    try:
        async with session.get(url, headers=headers, timeout=15) as response:
            if response.status == 200:
                return await response.text()
    except Exception as e:
        return None
    
    return None

async def extract_prices_from_html(html, url):
    """Extrae precios del HTML usando BeautifulSoup"""
    if not html:
        return []
    
    soup = BeautifulSoup(html, 'html.parser')
    precios_encontrados = []
    
    # Método 1: Buscar en elementos con clases de precio
    price_classes = ['price', 'selling-price', 'product-price', 'precio', 'vtex-product-price']
    for class_name in price_classes:
        elements = soup.find_all(class_=re.compile(class_name, re.I))
        for elem in elements:
            texto = elem.get_text(strip=True)
            match = re.search(r'L\.?\s*(\d+\.?\d*)', texto)
            if match:
                precio = float(match.group(1))
                if 1 <= precio <= 10000:
                    precios_encontrados.append(precio)
    
    # Método 2: Buscar en atributos data-price
    data_price_elements = soup.find_all(attrs={'data-price': True})
    for elem in data_price_elements:
        try:
            precio = float(elem['data-price'])
            if 1 <= precio <= 10000:
                precios_encontrados.append(precio)
        except:
            pass
    
    # Método 3: Buscar en meta tags
    meta_price = soup.find('meta', property=re.compile('price', re.I))
    if meta_price and meta_price.get('content'):
        try:
            precio = float(meta_price['content'])
            if 1 <= precio <= 10000:
                precios_encontrados.append(precio)
        except:
            pass
    
    # Método 4: Buscar en JSON-LD
    json_ld_scripts = soup.find_all('script', type='application/ld+json')
    for script in json_ld_scripts:
        try:
            data = json.loads(script.string)
            if isinstance(data, dict):
                if 'offers' in data:
                    offers = data['offers']
                    if isinstance(offers, dict) and 'price' in offers:
                        precio = float(offers['price'])
                        if 1 <= precio <= 10000:
                            precios_encontrados.append(precio)
        except:
            pass
    
    # Buscar URL del producto
    product_url = url
    product_links = soup.find_all('a', href=True)
    for link in product_links[:10]:  # Solo primeros 10 links
        href = link['href']
        if '/p' in href or 'product' in href or '/producto' in href:
            product_url = urljoin(url, href)
            break
    
    if precios_encontrados:
        return [{
            'precio': precios_encontrados[0],
            'url': product_url
        }]
    
    return []

async def ask_mistral_for_price(html_snippet, termino):
    """Pregunta a Mistral AI sobre el precio en el HTML"""
    prompt = f"""Analiza este HTML y encuentra el precio del producto: {termino}

HTML:
{html_snippet[:1500]}

Busca el precio en Lempiras (L.xx.xx). Devuelve JSON:
{{"encontrado": true, "precio": 45.99}}

Si no encuentras: {{"encontrado": false}}"""

    try:
        async with aiohttp.ClientSession() as session:
            async with session.post(
                'https://api.mistral.ai/v1/chat/completions',
                headers={
                    'Content-Type': 'application/json',
                    'Authorization': f'Bearer {MISTRAL_API_KEY}'
                },
                json={
                    'model': MISTRAL_MODEL,
                    'messages': [{'role': 'user', 'content': prompt}],
                    'temperature': 0.1,
                    'max_tokens': 150,
                    'response_format': {'type': 'json_object'}
                },
                timeout=30
            ) as response:
                if response.status == 200:
                    result = await response.json()
                    content = result['choices'][0]['message']['content']
                    data = json.loads(content)
                    
                    if data.get('encontrado') and data.get('precio'):
                        return float(data['precio'])
    except:
        pass
    
    return None

async def scrape_site(session, sitio, termino):
    """Scraping asíncrono de un sitio"""
    html = await fetch_url(session, sitio['url'])
    
    if html:
        # Intentar extraer precios con BeautifulSoup
        resultados = await extract_prices_from_html(html, sitio['url'])
        
        if resultados:
            return {
                'fuente': sitio['nombre'],
                'precio': resultados[0]['precio'],
                'url': resultados[0]['url'],
                'metodo': 'python_async_mistral',
                'confianza': 'alta'
            }
        
        # Si no encuentra con BS, intentar con Mistral
        precio_mistral = await ask_mistral_for_price(html, termino)
        if precio_mistral:
            return {
                'fuente': sitio['nombre'],
                'precio': precio_mistral,
                'url': sitio['url'],
                'metodo': 'python_async_mistral',
                'confianza': 'media'
            }
    
    return None

async def buscar_precio_async(termino, codigo):
    """Función principal de búsqueda asíncrona"""
    # Usar el término específico para la búsqueda
    termino_busqueda = termino if termino else codigo
    es_codigo = bool(codigo and not termino)  # True si estamos buscando por código
    
    sitios = []
    
    # La Colonia - URL diferente según si es código o nombre
    if es_codigo:
        sitios.append({
            'nombre': 'La Colonia',
            'url': f'https://www.lacolonia.com/{codigo}?map=ft'
        })
    else:
        nombre_url = termino_busqueda.lower().replace(' ', '-')
        sitios.append({
            'nombre': 'La Colonia',
            'url': f'https://www.lacolonia.com/{nombre_url}/p'
        })
    
    # Walmart - URL diferente según si es código o nombre
    if es_codigo:
        sitios.append({
            'nombre': 'Walmart Honduras',
            'url': f'https://www.walmart.com.hn/{codigo}?_q={codigo}&map=ft'
        })
    else:
        nombre_url = termino_busqueda.lower().replace(' ', '-')
        sitios.append({
            'nombre': 'Walmart Honduras',
            'url': f'https://www.walmart.com.hn/{nombre_url}/p'
        })
    
    # Paiz - URL diferente según si es código o nombre
    if es_codigo:
        sitios.append({
            'nombre': 'Paiz',
            'url': f'https://www.paiz.com.hn/{codigo}?map=ft'
        })
    else:
        nombre_url = termino_busqueda.lower().replace(' ', '-')
        sitios.append({
            'nombre': 'Paiz',
            'url': f'https://www.paiz.com.hn/{nombre_url}/p'
        })
    
    async with aiohttp.ClientSession() as session:
        tasks = [scrape_site(session, sitio, termino_busqueda) for sitio in sitios]
        resultados = await asyncio.gather(*tasks)
        
        # Filtrar None
        return [r for r in resultados if r is not None]

def buscar_precio(termino, codigo):
    """Wrapper síncrono para la función asíncrona"""
    return asyncio.run(buscar_precio_async(termino, codigo))

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Faltan argumentos'}))
        sys.exit(1)
    
    termino = sys.argv[1]
    codigo = sys.argv[2] if len(sys.argv) > 2 else ''
    
    resultados = buscar_precio(termino, codigo)
    print(json.dumps(resultados, ensure_ascii=False))
