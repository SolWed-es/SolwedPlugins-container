$(document).ready(function () {
    console.log('hello, man');

    // Función para cargar una hoja de estilo
    function loadCSS(url) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    // Función para cargar un script
    function loadScript(url) {
        return new Promise((resolve, reject) => {
            $.getScript(url, resolve).fail(reject);
        });
    }

    // URLs de las dependencias necesarias
    const leafletCSS = 'https://unpkg.com/leaflet/dist/leaflet.css';
    const leafletJS = 'https://unpkg.com/leaflet/dist/leaflet.js';

    // Cargar las dependencias en orden
    Promise.all([
        loadCSS(leafletCSS),
        loadScript(leafletJS)
    ]).then(() => {
        console.log('Dependencias cargadas correctamente.');

        // Seleccionar el input por su nombre
        const $input = $('input[name="localizacion"]');

        // Verificar si el input ya tiene un valor
        if ($input.val().trim() !== "") {
            console.log("El input ya tiene un valor. Usando las coordenadas proporcionadas.");

            // Extraer las coordenadas del input
            const inputText = $input.val().trim();
            const coordsMatch = inputText.match(/Latitud:\s*(-?\d+\.\d+),\s*Longitud:\s*(-?\d+\.\d+)/);

            if (coordsMatch) {
                // Obtener latitud y longitud desde el input
                const latitude = parseFloat(coordsMatch[1]);
                const longitude = parseFloat(coordsMatch[2]);

                // Configurar el input como solo lectura
                $input.prop('readonly', true);

                // Crear el contenedor del mapa dinámicamente
                const mapContainer = $('<div>', {
                    id: 'map',
                    css: {
                        height: '400px',
                        width: '100%',
                        marginTop: '20px'
                    }
                });

                // Insertar el contenedor del mapa después del formulario
                $('#formEditAttendance').append(mapContainer);

                // Inicializar el mapa con las coordenadas del input
                initMap(latitude, longitude);
            } else {
                console.error("Formato de coordenadas no válido en el input.");
                $input.val("Formato de coordenadas no válido");
                $input.prop('readonly', true);
            }
        } else {
            // Insertar un mensaje inicial en el input
            $input.val("Obteniendo tu ubicación...");

            if (navigator.geolocation) {
                // Solicitar la posición actual
                navigator.geolocation.getCurrentPosition(
                    function (position) { // Callback de éxito
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        // Formatear la información de localización
                        const localizacion = `Latitud: ${latitude}, Longitud: ${longitude}`;

                        // Usar jQuery para rellenar el input
                        $input.val(localizacion);

                        // Configurar el input como solo lectura después de obtener la localización
                        $input.prop('readonly', true);

                        // Crear el contenedor del mapa dinámicamente
                        const mapContainer = $('<div>', {
                            id: 'map',
                            css: {
                                height: '400px',
                                width: '100%',
                                marginTop: '20px'
                            }
                        });

                        // Insertar el contenedor del mapa después del formulario
                        $('#formEditAttendance').append(mapContainer);

                        // Inicializar el mapa
                        initMap(latitude, longitude);
                    },
                    function (error) { // Callback de error
                        console.error("Error al obtener la localización:", error);
                        let errorMessage = "No se pudo obtener la localización";
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Permiso denegado";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Posición no disponible";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Tiempo de espera agotado";
                                break;
                        }

                        // En caso de error, mostrar un mensaje en el input
                        $input.val(errorMessage);

                        // Configurar el input como solo lectura en caso de error
                        $input.prop('readonly', true);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                // Si la geolocalización no está disponible
                $input.val("Geolocalización no soportada en este navegador");

                // Configurar el input como solo lectura
                $input.prop('readonly', true);
            }
        }

        // Función para inicializar el mapa
        function initMap(lat, lng) {
            // Crear el mapa
            const map = L.map('map').setView([lat, lng], 20);

            // Agregar tiles de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Agregar un marcador en la posición indicada
            const marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup("Ubicación especificada").openPopup();
        }
    }).catch((error) => {
        console.error('Error al cargar las dependencias:', error);
        alert('No se pudieron cargar las dependencias necesarias.');
    });
});