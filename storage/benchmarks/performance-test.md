# Performance Benchmark Report

**Generated:** 2025-09-09T02:31:51.677492Z
**Environment:** local
**PHP Version:** 8.2.29
**Laravel Version:** 9.52.20

## Operation Performance

| Operation | Time (ms) | Memory (MB) | Queries | Status |
|-----------|-----------|-------------|---------|--------|
| database.facility_index | 15.78 | 2 | 4 | ✅ |
| database.facility_show | 0.88 | 0 | 7 | ❌ |
| database.land_info_calculations | 0.41 | 0 | 7 | ❌ |
| database.facility_statistics | 0.25 | 0 | 11 | ✅ |
| database.facility_search | 0.6 | 0 | 13 | ✅ |
| services.facility_create | 21.57 | 0 | 14 | ❌ |
| services.export_csv_generation | 0.76 | 0 | 17 | ✅ |
| services.activity_log_creation | 5.15 | 0 | 19 | ✅ |
| cache.write_operations | 20.21 | 0 | 19 | ✅ |
| cache.read_operations | 6.94 | 0 | 19 | ✅ |
| cache.complex_data | 7.21 | 0 | 21 | ✅ |

## Memory Usage Analysis

| Operation | Used (MB) | Peak (MB) | Status |
|-----------|-----------|-----------|--------|
| memory.large_facility_collection | 0 | 0 | ❌ |
| memory.chunked_processing | 0 | 0 | ✅ |
| memory.file_processing | 0 | 0 | ✅ |

## Performance Recommendations

⚠️ **database.facility_statistics** has many queries (11) - consider eager loading
⚠️ **database.facility_search** has many queries (13) - consider eager loading
⚠️ **services.facility_create** has many queries (14) - consider eager loading
⚠️ **services.export_csv_generation** has many queries (17) - consider eager loading
⚠️ **services.activity_log_creation** has many queries (19) - consider eager loading
⚠️ **cache.write_operations** has many queries (19) - consider eager loading
⚠️ **cache.read_operations** has many queries (19) - consider eager loading
⚠️ **cache.complex_data** has many queries (21) - consider eager loading
