#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Método 1: Selenium + BeautifulSoup + Mistral AI
Usa Selenium para renderizar JavaScript y BeautifulSoup para parsear
"""

import sys
import json
import requests
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

MISTRAL_API_KEY = "w9euutg7ruv8K2F8oi2iVEnHDCRI1s2X"
MISTRAL_MODEL = "mistral-large-latest"

def setup_driver():
    """Configura el driver de Selenium"""
    chrome_options = Options()
    chrome_options.add_argument('--headless=new')  # Nuevo modo headless
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--disable-software-rasterizer')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-setuid-sandbox')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--remote-debugging-port=9222')
    chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    chrome_options.add_argument('--log-level=3')  # Suprimir logs
    chrome_options.add_experimental_option('excludeSwitches', ['enable-logging'])
    
    # Configurar binario de Chrome
    chrome_options.binary_location = '/usr/bin/google-chrome'
    
    try:
        from selenium.webdriver.chrome.service import Service
        service = Service()
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.set_page_load_timeout(15)
        return driver
    except Exception as e:
        # Fallback sin Selenium
        return None

def scrape_with_selenium(url, termino):
    """Scraping usando Selenium para sitios con JavaScript"""
    driver = setup_driver()
    
    if not driver:
        return scrape_with_requests(url, termino)
    
    try:
        driver.get(url)
        # Esperar a que cargue el contenido
        time.sleep(3)
        
        # Obtener HTML renderizado
        html = driver.page_source
        driver.quit()
        
        # Parsear con BeautifulSoup
        soup = BeautifulSoup(html, 'html.parser')
        
        # Buscar precios
        precios = []
        
        # Patrones comunes de precios
        price_selectors = [
            {'class': 'price'},
            {'class': 'selling-price'},
            {'class': 'product-price'},
            {'data-price': True},
            {'itemprop': 'price'}
        ]
        
        for selector in price_selectors:
            elements = soup.find_all(attrs=selector)
            for elem in elements:
                texto = elem.get_text(strip=True)
                # Buscar patrón L.xx.xx
                import re
                match = re.search(r'L\.?\s*(\d+\.?\d*)', texto)
                if match:
                    precio = float(match.group(1))
                    if 1 <= precio <= 10000:
                        precios.append(precio)
        
        # Buscar URL del producto
        product_url = url
        product_links = soup.find_all('a', href=True)
        for link in product_links:
            if '/p' in link['href'] or 'product' in link['href']:
                product_url = link['href']
                if not product_url.startswith('http'):
                    from urllib.parse import urljoin
                    product_url = urljoin(url, product_url)
                break
        
        if precios:
            return {
                'precio': precios[0],
                'url': product_url,
                'metodo': 'selenium'
            }
        
        return None
        
    except Exception as e:
        if driver:
            driver.quit()
        return None

def scrape_with_requests(url, termino):
    """Fallback usando requests + BeautifulSoup"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'es-HN,es;q=0.9,en;q=0.8'
        }
        
        response = requests.get(url, headers=headers, timeout=15)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Buscar precios
        import re
        text = soup.get_text()
        matches = re.findall(r'L\.?\s*(\d+\.\d{2})', text)
        
        if matches:
            precio = float(matches[0])
            if 1 <= precio <= 10000:
                return {
                    'precio': precio,
                    'url': url,
                    'metodo': 'requests'
                }
        
        return None
        
    except Exception as e:
        return None

def analyze_with_mistral(html_snippet, termino):
    """Analiza el HTML con Mistral AI para extraer precios"""
    prompt = f"""Analiza este fragmento HTML de una página de supermercado en Honduras.

PRODUCTO BUSCADO: {termino}

HTML:
{html_snippet[:2000]}

INSTRUCCIONES:
1. Busca el precio del producto en el HTML
2. El precio debe estar en Lempiras (L.xx.xx)
3. Extrae SOLO el primer precio válido que encuentres

FORMATO (JSON):
{{"encontrado": true, "precio": 45.99}}

Si NO encuentras: {{"encontrado": false}}

RESPONDE SOLO JSON."""

    try:
        response = requests.post(
            'https://api.mistral.ai/v1/chat/completions',
            headers={
                'Content-Type': 'application/json',
                'Authorization': f'Bearer {MISTRAL_API_KEY}'
            },
            json={
                'model': MISTRAL_MODEL,
                'messages': [
                    {'role': 'user', 'content': prompt}
                ],
                'temperature': 0.1,
                'max_tokens': 200,
                'response_format': {'type': 'json_object'}
            },
            timeout=30
        )
        
        if response.status_code == 200:
            result = response.json()
            content = result['choices'][0]['message']['content']
            data = json.loads(content)
            
            if data.get('encontrado') and data.get('precio'):
                return float(data['precio'])
        
        return None
        
    except Exception as e:
        return None

def buscar_precio(termino, codigo):
    """Función principal de búsqueda"""
    resultados = []
    
    # Usar el término específico para la búsqueda
    termino_busqueda = termino if termino else codigo
    es_codigo = bool(codigo and not termino)  # True si estamos buscando por código
    
    # Sitios a buscar - cada uno con el término específico
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
    
    for sitio in sitios:
        # Intentar con Selenium
        resultado = scrape_with_selenium(sitio['url'], termino_busqueda)
        
        if resultado:
            resultados.append({
                'fuente': sitio['nombre'],
                'precio': resultado['precio'],
                'url': resultado['url'],
                'metodo': 'python_selenium_mistral',
                'confianza': 'alta'
            })
        
        time.sleep(1)  # Delay entre requests
    
    return resultados

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({'error': 'Faltan argumentos'}))
        sys.exit(1)
    
    termino = sys.argv[1]
    codigo = sys.argv[2] if len(sys.argv) > 2 else ''
    
    resultados = buscar_precio(termino, codigo)
    print(json.dumps(resultados, ensure_ascii=False))
