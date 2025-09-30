<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\CreateFolderRequest;
use App\Http\Requests\RenameFolderRequest;
use App\Http\Requests\UploadFileRequest;

class ValidationRequestsTest extends TestCase
{
    /** @test */
    public function create_folder_request_can_be_instantiated()
    {
        $request = new CreateFolderRequest();
        $this->assertInstanceOf(CreateFolderRequest::class, $request);
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function rename_folder_request_can_be_instantiated()
    {
        $request = new RenameFolderRequest();
        $this->assertInstanceOf(RenameFolderRequest::class, $request);
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function upload_file_request_can_be_instantiated()
    {
        $request = new UploadFileRequest();
        $this->assertInstanceOf(UploadFileRequest::class, $request);
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function create_folder_request_has_rules_method()
    {
        $request = new CreateFolderRequest();
        $this->assertTrue(method_exists($request, 'rules'));
    }

    /** @test */
    public function rename_folder_request_has_rules_method()
    {
        $request = new RenameFolderRequest();
        $this->assertTrue(method_exists($request, 'rules'));
    }

    /** @test */
    public function upload_file_request_has_rules_method()
    {
        $request = new UploadFileRequest();
        $this->assertTrue(method_exists($request, 'rules'));
    }

    /** @test */
    public function validation_requests_have_custom_messages()
    {
        $createRequest = new CreateFolderRequest();
        $renameRequest = new RenameFolderRequest();
        $uploadRequest = new UploadFileRequest();

        $this->assertIsArray($createRequest->messages());
        $this->assertIsArray($renameRequest->messages());
        $this->assertIsArray($uploadRequest->messages());

        $this->assertNotEmpty($createRequest->messages());
        $this->assertNotEmpty($renameRequest->messages());
        $this->assertNotEmpty($uploadRequest->messages());
    }

    /** @test */
    public function validation_requests_have_custom_attributes()
    {
        $createRequest = new CreateFolderRequest();
        $renameRequest = new RenameFolderRequest();
        $uploadRequest = new UploadFileRequest();

        $this->assertIsArray($createRequest->attributes());
        $this->assertIsArray($renameRequest->attributes());
        $this->assertIsArray($uploadRequest->attributes());
    }
}