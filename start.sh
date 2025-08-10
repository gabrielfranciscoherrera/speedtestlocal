#!/bin/bash

echo "🚀 Servidor de velocidad listo!"
echo "📱 Abre tu navegador y ve a: http://localhost/speedserver"
echo "✅ Usando Apache en puerto 80"
echo ""

# Verificar que Apache esté funcionando
if systemctl is-active --quiet apache2; then
    echo "✅ Apache está funcionando en puerto 80"
    echo "🌐 Accede a: http://localhost/speedserver"
else
    echo "❌ Apache no está funcionando"
    echo "🔧 Inicia Apache con: systemctl start apache2"
fi
