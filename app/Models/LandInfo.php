<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandInfo extends Model
{
    use HasFactory;

    protected $table = 'land_info';

    protected $fillable = [
        'facility_id',
        'ownership_type',
        'parking_spaces',
        'site_area_sqm',
        'site_area_tsubo',
        'purchase_price',
        'unit_price_per_tsubo',
        'monthly_rent',
        'contract_start_date',
        'contract_end_date',
        'auto_renewal',
        'contract_period_text',
        'management_company_name',
        'management_company_postal_code',
        'management_company_address',
        'management_company_building',
        'management_company_phone',
        'management_company_fax',
        'management_company_email',
        'management_company_url',
        'management_company_notes',
        'owner_name',
        'owner_postal_code',
        'owner_address',
        'owner_building',
        'owner_phone',
        'owner_fax',
        'owner_email',
        'owner_url',
        'owner_notes',
        'lease_contract_pdf_path',
        'lease_contract_pdf_name',
        'registry_pdf_path',
        'registry_pdf_name',
        'notes',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'site_area_sqm' => 'decimal:2',
        'site_area_tsubo' => 'decimal:2',
        'purchase_price' => 'integer',
        'unit_price_per_tsubo' => 'integer',
        'monthly_rent' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Valid status values
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    // Relationships
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(File::class, 'facility_id', 'facility_id')
            ->whereNotNull('land_document_type');
    }

    // Accessors
    public function getFormattedPurchasePriceAttribute(): string
    {
        return $this->purchase_price ? number_format($this->purchase_price) : '';
    }

    public function getFormattedUnitPricePerTsuboAttribute(): string
    {
        return $this->unit_price_per_tsubo ? number_format($this->unit_price_per_tsubo) : '';
    }

    public function getFormattedMonthlyRentAttribute(): string
    {
        return $this->monthly_rent ? number_format($this->monthly_rent) : '';
    }

    public function getFormattedSiteAreaSqmAttribute(): string
    {
        return $this->site_area_sqm ? number_format($this->site_area_sqm, 2).'㎡' : '';
    }

    public function getFormattedSiteAreaTsuboAttribute(): string
    {
        return $this->site_area_tsubo ? number_format($this->site_area_tsubo, 2).'坪' : '';
    }

    public function getJapaneseContractStartDateAttribute(): string
    {
        return $this->contract_start_date ?
            $this->contract_start_date->format('Y年n月j日') : '';
    }

    public function getJapaneseContractEndDateAttribute(): string
    {
        return $this->contract_end_date ?
            $this->contract_end_date->format('Y年n月j日') : '';
    }

    /**
     * Get structured data for table display
     */
    public function getTableDisplayData(): array
    {
        $calculationService = app(\App\Services\LandInfo\LandInfoCalculationService::class);
        
        return [
            'basic_info' => $this->getBasicInfoForTable(),
            'area_info' => $this->getAreaInfoForTable(),
            'ownership_info' => $this->getOwnershipInfoForTable($calculationService),
            'documents' => $this->getDocumentsForTable(),
            'meta' => $this->getMetaInfoForTable()
        ];
    }

    /**
     * Get basic information for table display
     */
    private function getBasicInfoForTable(): array
    {
        return [
            'ownership_type' => $this->ownership_type,
            'parking_spaces' => $this->parking_spaces,
        ];
    }

    /**
     * Get area information for table display
     */
    private function getAreaInfoForTable(): array
    {
        return [
            'site_area_sqm' => $this->site_area_sqm,
            'site_area_tsubo' => $this->site_area_tsubo,
        ];
    }

    /**
     * Get ownership information with calculated values for table display
     */
    private function getOwnershipInfoForTable($calculationService): array
    {
        $data = [
            'purchase_price' => $this->purchase_price,
            'monthly_rent' => $this->monthly_rent,
            'contract_start_date' => $this->contract_start_date,
            'contract_end_date' => $this->contract_end_date,
        ];
        
        // Add calculated values
        $data['tsubo_unit_price'] = $calculationService->calculateTsuboUnitPrice($this);
        $data['contract_years'] = $calculationService->calculateContractYears($this);
        
        return $data;
    }

    /**
     * Get documents information for table display
     */
    public function getDocumentsForTable(): array
    {
        $documents = [];
        
        // Rental contract document
        if ($this->hasDocument('lease_contract')) {
            $documents['lease_contract'] = [
                'label' => 'PDF',
                'url' => $this->getDocumentUrl('lease_contract'),
                'available' => true
            ];
        } else {
            $documents['lease_contract'] = [
                'label' => '未登録',
                'url' => null,
                'available' => false
            ];
        }
        
        // Registry document
        if ($this->hasDocument('property_register')) {
            $documents['property_register'] = [
                'label' => 'PDF',
                'url' => $this->getDocumentUrl('property_register'),
                'available' => true
            ];
        } else {
            $documents['property_register'] = [
                'label' => '未登録',
                'url' => null,
                'available' => false
            ];
        }
        
        return $documents;
    }

    /**
     * Get meta information for table display
     */
    public function getMetaInfoForTable(): array
    {
        return [
            'last_updated' => $this->updated_at,
            'created_at' => $this->created_at,
            'updated_by' => $this->updater?->name,
            'approval_status' => $this->status,
            'needs_attention' => $this->updated_at->diffInMonths(now()) > 6
        ];
    }

    /**
     * Check if the specified document type exists
     */
    public function hasDocument(string $documentType): bool
    {
        return $this->documents()
            ->where('land_document_type', $documentType)
            ->exists();
    }

    /**
     * Get the URL for the specified document type
     */
    public function getDocumentUrl(string $documentType): ?string
    {
        $file = $this->documents()
            ->where('land_document_type', $documentType)
            ->first();
            
        return $file ? route('files.download', $file->id) : null;
    }
}
