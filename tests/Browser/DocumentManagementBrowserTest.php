<?php

namespace Tests\Browser;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DocumentManagementBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected User $viewer;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'editor',
            'email' => 'test@example.com',
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'email' => 'viewer@example.com',
        ]);

        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'status' => 'approved',
        ]);

        // Set up fake storage for file operations
        Storage::fake('public');
    }

    /** @test */
    public function user_can_navigate_to_documents_tab()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->waitFor('#documents-tab')
                ->assertSee('ドキュメント')
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->assertVisible('#documents')
                ->assertSee('ドキュメント管理');
        });
    }

    /** @test */
    public function user_can_create_new_folder()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Click new folder button
                    $browser->click('.new-folder-btn')
                        ->waitFor('#createFolderModal')
                        ->assertVisible('#createFolderModal')
                        
                        // Fill in folder name
                        ->type('#folderName', '契約書類')
                        
                        // Create folder
                        ->click('#createFolderBtn')
                        ->waitUntilMissing('#createFolderModal')
                        ->waitFor('.folder-item')
                        
                        // Verify folder was created
                        ->assertSee('契約書類')
                        ->assertVisible('.folder-item[data-name="契約書類"]');
                });
        });
    }

    /** @test */
    public function user_can_navigate_into_folder()
    {
        // Create test folder
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '契約書類',
            'parent_id' => null,
            'path' => '/契約書類',
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($folder) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Double-click folder to navigate
                    $browser->doubleClick('.folder-item[data-name="契約書類"]')
                        ->waitFor('.breadcrumb')
                        ->assertSee('契約書類')
                        ->assertVisible('.breadcrumb-item:contains("契約書類")');
                });
        });
    }

    /** @test */
    public function user_can_use_breadcrumb_navigation()
    {
        // Create nested folder structure
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '契約書類',
            'parent_id' => null,
            'path' => '/契約書類',
            'created_by' => $this->user->id,
        ]);

        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '保守契約',
            'parent_id' => $parentFolder->id,
            'path' => '/契約書類/保守契約',
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($parentFolder, $childFolder) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}/documents/folders/{$childFolder->id}")
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Verify breadcrumb shows full path
                    $browser->assertSee('契約書類')
                        ->assertSee('保守契約')
                        ->assertVisible('.breadcrumb-item:contains("契約書類")')
                        ->assertVisible('.breadcrumb-item:contains("保守契約")')
                        
                        // Click parent folder in breadcrumb
                        ->click('.breadcrumb-item:contains("契約書類") a')
                        ->waitFor('.folder-item[data-name="保守契約"]')
                        ->assertSee('保守契約')
                        ->assertVisible('.folder-item[data-name="保守契約"]');
                });
        });
    }

    /** @test */
    public function user_can_upload_files_via_drag_and_drop()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Simulate file drag and drop
                    $browser->script([
                        'const dropZone = document.querySelector(".drop-zone");',
                        'const event = new DragEvent("drop", {',
                        '  dataTransfer: new DataTransfer()',
                        '});',
                        'const file = new File(["test content"], "test.pdf", { type: "application/pdf" });',
                        'event.dataTransfer.files = [file];',
                        'dropZone.dispatchEvent(event);'
                    ]);
                    
                    // Wait for upload to complete
                    $browser->waitFor('.file-item')
                        ->assertSee('test.pdf')
                        ->assertVisible('.file-item[data-name="test.pdf"]');
                });
        });
    }

    /** @test */
    public function user_can_upload_files_via_button()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Click upload button
                    $browser->click('.upload-btn')
                        ->waitFor('#fileInput')
                        
                        // Attach file
                        ->attach('#fileInput', __DIR__ . '/../fixtures/test.pdf')
                        
                        // Wait for upload to complete
                        ->waitFor('.file-item')
                        ->assertSee('test.pdf')
                        ->assertVisible('.file-item[data-name="test.pdf"]');
                });
        });
    }

    /** @test */
    public function user_can_switch_between_list_and_icon_view()
    {
        // Create test file
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_stored.pdf',
            'file_path' => 'documents/test_stored.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Default should be list view
                    $browser->assertVisible('.list-view')
                        ->assertSee('test.pdf')
                        
                        // Switch to icon view
                        ->click('.view-toggle .icon-view-btn')
                        ->waitFor('.icon-view')
                        ->assertVisible('.icon-view')
                        ->assertSee('test.pdf')
                        
                        // Switch back to list view
                        ->click('.view-toggle .list-view-btn')
                        ->waitFor('.list-view')
                        ->assertVisible('.list-view')
                        ->assertSee('test.pdf');
                });
        });
    }

    /** @test */
    public function user_can_sort_files_and_folders()
    {
        // Create test data
        $folder1 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'B_フォルダ',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $folder2 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'A_フォルダ',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $file1 = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'z_file.pdf',
            'uploaded_by' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $file2 = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'a_file.pdf',
            'uploaded_by' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Test name sorting (ascending)
                    $browser->click('.sort-dropdown')
                        ->waitFor('.sort-options')
                        ->click('.sort-name-asc')
                        ->waitFor('.sorted-content')
                        
                        // Verify folders come first, then files, both alphabetically
                        ->assertSeeIn('.item:nth-child(1)', 'A_フォルダ')
                        ->assertSeeIn('.item:nth-child(2)', 'B_フォルダ')
                        ->assertSeeIn('.item:nth-child(3)', 'a_file.pdf')
                        ->assertSeeIn('.item:nth-child(4)', 'z_file.pdf')
                        
                        // Test name sorting (descending)
                        ->click('.sort-dropdown')
                        ->click('.sort-name-desc')
                        ->waitFor('.sorted-content')
                        ->assertSeeIn('.item:nth-child(1)', 'B_フォルダ')
                        ->assertSeeIn('.item:nth-child(2)', 'A_フォルダ')
                        ->assertSeeIn('.item:nth-child(3)', 'z_file.pdf')
                        ->assertSeeIn('.item:nth-child(4)', 'a_file.pdf')
                        
                        // Test date sorting (newest first)
                        ->click('.sort-dropdown')
                        ->click('.sort-date-desc')
                        ->waitFor('.sorted-content')
                        ->assertSeeIn('.file-item:nth-child(1)', 'a_file.pdf')
                        ->assertSeeIn('.file-item:nth-child(2)', 'z_file.pdf');
                });
        });
    }

    /** @test */
    public function user_can_rename_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '古いフォルダ名',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($folder) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Right-click folder
                    $browser->rightClick('.folder-item[data-name="古いフォルダ名"]')
                        ->waitFor('.context-menu')
                        ->assertVisible('.context-menu')
                        
                        // Click rename option
                        ->click('.context-menu .rename-option')
                        ->waitFor('#renameFolderModal')
                        ->assertVisible('#renameFolderModal')
                        
                        // Change name
                        ->clear('#newFolderName')
                        ->type('#newFolderName', '新しいフォルダ名')
                        
                        // Save changes
                        ->click('#renameFolderBtn')
                        ->waitUntilMissing('#renameFolderModal')
                        ->waitFor('.folder-item[data-name="新しいフォルダ名"]')
                        
                        // Verify name changed
                        ->assertSee('新しいフォルダ名')
                        ->assertDontSee('古いフォルダ名');
                });
        });
    }

    /** @test */
    public function user_can_delete_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '削除予定フォルダ',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($folder) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Right-click folder
                    $browser->rightClick('.folder-item[data-name="削除予定フォルダ"]')
                        ->waitFor('.context-menu')
                        
                        // Click delete option
                        ->click('.context-menu .delete-option')
                        ->waitFor('#deleteFolderModal')
                        ->assertVisible('#deleteFolderModal')
                        ->assertSee('削除予定フォルダ')
                        
                        // Confirm deletion
                        ->click('#deleteFolderBtn')
                        ->waitUntilMissing('#deleteFolderModal')
                        ->waitUntilMissing('.folder-item[data-name="削除予定フォルダ"]')
                        
                        // Verify folder is gone
                        ->assertDontSee('削除予定フォルダ');
                });
        });
    }

    /** @test */
    public function user_can_delete_file()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => '削除予定.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($file) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Right-click file
                    $browser->rightClick('.file-item[data-name="削除予定.pdf"]')
                        ->waitFor('.context-menu')
                        
                        // Click delete option
                        ->click('.context-menu .delete-option')
                        ->waitFor('#deleteFileModal')
                        ->assertVisible('#deleteFileModal')
                        ->assertSee('削除予定.pdf')
                        
                        // Confirm deletion
                        ->click('#deleteFileBtn')
                        ->waitUntilMissing('#deleteFileModal')
                        ->waitUntilMissing('.file-item[data-name="削除予定.pdf"]')
                        
                        // Verify file is gone
                        ->assertDontSee('削除予定.pdf');
                });
        });
    }

    /** @test */
    public function user_can_download_file()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'download_test.pdf',
            'stored_name' => 'stored_download_test.pdf',
            'file_path' => 'documents/stored_download_test.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // Create actual file in storage
        Storage::disk('public')->put('documents/stored_download_test.pdf', 'test content');

        $this->browse(function (Browser $browser) use ($file) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Click file to download
                    $browser->click('.file-item[data-name="download_test.pdf"] .download-link')
                        ->pause(1000); // Wait for download to start
                    
                    // Note: We can't easily verify the actual download in browser tests,
                    // but we can verify the link exists and is clickable
                    $browser->assertVisible('.file-item[data-name="download_test.pdf"] .download-link');
                });
        });
    }

    /** @test */
    public function user_can_preview_file()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'preview_test.pdf',
            'stored_name' => 'stored_preview_test.pdf',
            'file_path' => 'documents/stored_preview_test.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($file) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Right-click file
                    $browser->rightClick('.file-item[data-name="preview_test.pdf"]')
                        ->waitFor('.context-menu')
                        
                        // Click preview option
                        ->click('.context-menu .preview-option')
                        ->waitFor('#previewModal')
                        ->assertVisible('#previewModal')
                        ->assertSee('preview_test.pdf')
                        
                        // Close preview
                        ->click('#previewModal .close')
                        ->waitUntilMissing('#previewModal');
                });
        });
    }

    /** @test */
    public function viewer_cannot_edit_documents()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'テストフォルダ',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'test.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->viewer)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Upload button should not be visible
                    $browser->assertMissing('.upload-btn')
                        ->assertMissing('.new-folder-btn')
                        
                        // Right-click should not show edit options
                        ->rightClick('.folder-item[data-name="テストフォルダ"]')
                        ->waitFor('.context-menu')
                        ->assertMissing('.context-menu .rename-option')
                        ->assertMissing('.context-menu .delete-option')
                        
                        // But should show view options
                        ->assertVisible('.context-menu .open-option');
                });
        });
    }

    /** @test */
    public function responsive_design_works_on_mobile()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'モバイルテスト',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // iPhone SE size
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Verify mobile layout
                    $browser->assertVisible('.document-container')
                        ->assertVisible('.folder-item[data-name="モバイルテスト"]')
                        
                        // Toolbar should be responsive
                        ->assertVisible('.toolbar')
                        ->assertVisible('.view-toggle')
                        
                        // Touch-friendly buttons
                        ->assertVisible('.new-folder-btn')
                        ->assertVisible('.upload-btn');
                });
        });
    }

    /** @test */
    public function responsive_design_works_on_tablet()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(768, 1024) // iPad size
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Verify tablet layout
                    $browser->assertVisible('.document-container')
                        ->assertVisible('.toolbar')
                        
                        // Should show more items per row than mobile
                        ->assertVisible('.view-toggle')
                        ->click('.view-toggle .icon-view-btn')
                        ->waitFor('.icon-view')
                        ->assertVisible('.icon-view');
                });
        });
    }

    /** @test */
    public function user_can_handle_upload_progress()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Click upload button
                    $browser->click('.upload-btn')
                        ->waitFor('#fileInput')
                        
                        // Attach large file to see progress
                        ->attach('#fileInput', __DIR__ . '/../fixtures/large_test.pdf')
                        
                        // Should show progress indicator
                        ->waitFor('.upload-progress')
                        ->assertVisible('.upload-progress')
                        ->assertVisible('.progress-bar')
                        
                        // Wait for upload to complete
                        ->waitFor('.file-item')
                        ->assertMissing('.upload-progress');
                });
        });
    }

    /** @test */
    public function user_can_handle_upload_errors()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Try to upload invalid file type
                    $browser->click('.upload-btn')
                        ->waitFor('#fileInput')
                        ->attach('#fileInput', __DIR__ . '/../fixtures/invalid.exe')
                        
                        // Should show error message
                        ->waitFor('.error-message')
                        ->assertSee('許可されていないファイル形式です')
                        ->assertVisible('.error-message');
                });
        });
    }

    /** @test */
    public function user_can_use_keyboard_navigation()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'キーボードテスト',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Focus on folder item
                    $browser->click('.folder-item[data-name="キーボードテスト"]')
                        ->assertFocused('.folder-item[data-name="キーボードテスト"]')
                        
                        // Press Enter to open folder
                        ->keys('.folder-item[data-name="キーボードテスト"]', ['{enter}'])
                        ->waitFor('.breadcrumb')
                        ->assertSee('キーボードテスト')
                        
                        // Use keyboard to navigate back
                        ->keys('.breadcrumb-item:first-child a', ['{enter}'])
                        ->waitFor('.folder-item[data-name="キーボードテスト"]')
                        ->assertVisible('.folder-item[data-name="キーボードテスト"]');
                });
        });
    }

    /** @test */
    public function accessibility_features_work_correctly()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'アクセシビリティテスト',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Check ARIA attributes
                    $browser->assertAttribute('.folder-item', 'role', 'button')
                        ->assertAttribute('.folder-item', 'tabindex', '0')
                        ->assertAttribute('.folder-item', 'aria-label')
                        
                        // Check button accessibility
                        ->assertAttribute('.new-folder-btn', 'aria-label')
                        ->assertAttribute('.upload-btn', 'aria-label')
                        
                        // Check modal accessibility
                        ->click('.new-folder-btn')
                        ->waitFor('#createFolderModal')
                        ->assertAttribute('#createFolderModal', 'role', 'dialog')
                        ->assertAttribute('#createFolderModal', 'aria-labelledby')
                        ->assertAttribute('#folderName', 'aria-describedby')
                        
                        // Close modal
                        ->keys('#createFolderModal', ['{escape}'])
                        ->waitUntilMissing('#createFolderModal');
                });
        });
    }

    /** @test */
    public function user_can_handle_nested_folder_operations()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Create parent folder
                    $browser->click('.new-folder-btn')
                        ->waitFor('#createFolderModal')
                        ->type('#folderName', '親フォルダ')
                        ->click('#createFolderBtn')
                        ->waitUntilMissing('#createFolderModal')
                        ->waitFor('.folder-item[data-name="親フォルダ"]')
                        
                        // Navigate into parent folder
                        ->doubleClick('.folder-item[data-name="親フォルダ"]')
                        ->waitFor('.breadcrumb')
                        ->assertSee('親フォルダ')
                        
                        // Create child folder
                        ->click('.new-folder-btn')
                        ->waitFor('#createFolderModal')
                        ->type('#folderName', '子フォルダ')
                        ->click('#createFolderBtn')
                        ->waitUntilMissing('#createFolderModal')
                        ->waitFor('.folder-item[data-name="子フォルダ"]')
                        
                        // Navigate into child folder
                        ->doubleClick('.folder-item[data-name="子フォルダ"]')
                        ->waitFor('.breadcrumb')
                        ->assertSee('親フォルダ')
                        ->assertSee('子フォルダ')
                        
                        // Navigate back using breadcrumb
                        ->click('.breadcrumb-item:contains("親フォルダ") a')
                        ->waitFor('.folder-item[data-name="子フォルダ"]')
                        ->assertVisible('.folder-item[data-name="子フォルダ"]');
                });
        });
    }

    /** @test */
    public function user_can_handle_multiple_file_uploads()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Upload multiple files
                    $browser->click('.upload-btn')
                        ->waitFor('#fileInput')
                        ->attach('#fileInput', [
                            __DIR__ . '/../fixtures/test1.pdf',
                            __DIR__ . '/../fixtures/test2.pdf',
                            __DIR__ . '/../fixtures/test3.pdf'
                        ])
                        
                        // Should show progress for each file
                        ->waitFor('.upload-progress')
                        ->assertVisible('.upload-progress')
                        
                        // Wait for all uploads to complete
                        ->waitFor('.file-item[data-name="test1.pdf"]')
                        ->waitFor('.file-item[data-name="test2.pdf"]')
                        ->waitFor('.file-item[data-name="test3.pdf"]')
                        
                        // Verify all files are present
                        ->assertSee('test1.pdf')
                        ->assertSee('test2.pdf')
                        ->assertSee('test3.pdf');
                });
        });
    }

    /** @test */
    public function user_can_search_files_and_folders()
    {
        // Create test data
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '検索テストフォルダ',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => '検索テストファイル.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Use search functionality
                    $browser->type('.search-input', '検索テスト')
                        ->waitFor('.search-results')
                        
                        // Should show matching items
                        ->assertSee('検索テストフォルダ')
                        ->assertSee('検索テストファイル.pdf')
                        
                        // Clear search
                        ->clear('.search-input')
                        ->waitFor('.document-container')
                        ->assertVisible('.folder-item[data-name="検索テストフォルダ"]')
                        ->assertVisible('.file-item[data-name="検索テストファイル.pdf"]');
                });
        });
    }

    /** @test */
    public function user_can_handle_context_menu_operations()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'コンテキストメニューテスト',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/facilities/{$this->facility->id}")
                ->click('#documents-tab')
                ->waitFor('#documents')
                ->within('#documents', function ($browser) {
                    // Right-click folder
                    $browser->rightClick('.folder-item[data-name="コンテキストメニューテスト"]')
                        ->waitFor('.context-menu')
                        ->assertVisible('.context-menu')
                        
                        // Verify menu options
                        ->assertSee('開く')
                        ->assertSee('名前を変更')
                        ->assertSee('削除')
                        ->assertVisible('.context-menu .open-option')
                        ->assertVisible('.context-menu .rename-option')
                        ->assertVisible('.context-menu .delete-option')
                        
                        // Click outside to close menu
                        ->click('.document-container')
                        ->waitUntilMissing('.context-menu');
                });
        });
    }
}