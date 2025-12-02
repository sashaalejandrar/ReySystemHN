#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Método 3: Smart Scraper + Mistral AI
Combina múltiples técnicas y usa Mistral para decisiones inteligentes
"""

import sys
import json
import requests
from bs4 import BeautifulSoup
import re
from urllib.parse import urljoin, quote
import time

MISTRAL_API_KEY = "w9euutg7ruv8K2F8oi2iVEnHDCRI1s2X"
MISTRAL_MODEL = "mistral-large-latest"

class SmartScraper:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'es-HN,es;q=0.9,en;q=0.8'
        })
    
    def extract_with_beautifulsoup(self, html, url):
        """Extracción avanzada con BeautifulSoup"""
        soup = BeautifulSoup(html, 'html.parser')
        precios = []
        
        # Técnica 1: CSS Selectors avanzados
        selectors = [
            'span[class*="price"]',
            'div[class*="price"]',
            '[data-price]',
            '[itemprop="price"]',
            'meta[property*="price"]'
        ]
        
        for selector in selectors:
            elements = soup.select(selector)
            for elem in elements:
                # Buscar en texto
                texto = elem.get_text(strip=True)
                match = re.search(r'L\.?\s*(\d+\.?\d*)', texto)
                if match:
                    precio = float(match.group(1))
                    if 1 <= precio <= 10000:
                        precios.append(precio)
                
                # Buscar en atributos
                for attr in ['content', 'data-price', 'value']:
                    if elem.has_attr(attr):
                        try:
                            precio = float(elem[attr])
                            if 1 <= precio <= 10000:
                                precios.append(precio)
                        except:
                            pass
        
        # Técnica 2: Buscar en scripts JSON
        scripts = soup.find_all('script', type='application/ld+json')
        for script in scripts:
            try:
                data = json.loads(script.string)
                precio = self.extract_price_from_json(data)
                if precio:
                    precios.append(precio)
            except:
                pass
        
        # Técnica 3: Buscar en texto plano con regex mejorado
        text = soup.get_text()
        patterns = [
            r'L\.?\s*(\d+\.\d{2})',
            r'HNL\s*(\d+\.?\d*)',
            r'Lempiras?\s*(\d+\.?\d*)',
            r'precio[:\s]+L\.?\s*(\d+\.?\d*)'
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, text, re.IGNORECASE)
            for match in matches:
                try:
                    precio = float(match)
                    if 1 <= precio <= 10000:
                        precios.append(precio)
                except:
                    pass
        
        # Buscar URL del producto
        product_url = url
        for link in soup.find_all('a', href=True):
            href = link['href']
            if any(x in href for x in ['/p', '/product', '/producto', '/item']):
                product_url = urljoin(url, href)
                break
        
        if precios:
            return {
                'precio': precios[0],
                'url': product_url,
                'metodo': 'beautifulsoup'
            }
        
        return None
    
    def extract_price_from_json(self, data):
        """Extrae precio de estructuras JSON recursivamente"""
        if isinstance(data, dict):
            # Buscar claves comunes
            for key in ['price', 'sellingPrice', 'lowPrice', 'highPrice']:
                if key in data:
                    try:
                        precio = float(data[key])
                        if 1 <= precio <= 10000:
                            return precio
                    except:
                        pass
            
            # Buscar en offers
            if 'offers' in data:
                offers = data['offers']
                if isinstance(offers, dict):
                    return self.extract_price_from_json(offers)
                elif isinstance(offers, list) and offers:
                    return self.extract_price_from_json(offers[0])
            
            # Buscar recursivamente
            for value in data.values():
                precio = self.extract_price_from_json(value)
                if precio:
                    return precio
        
        elif isinstance(data, list):
            for item in data:
                precio = self.extract_price_from_json(item)
                if precio:
                    return precio
        
        return None
    
    def ask_mistral_smart(self, html, termino, sitio_nombre):
        """Pregunta inteligente a Mistral con contexto"""
        # Extraer snippet relevante
        soup = BeautifulSoup(html, 'html.parser')
        
        # Remover scripts y styles
        for script in soup(['script', 'style']):
            script.decompose()
        
        text = soup.get_text()
        lines = [line.strip() for line in text.splitlines() if line.strip()]
        snippet = '\n'.join(lines[:50])  # Primeras 50 líneas
        
        prompt = f"""Eres un experto en extracción de precios de páginas web de Honduras.

SITIO: {sitio_nombre}
PRODUCTO BUSCADO: {termino}

CONTENIDO DE LA PÁGINA:
{snippet}

INSTRUCCIONES:
1. Busca el precio del producto "{termino}"
2. El precio DEBE estar en Lempiras (L.xx.xx o HNL)
3. Ignora precios en otras monedas
4. Si ves múltiples precios, devuelve el primero

FORMATO (JSON):
{{"encontrado": true, "precio": 45.99, "confianza": "alta"}}

Si NO encuentras precio en Lempiras: {{"encontrado": false}}

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
                        {
                            'role': 'system',
                            'content': 'Eres un experto en extracción de precios de páginas web. Solo devuelves precios en Lempiras de Honduras.'
                        },
                        {
                            'role': 'user',
                            'content': prompt
                        }
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
                    return {
                        'precio': float(data['precio']),
                        'confianza': data.get('confianza', 'media')
                    }
        
        except Exception as e:
            pass
        
        return None
    
    def scrape_site(self, url, termino, sitio_nombre):
        """Scraping inteligente de un sitio"""
        try:
            response = self.session.get(url, timeout=15)
            
            if response.status_code == 200:
                html = response.text
                
                # Método 1: BeautifulSoup avanzado
                resultado_bs = self.extract_with_beautifulsoup(html, url)
                if resultado_bs:
                    return {
                        'fuente': sitio_nombre,
                        'precio': resultado_bs['precio'],
                        'url': resultado_bs['url'],
                        'metodo': 'python_smart_mistral',
                        'confianza': 'alta'
                    }
                
                # Método 2: Mistral AI inteligente
                resultado_mistral = self.ask_mistral_smart(html, termino, sitio_nombre)
                if resultado_mistral:
                    return {
                        'fuente': sitio_nombre,
                        'precio': resultado_mistral['precio'],
                        'url': url,
                        'metodo': 'python_smart_mistral',
                        'confianza': resultado_mistral['confianza']
                    }
        
        except Exception as e:
            pass
        
        return None

def buscar_precio(termino, codigo):
    """Función principal de búsqueda"""
    scraper = SmartScraper()
    resultados = []
    
    # Usar el término específico para la búsqueda
    termino_busqueda = termino if termino else codigo
    es_codigo = bool(codigo and not termino)  # True si estamos buscando por código
    
    sitios = []
    
    # La Colonia - URL diferente según si es código o nombre
    if es_codigo:
        sitios.append({
            'nombre': 'La Colonia',
            'url': f'https://www.lacolonia.com/{codigo}?_q={codigo}&map=ft'
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
            'url': f'https://www.paiz.com.hn/{codigo}?_q={codigo}&map=ft'
        })
    else:
        nombre_url = termino_busqueda.lower().replace(' ', '-')
        sitios.append({
            'nombre': 'Paiz',
            'url': f'https://www.paiz.com.hn/{nombre_url}/p'
        })
    
    # Otros sitios
    sitios.extend([
        {
            'nombre': 'Maxi Despensa',
            'url': f'https://maxidespensa.com.hn/search?q={quote(termino_busqueda)}'
        }
    ])
    
    for sitio in sitios:
        resultado = scraper.scrape_site(sitio['url'], termino_busqueda, sitio['nombre'])
        if resultado:
            resultados.append(resultado)
        
        time.sleep(0.5)  # Delay entre requests
    
    return resultados

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Faltan argumentos'}))
        sys.exit(1)
    
    termino = sys.argv[1]
    codigo = sys.argv[2] if len(sys.argv) > 2 else ''
    
    resultados = buscar_precio(termino, codigo)
    print(json.dumps(resultados, ensure_ascii=False))
