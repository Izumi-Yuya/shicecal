# Performance Benchmark Report

**Generated:** 2025-09-09T02:36:24.237071Z
**Environment:** local
**PHP Version:** 8.2.29
**Laravel Version:** 9.52.20

## Operation Performance

| Operation | Time (ms) | Memory (MB) | Queries | Status |
|-----------|-----------|-------------|---------|--------|
| database.facility_index | 9.24 | 2 | 4 | ✅ |
| database.facility_show | 3.3 | 0 | 10 | ✅ |
| database.land_info_calculations | 0.3 | 0 | 11 | ✅ |
| database.facility_statistics | 0.48 | 0 | 16 | ✅ |
| database.facility_search | 0.35 | 0 | 18 | ✅ |
| services.facility_create | 17.32 | 0 | 21 | ✅ |
| services.export_csv_generation | 0.68 | 0 | 24 | ✅ |
| services.activity_log_creation | 4.19 | 0 | 26 | ✅ |
| cache.write_operations | 11.4 | 0 | 26 | ✅ |
| cache.read_operations | 2.41 | 0 | 26 | ✅ |
| cache.complex_data | 2.09 | 0 | 28 | ✅ |

## Memory Usage Analysis

| Operation | Used (MB) | Peak (MB) | Status |
|-----------|-----------|-----------|--------|
| memory.large_facility_collection | 0 | 0 | ✅ |
| memory.chunked_processing | 0 | 0 | ✅ |
| memory.file_processing | 0 | 0 | ✅ |

## Performance Recommendations

⚠️ **database.land_info_calculations** has many queries (11) - consider eager loading
⚠️ **database.facility_statistics** has many queries (16) - consider eager loading
⚠️ **database.facility_search** has many queries (18) - consider eager loading
⚠️ **services.facility_create** has many queries (21) - consider eager loading
⚠️ **services.export_csv_generation** has many queries (24) - consider eager loading
⚠️ **services.activity_log_creation** has many queries (26) - consider eager loading
⚠️ **cache.write_operations** has many queries (26) - consider eager loading
⚠️ **cache.read_operations** has many queries (26) - consider eager loading
⚠️ **cache.complex_data** has many queries (28) - consider eager loading
