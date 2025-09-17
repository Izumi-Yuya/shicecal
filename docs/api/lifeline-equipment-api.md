# Lifeline Equipment API Documentation

## Overview

The Lifeline Equipment API provides comprehensive endpoints for managing all equipment categories (electrical, gas, water, elevator, HVAC/lighting) within the Shise-Cal facility management system.

## Base URL

All endpoints are nested under the facility resource:
```
/facilities/{facility}/lifeline-equipment/
```

## Authentication & Authorization

- All endpoints require authentication
- View permissions: Users must have facility view access
- Update permissions: Users must have facility edit access
- Authorization is handled through the `LifelineEquipmentPolicy`

## API Response Format

All endpoints return a unified JSON response format:

```json
{
  "success": true|false,
  "message": "Response message",
  "data": {
    // Response data
  },
  "meta": {
    "timestamp": "2025-09-17T14:15:30.123456Z",
    "user_id": 123,
    "request_id": "unique-request-id"
  },
  "errors": {
    // Error details (only present when success is false)
  },
  "error_code": "ERROR_CODE" // Optional error code
}
```

## Endpoints

### 1. Get All Equipment Data

**GET** `/facilities/{facility}/lifeline-equipment/`

Returns all lifeline equipment data for a facility across all categories.

**Response:**
```json
{
  "success": true,
  "data": {
    "facility_id": 123,
    "facility_name": "Sample Facility",
    "equipment_data": {
      "electrical": { /* electrical equipment data */ },
      "gas": { /* gas equipment data */ },
      "water": { /* water equipment data */ },
      "elevator": { /* elevator equipment data */ },
      "hvac_lighting": { /* hvac/lighting equipment data */ }
    },
    "categories": {
      "electrical": "電気",
      "gas": "ガス",
      "water": "水道",
      "elevator": "エレベーター",
      "hvac_lighting": "空調・照明"
    }
  }
}
```

### 2. Get Multiple Categories Data

**POST** `/facilities/{facility}/lifeline-equipment/multiple`

Returns equipment data for specified categories only.

**Request Body:**
```json
{
  "categories": ["electrical", "gas", "water"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "facility_id": 123,
    "equipment_data": {
      "electrical": { /* electrical equipment data */ },
      "gas": { /* gas equipment data */ },
      "water": { /* water equipment data */ }
    },
    "requested_categories": ["electrical", "gas", "water"]
  }
}
```

### 3. Bulk Update Equipment Data

**PUT** `/facilities/{facility}/lifeline-equipment/`

Updates multiple equipment categories in a single transaction.

**Request Body:**
```json
{
  "equipment_data": [
    {
      "category": "electrical",
      "data": {
        "basic_info": {
          "electrical_contractor": "Tokyo Electric Co.",
          "safety_management_company": "Safety Corp"
        }
      }
    },
    {
      "category": "gas",
      "data": {
        "basic_info": {
          "gas_supplier": "Tokyo Gas Co."
        }
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "2個のカテゴリを正常に更新しました。",
  "data": {
    "updated_categories": ["electrical", "gas"],
    "success_count": 2,
    "results": {
      "electrical": { /* update result */ },
      "gas": { /* update result */ }
    }
  }
}
```

### 4. Get Equipment Summary

**GET** `/facilities/{facility}/lifeline-equipment/summary`

Returns a summary of equipment configuration status across all categories.

**Response:**
```json
{
  "success": true,
  "data": {
    "facility_id": 123,
    "facility_name": "Sample Facility",
    "summary": {
      "electrical": {
        "category_name": "電気",
        "has_data": true,
        "status": "active",
        "status_display": "アクティブ",
        "last_updated": "2025-09-17T14:15:30.123456Z",
        "updated_by": "Admin User"
      },
      // ... other categories
    },
    "statistics": {
      "total_categories": 5,
      "configured_categories": 2,
      "completion_percentage": 40.0
    }
  }
}
```

### 5. Validate Data Consistency

**POST** `/facilities/{facility}/lifeline-equipment/validate-consistency`

Validates data consistency across equipment categories and provides recommendations.

**Request Body:**
```json
{
  "equipment_data": {
    "electrical": {
      "basic_info": {
        "electrical_contractor": "Company A",
        "safety_management_company": "Safety Company A"
      }
    },
    "gas": {
      "basic_info": {
        "gas_supplier": "Company B",
        "maintenance_company": "Safety Company B"
      }
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "facility_id": 123,
    "validation_status": "warnings_found",
    "consistency_issues": [],
    "warnings": [
      {
        "type": "maintenance_company_inconsistency",
        "message": "複数の異なる保守会社が設定されています。",
        "details": {
          "electrical": "Safety Company A",
          "gas": "Safety Company B"
        }
      }
    ],
    "recommendations": [
      {
        "priority": "medium",
        "action": "保守会社の情報を確認し、必要に応じて統一してください。",
        "category": "data_consistency"
      }
    ]
  }
}
```

### 6. Get Available Categories

**GET** `/facilities/{facility}/lifeline-equipment/categories`

Returns information about all available equipment categories and their configuration status.

**Response:**
```json
{
  "success": true,
  "data": {
    "facility_id": 123,
    "categories": {
      "electrical": {
        "key": "electrical",
        "name": "電気",
        "is_configured": true,
        "status": "active",
        "status_display": "アクティブ",
        "has_detailed_implementation": true,
        "last_updated": "2025-09-17T14:15:30.123456Z"
      },
      // ... other categories
    },
    "available_statuses": {
      "active": "アクティブ",
      "inactive": "非アクティブ",
      "decommissioned": "廃止",
      "draft": "下書き",
      "pending_approval": "承認待ち",
      "approved": "承認済み",
      "rejected": "却下"
    }
  }
}
```

### 7. Individual Category Operations

**GET** `/facilities/{facility}/lifeline-equipment/{category}`

Returns data for a specific equipment category.

**PUT** `/facilities/{facility}/lifeline-equipment/{category}`

Updates data for a specific equipment category.

## Error Handling

### Validation Errors (422)
```json
{
  "success": false,
  "message": "入力内容に誤りがあります。",
  "errors": {
    "categories.0": ["The selected categories.0 is invalid."]
  },
  "error_code": "VALIDATION_ERROR"
}
```

### Authorization Errors (403)
```json
{
  "success": false,
  "message": "この施設のライフライン設備情報を編集する権限がありません。"
}
```

### System Errors (500)
```json
{
  "success": false,
  "message": "システムエラーが発生しました。"
}
```

## Data Consistency Features

The API includes built-in data consistency validation that:

1. **Contractor Consistency**: Checks for inconsistencies in contractor information across categories
2. **Maintenance Company Consistency**: Validates maintenance company information
3. **Critical Equipment Validation**: Ensures essential equipment categories (electrical, gas, water) have basic information
4. **Recommendations**: Provides actionable recommendations for improving data consistency

## Transaction Safety

- Bulk update operations use database transactions
- If any category update fails, all changes are rolled back
- Individual category updates are atomic operations
- Comprehensive error reporting for failed operations

## Performance Considerations

- All endpoints include appropriate database indexing
- Bulk operations are optimized for multiple category updates
- Response caching is available for read operations
- Pagination is not required as equipment data per facility is typically small

## Integration with Existing Systems

The API seamlessly integrates with:

- **Activity Logging**: All changes are logged through the existing activity log system
- **Comment System**: Equipment data supports the existing comment functionality
- **Permission System**: Uses the established facility-based permission model
- **Export System**: Equipment data can be included in facility exports