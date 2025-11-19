/**
 * Map JavaScript for Majelis Dzikir Management System
 */

let map;
let wilayahLayers = [];
let majelisMarkers = [];
let petugasMarkers = [];
let currentDrawnItems;

// Initialize map when page loads
$(document).ready(function() {
    console.log('Map.js loaded, checking dependencies...');

    // Debug information
    console.log('jQuery version:', $.fn.jquery);
    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    console.log('Leaflet loaded:', typeof L !== 'undefined');
    console.log('DEFAULT_LAT:', typeof DEFAULT_LAT !== 'undefined' ? DEFAULT_LAT : 'NOT SET');
    console.log('DEFAULT_LNG:', typeof DEFAULT_LNG !== 'undefined' ? DEFAULT_LNG : 'NOT SET');
    console.log('DEFAULT_ZOOM:', typeof DEFAULT_ZOOM !== 'undefined' ? DEFAULT_ZOOM : 'NOT SET');
    console.log('USER_ROLE:', typeof USER_ROLE !== 'undefined' ? USER_ROLE : 'NOT SET');

    // Delay initialization to ensure all scripts are loaded
    setTimeout(function() {
        console.log('Starting map initialization...');
        initializeMap();
        loadMapData();
        loadWilayahOptions();
        updateStatistics();
    }, 500); // Increased delay for debugging
});

function initializeMap() {
    // Check if map container exists
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded');
        // Try to load Leaflet from CDN
        const leafletScript = document.createElement('script');
        leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletScript.onload = function() {
            console.log('Leaflet loaded dynamically');
            initializeMap();
        };
        document.head.appendChild(leafletScript);
        return;
    }

    try {
        // Initialize Leaflet map
        map = L.map('map').setView([DEFAULT_LAT, DEFAULT_LNG], DEFAULT_ZOOM);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        console.log('Map initialized successfully');

        // Initialize FeatureGroup for drawn items
        currentDrawnItems = new L.FeatureGroup();
        map.addLayer(currentDrawnItems);

        // Add draw controls for admin users
        if (USER_ROLE === 'admin') {
            initializeDrawControls();
        }

        // Custom icons
        window.icons = {
            majelis: L.divIcon({
                html: '<i class="fas fa-mosque text-primary" style="font-size: 24px;"></i>',
                iconSize: [30, 30],
                className: 'custom-marker'
            }),
            petugas: L.divIcon({
                html: '<i class="fas fa-user-tie text-danger" style="font-size: 24px;"></i>',
                iconSize: [30, 30],
                className: 'custom-marker'
            })
        };

    } catch (error) {
        console.error('Error initializing map:', error);
        // Fallback: Show error message on page
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="alert alert-danger text-center p-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error loading map:</strong> ${error.message}
                    <br>
                    <small>Please check your internet connection and refresh the page.</small>
                </div>
            `;
        }
    }
}

function initializeDrawControls() {
    if (!map || typeof L.Control.Draw === 'undefined') {
        console.warn('Leaflet Draw plugin not loaded');
        return;
    }

    const drawControl = new L.Control.Draw({
        edit: {
            featureGroup: currentDrawnItems,
            remove: true
        },
        draw: {
            polygon: {
                allowIntersection: false,
                drawError: {
                    color: '#e1e100',
                    message: '<strong>Error:</strong> Shape edges cannot cross!'
                },
                shapeOptions: {
                    color: '#28a745',
                    weight: 2,
                    fillOpacity: 0.3
                }
            },
            rectangle: false,
            circle: false,
            circlemarker: false,
            marker: false,
            polyline: false
        }
    });

    map.addControl(drawControl);

    // Handle draw events
    map.on('draw:created', function(e) {
        const layer = e.layer;
        currentDrawnItems.addLayer(layer);

        if (e.layerType === 'polygon') {
            handlePolygonDrawn(layer);
        }
    });

    map.on('draw:edited', function(e) {
        const layers = e.layers;
        layers.eachLayer(function(layer) {
            if (layer instanceof L.Polygon) {
                handlePolygonEdited(layer);
            }
        });
    });
}

function loadMapData() {
    // Load wilayah data first
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                response.data.forEach(function(wilayah) {
                    addWilayahToMap(wilayah);
                });

                // Fit map to show all regions
                if (wilayahLayers.length > 0) {
                    const group = new L.featureGroup(wilayahLayers);
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            } else {
                console.error('Error loading wilayah data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading map data:', error);
            showNotification('Gagal memuat data wilayah', 'error');
        }
    });

    // Load markers
    loadMajelisMarkers();
    loadPetugasMarkers();
}

function loadMajelisMarkers() {
    // Clear existing markers
    majelisMarkers.forEach(marker => {
        if (map && map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });
    majelisMarkers = [];

    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/majelis.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success && response.data) {
                response.data.forEach(function(majelis) {
                    if (!majelis.latitude || !majelis.longitude) {
                        console.warn('Invalid coordinates for majelis:', majelis.nama_majelis);
                        return;
                    }

                    const marker = L.marker([parseFloat(majelis.latitude), parseFloat(majelis.longitude)], {
                        icon: window.icons ? window.icons.majelis : null
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="popup-content">
                            <h6><i class="fas fa-mosque me-2"></i>${majelis.nama_majelis}</h6>
                            <p class="mb-1"><strong>Alamat:</strong> ${majelis.alamat || '-'}</p>
                            <p class="mb-1"><strong>Wilayah:</strong> ${majelis.nama_wilayah || '-'}</p>
                            ${typeof editMajelis === 'function' ? `
                                <button class="btn btn-sm btn-primary mt-2" onclick="editMajelis(${majelis.id})">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                            ` : ''}
                        </div>
                    `);

                    majelisMarkers.push(marker);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading majelis markers:', error);
            showNotification('Gagal memuat data majelis', 'error');
        }
    });
}

function loadPetugasMarkers() {
    // Clear existing markers
    petugasMarkers.forEach(marker => {
        if (map && map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });
    petugasMarkers = [];

    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/petugas.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success && response.data) {
                response.data.forEach(function(petugas) {
                    if (!petugas.latitude || !petugas.longitude) {
                        console.warn('Invalid coordinates for petugas:', petugas.nama);
                        return;
                    }

                    const marker = L.marker([parseFloat(petugas.latitude), parseFloat(petugas.longitude)], {
                        icon: window.icons ? window.icons.petugas : null
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="popup-content">
                            <h6><i class="fas fa-user-tie me-2"></i>${petugas.nama}</h6>
                            <p class="mb-1"><strong>Telepon:</strong> ${petugas.telepon || '-'}</p>
                            <p class="mb-1"><strong>Wilayah:</strong> ${petugas.nama_wilayah || '-'}</p>
                            ${typeof editPetugas === 'function' ? `
                                <button class="btn btn-sm btn-primary mt-2" onclick="editPetugas(${petugas.id})">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                            ` : ''}
                        </div>
                    `);

                    petugasMarkers.push(marker);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading petugas markers:', error);
            showNotification('Gagal memuat data petugas', 'error');
        }
    });
}

function addWilayahToMap(wilayah) {
    if (!wilayah.polygon || !map) return;

    try {
        const coordinates = JSON.parse(wilayah.polygon);
        if (!Array.isArray(coordinates)) {
            console.error('Invalid polygon data:', wilayah.polygon);
            return;
        }

        const latLngs = coordinates.map(coord => {
            if (!Array.isArray(coord) || coord.length < 2) {
                throw new Error('Invalid coordinate format');
            }
            return [coord[1], coord[0]]; // Note: GeoJSON is [lon, lat]
        });

        const polygon = L.polygon(latLngs, {
            color: '#28a745',
            weight: 2,
            fillOpacity: 0.2,
            fillColor: '#28a745'
        }).addTo(map);

        polygon.bindPopup(`
            <div class="popup-content">
                <h6><i class="fas fa-map-marked-alt me-2"></i>${wilayah.nama_wilayah}</h6>
                ${USER_ROLE === 'admin' && typeof editWilayah === 'function' ? `
                    <button class="btn btn-sm btn-primary me-2" onclick="editWilayah(${wilayah.id})">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteWilayah(${wilayah.id})">
                        <i class="fas fa-trash me-1"></i>Hapus
                    </button>
                ` : ''}
            </div>
        `);

        wilayahLayers.push(polygon);
    } catch (error) {
        console.error('Error parsing polygon coordinates for', wilayah.nama_wilayah, ':', error);
    }
}

function handlePolygonDrawn(layer) {
    const coordinates = layer.getLatLngs()[0].map(latlng => [latlng.lng, latlng.lat]);
    const polygonString = JSON.stringify(coordinates);

    // Check if the modal and elements exist
    if (typeof $('#wilayahPolygon').val === 'function') {
        $('#wilayahPolygon').val(polygonString);
    }

    // Show modal to create new wilayah
    if (typeof $('#wilayahModal').modal === 'function') {
        $('#wilayahModalLabel').text('Tambah Wilayah Baru');
        $('#wilayahModal').modal('show');
    }
}

function handlePolygonEdited(layer) {
    const coordinates = layer.getLatLngs()[0].map(latlng => [latlng.lng, latlng.lat]);
    const polygonString = JSON.stringify(coordinates);

    // Find wilayah ID from layer and update
    // This would require storing wilayah ID with the layer
    console.log('Polygon edited:', polygonString);
}

function refreshMap() {
    if (typeof showLoading === 'function') {
        showLoading();
    }

    // Clear all layers
    wilayahLayers.forEach(layer => {
        if (map && map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });
    wilayahLayers = [];

    // Reload data
    loadMapData();

    setTimeout(function() {
        if (typeof hideLoading === 'function') {
            hideLoading();
        }
        if (typeof showNotification === 'function') {
            showNotification('Peta berhasil diperbarui', 'success');
        }
    }, 1000);
}

function loadWilayahOptions() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                const select = $('#wilayahFilter');
                if (select.length) {
                    select.find('option:not(:first)').remove();

                    response.data.forEach(function(wilayah) {
                        select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                    });

                    // Set selected wilayah for regional users
                    if (USER_WILAYAH) {
                        select.val(USER_WILAYAH).prop('disabled', true);
                    }
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading wilayah options:', error);
        }
    });
}

function updateStatistics() {
    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/statistics.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success && response.data) {
                const totalMajelisEl = $('#totalMajelis');
                const totalPetugasEl = $('#totalPetugas');
                const totalPenugasanEl = $('#totalPenugasan');

                if (totalMajelisEl.length) {
                    totalMajelisEl.text(response.data.total_majelis || 0);
                }
                if (totalPetugasEl.length) {
                    totalPetugasEl.text(response.data.total_petugas || 0);
                }
                if (totalPenugasanEl.length) {
                    totalPenugasanEl.text(response.data.total_penugasan || 0);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading statistics:', error);
        }
    });
}

function toggleFullscreen(elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        console.error('Element not found:', elementId);
        return;
    }

    if (!document.fullscreenElement) {
        element.classList.add('fullscreen');
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }

        // Resize map after entering fullscreen
        setTimeout(function() {
            if (map) {
                map.invalidateSize();
            }
        }, 100);
    } else {
        element.classList.remove('fullscreen');
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }

        // Resize map after exiting fullscreen
        setTimeout(function() {
            if (map) {
                map.invalidateSize();
            }
        }, 100);
    }
}

// Handle filter form submission
$(document).ready(function() {
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();

        // Reload markers based on filter
        loadMajelisMarkers();
        loadPetugasMarkers();
        updateStatistics();

        // Reload assignments table
        if (typeof loadAssignments === 'function') {
            loadAssignments();
        }
    });
});

// Show notification function
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' :
                      type === 'error' ? 'alert-danger' : 'alert-info';

    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(alert);

    // Auto-remove after 5 seconds
    setTimeout(function() {
        alert.fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}