{{-- View Toggle Component --}}
<div class="view-toggle-container mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div class="view-toggle-buttons">
            <div class="btn-group" role="group" aria-label="表示形式切り替え">
                <input type="radio" class="btn-check" name="viewMode" id="cardView" value="card" 
                       {{ ($viewMode ?? 'card') === 'card' ? 'checked' : '' }}>
                <label class="btn btn-outline-primary" for="cardView">
                    <i class="fas fa-th-large me-2"></i>カード形式
                </label>
                
                <input type="radio" class="btn-check" name="viewMode" id="tableView" value="table"
                       {{ ($viewMode ?? 'card') === 'table' ? 'checked' : '' }}>
                <label class="btn btn-outline-primary" for="tableView">
                    <i class="fas fa-table me-2"></i>テーブル形式
                </label>
            </div>
        </div>
        
        <div class="view-toggle-info">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                表示形式を選択してください
            </small>
        </div>
    </div>
</div>

{{-- JavaScript functionality is handled by the facility-view-toggle.js module --}}

<style>
.view-toggle-container {
    background: var(--light-color);
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.view-toggle-buttons .btn-group {
    box-shadow: var(--shadow-sm);
}

.view-toggle-buttons .btn {
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: var(--transition-fast);
}

.view-toggle-buttons .btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.view-toggle-buttons .btn-check:checked + .btn {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    box-shadow: var(--shadow-md);
}

.view-toggle-info {
    opacity: 0.8;
}

@media (max-width: 768px) {
    .view-toggle-container .d-flex {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .view-toggle-info {
        text-align: center;
    }
    
    .view-toggle-buttons {
        align-self: center;
    }
}
</style>