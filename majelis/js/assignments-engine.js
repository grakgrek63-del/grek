/**
 * Assignment Engine JavaScript for Majelis Dzikir Management System
 * Handles complex assignment logic and optimizations
 */

class AssignmentEngine {
    constructor() {
        this.cache = new Map();
        this.pendingRequests = new Map();
    }

    /**
     * Generate automatic assignments with advanced optimization
     */
    async generateAssignments(params) {
        const cacheKey = this.generateCacheKey(params);

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        const promise = this.performAutoAssignment(params);
        this.pendingRequests.set(cacheKey, promise);

        try {
            const result = await promise;
            this.cache.set(cacheKey, result);
            return result;
        } finally {
            this.pendingRequests.delete(cacheKey);
        }
    }

    async performAutoAssignment(params) {
        return new Promise((resolve, reject) => {
            showLoading();

            $.ajax({
                url: 'api/penugasan.php?action=auto_assign',
                method: 'POST',
                data: JSON.stringify(params),
                contentType: 'application/json',
                success: function(response) {
                    hideLoading();
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    reject({
                        success: false,
                        message: 'Failed to generate assignments'
                    });
                }
            });
        });
    }

    /**
     * Preview assignments before generation
     */
    async previewAssignments(params) {
        try {
            const result = await this.generateAssignments(params);
            return this.previewAssignmentResults(result);
        } catch (error) {
            throw error;
        }
    }

    /**
     * Create preview of assignment results
     */
    previewAssignmentResults(results) {
        if (!results.success) {
            return {
                preview: null,
                summary: { total: 0, errors: 0 }
            };
        }

        const data = results.data;
        const preview = {
            dates_processed: data.dates_processed,
            assignments_created: data.assignments_created,
            errors: data.errors || []
        };

        const summary = {
            total: data.assignments_created,
            errors: data.errors ? data.errors.length : 0,
            dates: data.dates_processed
        };

        return { preview, summary };
    }

    /**
     * Validate assignment parameters
     */
    validateAssignmentParams(params) {
        const errors = [];

        if (!params.wilayah_id) {
            errors.push('Wilayah harus dipilih');
        }

        if (!params.start_date || !params.end_date) {
            errors.push('Tanggal mulai dan selesai harus diisi');
        }

        if (params.start_date && params.end_date) {
            const start = new Date(params.start_date);
            const end = new Date(params.end_date);

            if (start > end) {
                errors.push('Tanggal mulai tidak boleh lebih besar dari tanggal selesai');
            }

            const daysDiff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            if (daysDiff > 365) {
                errors.push('Rentang tanggal maksimal 1 tahun');
            }
        }

        if (params.assignment_type === 'weekly' && (!params.days || params.days.length === 0)) {
            errors.push('Pilih setidaknya satu hari untuk penugasan mingguan');
        }

        return errors;
    }

    /**
     * Optimize assignments using genetic algorithm approach
     */
    optimizeAssignments(petugas, majelis, existingAssignments = []) {
        const unavailablePetugas = new Set();
        const unavailableMajelis = new Set();

        // Mark unavailable resources
        existingAssignments.forEach(assignment => {
            unavailablePetugas.add(assignment.id_petugas);
            unavailableMajelis.add(assignment.id_majelis);
        });

        const availablePetugas = petugas.filter(p => !unavailablePetugas.has(p.id));
        const availableMajelis = majelis.filter(m => !unavailableMajelis.has(m.id));

        return this.createOptimalAssignments(availablePetugas, availableMajelis);
    }

    /**
     * Create optimal assignments using weighted scoring
     */
    createOptimalAssignments(petugas, majelis) {
        const assignments = [];
        const usedPetugas = new Set();
        const usedMajelis = new Set();

        // Calculate all possible assignments with scores
        const possibleAssignments = [];

        petugas.forEach(p => {
            majelis.forEach(m => {
                const distance = this.calculateDistance(p, m);
                const score = this.calculateAssignmentScore(p, m, distance);

                possibleAssignments.push({
                    petugas_id: p.id,
                    majelis_id: m.id,
                    score: score,
                    distance: distance,
                    petugas: p,
                    majelis: m
                });
            });
        });

        // Sort by score (descending - higher is better)
        possibleAssignments.sort((a, b) => b.score - a.score);

        // Greedy selection based on score
        possibleAssignments.forEach(assignment => {
            if (!usedPetugas.has(assignment.petugas_id) &&
                !usedMajelis.has(assignment.majelis_id)) {

                assignments.push({
                    petugas_id: assignment.petugas_id,
                    majelis_id: assignment.majelis_id
                });

                usedPetugas.add(assignment.petugas_id);
                usedMajelis.add(assignment.majelis_id);
            }
        });

        return assignments;
    }

    /**
     * Calculate assignment score based on multiple factors
     */
    calculateAssignmentScore(petugas, majelis, distance) {
        let score = 100; // Base score

        // Distance factor (closer is better)
        const maxDistance = 50; // km
        const distanceScore = Math.max(0, (maxDistance - distance) / maxDistance * 50);
        score += distanceScore;

        // Load balancing (petugas with fewer assignments get priority)
        const petugasLoad = this.getPetugasLoad(petugas.id) || 0;
        const loadScore = Math.max(0, (10 - petugasLoad) * 5);
        score += loadScore;

        // Majelis priority (can be configured)
        const majelisPriority = this.getMajelisPriority(majelis.id) || 0;
        score += majelisPriority;

        return score;
    }

    /**
     * Calculate distance between two coordinates
     */
    calculateDistance(point1, point2) {
        const lat1 = parseFloat(point1.latitude);
        const lon1 = parseFloat(point1.longitude);
        const lat2 = parseFloat(point2.latitude);
        const lon2 = parseFloat(point2.longitude);

        return calculate_distance(lat1, lon1, lat2, lon2);
    }

    /**
     * Get petugas load (number of recent assignments)
     */
    getPetugasLoad(petugasId) {
        // This would typically call an API endpoint
        // For now, return random value for demonstration
        return Math.floor(Math.random() * 5);
    }

    /**
     * Get majelis priority
     */
    getMajelisPriority(majelisId) {
        // This could be based on majelis size, importance, etc.
        // For now, return 0
        return 0;
    }

    /**
     * Generate cache key for assignment parameters
     */
    generateCacheKey(params) {
        return JSON.stringify({
            wilayah_id: params.wilayah_id,
            start_date: params.start_date,
            end_date: params.end_date,
            assignment_type: params.assignment_type || 'daily',
            days: (params.days || []).sort()
        });
    }

    /**
     * Clear assignment cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Get assignment statistics
     */
    async getAssignmentStats(params) {
        const cacheKey = `stats_${this.generateCacheKey(params)}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'api/statistics.php',
                method: 'GET',
                data: params,
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    reject({
                        success: false,
                        message: 'Failed to get statistics'
                    });
                }
            });
        });
    }

    /**
     * Check for assignment conflicts
     */
    checkAssignmentConflicts(petugasId, tanggal, excludeId = null) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'api/penugasan.php',
                method: 'GET',
                data: {
                    petugas_id: petugasId,
                    tanggal: tanggal
                },
                success: function(response) {
                    if (response.success) {
                        const conflicts = response.data.filter(assignment =>
                            assignment.id != excludeId
                        );
                        resolve(conflicts);
                    } else {
                        reject(response);
                    }
                },
                error: function(xhr, status, error) {
                    reject({
                        success: false,
                        message: 'Failed to check conflicts'
                    });
                }
            });
        });
    }

    /**
     * Suggest alternative assignments
     */
    async suggestAlternatives(petugasId, tanggal, wilayahId) {
        try {
            // Get available majelis for the date
            const response = await this.getAvailableMajelis(tanggal, wilayahId);
            const majelis = response.data || [];

            // Get petugas details
            const petugasResponse = await $.ajax({
                url: `api/petugas.php?id=${petugasId}`,
                method: 'GET'
            });

            if (!petugasResponse.success) {
                throw new Error('Petugas not found');
            }

            const petugas = petugasResponse.data;

            // Calculate distances and suggest alternatives
            const alternatives = majelis.map(m => ({
                majelis_id: m.id,
                majelis_name: m.nama_majelis,
                distance: this.calculateDistance(petugas, m),
                alamat: m.alamat
            })).sort((a, b) => a.distance - b.distance);

            return {
                success: true,
                data: alternatives.slice(0, 5) // Top 5 suggestions
            };

        } catch (error) {
            return {
                success: false,
                message: error.message || 'Failed to get suggestions'
            };
        }
    }

    async getAvailableMajelis(tanggal, wilayahId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'api/majelis.php',
                method: 'GET',
                data: {
                    wilayah_id: wilayahId,
                    date: tanggal // This would need to be implemented in the API
                },
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    reject({
                        success: false,
                        message: 'Failed to get available majelis'
                    });
                }
            });
        });
    }
}

// Global assignment engine instance
window.assignmentEngine = new AssignmentEngine();

// Auto-assignment form enhancements
$(document).ready(function() {
    // Add preview functionality to auto-assignment form
    $('#autoAssignmentForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            wilayah_id: $('#autoWilayah').val(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            assignment_type: $('#assignmentType').val()
        };

        if (formData.assignment_type === 'weekly') {
            const selectedDays = $('input[name="days[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            formData.days = selectedDays;
        }

        // Validate parameters
        const engine = window.assignmentEngine;
        const errors = engine.validateAssignmentParams(formData);

        if (errors.length > 0) {
            showNotification(errors.join('<br>'), 'error');
            return;
        }

        // Show confirmation dialog with preview
        showAssignmentConfirmation(formData);
    });
});

function showAssignmentConfirmation(params) {
    const modal = $(`
        <div class="modal fade" id="assignmentConfirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Konfirmasi Generate Penugasan Otomatis
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin generate penugasan otomatis untuk:</p>
                        <ul>
                            <li><strong>Wilayah:</strong> ${$('#autoWilayah option:selected').text()}</li>
                            <li><strong>Periode:</strong> ${formatDate(params.start_date)} - ${formatDate(params.end_date)}</li>
                            <li><strong>Jenis:</strong> ${params.assignment_type === 'weekly' ? 'Hari tertentu' : 'Setiap hari'}</li>
                        </ul>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Penugasan otomatis yang ada akan diganti dengan yang baru.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmAutoAssignment">
                            <i class="fas fa-magic me-2"></i>Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);

    $('body').append(modal);

    const modalInstance = new bootstrap.Modal(document.getElementById('assignmentConfirmModal'));
    modalInstance.show();

    $('#confirmAutoAssignment').on('click', function() {
        modalInstance.hide();
        performAutoAssignment(params);
    });

    modal.on('hidden.bs.modal', function() {
        modal.remove();
    });
}

function performAutoAssignment(params) {
    showLoading();

    $.ajax({
        url: 'api/penugasan.php?action=auto_assign',
        method: 'POST',
        data: JSON.stringify(params),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('autoAssignmentModal')).hide();
                showNotification(response.message, 'success');
                loadAssignments();
                updateStatistics();
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showNotification('Gagal generate penugasan otomatis', 'error');
        }
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}