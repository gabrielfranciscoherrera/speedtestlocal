# 🚀 Medidor de Velocidad Local

Un servidor web simple para medir la velocidad de tu conexión usando solo **HTML**, **CSS**, **JavaScript** y **PHP**.

## ✨ Características

- **Test de Descarga**: Mide velocidad simulando descarga de datos
- **Test de Subida**: Mide velocidad simulando subida de datos  
- **Test de Ping**: Mide latencia de procesamiento
- **Interfaz Moderna**: Diseño responsive y atractivo
- **Sin Dependencias**: Solo usa tecnologías web estándar

## 🚀 Cómo Usar

### Opción 1: Apache (Recomendado)
```bash
# Verificar que Apache esté funcionando
./start.sh

# Acceder desde el navegador
http://localhost/speedserver
```

### Opción 2: Servidor PHP Separado
```bash
php -S 0.0.0.0:8000 server.php
# Luego acceder a: http://localhost:8000
```

### Opción 3: Cualquier Servidor Web
Simplemente coloca los archivos en tu directorio web y accede a `index.html`

## 📱 Acceso

### Con Apache (Puerto 80):
**http://localhost/speedserver**

### Con Servidor PHP Separado:
**http://localhost:8000**

## 🔧 Requisitos

- PHP 7.0 o superior
- Navegador web moderno
- No se requieren dependencias externas

## 📊 Cómo Funciona

### Test de Descarga
- Crea datos de prueba en memoria (64KB, 128KB, 256KB)
- Simula procesamiento de datos
- Calcula velocidad basada en tiempo de procesamiento

### Test de Subida
- Similar al test de descarga
- Simula envío y procesamiento de datos
- Mide velocidad de "subida"

### Test de Ping
- Procesa pequeñas cantidades de datos
- Mide latencia de procesamiento
- Promedio de 5 mediciones

## 🎯 Ventajas

✅ **Sin Dependencias** - Solo tecnologías web estándar  
✅ **Portable** - Funciona en cualquier servidor con PHP  
✅ **Rápido** - Tests se ejecutan en memoria local  
✅ **Confiable** - No depende de servicios externos  
✅ **Responsive** - Funciona en móviles y desktop  

## 📁 Archivos

- `index.html` - Interfaz principal con CSS integrado
- `script.js` - Lógica de medición de velocidad
- `server.php` - Servidor PHP simple
- `start.sh` - Script de inicio automático
- `README.md` - Esta documentación

## 🚨 Notas Importantes

- **Velocidades Simuladas**: Los tests simulan velocidad de procesamiento, no velocidad real de red
- **Uso Local**: Diseñado para uso en red local
- **Comparativo**: Útil para comparar rendimiento entre dispositivos

---

**¡Disfruta midiendo tu velocidad! 🚀**
