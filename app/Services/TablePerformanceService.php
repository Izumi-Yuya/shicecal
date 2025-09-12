<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Service for table performance optimizations
 * 
 * This service handles performance optimizations for table rendering,
 * including DOM optimization, lazy loading, and memory management.
 */
class TablePerformanceService
{
    /**
     * Maximum rows to render without pagination
     */
    private const MAX_ROWS_WITHOUT_PAGINATION = 100;
    
    /**
     * Lazy loading threshold (rows)
     */
    private const LAZY_LOADING_THRESHOLD = 50;
    
    /**
     * Virtual scrolling threshold (rows)
     */
    private const VIRTUAL_SCROLL_THRESHOLD = 200;
    
    /**
     * Cache key for performance metrics
     */
    private const PERFORMANCE_CACHE_PREFIX = 'table_performance_';

    /**
     * Optimize table data for rendering
     *
     * @param array $data The table data
     * @param array $config The table configuration
     * @param array $options Additional options
     * @return array Optimized data and configuration
     */
    public function optimizeTableData(array $data, array $config, array $options = []): array
    {
        $dataCount = count($data);
        $optimizations = [];
        
        // Determine optimization strategy based on data size
        if ($dataCount > self::VIRTUAL_SCROLL_THRESHOLD) {
            $optimizations['strategy'] = 'virtual_scroll';
            $optimizations['chunk_size'] = 50;
            $optimizations['render_buffer'] = 10;
        } elseif ($dataCount > self::LAZY_LOADING_THRESHOLD) {
            $optimizations['strategy'] = 'lazy_loading';
            $optimizations['initial_load'] = self::LAZY_LOADING_THRESHOLD;
            $optimizations['load_increment'] = 25;
        } elseif ($dataCount > self::MAX_ROWS_WITHOUT_PAGINATION) {
            $optimizations['strategy'] = 'pagination';
            $optimizations['per_page'] = self::MAX_ROWS_WITHOUT_PAGINATION;
        } else {
            $optimizations['strategy'] = 'full_render';
        }
        
        // Apply DOM optimizations
        $optimizations['dom'] = $this->getDomOptimizations($config, $dataCount);
        
        // Apply CSS optimizations
        $optimizations['css'] = $this->getCssOptimizations($config);
        
        // Apply JavaScript optimizations
        $optimizations['js'] = $this->getJavaScriptOptimizations($dataCount);
        
        return [
            'data' => $this->processDataForStrategy($data, $optimizations),
            'config' => $this->optimizeConfig($config, $optimizations),
            'optimizations' => $optimizations
        ];
    }
    
    /**
     * Get DOM optimization settings
     *
     * @param array $config Table configuration
     * @param int $dataCount Number of data rows
     * @return array DOM optimizations
     */
    private function getDomOptimizations(array $config, int $dataCount): array
    {
        return [
            'use_document_fragment' => $dataCount > 20,
            'batch_dom_updates' => $dataCount > 50,
            'minimize_reflows' => true,
            'use_css_containment' => $dataCount > 30,
            'optimize_images' => true,
            'defer_non_critical_content' => $dataCount > 100,
            'use_intersection_observer' => $dataCount > self::LAZY_LOADING_THRESHOLD
        ];
    }
    
    /**
     * Get CSS optimization settings
     *
     * @param array $config Table configuration
     * @return array CSS optimizations
     */
    private function getCssOptimizations(array $config): array
    {
        return [
            'critical_css_inline' => true,
            'defer_non_critical_css' => true,
            'use_css_containment' => true,
            'optimize_selectors' => true,
            'minimize_paint_area' => true,
            'use_transform_animations' => true,
            'enable_gpu_acceleration' => true
        ];
    }
    
    /**
     * Get JavaScript optimization settings
     *
     * @param int $dataCount Number of data rows
     * @return array JavaScript optimizations
     */
    private function getJavaScriptOptimizations(int $dataCount): array
    {
        return [
            'use_event_delegation' => $dataCount > 10,
            'debounce_scroll_events' => true,
            'throttle_resize_events' => true,
            'lazy_load_handlers' => $dataCount > 50,
            'use_web_workers' => $dataCount > 500,
            'optimize_memory_usage' => $dataCount > 100,
            'cleanup_event_listeners' => true
        ];
    }
    
    /**
     * Process data based on optimization strategy
     *
     * @param array $data Original data
     * @param array $optimizations Optimization settings
     * @return array Processed data
     */
    private function processDataForStrategy(array $data, array $optimizations): array
    {
        switch ($optimizations['strategy']) {
            case 'virtual_scroll':
                return $this->prepareVirtualScrollData($data, $optimizations);
                
            case 'lazy_loading':
                return $this->prepareLazyLoadingData($data, $optimizations);
                
            case 'pagination':
                return $this->preparePaginationData($data, $optimizations);
                
            default:
                return $data;
        }
    }
    
    /**
     * Prepare data for virtual scrolling
     *
     * @param array $data Original data
     * @param array $optimizations Optimization settings
     * @return array Virtual scroll data structure
     */
    private function prepareVirtualScrollData(array $data, array $optimizations): array
    {
        $chunkSize = $optimizations['chunk_size'];
        $chunks = array_chunk($data, $chunkSize);
        
        return [
            'total_rows' => count($data),
            'chunk_size' => $chunkSize,
            'total_chunks' => count($chunks),
            'initial_chunks' => array_slice($chunks, 0, 3), // Load first 3 chunks
            'chunk_metadata' => $this->generateChunkMetadata($chunks)
        ];
    }
    
    /**
     * Prepare data for lazy loading
     *
     * @param array $data Original data
     * @param array $optimizations Optimization settings
     * @return array Lazy loading data structure
     */
    private function prepareLazyLoadingData(array $data, array $optimizations): array
    {
        $initialLoad = $optimizations['initial_load'];
        
        return [
            'initial_data' => array_slice($data, 0, $initialLoad),
            'remaining_data' => array_slice($data, $initialLoad),
            'total_rows' => count($data),
            'loaded_rows' => min($initialLoad, count($data)),
            'load_increment' => $optimizations['load_increment']
        ];
    }
    
    /**
     * Prepare data for pagination
     *
     * @param array $data Original data
     * @param array $optimizations Optimization settings
     * @return array Pagination data structure
     */
    private function preparePaginationData(array $data, array $optimizations): array
    {
        $perPage = $optimizations['per_page'];
        $totalPages = ceil(count($data) / $perPage);
        
        return [
            'current_page_data' => array_slice($data, 0, $perPage),
            'total_rows' => count($data),
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'current_page' => 1
        ];
    }
    
    /**
     * Generate metadata for virtual scroll chunks
     *
     * @param array $chunks Data chunks
     * @return array Chunk metadata
     */
    private function generateChunkMetadata(array $chunks): array
    {
        $metadata = [];
        
        foreach ($chunks as $index => $chunk) {
            $metadata[$index] = [
                'start_index' => $index * count($chunk),
                'end_index' => ($index * count($chunk)) + count($chunk) - 1,
                'row_count' => count($chunk),
                'estimated_height' => count($chunk) * 40 // Assume 40px per row
            ];
        }
        
        return $metadata;
    }
    
    /**
     * Optimize configuration for performance
     *
     * @param array $config Original configuration
     * @param array $optimizations Optimization settings
     * @return array Optimized configuration
     */
    private function optimizeConfig(array $config, array $optimizations): array
    {
        // Add performance-specific configuration
        $config['performance'] = [
            'strategy' => $optimizations['strategy'],
            'dom_optimizations' => $optimizations['dom'],
            'css_optimizations' => $optimizations['css'],
            'js_optimizations' => $optimizations['js']
        ];
        
        // Optimize styling for performance
        if (isset($config['styling'])) {
            $config['styling']['table_class'] = trim(
                ($config['styling']['table_class'] ?? 'table') . ' performance-optimized'
            );
            
            // Add containment for large tables
            if (isset($optimizations['dom']['use_css_containment']) && $optimizations['dom']['use_css_containment']) {
                $config['styling']['table_class'] .= ' css-containment';
            }
        }
        
        // Add performance attributes
        $config['attributes'] = array_merge($config['attributes'] ?? [], [
            'data-performance-strategy' => $optimizations['strategy'],
            'data-row-count' => $optimizations['strategy'] === 'virtual_scroll' 
                ? $optimizations['total_rows'] ?? 0 
                : count($config['data'] ?? [])
        ]);
        
        return $config;
    }
    
    /**
     * Generate performance-optimized CSS
     *
     * @param array $config Table configuration
     * @param array $optimizations Optimization settings
     * @return string CSS content
     */
    public function generateOptimizedCSS(array $config, array $optimizations): string
    {
        $css = [];
        
        // Base performance optimizations
        $css[] = '.performance-optimized {';
        $css[] = '  contain: layout style paint;';
        $css[] = '  will-change: transform;';
        $css[] = '}';
        
        // CSS containment for large tables
        if (isset($optimizations['dom']['use_css_containment']) && $optimizations['dom']['use_css_containment']) {
            $css[] = '.css-containment {';
            $css[] = '  contain: layout style;';
            $css[] = '}';
            
            $css[] = '.css-containment tbody {';
            $css[] = '  contain: layout;';
            $css[] = '}';
        }
        
        // Virtual scrolling styles
        if ($optimizations['strategy'] === 'virtual_scroll') {
            $css[] = '.virtual-scroll-container {';
            $css[] = '  height: 400px;';
            $css[] = '  overflow-y: auto;';
            $css[] = '  position: relative;';
            $css[] = '}';
            
            $css[] = '.virtual-scroll-spacer {';
            $css[] = '  pointer-events: none;';
            $css[] = '}';
        }
        
        // Lazy loading styles
        if ($optimizations['strategy'] === 'lazy_loading') {
            $css[] = '.lazy-loading-trigger {';
            $css[] = '  height: 20px;';
            $css[] = '  display: flex;';
            $css[] = '  align-items: center;';
            $css[] = '  justify-content: center;';
            $css[] = '}';
            
            $css[] = '.lazy-loading-spinner {';
            $css[] = '  width: 20px;';
            $css[] = '  height: 20px;';
            $css[] = '  border: 2px solid #f3f3f3;';
            $css[] = '  border-top: 2px solid #007bff;';
            $css[] = '  border-radius: 50%;';
            $css[] = '  animation: spin 1s linear infinite;';
            $css[] = '}';
            
            $css[] = '@keyframes spin {';
            $css[] = '  0% { transform: rotate(0deg); }';
            $css[] = '  100% { transform: rotate(360deg); }';
            $css[] = '}';
        }
        
        // GPU acceleration for animations
        if (isset($optimizations['css']['enable_gpu_acceleration']) && $optimizations['css']['enable_gpu_acceleration']) {
            $css[] = '.performance-optimized tr:hover {';
            $css[] = '  transform: translateZ(0);';
            $css[] = '  backface-visibility: hidden;';
            $css[] = '}';
        }
        
        return implode("\n", $css);
    }
    
    /**
     * Generate performance-optimized JavaScript
     *
     * @param array $optimizations Optimization settings
     * @return string JavaScript content
     */
    public function generateOptimizedJavaScript(array $optimizations): string
    {
        $js = [];
        
        // Base performance utilities - only declare class if not already declared
        $js[] = 'if (typeof window.InlineTablePerformanceManager === "undefined") {';
        $js[] = 'window.InlineTablePerformanceManager = class InlineTablePerformanceManager {';
        $js[] = '  constructor(tableElement, options = {}) {';
        $js[] = '    this.table = tableElement;';
        $js[] = '    this.options = options;';
        $js[] = '    this.strategy = options.strategy || "full_render";';
        $js[] = '    this.init();';
        $js[] = '  }';
        $js[] = '';
        $js[] = '  init() {';
        $js[] = '    this.setupEventDelegation();';
        $js[] = '    this.setupPerformanceObserver();';
        $js[] = '    this.initializeStrategy();';
        $js[] = '  }';
        $js[] = '';
        
        // Default initializeStrategy method
        $js[] = '  initializeStrategy() {';
        $js[] = '    switch(this.strategy) {';
        $js[] = '      case "virtual_scroll":';
        $js[] = '        this.initVirtualScroll();';
        $js[] = '        break;';
        $js[] = '      case "lazy_loading":';
        $js[] = '        this.initLazyLoading();';
        $js[] = '        break;';
        $js[] = '      default:';
        $js[] = '        this.initFullRender();';
        $js[] = '        break;';
        $js[] = '    }';
        $js[] = '  }';
        $js[] = '';
        $js[] = '  initFullRender() {';
        $js[] = '    // Default strategy - no special initialization needed';
        $js[] = '  }';
        $js[] = '';
        
        // Event delegation for performance
        if (isset($optimizations['js']['use_event_delegation']) && $optimizations['js']['use_event_delegation']) {
            $js[] = '  setupEventDelegation() {';
            $js[] = '    this.table.addEventListener("click", this.handleClick.bind(this));';
            $js[] = '    this.table.addEventListener("mouseover", this.handleMouseOver.bind(this));';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  handleClick(event) {';
            $js[] = '    const target = event.target.closest("[data-action]");';
            $js[] = '    if (target) {';
            $js[] = '      const action = target.dataset.action;';
            $js[] = '      this.executeAction(action, target, event);';
            $js[] = '    }';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  handleMouseOver(event) {';
            $js[] = '    const row = event.target.closest("tr");';
            $js[] = '    if (row && !row.classList.contains("hover-optimized")) {';
            $js[] = '      this.optimizeRowHover(row);';
            $js[] = '    }';
            $js[] = '  }';
            $js[] = '';
        } else {
            // Add minimal event delegation even when not enabled
            $js[] = '  setupEventDelegation() {';
            $js[] = '    // Minimal event delegation';
            $js[] = '  }';
            $js[] = '';
        }
        
        // Virtual scrolling implementation
        if ($optimizations['strategy'] === 'virtual_scroll') {
            $js[] = '  initVirtualScroll() {';
            $js[] = '    const container = this.table.closest(".virtual-scroll-container");';
            $js[] = '    if (!container) return;';
            $js[] = '';
            $js[] = '    this.virtualScroll = {';
            $js[] = '      container,';
            $js[] = '      rowHeight: 40,';
            $js[] = '      visibleRows: Math.ceil(container.clientHeight / 40),';
            $js[] = '      scrollTop: 0,';
            $js[] = '      totalRows: parseInt(this.table.dataset.rowCount) || 0';
            $js[] = '    };';
            $js[] = '';
            $js[] = '    container.addEventListener("scroll", this.throttle(this.handleVirtualScroll.bind(this), 16));';
            $js[] = '    this.renderVisibleRows();';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  handleVirtualScroll() {';
            $js[] = '    const { container, rowHeight } = this.virtualScroll;';
            $js[] = '    this.virtualScroll.scrollTop = container.scrollTop;';
            $js[] = '    this.renderVisibleRows();';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  renderVisibleRows() {';
            $js[] = '    const { scrollTop, rowHeight, visibleRows, totalRows } = this.virtualScroll;';
            $js[] = '    const startIndex = Math.floor(scrollTop / rowHeight);';
            $js[] = '    const endIndex = Math.min(startIndex + visibleRows + 5, totalRows);';
            $js[] = '';
            $js[] = '    // Request visible data chunk';
            $js[] = '    this.loadDataChunk(startIndex, endIndex);';
            $js[] = '  }';
            $js[] = '';
        }
        
        // Lazy loading implementation
        if ($optimizations['strategy'] === 'lazy_loading') {
            $js[] = '  initLazyLoading() {';
            $js[] = '    this.lazyLoading = {';
            $js[] = '      loadedRows: parseInt(this.table.dataset.loadedRows) || 0,';
            $js[] = '      totalRows: parseInt(this.table.dataset.totalRows) || 0,';
            $js[] = '      loadIncrement: parseInt(this.table.dataset.loadIncrement) || 25,';
            $js[] = '      loading: false';
            $js[] = '    };';
            $js[] = '';
            $js[] = '    this.setupIntersectionObserver();';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  setupIntersectionObserver() {';
            $js[] = '    const trigger = this.table.querySelector(".lazy-loading-trigger");';
            $js[] = '    if (!trigger) return;';
            $js[] = '';
            $js[] = '    this.observer = new IntersectionObserver((entries) => {';
            $js[] = '      entries.forEach(entry => {';
            $js[] = '        if (entry.isIntersecting && !this.lazyLoading.loading) {';
            $js[] = '          this.loadMoreRows();';
            $js[] = '        }';
            $js[] = '      });';
            $js[] = '    }, { threshold: 0.1 });';
            $js[] = '';
            $js[] = '    this.observer.observe(trigger);';
            $js[] = '  }';
            $js[] = '';
            $js[] = '  loadMoreRows() {';
            $js[] = '    if (this.lazyLoading.loadedRows >= this.lazyLoading.totalRows) return;';
            $js[] = '';
            $js[] = '    this.lazyLoading.loading = true;';
            $js[] = '    const startIndex = this.lazyLoading.loadedRows;';
            $js[] = '    const endIndex = Math.min(startIndex + this.lazyLoading.loadIncrement, this.lazyLoading.totalRows);';
            $js[] = '';
            $js[] = '    this.loadDataChunk(startIndex, endIndex).then(() => {';
            $js[] = '      this.lazyLoading.loadedRows = endIndex;';
            $js[] = '      this.lazyLoading.loading = false;';
            $js[] = '    });';
            $js[] = '  }';
            $js[] = '';
        }
        
        // Utility functions
        $js[] = '  throttle(func, limit) {';
        $js[] = '    let inThrottle;';
        $js[] = '    return function() {';
        $js[] = '      const args = arguments;';
        $js[] = '      const context = this;';
        $js[] = '      if (!inThrottle) {';
        $js[] = '        func.apply(context, args);';
        $js[] = '        inThrottle = true;';
        $js[] = '        setTimeout(() => inThrottle = false, limit);';
        $js[] = '      }';
        $js[] = '    }';
        $js[] = '  }';
        $js[] = '';
        $js[] = '  debounce(func, wait) {';
        $js[] = '    let timeout;';
        $js[] = '    return function executedFunction(...args) {';
        $js[] = '      const later = () => {';
        $js[] = '        clearTimeout(timeout);';
        $js[] = '        func(...args);';
        $js[] = '      };';
        $js[] = '      clearTimeout(timeout);';
        $js[] = '      timeout = setTimeout(later, wait);';
        $js[] = '    };';
        $js[] = '  }';
        $js[] = '';
        
        // Add missing methods that are called but not defined
        $js[] = '  setupPerformanceObserver() {';
        $js[] = '    // Performance observer setup (optional)';
        $js[] = '    if ("PerformanceObserver" in window) {';
        $js[] = '      try {';
        $js[] = '        this.performanceObserver = new PerformanceObserver((list) => {';
        $js[] = '          // Handle performance entries';
        $js[] = '        });';
        $js[] = '        this.performanceObserver.observe({ entryTypes: ["measure"] });';
        $js[] = '      } catch (e) {';
        $js[] = '        console.warn("Performance Observer not supported:", e);';
        $js[] = '      }';
        $js[] = '    }';
        $js[] = '  }';
        $js[] = '';
        $js[] = '  optimizeRowHover(row) {';
        $js[] = '    row.classList.add("hover-optimized");';
        $js[] = '    row.style.willChange = "background-color";';
        $js[] = '    const cleanup = () => {';
        $js[] = '      row.style.willChange = "auto";';
        $js[] = '      row.removeEventListener("mouseleave", cleanup);';
        $js[] = '    };';
        $js[] = '    row.addEventListener("mouseleave", cleanup);';
        $js[] = '  }';
        $js[] = '';
        $js[] = '  executeAction(action, target, event) {';
        $js[] = '    // Handle table actions';
        $js[] = '    console.log("Table action:", action);';
        $js[] = '  }';
        $js[] = '';
        
        // Memory cleanup
        if (isset($optimizations['js']['cleanup_event_listeners']) && $optimizations['js']['cleanup_event_listeners']) {
            $js[] = '  destroy() {';
            $js[] = '    if (this.observer) {';
            $js[] = '      this.observer.disconnect();';
            $js[] = '    }';
            $js[] = '    if (this.performanceObserver) {';
            $js[] = '      this.performanceObserver.disconnect();';
            $js[] = '    }';
            $js[] = '    this.table.removeEventListener("click", this.handleClick);';
            $js[] = '    this.table.removeEventListener("mouseover", this.handleMouseOver);';
            $js[] = '  }';
            $js[] = '';
        }
        
        $js[] = '}';
        $js[] = '} // End of class declaration check';
        $js[] = '';
        $js[] = '// Initialize performance manager when DOM is ready';
        $js[] = 'if (document.readyState === "loading") {';
        $js[] = '  document.addEventListener("DOMContentLoaded", initializeInlinePerformanceManagers);';
        $js[] = '} else {';
        $js[] = '  initializeInlinePerformanceManagers();';
        $js[] = '}';
        $js[] = '';
        $js[] = 'function initializeInlinePerformanceManagers() {';
        $js[] = '  if (typeof window.InlineTablePerformanceManager !== "undefined") {';
        $js[] = '    document.querySelectorAll(".performance-optimized:not([data-performance-initialized])").forEach(table => {';
        $js[] = '      const strategy = table.dataset.performanceStrategy || "full_render";';
        $js[] = '      table._performanceManager = new window.InlineTablePerformanceManager(table, { strategy });';
        $js[] = '      table.setAttribute("data-performance-initialized", "true");';
        $js[] = '    });';
        $js[] = '  }';
        $js[] = '}';
        
        return implode("\n", $js);
    }
    
    /**
     * Record performance metrics
     *
     * @param string $tableType Table type
     * @param array $metrics Performance metrics
     * @return void
     */
    public function recordPerformanceMetrics(string $tableType, array $metrics): void
    {
        $cacheKey = self::PERFORMANCE_CACHE_PREFIX . $tableType . '_' . date('Y-m-d-H');
        
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = array_merge($metrics, ['timestamp' => time()]);
        
        // Keep only last 100 entries per hour
        if (count($existingMetrics) > 100) {
            $existingMetrics = array_slice($existingMetrics, -100);
        }
        
        Cache::put($cacheKey, $existingMetrics, 3600); // 1 hour TTL
    }
    
    /**
     * Get performance metrics for analysis
     *
     * @param string $tableType Table type
     * @param int $hours Number of hours to look back
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(string $tableType, int $hours = 24): array
    {
        $metrics = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $cacheKey = self::PERFORMANCE_CACHE_PREFIX . $tableType . '_' . $hour;
            
            $hourlyMetrics = Cache::get($cacheKey, []);
            $metrics = array_merge($metrics, $hourlyMetrics);
        }
        
        return $metrics;
    }
    
    /**
     * Implement pagination for large datasets
     *
     * @param array $data The full dataset
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Paginated data with metadata
     */
    public function paginateData(array $data, int $page = 1, int $perPage = null): array
    {
        $perPage = $perPage ?? self::MAX_ROWS_WITHOUT_PAGINATION;
        $totalItems = count($data);
        $totalPages = ceil($totalItems / $perPage);
        
        // Ensure page is within bounds
        $page = max(1, min($page, $totalPages));
        
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($data, $offset, $perPage);
        
        return [
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'start_item' => $offset + 1,
                'end_item' => min($offset + $perPage, $totalItems)
            ]
        ];
    }
    
    /**
     * Implement lazy loading data structure
     *
     * @param array $data The full dataset
     * @param int $initialLoad Initial number of items to load
     * @param int $loadIncrement Number of items to load per request
     * @return array Lazy loading data structure
     */
    public function prepareLazyLoadingStructure(array $data, int $initialLoad = null, int $loadIncrement = null): array
    {
        $initialLoad = $initialLoad ?? self::LAZY_LOADING_THRESHOLD;
        $loadIncrement = $loadIncrement ?? 25;
        
        $totalItems = count($data);
        $initialData = array_slice($data, 0, $initialLoad);
        
        return [
            'initial_data' => $initialData,
            'lazy_config' => [
                'total_items' => $totalItems,
                'loaded_items' => count($initialData),
                'load_increment' => $loadIncrement,
                'has_more' => $totalItems > $initialLoad,
                'next_offset' => count($initialData)
            ]
        ];
    }
    
    /**
     * Get next chunk of data for lazy loading
     *
     * @param array $data The full dataset
     * @param int $offset Current offset
     * @param int $limit Number of items to load
     * @return array Next chunk of data
     */
    public function getNextLazyChunk(array $data, int $offset, int $limit): array
    {
        $chunk = array_slice($data, $offset, $limit);
        $totalItems = count($data);
        $newOffset = $offset + count($chunk);
        
        return [
            'data' => $chunk,
            'meta' => [
                'loaded_items' => count($chunk),
                'new_offset' => $newOffset,
                'has_more' => $newOffset < $totalItems,
                'total_items' => $totalItems,
                'progress_percentage' => round(($newOffset / $totalItems) * 100, 2)
            ]
        ];
    }
    
    /**
     * Optimize memory usage by cleaning up unused data
     *
     * @param array $config Table configuration
     * @param array $data Table data
     * @return array Memory optimization recommendations
     */
    public function optimizeMemoryUsage(array $config, array $data): array
    {
        $optimizations = [];
        $dataSize = count($data);
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        // Calculate estimated memory per row
        if ($dataSize > 0) {
            $sampleRow = reset($data);
            $estimatedRowSize = strlen(serialize($sampleRow));
            $estimatedTotalSize = $estimatedRowSize * $dataSize;
            
            $optimizations['estimated_memory'] = [
                'per_row' => $estimatedRowSize,
                'total_estimated' => $estimatedTotalSize,
                'current_usage' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'usage_percentage' => round(($memoryUsage / $memoryLimit) * 100, 2)
            ];
        }
        
        // Recommend optimizations based on data size and memory usage
        if ($dataSize > self::VIRTUAL_SCROLL_THRESHOLD) {
            $optimizations['recommendations'][] = [
                'type' => 'virtual_scrolling',
                'reason' => 'Large dataset detected',
                'benefit' => 'Reduces DOM nodes and memory usage',
                'implementation' => 'Use virtual scrolling with chunk size of 50'
            ];
        } elseif ($dataSize > self::LAZY_LOADING_THRESHOLD) {
            $optimizations['recommendations'][] = [
                'type' => 'lazy_loading',
                'reason' => 'Medium dataset detected',
                'benefit' => 'Reduces initial load time and memory usage',
                'implementation' => 'Load initial 50 rows, then 25 per request'
            ];
        }
        
        // Memory-specific optimizations
        if ($memoryUsage > ($memoryLimit * 0.7)) {
            $optimizations['recommendations'][] = [
                'type' => 'memory_cleanup',
                'reason' => 'High memory usage detected',
                'benefit' => 'Prevents memory exhaustion',
                'implementation' => 'Enable aggressive garbage collection and data cleanup'
            ];
        }
        
        // Column optimization
        $columnCount = count($config['columns'] ?? []);
        if ($columnCount > 20) {
            $optimizations['recommendations'][] = [
                'type' => 'column_virtualization',
                'reason' => 'Many columns detected',
                'benefit' => 'Reduces DOM complexity',
                'implementation' => 'Only render visible columns'
            ];
        }
        
        return $optimizations;
    }
    
    /**
     * Get PHP memory limit in bytes
     *
     * @return int Memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit == -1) {
            return PHP_INT_MAX; // No limit
        }
        
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }
    
    /**
     * Clean up data to reduce memory usage
     *
     * @param array $data The data to clean
     * @param array $options Cleanup options
     * @return array Cleaned data
     */
    public function cleanupDataForMemory(array $data, array $options = []): array
    {
        $cleanedData = [];
        $removeEmpty = $options['remove_empty'] ?? true;
        $trimStrings = $options['trim_strings'] ?? true;
        $maxStringLength = $options['max_string_length'] ?? 1000;
        
        foreach ($data as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $cleanedRow = [];
            
            foreach ($row as $field => $value) {
                // Skip empty values if requested
                if ($removeEmpty && empty($value) && $value !== 0 && $value !== '0') {
                    continue;
                }
                
                // Trim strings if requested
                if ($trimStrings && is_string($value)) {
                    $value = trim($value);
                    
                    // Truncate very long strings
                    if (strlen($value) > $maxStringLength) {
                        $value = substr($value, 0, $maxStringLength) . '...';
                    }
                }
                
                $cleanedRow[$field] = $value;
            }
            
            $cleanedData[$key] = $cleanedRow;
        }
        
        return $cleanedData;
    }
    
    /**
     * Generate memory-optimized configuration
     *
     * @param array $config Original configuration
     * @param array $memoryOptimizations Memory optimization settings
     * @return array Optimized configuration
     */
    public function generateMemoryOptimizedConfig(array $config, array $memoryOptimizations): array
    {
        $optimizedConfig = $config;
        
        // Add memory optimization flags
        $optimizedConfig['memory_optimizations'] = [
            'enabled' => true,
            'cleanup_interval' => 30000, // 30 seconds
            'max_dom_nodes' => 1000,
            'aggressive_cleanup' => $memoryOptimizations['aggressive_cleanup'] ?? false,
            'column_virtualization' => $memoryOptimizations['column_virtualization'] ?? false
        ];
        
        // Optimize styling for memory
        if (isset($optimizedConfig['styling'])) {
            $optimizedConfig['styling']['table_class'] = trim(
                ($optimizedConfig['styling']['table_class'] ?? 'table') . ' memory-optimized'
            );
        }
        
        // Add memory-specific attributes
        $optimizedConfig['attributes'] = array_merge($optimizedConfig['attributes'] ?? [], [
            'data-memory-optimized' => 'true',
            'data-cleanup-interval' => $optimizedConfig['memory_optimizations']['cleanup_interval']
        ]);
        
        return $optimizedConfig;
    }
}