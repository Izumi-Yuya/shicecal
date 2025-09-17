/**
 * Export Module - ES6 Module
 * Handles CSV export functionality including field selection, favorites, and data preview
 */

export class ExportManager {
    constructor() {
        this.facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
        this.fieldCheckboxes = document.querySelectorAll('.field-checkbox');
        this.exportButton = document.getElementById('exportButton');
        this.saveFavoriteButton = document.getElementById('saveFavoriteButton');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateSelectionStatus();
    }

    setupEventListeners() {
    // Monitor checkbox changes
        this.facilityCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateSelectionStatus());
        });

        this.fieldCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateSelectionStatus());
        });

        // Select all/deselect all buttons
        this.setupSelectAllButtons();

        // Favorite functionality
        this.setupFavoriteHandlers();

        // Form submission
        this.setupFormSubmission();
    }

    setupSelectAllButtons() {
        const selectAllFacilities = document.getElementById('selectAllFacilities');
        const deselectAllFacilities = document.getElementById('deselectAllFacilities');
        const selectAllFields = document.getElementById('selectAllFields');
        const deselectAllFields = document.getElementById('deselectAllFields');

        if (selectAllFacilities) {
            selectAllFacilities.addEventListener('click', () => {
                this.facilityCheckboxes.forEach(cb => cb.checked = true);
                this.updateSelectionStatus();
            });
        }

        if (deselectAllFacilities) {
            deselectAllFacilities.addEventListener('click', () => {
                this.facilityCheckboxes.forEach(cb => cb.checked = false);
                this.updateSelectionStatus();
            });
        }

        if (selectAllFields) {
            selectAllFields.addEventListener('click', () => {
                this.fieldCheckboxes.forEach(cb => cb.checked = true);
                this.updateSelectionStatus();
            });
        }

        if (deselectAllFields) {
            deselectAllFields.addEventListener('click', () => {
                this.fieldCheckboxes.forEach(cb => cb.checked = false);
                this.updateSelectionStatus();
            });
        }
    }

    setupFavoriteHandlers() {
    // Save favorite button
        if (this.saveFavoriteButton) {
            this.saveFavoriteButton.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('saveFavoriteModal'));
                modal.show();
            });
        }

        // Save favorite confirmation
        const saveFavoriteConfirm = document.getElementById('saveFavoriteConfirm');
        if (saveFavoriteConfirm) {
            saveFavoriteConfirm.addEventListener('click', () => this.saveFavorite());
        }

        // Load favorites when modal opens
        const favoritesModal = document.getElementById('favoritesModal');
        if (favoritesModal) {
            favoritesModal.addEventListener('show.bs.modal', () => {
                this.loadFavoritesList();
            });
        }
    }

    setupFormSubmission() {
        const csvExportForm = document.getElementById('csvExportForm');
        if (csvExportForm) {
            csvExportForm.addEventListener('submit', (e) => this.handleFormSubmission(e));
        }
    }

    updateSelectionStatus() {
        const selectedFacilities = document.querySelectorAll('.facility-checkbox:checked');
        const selectedFields = document.querySelectorAll('.field-checkbox:checked');

        // Update counts
        const facilitiesCountElement = document.getElementById('selectedFacilitiesCount');
        const fieldsCountElement = document.getElementById('selectedFieldsCount');

        if (facilitiesCountElement) {
            facilitiesCountElement.textContent = selectedFacilities.length;
        }
        if (fieldsCountElement) {
            fieldsCountElement.textContent = selectedFields.length;
        }

        // Update preview
        this.updatePreview(selectedFacilities, selectedFields);

        // Update button states
        const canExport = selectedFacilities.length > 0 && selectedFields.length > 0;
        if (this.exportButton) {
            this.exportButton.disabled = !canExport;
        }
        if (this.saveFavoriteButton) {
            this.saveFavoriteButton.disabled = !canExport;
        }

        // Update data preview
        this.updateDataPreview();
    }

    updatePreview(selectedFacilities, selectedFields) {
    // Update facility names preview
        const facilityNames = Array.from(selectedFacilities).map(cb => {
            const label = document.querySelector(`label[for="${cb.id}"]`);
            return label ? label.querySelector('strong')?.textContent || '' : '';
        });

        // Update field names preview
        const fieldNames = Array.from(selectedFields).map(cb => {
            const label = document.querySelector(`label[for="${cb.id}"]`);
            return label ? label.textContent : '';
        });

        const previewFacilities = document.getElementById('previewFacilities');
        const previewFields = document.getElementById('previewFields');

        if (previewFacilities) {
            previewFacilities.textContent =
        facilityNames.length > 0 ? facilityNames.join(', ') : '未選択';
        }
        if (previewFields) {
            previewFields.textContent =
        fieldNames.length > 0 ? fieldNames.join(', ') : '未選択';
        }
    }

    async updateDataPreview() {
        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
        const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

        const previewContainer = document.getElementById('dataPreviewContainer');
        if (!previewContainer) {
            return;
        }

        if (selectedFacilities.length === 0 || selectedFields.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }

        try {
            const response = await fetch('/export/csv/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    facility_ids: selectedFacilities,
                    export_fields: selectedFields
                })
            });

            const data = await response.json();

            if (data.success) {
                this.displayDataPreview(data.data);
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        } catch (error) {
            console.error('プレビューデータの取得に失敗しました:', error);
            previewContainer.style.display = 'none';
        }
    }

    displayDataPreview(data) {
        const headerRow = document.getElementById('previewTableHeader');
        const bodyElement = document.getElementById('previewTableBody');
        const previewInfo = document.getElementById('previewInfo');

        if (!headerRow || !bodyElement) {
            return;
        }

        // Clear header
        headerRow.innerHTML = '';

        // Add headers
        Object.values(data.fields).forEach(fieldLabel => {
            const th = document.createElement('th');
            th.textContent = fieldLabel;
            headerRow.appendChild(th);
        });

        // Clear body
        bodyElement.innerHTML = '';

        // Add data rows
        data.preview_data.forEach(row => {
            const tr = document.createElement('tr');
            Object.keys(data.fields).forEach(fieldKey => {
                const td = document.createElement('td');
                td.textContent = row[fieldKey] || '';
                tr.appendChild(td);
            });
            bodyElement.appendChild(tr);
        });

        // Update preview info
        if (previewInfo) {
            previewInfo.textContent = `${data.preview_count}件のプレビューを表示中（全${data.total_facilities}件中）`;
        }
    }

    async saveFavorite() {
        const nameInput = document.getElementById('favoriteName');
        if (!nameInput) {
            return;
        }

        const name = nameInput.value.trim();
        if (!name) {
            alert('お気に入り名を入力してください。');
            return;
        }

        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
        const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

        if (selectedFacilities.length === 0 || selectedFields.length === 0) {
            alert('施設と出力項目を選択してください。');
            return;
        }

        try {
            const response = await fetch('/export/csv/favorites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name,
                    facility_ids: selectedFacilities,
                    export_fields: selectedFields
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                nameInput.value = '';
                const modal = bootstrap.Modal.getInstance(document.getElementById('saveFavoriteModal'));
                modal.hide();
                this.loadFavoritesList();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('お気に入り保存エラー:', error);
            alert('お気に入りの保存に失敗しました。');
        }
    }

    async loadFavoritesList() {
        try {
            const response = await fetch('/export/csv/favorites');
            const data = await response.json();

            if (data.success) {
                this.displayFavoritesList(data.data);
            }
        } catch (error) {
            console.error('お気に入り一覧の取得に失敗しました:', error);
        }
    }

    displayFavoritesList(favorites) {
        const container = document.getElementById('favoritesList');
        if (!container) {
            return;
        }

        if (favorites.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">お気に入りがありません。</p>';
            return;
        }

        let html = '<div class="list-group">';
        favorites.forEach(favorite => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${favorite.name}</h6>
                        <small class="text-muted">
                            施設: ${favorite.facility_ids.length}件 | 
                            項目: ${favorite.export_fields.length}項目 | 
                            作成: ${new Date(favorite.created_at).toLocaleDateString()}
                        </small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="window.exportManager.loadFavorite(${favorite.id})">
                            読み込み
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.exportManager.editFavorite(${favorite.id}, '${favorite.name}')">
                            編集
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="window.exportManager.deleteFavorite(${favorite.id})">
                            削除
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    async loadFavorite(id) {
        try {
            const response = await fetch(`/export/csv/favorites/${id}`);
            const data = await response.json();

            if (data.success) {
                const favoriteData = data.data;

                // Clear all checkboxes
                document.querySelectorAll('.facility-checkbox').forEach(cb => cb.checked = false);
                document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = false);

                // Select facilities
                favoriteData.facility_ids.forEach(facilityId => {
                    const checkbox = document.getElementById(`facility_${facilityId}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                // Select fields
                favoriteData.export_fields.forEach(field => {
                    const checkbox = document.getElementById(`field_${field}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                // Update selection status
                this.updateSelectionStatus();

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('favoritesModal'));
                modal.hide();

                // Show warning if some facilities are not accessible
                if (favoriteData.original_facility_count > favoriteData.accessible_facility_count) {
                    alert(`注意: ${favoriteData.original_facility_count - favoriteData.accessible_facility_count}件の施設にアクセス権限がないため、選択から除外されました。`);
                }
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('お気に入りの読み込みに失敗しました:', error);
            alert('お気に入りの読み込みに失敗しました。');
        }
    }

    async editFavorite(id, currentName) {
        const newName = prompt('新しい名前を入力してください:', currentName);
        if (newName && newName.trim() !== '' && newName !== currentName) {
            try {
                const response = await fetch(`/export/csv/favorites/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: newName.trim()
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    this.loadFavoritesList();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('お気に入りの更新に失敗しました:', error);
                alert('お気に入りの更新に失敗しました。');
            }
        }
    }

    async deleteFavorite(id) {
        if (confirm('このお気に入りを削除しますか？')) {
            try {
                const response = await fetch(`/export/csv/favorites/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    this.loadFavoritesList();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('お気に入りの削除に失敗しました:', error);
                alert('お気に入りの削除に失敗しました。');
            }
        }
    }

    handleFormSubmission(e) {
        e.preventDefault();

        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
        const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

        if (selectedFacilities.length === 0 || selectedFields.length === 0) {
            alert('施設と出力項目を選択してください。');
            return;
        }

        // Create a form to submit the data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/csv/generate';

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);

        // Add facility IDs
        selectedFacilities.forEach(facilityId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'facility_ids[]';
            input.value = facilityId;
            form.appendChild(input);
        });

        // Add export fields
        selectedFields.forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'export_fields[]';
            input.value = field;
            form.appendChild(input);
        });

        // Submit the form
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}

/**
 * Global functions for backward compatibility
 * These functions provide a bridge between the old global API and the new ES6 modules
 */
export function loadFavorite(id) {
    if (window.exportManager) {
        window.exportManager.loadFavorite(id);
    }
}

export function editFavorite(id, currentName) {
    if (window.exportManager) {
        window.exportManager.editFavorite(id, currentName);
    }
}

export function deleteFavorite(id) {
    if (window.exportManager) {
        window.exportManager.deleteFavorite(id);
    }
}

/**
 * Initialize export manager
 * @returns {ExportManager} - Export manager instance
 */
export function initializeExportManager() {
    const manager = new ExportManager();
    // Make it globally accessible for backward compatibility
    window.exportManager = manager;

    // Expose global functions for backward compatibility
    window.loadFavorite = loadFavorite;
    window.editFavorite = editFavorite;
    window.deleteFavorite = deleteFavorite;

    return manager;
}
