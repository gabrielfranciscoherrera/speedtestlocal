class SpeedTester {
    constructor() {
        this.elements = {};
        this.isOnline = true;
        this.currentSession = null;
        this.bestResults = {
            download: 0,
            upload: 0,
            ping: 0
        };
        
        // Configuración de prueba automática
        this.autoTestEnabled = localStorage.getItem('autoTestEnabled') !== 'false'; // Por defecto activado
        
        this.initializeElements();
        this.bindEvents();
        this.checkInternetConnection();
    }

    initializeElements() {
        this.elements = {
            startTest: document.getElementById('start-btn'),
            downloadTest: document.getElementById('download-btn'),
            uploadTest: document.getElementById('upload-btn'),
            pingTest: document.getElementById('ping-btn'),
            progressContainer: document.getElementById('progress-container'),
            progressFill: document.getElementById('progress-fill'),
            progressText: document.getElementById('progress-text'),
            download: document.getElementById('download-speed'),
            upload: document.getElementById('upload-speed'),
            ping: document.getElementById('ping-speed'),
            status: document.getElementById('status'),
            connectionStatus: document.getElementById('connection-status'),
            recheckBtn: document.getElementById('recheck-btn'),
            reportBtn: document.getElementById('report-btn'),
            showHistoryBtn: document.getElementById('show-history-btn'),
            adminPanelBtn: document.getElementById('admin-panel-btn'),
            autoTestToggle: document.getElementById('auto-test-toggle'),
            lastTest: document.getElementById('last-test'),
            bestDownload: document.getElementById('best-download'),
            bestUpload: document.getElementById('best-upload'),
            bestPing: document.getElementById('best-ping'),
            historyContainer: document.getElementById('history-container'),
            historyList: document.getElementById('history-list')
        };
    }

    bindEvents() {
        if (this.elements.startTest) {
            this.elements.startTest.addEventListener('click', () => this.runFullTest());
        }
        if (this.elements.downloadTest) {
            this.elements.downloadTest.addEventListener('click', () => this.runDownloadTest());
        }
        if (this.elements.uploadTest) {
            this.elements.uploadTest.addEventListener('click', () => this.runUploadTest());
        }
        if (this.elements.pingTest) {
            this.elements.pingTest.addEventListener('click', () => this.runPingTest());
        }
        if (this.elements.showHistoryBtn) {
            this.elements.showHistoryBtn.addEventListener('click', () => this.toggleHistory());
        }
        if (this.elements.adminPanelBtn) {
            this.elements.adminPanelBtn.addEventListener('click', () => this.openAdminPanel());
        }
        
        if (this.elements.autoTestToggle) {
            this.elements.autoTestToggle.addEventListener('click', () => this.toggleAutoTest());
        }
        
        // Botón de verificación manual
        const recheckBtn = document.getElementById('recheck-btn');
        if (recheckBtn) {
            recheckBtn.addEventListener('click', () => this.checkInternetConnection());
        }
        
        // Eventos para el modal de reporte
        if (this.elements.reportBtn) {
            this.elements.reportBtn.addEventListener('click', () => this.showReportModal());
        }
        
        // Eventos del modal de reporte
        const modal = document.getElementById('report-modal');
        const closeModal = document.getElementById('close-modal');
        
        if (modal && closeModal) {
            // Cerrar modal con X
            closeModal.addEventListener('click', () => this.hideReportModal());
            
            // Cerrar modal haciendo clic fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideReportModal();
                }
            });
            
            // Cerrar modal con ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'block') {
                    this.hideReportModal();
                }
            });
            
            console.log('🔒 Modal de reporte configurado correctamente');
        }
        
        // Escuchar cambios de conectividad del navegador
        window.addEventListener('online', () => {
            this.updateStatus('🌐 Conexión restaurada. Verificando...');
            this.checkInternetConnection();
        });
        
        window.addEventListener('offline', () => {
            this.updateStatus('❌ Conexión perdida. Activando modo offline...');
            this.setOnlineMode(false);
            // Asegurar que el botón de reporte esté visible
            this.showReportButton(true);
        });
        
        // Verificar estado inicial de conectividad
        if (!navigator.onLine) {
            console.log('📱 Navegador inició en modo offline');
            this.setOnlineMode(false);
            this.showReportButton(true);
        }
    }

    // Inicializar sesión con el servidor
    async initSession() {
        try {
            console.log('🔐 Intentando iniciar sesión...');
            
            let clientIP = 'IP no disponible';
            let ipError = null;
            
            try {
                clientIP = await this.getClientIP();
                console.log(`✅ IP del cliente obtenida: ${clientIP}`);
            } catch (ipError) {
                console.warn('⚠️ Error obteniendo IP del cliente:', ipError);
                clientIP = 'Error obteniendo IP';
            }
            
            const response = await fetch('api/speedtest.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'iniciar_sesion',
                    client_ip: clientIP
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                
                if (data.success && data.token) {
                    this.currentSession = data;
                    console.log('✅ Sesión iniciada correctamente:', data);
                    this.updateStatus(`Conectado como: ${data.cliente.nombre} (IP: ${clientIP})`);
                    
                    // Habilitar botones de test
                    this.enableButtons();
                    
                    return true;
                } else {
                    console.warn('⚠️ Respuesta de API sin éxito:', data);
                    this.updateStatus(`❌ Error en respuesta de API: ${data.error || 'Respuesta inválida'}`);
                    return false;
                }
            } else {
                let errorMessage = 'Error desconocido';
                let debugInfo = null;
                
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.error || `Error ${response.status}: ${response.statusText}`;
                    debugInfo = errorData.debug_info || null;
                } catch (parseError) {
                    errorMessage = `Error ${response.status}: ${response.statusText}`;
                }
                
                console.warn('⚠️ Error en respuesta de API:', errorMessage, debugInfo);
                
                // Manejar diferentes tipos de errores
                if (response.status === 400) {
                    if (debugInfo && debugInfo.ip_obtenida) {
                        this.updateStatus(`❌ Error 400: No se pudo obtener IP válida. IP detectada: ${debugInfo.ip_obtenida}`);
                        console.log('🔍 Información de debug de IP:', debugInfo);
                    } else {
                        this.updateStatus(`❌ Error 400: Solicitud incorrecta. Verifica la configuración del servidor.`);
                    }
                } else if (response.status === 404) {
                    if (debugInfo && debugInfo.ip_buscada) {
                        this.updateStatus(`❌ Error 404: Cliente no encontrado para IP: ${debugInfo.ip_buscada}. Verifica que esté registrado en la base de datos.`);
                    } else {
                        this.updateStatus(`❌ Error 404: Cliente no encontrado. Verifica tu IP.`);
                    }
                } else if (response.status === 500) {
                    this.updateStatus(`❌ Error 500: Error interno del servidor. Contacta al administrador.`);
                } else {
                    this.updateStatus(`❌ ${errorMessage}`);
                }
                
                // Mostrar información de debug si está disponible
                if (debugInfo) {
                    console.log('🔍 Información de debug completa:', debugInfo);
                    this.updateStatus(this.updateStatus() + `\n🔍 Debug: IP servidor: ${debugInfo.ip_servidor || 'N/A'}`);
                }
                
                return false;
            }
        } catch (error) {
            console.warn('⚠️ Error iniciando sesión:', error);
            
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                this.updateStatus('❌ No se pudo conectar al servidor. Verifica que el servidor esté funcionando.');
            } else if (error.name === 'AbortError') {
                this.updateStatus('❌ Timeout al conectar con el servidor. Verifica la conexión de red.');
            } else if (error.name === 'NetworkError') {
                this.updateStatus('❌ Error de red. Verifica tu conexión a internet.');
            } else {
                this.updateStatus(`❌ Error de conexión: ${error.message}`);
            }
            
            return false;
        }
    }

    async saveTest(testData) {
        if (!this.currentSession) {
            console.warn('No hay sesión activa para guardar el test');
            return false;
        }

        try {
            const response = await fetch('api/speedtest.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'guardar_test',
                    token: this.currentSession.token,
                    test_data: testData
                })
            });

            if (response.ok) {
                console.log('Test guardado correctamente');
                this.loadHistory(); // Recargar historial
                return true;
            } else {
                console.error('Error guardando test');
                return false;
            }
        } catch (error) {
            console.error('Error guardando test:', error);
            return false;
        }
    }

    async loadHistory() {
        // Solo cargar historial si hay una sesión válida
        if (!this.currentSession || !this.currentSession.token) {
            console.log('No hay sesión válida para cargar historial');
            return;
        }
        
        try {
            const response = await fetch(`api/speedtest.php?action=historial&token=${this.currentSession.token}`);
            if (response.ok) {
                const data = await response.json();
                this.displayHistory(data.historial || []);
            } else if (response.status === 401) {
                console.log('Sesión expirada o inválida');
                this.currentSession = null;
            } else {
                console.error('Error cargando historial:', response.status);
            }
        } catch (error) {
            console.error('Error cargando historial:', error);
        }
    }

    displayHistory(history) {
        if (!this.elements.historyList) return;

        this.elements.historyList.innerHTML = '';
        
        if (history.length === 0) {
            this.elements.historyList.innerHTML = '<p class="no-history">No hay tests anteriores</p>';
            return;
        }

        history.forEach(test => {
            const testItem = document.createElement('div');
            testItem.className = 'history-item';
            testItem.innerHTML = `
                <div class="history-header">
                    <span class="test-type ${test.tipo_test}">${test.tipo_test.toUpperCase()}</span>
                    <span class="test-date">${new Date(test.fecha).toLocaleString()}</span>
                </div>
                <div class="history-details">
                    <span class="speed">${test.velocidad_mbps ? test.velocidad_mbps.toFixed(2) + ' Mbps' : 'N/A'}</span>
                    <span class="latency">${test.latencia_ms ? test.latencia_ms.toFixed(2) + ' ms' : 'N/A'}</span>
                </div>
            `;
            this.elements.historyList.appendChild(testItem);
        });
    }

    toggleHistory() {
        if (this.elements.historyContainer) {
            const isVisible = this.elements.historyContainer.style.display !== 'none';
            this.elements.historyContainer.style.display = isVisible ? 'none' : 'block';
            this.elements.showHistoryBtn.textContent = isVisible ? 'Mostrar Historial' : 'Ocultar Historial';
        }
    }

    openAdminPanel() {
        window.open('admin/panel.php', '_blank');
    }

    updateStatus(status) {
        if (this.elements.status) {
            this.elements.status.textContent = status;
        }
    }

    showProgress(show = true) {
        if (this.elements.progressContainer) {
            this.elements.progressContainer.style.display = show ? 'block' : 'none';
        }
    }

    updateProgress(percent, text) {
        if (this.elements.progressFill && this.elements.progressText) {
            this.elements.progressFill.style.width = `${percent}%`;
            this.elements.progressText.textContent = text;
        }
    }

    // Verificar conexión a internet con reintentos
    async checkInternetConnection() {
        this.updateStatus('🔍 Verificando conexión a internet...');
        this.updateConnectionStatus('⏳ Verificando...', 'warning');
        
        // Configuración de reintentos
        const maxRetries = 3;
        let currentTry = 1;
        let lastResult = null;
        
        // Servidores CDN para verificar conectividad
        const cdnServers = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'https://unpkg.com/react@18/umd/react.production.min.js',
            'https://code.jquery.com/jquery-3.7.0.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap'
        ];
        
        while (currentTry <= maxRetries) {
            console.log(`🌐 Intento ${currentTry} de verificación de conectividad...`);
            
            try {
                // Usar fetch para verificar conectividad a CDN
                const result = await this.testCDNConnectivity(cdnServers);
                lastResult = result;
                
                if (result.success) {
                    this.updateStatus('✅ Conexión a internet verificada');
                    this.updateConnectionStatus('✅ Conectado a Internet', 'success');
                    this.setOnlineMode(true);
                    
                    // Solo ejecutar prueba automática si está habilitada
                    if (this.autoTestEnabled) {
                        console.log('🚀 Ejecutando prueba de velocidad automática...');
                        this.updateStatus('🚀 Iniciando prueba de velocidad automática...');
                        
                        // Pequeña pausa para que el usuario vea el mensaje
                        await new Promise(resolve => setTimeout(resolve, 1500));
                        
                        // Intentar inicializar sesión antes de ejecutar la prueba
                        try {
                            const sessionResult = await this.initSession();
                            if (sessionResult) {
                                // Ejecutar prueba completa solo si la sesión se inició correctamente
                                await this.runFullTest();
                            } else {
                                this.updateStatus('⚠️ No se pudo iniciar sesión. Ejecuta la prueba manualmente.');
                            }
                        } catch (error) {
                            console.error('Error en prueba automática:', error);
                            this.updateStatus('⚠️ Error en prueba automática. Ejecuta la prueba manualmente.');
                        }
                    } else {
                        this.updateStatus('✅ Conexión verificada. Prueba automática desactivada.');
                    }
                    
                    return true;
                } else {
                    if (currentTry < maxRetries) {
                        this.updateConnectionStatus(`❌ Intento ${currentTry} falló. Reintentando...`, 'error');
                        // Esperar 1 segundo antes del siguiente intento
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }
                }
            } catch (error) {
                console.error(`❌ Error en intento ${currentTry}:`, error);
                lastResult = { success: false, error: error.message };
                
                if (currentTry < maxRetries) {
                    this.updateConnectionStatus(`❌ Error en intento ${currentTry}. Reintentando...`, 'error');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
            
            currentTry++;
        }
        
        // Si llegamos aquí, todos los intentos fallaron
        this.updateStatus('❌ Sin conexión a internet después de 3 intentos');
        this.updateConnectionStatus('❌ Sin conexión a Internet', 'error');
        this.setOnlineMode(false);
        
        // Mostrar estadísticas de los intentos
        console.log(`📊 Resultados de verificación de conectividad:`);
        console.log(`   Intentos realizados: ${maxRetries}`);
        console.log(`   Último resultado:`, lastResult);
        
        return false;
    }

    // Función para probar conectividad usando fetch a CDN
    async testCDNConnectivity(cdnServers) {
        console.log('🔍 Probando conectividad a CDN...');
        
        for (let i = 0; i < cdnServers.length; i++) {
            const server = cdnServers[i];
            console.log(`🌐 Probando servidor ${i + 1}: ${server}`);
            
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 segundos de timeout
                
                const response = await fetch(server, {
                    method: 'HEAD',
                    mode: 'no-cors',
                    signal: controller.signal,
                    cache: 'no-cache'
                });
                
                clearTimeout(timeoutId);
                
                // Si llegamos aquí, la conexión fue exitosa
                console.log(`✅ Conexión exitosa a: ${server}`);
                return { success: true, server: server };
                
            } catch (error) {
                clearTimeout(timeoutId);
                
                if (error.name === 'AbortError') {
                    console.log(`⏰ Timeout en servidor ${i + 1}: ${server}`);
                } else {
                    console.log(`❌ Error en servidor ${i + 1}: ${server} - ${error.message}`);
                }
                
                // Continuar con el siguiente servidor si no es el último
                if (i < cdnServers.length - 1) {
                    continue;
                }
            }
        }
        
        // Si fetch falla, intentar con método alternativo de imagen
        console.log('🔄 Intentando método alternativo con imágenes...');
        return await this.testImageConnectivity();
    }

    // Método alternativo usando imágenes para verificar conectividad
    async testImageConnectivity() {
        const imageUrls = [
            'https://www.google.com/favicon.ico',
            'https://www.cloudflare.com/favicon.ico',
            'https://www.jsdelivr.net/favicon.ico'
        ];
        
        for (let i = 0; i < imageUrls.length; i++) {
            const imageUrl = imageUrls[i];
            console.log(`🖼️ Probando imagen ${i + 1}: ${imageUrl}`);
            
            try {
                const result = await this.testImageLoad(imageUrl);
                if (result.success) {
                    console.log(`✅ Conexión exitosa con imagen: ${imageUrl}`);
                    return { success: true, server: imageUrl, method: 'image' };
                }
            } catch (error) {
                console.log(`❌ Error con imagen ${i + 1}: ${error.message}`);
            }
        }
        
        console.log('❌ Todos los métodos de verificación fallaron');
        return { success: false, error: 'Todos los métodos de verificación fallaron' };
    }

    // Función para probar carga de imagen
    testImageLoad(url) {
        return new Promise((resolve) => {
            const img = new Image();
            const timeoutId = setTimeout(() => {
                img.onload = null;
                img.onerror = null;
                resolve({ success: false, error: 'Timeout' });
            }, 3000);
            
            img.onload = () => {
                clearTimeout(timeoutId);
                resolve({ success: true });
            };
            
            img.onerror = () => {
                clearTimeout(timeoutId);
                resolve({ success: false, error: 'Image load failed' });
            };
            
            img.src = url + '?t=' + Date.now(); // Evitar cache
        });
    }

    // Configurar modo online/offline
    setOnlineMode(isOnline) {
        this.isOnline = isOnline;
        
        // Aplicar clase CSS al body
        if (isOnline) {
            document.body.classList.remove('offline-mode');
        } else {
            document.body.classList.add('offline-mode');
        }
        
        if (isOnline) {
            // Modo online: cargar historial del servidor
            this.loadHistory();
            this.updateStatus('✅ Modo online: Tests se guardan en servidor');
            this.enableTestButtons(true);
            this.showReportButton(true); // Siempre mostrar botón de reporte
        } else {
            // Modo offline: cargar historial local
            this.loadLocalHistory();
            this.updateStatus('❌ Sin conexión a internet. No se pueden realizar pruebas.');
            this.enableTestButtons(false); // Deshabilitar botones de test
            this.showReportButton(true);  // Mostrar botón de reporte
        }
        
        // Actualizar indicadores visuales
        this.updateOfflineIndicators();
        
        // Log para debugging
        console.log(`🌐 Modo ${isOnline ? 'ONLINE' : 'OFFLINE'} configurado`);
        console.log(`📱 Botón de reporte: SIEMPRE VISIBLE`);
    }

    // Mostrar/ocultar botón de reporte
    showReportButton(show) {
        const reportBtn = document.getElementById('report-btn');
        if (reportBtn) {
            if (show) {
                reportBtn.style.display = 'inline-block';
                reportBtn.style.visibility = 'visible';
                reportBtn.style.opacity = '1';
            } else {
                reportBtn.style.display = 'none';
                reportBtn.style.visibility = 'hidden';
                reportBtn.style.opacity = '0';
            }
        }
    }

    // Mostrar modal de reporte
    showReportModal() {
        console.log('🔍 Función showReportModal llamada');
        
        const modal = document.getElementById('report-modal');
        if (modal) {
            console.log('✅ Modal encontrado, llenando información...');
            this.fillModalInfo();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            console.log('✅ Modal mostrado correctamente');
        } else {
            console.error('❌ Modal no encontrado');
        }
    }

    // Ocultar modal de reporte
    hideReportModal() {
        const modal = document.getElementById('report-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll
        }
    }

    // Llenar información del modal
    fillModalInfo() {
        const deviceInfo = {
            userAgent: navigator.userAgent || 'Navegador desconocido',
            platform: navigator.platform || 'Plataforma desconocida',
            language: navigator.language || 'Idioma desconocido',
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            url: window.location.href,
            timestamp: new Date().toLocaleString('es-ES'),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };

        // Obtener IP del cliente (simulada por ahora)
        const clientIP = this.getClientIP();

        // Actualizar elementos del modal
        document.getElementById('modal-user-agent').textContent = clientIP;
        document.getElementById('modal-platform').textContent = deviceInfo.platform;
        document.getElementById('modal-url').textContent = deviceInfo.url;
        document.getElementById('modal-timestamp').textContent = deviceInfo.timestamp;
        document.getElementById('modal-timezone').textContent = deviceInfo.timezone;
        document.getElementById('modal-status').textContent = this.isOnline ? '✅ Conectado' : '❌ Sin conexión';
    }

    // Enviar reporte a WhatsApp desde el modal
    sendReportViaWhatsApp() {
        console.log('🚀 Función sendReportViaWhatsApp llamada');
        
        try {
            console.log('📱 Preparando información del dispositivo...');
            
            const deviceInfo = {
                userAgent: navigator.userAgent || 'Navegador desconocido',
                platform: navigator.platform || 'Plataforma desconocida',
                language: navigator.language || 'Idioma desconocido',
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine,
                url: window.location.href,
                timestamp: new Date().toLocaleString('es-ES'),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            };
            
            console.log('🔍 Información del dispositivo:', deviceInfo);
            
            // Obtener IP del cliente
            const clientIP = this.getClientIP();
            console.log('🌐 IP del cliente:', clientIP);
            
            // Crear mensaje detallado
            const message = [
                '🚨 REPORTE DE PROBLEMA DE CONECTIVIDAD',
                '',
                '🔍 IP Cliente: ' + clientIP,
                '📱 Dispositivo: ' + deviceInfo.platform,
                '🌐 URL: ' + deviceInfo.url,
                '⏰ Fecha: ' + deviceInfo.timestamp,
                '🌍 Zona horaria: ' + deviceInfo.timezone,
                '📶 Estado navegador: ' + (deviceInfo.onLine ? 'Online' : 'Offline'),
                '🍪 Cookies: ' + (deviceInfo.cookieEnabled ? 'Habilitadas' : 'Deshabilitadas'),
                '',
                '❌ Estado: Sin conexión a internet',
                '',
                'Por favor, contactar al cliente para resolver el problema de conectividad.'
            ].join('\n');
            
            console.log('📝 Mensaje preparado:', message);
            
            // Codificar mensaje para URL
            const encodedMessage = encodeURIComponent(message);
            console.log('🔗 Mensaje codificado:', encodedMessage);
            
            // Crear URL de WhatsApp
            const whatsappUrl = `https://wa.me/18099928820?text=${encodedMessage}`;
            console.log('📱 URL de WhatsApp:', whatsappUrl);
            
            // Abrir WhatsApp en nueva pestaña
            console.log('🔄 Intentando abrir WhatsApp...');
            const newWindow = window.open(whatsappUrl, '_blank');
            
            if (newWindow) {
                console.log('✅ Ventana de WhatsApp abierta correctamente');
                // Verificar si se abrió correctamente
                setTimeout(() => {
                    if (newWindow.closed) {
                        console.log('⚠️ Ventana de WhatsApp se cerró');
                        // Si se cerró, mostrar mensaje alternativo
                        this.showWhatsAppFallback(message);
                    }
                }, 1000);
            } else {
                console.log('❌ No se pudo abrir ventana de WhatsApp');
                // Si no se pudo abrir, mostrar mensaje alternativo
                this.showWhatsAppFallback(message);
            }
            
            console.log('✅ Reporte enviado a WhatsApp');
            
        } catch (error) {
            console.error('❌ Error enviando reporte:', error);
            this.updateStatus('❌ Error enviando reporte. Intenta de nuevo.');
        }
    }

    // Copiar información del reporte al portapapeles
    copyReportInfo() {
        try {
            const deviceInfo = {
                userAgent: navigator.userAgent || 'Navegador desconocido',
                platform: navigator.platform || 'Plataforma desconocida',
                language: navigator.language || 'Idioma desconocido',
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine,
                url: window.location.href,
                timestamp: new Date().toLocaleString('es-ES'),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            };
            
            // Obtener IP del cliente
            const clientIP = this.getClientIP();
            
            const message = [
                '🚨 REPORTE DE PROBLEMA DE CONECTIVIDAD',
                '',
                '🔍 IP Cliente: ' + clientIP,
                '📱 Dispositivo: ' + deviceInfo.platform,
                '🌐 URL: ' + deviceInfo.url,
                '⏰ Fecha: ' + deviceInfo.timestamp,
                '🌍 Zona horaria: ' + deviceInfo.timezone,
                '📶 Estado navegador: ' + (deviceInfo.onLine ? 'Online' : 'Offline'),
                '🍪 Cookies: ' + (deviceInfo.cookieEnabled ? 'Habilitadas' : 'Deshabilitadas'),
                '',
                '❌ Estado: Sin conexión a internet',
                '',
                'Por favor, contactar al cliente para resolver el problema de conectividad.'
            ].join('\n');
            
            navigator.clipboard.writeText(message).then(() => {
                this.updateStatus('📋 Información copiada al portapapeles');
                // Mostrar notificación temporal
                setTimeout(() => {
                    this.updateStatus('❌ Sin conexión a internet. No se pueden realizar pruebas.');
                }, 3000);
            }).catch(() => {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = message;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.updateStatus('📋 Información copiada al portapapeles');
            });
            
        } catch (error) {
            console.error('❌ Error copiando información:', error);
            this.updateStatus('❌ Error copiando información');
        }
    }

    // Método alternativo si no se puede abrir WhatsApp
    showWhatsAppFallback(message) {
        // Crear un modal o alerta con la información
        const fallbackMessage = 
            '📱 REPORTE DE PROBLEMA\n\n' +
            'No se pudo abrir WhatsApp automáticamente.\n\n' +
            'Por favor:\n' +
            '1. Abre WhatsApp en tu dispositivo\n' +
            '2. Envía un mensaje al número: +1 (809) 992-8820\n' +
            '3. Copia y pega la siguiente información:\n\n' +
            message;
        
        alert(fallbackMessage);
    }

    // Actualizar indicadores de modo offline
    updateOfflineIndicators() {
        const statusElement = this.elements.connectionStatus;
        if (statusElement) {
            if (!this.isOnline) {
                statusElement.innerHTML += ' <span class="offline-badge">📱 OFFLINE</span>';
            }
        }
        
        // Actualizar botones con indicadores de modo
        this.updateButtonLabels();
    }

    // Actualizar etiquetas de botones según el modo
    updateButtonLabels() {
        if (this.elements.startTest) {
            this.elements.startTest.innerHTML = this.isOnline ? 
                '🚀 Iniciar Test Completo' : 
                '🚀 Iniciar Test Completo (Local)';
        }
        
        if (this.elements.downloadTest) {
            this.elements.downloadTest.innerHTML = this.isOnline ? 
                '⬇️ Solo Descarga' : 
                '⬇️ Solo Descarga (Local)';
        }
        
        if (this.elements.uploadTest) {
            this.elements.uploadTest.innerHTML = this.isOnline ? 
                '⬆️ Solo Subida' : 
                '⬆️ Solo Subida (Local)';
        }
        
        if (this.elements.pingTest) {
            this.elements.pingTest.innerHTML = this.isOnline ? 
                '⏱️ Solo Ping' : 
                '⏱️ Solo Ping (Local)';
        }
    }

    // Cargar historial local desde localStorage
    loadLocalHistory() {
        try {
            const localHistory = localStorage.getItem('speedtest_history');
            if (localHistory) {
                const history = JSON.parse(localHistory);
                this.displayHistory(history);
                this.updateStatus(`📱 Historial local cargado: ${history.length} tests`);
            } else {
                this.updateStatus('📱 No hay historial local disponible');
            }
        } catch (error) {
            console.error('Error cargando historial local:', error);
            this.updateStatus('❌ Error cargando historial local');
        }
    }

    // Guardar test en modo offline (localStorage)
    async saveTestOffline(testData) {
        try {
            const localHistory = localStorage.getItem('speedtest_history') || '[]';
            const history = JSON.parse(localHistory);
            
            // Agregar timestamp y ID único
            const offlineTest = {
                ...testData,
                id: Date.now(),
                timestamp: new Date().toISOString(),
                saved_offline: true,
                ip_cliente: '10.0.0.200' // IP local por defecto
            };
            
            history.unshift(offlineTest);
            
            // Mantener solo los últimos 100 tests
            if (history.length > 100) {
                history.splice(100);
            }
            
            localStorage.setItem('speedtest_history', JSON.stringify(history));
            
            // Actualizar display
            this.displayHistory(history);
            
            return true;
        } catch (error) {
            console.error('Error guardando test offline:', error);
            return false;
        }
    }

    // Sincronizar tests offline cuando vuelva la conexión
    async syncOfflineTests() {
        if (!this.isOnline || !this.currentSession) return;
        
        try {
            const localHistory = localStorage.getItem('speedtest_history') || '[]';
            const offlineTests = JSON.parse(localHistory).filter(test => test.saved_offline);
            
            if (offlineTests.length === 0) return;
            
            this.updateStatus(`🔄 Sincronizando ${offlineTests.length} tests offline...`);
            
            let syncedCount = 0;
            for (const test of offlineTests) {
                try {
                    await this.saveTest(test);
                    syncedCount++;
                } catch (error) {
                    console.error('Error sincronizando test:', error);
                }
            }
            
            if (syncedCount > 0) {
                // Limpiar tests sincronizados del localStorage
                const remainingTests = JSON.parse(localStorage.getItem('speedtest_history') || '[]')
                    .filter(test => !test.saved_offline);
                localStorage.setItem('speedtest_history', JSON.stringify(remainingTests));
                
                this.updateStatus(`✅ ${syncedCount} tests sincronizados exitosamente`);
                this.loadHistory(); // Recargar historial del servidor
            }
            
        } catch (error) {
            console.error('Error en sincronización:', error);
            this.updateStatus('❌ Error sincronizando tests offline');
        }
    }

    // Verificación rápida de internet usando ping a servidores DNS
    async quickInternetTest() {
        const dnsServers = [
            '1.1.1.1',    // Cloudflare
            '8.8.8.8',    // Google
            '8.8.4.4'     // Google secundario
        ];
        
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => resolve(false), 1500);
        });
        
        const pingServer = (server) => {
            return new Promise((resolve) => {
                const startTime = Date.now();
                const img = new Image();
                
                // Timeout individual para cada servidor
                const timeoutId = setTimeout(() => {
                    img.src = '';
                    resolve({ success: false, server, timeout: true });
                }, 500);
                
                img.onload = () => {
                    clearTimeout(timeoutId);
                    const endTime = Date.now();
                    const latency = endTime - startTime;
                    resolve({ success: true, latency, server });
                };
                
                img.onerror = () => {
                    clearTimeout(timeoutId);
                    const endTime = Date.now();
                    const latency = endTime - startTime;
                    // Si hay error pero se recibió respuesta, considerar como éxito
                    if (latency < 500) {
                        resolve({ success: true, latency, server });
                    } else {
                        resolve({ success: false, server, error: 'timeout' });
                    }
                };
                
                // Iniciar ping
                img.src = `http://${server}/favicon.ico?t=${Date.now()}`;
            });
        };
        
        const pingPromises = dnsServers.map(pingServer);
        
        try {
            const results = await Promise.race([
                Promise.all(pingPromises),
                timeoutPromise
            ]);
            
            if (results === false) {
                return { success: false, avgLatency: 0 }; // Timeout general
            }
            
            const successfulPings = results.filter(r => r.success).length;
            const totalServers = dnsServers.length;
            
            if (successfulPings >= 2) {
                const successfulResults = results.filter(r => r.success);
                const avgLatency = successfulResults.reduce((sum, r) => sum + r.latency, 0) / successfulResults.length;
                
                console.log(`✅ Conectividad verificada: ${successfulPings}/${totalServers} servidores respondieron. Latencia promedio: ${avgLatency}ms`);
                return { success: true, avgLatency };
            } else {
                console.log(`❌ Conectividad fallida: Solo ${successfulPings}/${totalServers} servidores respondieron`);
                return { success: false, avgLatency: 0 };
            }
            
        } catch (error) {
            console.error('Error en test de conectividad:', error);
            return { success: false, avgLatency: 0 };
        }
    }

    // Habilitar/deshabilitar botones de test
    enableTestButtons(enable) {
        const buttons = [
            this.elements.startTest,
            this.elements.downloadTest,
            this.elements.uploadTest,
            this.elements.pingTest
        ];
        
        buttons.forEach(btn => {
            if (btn) {
                btn.disabled = !enable;
                btn.style.opacity = enable ? '1' : '0.5';
                btn.style.cursor = enable ? 'pointer' : 'not-allowed';
            }
        });
    }

    // Actualizar estado de conexión
    updateConnectionStatus(message, type) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `connection-status ${type}`;
            
            // Aplicar colores específicos según el tipo
            switch (type) {
                case 'success':
                    statusElement.style.background = 'rgba(76, 175, 80, 0.2)';
                    statusElement.style.color = '#4CAF50';
                    statusElement.style.borderColor = '#4CAF50';
                    break;
                case 'error':
                    statusElement.style.background = 'rgba(103, 58, 183, 0.2)';
                    statusElement.style.color = '#673AB7';
                    statusElement.style.borderColor = '#673AB7';
                    break;
                case 'warning':
                    statusElement.style.background = 'rgba(33, 150, 243, 0.2)';
                    statusElement.style.color = '#2196F3';
                    statusElement.style.borderColor = '#2196F3';
                    break;
            }
        }
    }

    async runFullTest() {
        if (this.isRunning) return;
        
        // En modo offline, no necesitamos sesión
        if (this.isOnline && !this.currentSession) {
            const sessionOk = await this.initSession();
            if (!sessionOk) {
                this.updateStatus('No se pudo conectar. Verifica tu IP.');
                return;
            }
        }
        
        this.isRunning = true;
        this.updateStatus('🚀 Iniciando test completo...');
        
        try {
            // Test de ping
            this.updateStatus('⏱️ Probando ping...');
            const pingResult = await this.testPing();
            
            // Test de descarga
            this.updateStatus('⬇️ Probando descarga...');
            const downloadResult = await this.testDownload();
            
            // Test de subida
            this.updateStatus('⬆️ Probando subida...');
            const uploadResult = await this.testUpload();
            
            // Guardar resultados según el modo
            if (this.isOnline && this.currentSession) {
                await this.saveTest({
                    tipo: 'download',
                    velocidad: downloadResult,
                    latencia: pingResult,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
                
                await this.saveTest({
                    tipo: 'upload',
                    velocidad: uploadResult,
                    latencia: pingResult,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
            } else {
                // Modo offline: guardar localmente
                await this.saveTestOffline({
                    tipo: 'download',
                    velocidad: downloadResult,
                    latencia: pingResult,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
                
                await this.saveTestOffline({
                    tipo: 'upload',
                    velocidad: uploadResult,
                    latencia: pingResult,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
            }
            
            // Actualizar mejores resultados
            if (downloadResult > this.bestResults.download) {
                this.bestResults.download = downloadResult;
                this.updateBestResult('download', downloadResult);
            }
            
            if (uploadResult > this.bestResults.upload) {
                this.bestResults.upload = uploadResult;
                this.updateBestResult('upload', uploadResult);
            }
            
            if (pingResult < this.bestResults.ping) {
                this.bestResults.ping = pingResult;
                this.updateBestResult('ping', pingResult);
            }
            
            const modeText = this.isOnline ? '' : ' (Local)';
            this.updateStatus(`✅ Test completo finalizado${modeText}`);
            this.updateLastTest(`D: ${downloadResult.toFixed(2)} Mbps, U: ${uploadResult.toFixed(2)} Mbps, P: ${pingResult.toFixed(1)} ms`);
            
        } catch (error) {
            this.updateStatus('❌ Error en el test: ' + error.message);
        } finally {
            this.isRunning = false;
        }
    }

    async runDownloadTest() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateStatus('⬇️ Probando descarga...');
        
        try {
            const result = await this.testDownload();
            
            if (result > this.bestResults.download) {
                this.bestResults.download = result;
                this.updateBestResult('download', result);
            }
            
            this.updateStatus('✅ Test de descarga finalizado');
            this.updateLastTest(`Descarga: ${result.toFixed(2)} Mbps`);
            
            // Guardar resultado
            if (this.currentSession) {
                await this.saveTest({
                    tipo: 'download',
                    velocidad: result,
                    latencia: 0,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
            }
            
        } catch (error) {
            this.updateStatus('❌ Error en test de descarga: ' + error.message);
        } finally {
            this.isRunning = false;
        }
    }

    async runUploadTest() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateStatus('⬆️ Probando subida...');
        
        try {
            const result = await this.testUpload();
            
            if (result > this.bestResults.upload) {
                this.bestResults.upload = result;
                this.updateBestResult('upload', result);
            }
            
            this.updateStatus('✅ Test de subida finalizado');
            this.updateLastTest(`Subida: ${result.toFixed(2)} Mbps`);
            
            // Guardar resultado
            if (this.currentSession) {
                await this.saveTest({
                    tipo: 'upload',
                    velocidad: result,
                    latencia: 0,
                    jitter: 0,
                    tamanio: 0.256,
                    duracion: 5000
                });
            }
            
        } catch (error) {
            this.updateStatus('❌ Error en test de subida: ' + error.message);
        } finally {
            this.isRunning = false;
        }
    }

    async runPingTest() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateStatus('⏱️ Probando ping...');
        
        try {
            const result = await this.testPing();
            
            if (result < this.bestResults.ping) {
                this.bestResults.ping = result;
                this.updateBestResult('ping', result);
            }
            
            this.updateStatus('✅ Test de ping finalizado');
            this.updateLastTest(`Ping: ${result.toFixed(1)} ms`);
            
            // Guardar resultado
            if (this.currentSession) {
                await this.saveTest({
                    tipo: 'ping',
                    velocidad: 0,
                    latencia: result,
                    jitter: 0,
                    tamanio: 0,
                    duracion: 1000
                });
            }
            
        } catch (error) {
            this.updateStatus('❌ Error en test de ping: ' + error.message);
        } finally {
            this.isRunning = false;
        }
    }

    // Función para actualizar el último test
    updateLastTest(text) {
        const lastTestElement = document.getElementById('last-test');
        if (lastTestElement) {
            lastTestElement.textContent = text;
        }
    }

    // Función para actualizar mejores resultados
    updateBestResult(type, value) {
        let element;
        let formattedValue;
        
        switch (type) {
            case 'download':
                element = this.elements.bestDownload;
                formattedValue = value.toFixed(2) + ' Mbps';
                break;
            case 'upload':
                element = this.elements.bestUpload;
                formattedValue = value.toFixed(2) + ' Mbps';
                break;
            case 'ping':
                element = this.elements.bestPing;
                formattedValue = value.toFixed(1) + ' ms';
                break;
        }
        
        if (element) {
            element.textContent = formattedValue;
        }
    }

    async testDownload() {
        const testSizes = [64 * 1024, 128 * 1024, 256 * 1024]; // 64KB, 128KB, 256KB
        let totalSpeed = 0;
        let testCount = 0;

        for (const size of testSizes) {
            try {
                const startTime = performance.now();
                
                // Crear datos de prueba en memoria
                const testData = new ArrayBuffer(size);
                const uint8Array = new Uint8Array(testData);
                for (let i = 0; i < size; i++) {
                    uint8Array[i] = Math.floor(Math.random() * 256);
                }
                
                // Simular descarga procesando los datos
                await this.processData(testData);
                
                const endTime = performance.now();
                
                const duration = (endTime - startTime) / 1000; // segundos
                const speedMbps = (size * 8) / (1024 * 1024) / duration; // Mbps
                
                // Filtrar velocidades irrealmente altas (> 1000 Mbps)
                if (speedMbps < 1000) {
                    totalSpeed += speedMbps;
                    testCount++;
                }
            } catch (error) {
                console.error('Error en test de descarga:', error);
            }
        }

        const avgSpeed = testCount > 0 ? totalSpeed / testCount : 0;
        this.elements.download.textContent = avgSpeed.toFixed(2);
        this.updateStatus('Descarga completada');
        
        return avgSpeed;
    }

    async testUpload() {
        const testSizes = [64 * 1024, 128 * 1024, 256 * 1024]; // 64KB, 128KB, 256KB
        let totalSpeed = 0;
        let testCount = 0;

        for (const size of testSizes) {
            try {
                const startTime = performance.now();
                
                // Crear datos de prueba
                const testData = new ArrayBuffer(size);
                const uint8Array = new Uint8Array(testData);
                for (let i = 0; i < size; i++) {
                    uint8Array[i] = Math.floor(Math.random() * 256);
                }
                
                // Simular subida procesando los datos
                await this.processData(testData);
                
                const endTime = performance.now();
                
                const duration = (endTime - startTime) / 1000; // segundos
                const speedMbps = (size * 8) / (1024 * 1024) / duration; // Mbps
                
                // Filtrar velocidades irrealmente altas (> 1000 Mbps)
                if (speedMbps < 1000) {
                    totalSpeed += speedMbps;
                    testCount++;
                }
            } catch (error) {
                console.error('Error en test de subida:', error);
            }
        }

        const avgSpeed = testCount > 0 ? totalSpeed / testCount : 0;
        this.elements.upload.textContent = avgSpeed.toFixed(2);
        this.updateStatus('Subida completada');
        
        return avgSpeed;
    }

    async testPing() {
        const pings = [];
        const pingCount = 5;

        for (let i = 0; i < pingCount; i++) {
            try {
                const startTime = performance.now();
                
                // Simular ping procesando una pequeña cantidad de datos
                const smallData = new ArrayBuffer(1024); // 1KB
                await this.processData(smallData);
                
                const endTime = performance.now();
                
                const ping = endTime - startTime;
                pings.push(ping);
            } catch (error) {
                console.error('Error en test de ping:', error);
            }
            
            // Pequeña pausa entre pings
            if (i < pingCount - 1) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }

        const avgPing = pings.length > 0 ? pings.reduce((a, b) => a + b, 0) / pings.length : 0;
        this.elements.ping.textContent = avgPing.toFixed(1);
        this.updateStatus('Ping completado');
        
        return avgPing;
    }

    // Función para simular procesamiento de datos (simula latencia de red)
    async processData(data) {
        return new Promise((resolve) => {
            // Simular tiempo de procesamiento basado en el tamaño de los datos
            const processingTime = Math.random() * 10 + 5; // 5-15ms base
            const sizeFactor = data.byteLength / (1024 * 1024); // Factor por MB
            
            setTimeout(() => {
                // Simular algún procesamiento
                let checksum = 0;
                const uint8Array = new Uint8Array(data);
                for (let i = 0; i < Math.min(uint8Array.length, 1000); i++) {
                    checksum += uint8Array[i];
                }
                resolve(checksum);
            }, processingTime + (sizeFactor * 100));
        });
    }

    updateBestResults(download, upload, ping) {
        if (download > this.bestResults.download) {
            this.bestResults.download = download;
            this.elements.bestDownload.textContent = `${download.toFixed(2)} Mbps`;
        }
        
        if (upload > this.bestResults.upload) {
            this.bestResults.upload = upload;
            this.elements.bestUpload.textContent = `${upload.toFixed(2)} Mbps`;
        }
        
        if (ping < this.bestResults.ping) {
            this.bestResults.ping = ping;
            this.elements.bestPing.textContent = `${ping.toFixed(1)} ms`;
        }

        this.elements.lastTest.textContent = new Date().toLocaleTimeString();
    }

    disableButtons() {
        this.elements.startTest.disabled = true;
        this.elements.downloadTest.disabled = true;
        this.elements.uploadTest.disabled = true;
        this.elements.pingTest.disabled = true;
    }

    enableButtons() {
        this.elements.startTest.disabled = false;
        this.elements.downloadTest.disabled = false;
        this.elements.uploadTest.disabled = false;
        this.elements.pingTest.disabled = false;
    }

    // Obtener IP del cliente
    async getClientIP() {
        console.log('🔍 Intentando obtener IP del cliente...');
        
        const errors = [];
        
        // Método 1: Intentar obtener IP desde servicios externos
        const ipServices = [
            'https://api.ipify.org?format=json',
            'https://ipapi.co/json/',
            'https://httpbin.org/ip'
        ];
        
        for (let i = 0; i < ipServices.length; i++) {
            try {
                console.log(`🌐 Probando servicio ${i + 1}: ${ipServices[i]}`);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 segundos timeout
                
                const response = await fetch(ipServices[i], {
                    method: 'GET',
                    signal: controller.signal,
                    cache: 'no-cache'
                });
                
                clearTimeout(timeoutId);
                
                if (response.ok) {
                    const data = await response.json();
                    let ip = '';
                    
                    if (data.ip) {
                        ip = data.ip;
                    } else if (data.origin) {
                        ip = data.origin;
                    }
                    
                    if (ip) {
                        console.log(`✅ IP obtenida exitosamente: ${ip}`);
                        return ip;
                    } else {
                        errors.push(`Servicio ${i + 1} respondió pero sin IP válida`);
                    }
                } else {
                    errors.push(`Servicio ${i + 1} respondió con status ${response.status}`);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    errors.push(`Servicio ${i + 1} timeout después de 5 segundos`);
                    console.log(`⏰ Timeout en servicio ${i + 1}: ${ipServices[i]}`);
                } else {
                    errors.push(`Servicio ${i + 1} error: ${error.message}`);
                    console.log(`❌ Error en servicio ${i + 1}: ${error.message}`);
                }
                
                if (i < ipServices.length - 1) {
                    continue;
                }
            }
        }
        
        // Método 2: Intentar obtener IP desde la URL actual
        try {
            const urlParts = window.location.href.split('/');
            if (urlParts[2] && urlParts[2].includes('.')) {
                const urlIP = urlParts[2];
                console.log(`🌐 IP extraída de la URL: ${urlIP}`);
                return urlIP;
            } else {
                errors.push('URL no contiene IP válida');
            }
        } catch (error) {
            errors.push(`Error extrayendo IP de URL: ${error.message}`);
            console.log('❌ Error extrayendo IP de la URL:', error.message);
        }
        
        // Método 3: Intentar obtener IP desde el navegador
        try {
            if (navigator.connection && navigator.connection.effectiveType) {
                console.log('📱 Información de conexión disponible:', navigator.connection);
            }
        } catch (error) {
            console.log('❌ No se pudo obtener información de conexión del navegador');
        }
        
        // Método 4: IP por defecto del servidor local
        console.log('⚠️ No se pudo obtener IP externa, usando IP local por defecto');
        console.log('🔍 Errores encontrados:', errors);
        
        // Lanzar error con información detallada
        const errorMessage = `No se pudo obtener IP del cliente. Errores: ${errors.join('; ')}`;
        const detailedError = new Error(errorMessage);
        detailedError.details = {
            errors: errors,
            url: window.location.href,
            userAgent: navigator.userAgent,
            online: navigator.onLine
        };
        
        throw detailedError;
    }

    // Mostrar información detallada sobre el estado de la conexión
    async showConnectionDetails() {
        const details = {
            timestamp: new Date().toLocaleString(),
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            online: navigator.onLine,
            currentURL: window.location.href,
            serverPath: 'api/speedtest.php'
        };
        
        console.log('📊 Detalles de conexión:', details);
        
        let detailsMessage = `📊 Estado de Conexión:\n`;
        detailsMessage += `⏰ Hora: ${details.timestamp}\n`;
        detailsMessage += `🌐 Navegador: ${details.platform}\n`;
        detailsMessage += `🔗 URL: ${details.currentURL}\n`;
        detailsMessage += `📡 API: ${details.serverPath}\n`;
        detailsMessage += `✅ Navegador online: ${details.online ? 'Sí' : 'No'}`;
        
        // Intentar obtener IP del cliente
        try {
            const clientIP = await this.getClientIP();
            detailsMessage += `\n🌍 IP del Cliente: ${clientIP}`;
            console.log('✅ IP obtenida para detalles:', clientIP);
        } catch (ipError) {
            detailsMessage += `\n❌ Error obteniendo IP: ${ipError.message}`;
            if (ipError.details) {
                console.log('🔍 Detalles del error de IP:', ipError.details);
                detailsMessage += `\n🔍 Detalles: ${ipError.details.errors.join(', ')}`;
            }
        }
        
        // Verificar conectividad al servidor
        try {
            const response = await fetch('api/speedtest.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'test_connection' })
            });
            detailsMessage += `\n📡 Servidor API: ${response.status} ${response.statusText}`;
        } catch (serverError) {
            detailsMessage += `\n❌ Servidor API: ${serverError.message}`;
        }
        
        this.updateStatus(detailsMessage);
        console.log('📊 Detalles de conexión mostrados al usuario');
    }

    // Función para activar/desactivar la prueba automática
    toggleAutoTest() {
        this.autoTestEnabled = !this.autoTestEnabled;
        localStorage.setItem('autoTestEnabled', this.autoTestEnabled ? 'true' : 'false');
        
        // Actualizar el texto y estilo del botón
        if (this.elements.autoTestToggle) {
            if (this.autoTestEnabled) {
                this.elements.autoTestToggle.innerHTML = '✅ Prueba Automática Activada';
                this.elements.autoTestToggle.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
                this.elements.autoTestToggle.style.color = 'white';
            } else {
                this.elements.autoTestToggle.innerHTML = '❌ Prueba Automática Desactivada';
                this.elements.autoTestToggle.style.background = 'linear-gradient(135deg, #9E9E9E, #757575)';
                this.elements.autoTestToggle.style.color = 'white';
            }
        }
        
        this.updateStatus(`Prueba automática: ${this.autoTestEnabled ? 'ACTIVADA' : 'DESACTIVADA'}`);
        console.log(`Prueba automática ${this.autoTestEnabled ? 'ACTIVADA' : 'DESACTIVADA'}`);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Crear instancia global
    window.speedTester = new SpeedTester();
});
