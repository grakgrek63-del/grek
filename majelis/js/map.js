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
    initializeMap();
    loadMapData();
    loadWilayahOptions();
    updateStatistics();
});

function initializeMap() {
    // Check if map container exists
    if (!document.getElementById('map')) {
        console.error('Map container not found');
        return;
    }

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded');
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
    } catch (error) {
        console.error('Error initializing map:', error);
    }

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
}

function initializeDrawControls() {
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
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
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
            }
        }
    });

    loadMajelisMarkers();
    loadPetugasMarkers();
}

function loadMajelisMarkers() {
    // Clear existing markers
    majelisMarkers.forEach(marker => map.removeLayer(marker));
    majelisMarkers = [];

    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/majelis.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        success: function(response) {
            if (response.success) {
                response.data.forEach(function(majelis) {
                    const marker = L.marker([majelis.latitude, majelis.longitude], {
                        icon: window.icons.majelis
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="popup-content">
                            <h6><i class="fas fa-mosque me-2"></i>${majelis.nama_majelis}</h6>
                            <p class="mb-1"><strong>Alamat:</strong> ${majelis.alamat || '-'}</p>
                            <p class="mb-1"><strong>Wilayah:</strong> ${majelis.nama_wilayah || '-'}</p>
                            <button class="btn btn-sm btn-primary" onclick="editMajelis(${majelis.id})">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                        </div>
                    `);

                    majelisMarkers.push(marker);
                });
            }
        }
    });
}

function loadPetugasMarkers() {
    // Clear existing markers
    petugasMarkers.forEach(marker => map.removeLayer(marker));
    petugasMarkers = [];

    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/petugas.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        success: function(response) {
            if (response.success) {
                response.data.forEach(function(petugas) {
                    const marker = L.marker([petugas.latitude, petugas.longitude], {
                        icon: window.icons.petugas
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="popup-content">
                            <h6><i class="fas fa-user-tie me-2"></i>${petugas.nama}</h6>
                            <p class="mb-1"><strong>Telepon:</strong> ${petugas.telepon || '-'}</p>
                            <p class="mb-1"><strong>Wilayah:</strong> ${petugas.nama_wilayah || '-'}</p>
                            <button class="btn btn-sm btn-primary" onclick="editPetugas(${petugas.id})">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                        </div>
                    `);

                    petugasMarkers.push(marker);
                });
            }
        }
    });
}

function addWilayahToMap(wilayah) {
    if (!wilayah.polygon) return;

    try {
        const coordinates = JSON.parse(wilayah.polygon);
        const latLngs = coordinates.map(coord => [coord[1], coord[0]]); // Note: GeoJSON is [lon, lat]

        const polygon = L.polygon(latLngs, {
            color: '#28a745',
            weight: 2,
            fillOpacity: 0.2,
            fillColor: '#28a745'
        }).addTo(map);

        polygon.bindPopup(`
            <div class="popup-content">
                <h6><i class="fas fa-map-marked-alt me-2"></i>${wilayah.nama_wilayah}</h6>
                ${USER_ROLE === 'admin' ? `
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
        console.error('Error parsing polygon coordinates:', error);
    }
}

function handlePolygonDrawn(layer) {
    const coordinates = layer.getLatLngs()[0].map(latlng => [latlng.lng, latlng.lat]);
    const polygonString = JSON.stringify(coordinates);

    // Show modal to create new wilayah
    $('#wilayahPolygon').val(polygonString);
    $('#wilayahModalLabel').text('Tambah Wilayah Baru');
    $('#wilayahModal').modal('show');
}

function handlePolygonEdited(layer) {
    const coordinates = layer.getLatLngs()[0].map(latlng => [latlng.lng, latlng.lat]);
    const polygonString = JSON.stringify(coordinates);

    // Find wilayah ID from layer and update
    // This would require storing wilayah ID with the layer
    console.log('Polygon edited:', polygonString);
}

function refreshMap() {
    showLoading();

    // Clear all layers
    wilayahLayers.forEach(layer => map.removeLayer(layer));
    wilayahLayers = [];

    // Reload data
    loadMapData();

    setTimeout(function() {
        hideLoading();
        showNotification('Peta berhasil diperbarui', 'success');
    }, 1000);
}

function loadWilayahOptions() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#wilayahFilter');
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
    });
}

function updateStatistics() {
    const wilayahId = $('#wilayahFilter').val();

    $.ajax({
        url: 'api/statistics.php',
        method: 'GET',
        data: { wilayah_id: wilayahId || '' },
        success: function(response) {
            if (response.success) {
                $('#totalMajelis').text(response.data.total_majelis || 0);
                $('#totalPetugas').text(response.data.total_petugas || 0);
                $('#totalPenugasan').text(response.data.total_penugasan || 0);
            }
        }
    });
}

function toggleFullscreen(elementId) {
    const element = document.getElementById(elementId);

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
            map.invalidateSize();
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
            map.invalidateSize();
        }, 100);
    }
}

// Handle filter form submission
$('#filterForm').on('submit', function(e) {
    e.preventDefault();

    // Reload markers based on filter
    loadMajelisMarkers();
    loadPetugasMarkers();
    updateStatistics();

    // Reload assignments table
    loadAssignments();
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