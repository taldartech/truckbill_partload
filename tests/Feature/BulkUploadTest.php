<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Good;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthContext(array $permissions = []): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Test Bulk Upload Co',
            'user_id' => $owner->id,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'owner' => $owner,
            'user' => $user,
        ];
    }

    public function test_goods_template_download(): void
    {
        $context = $this->createAuthContext(['create goods']);

        $response = $this->actingAs($context['user'])
            ->get(route('goods.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('goods_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_package_types_template_download(): void
    {
        $context = $this->createAuthContext(['create package_types']);

        $response = $this->actingAs($context['user'])
            ->get(route('package-types.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('package_types_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_truck_types_template_download(): void
    {
        $context = $this->createAuthContext(['create truck_types']);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-types.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('truck_types_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_goods_bulk_upload_success_and_skips_empty_rows(): void
    {
        $context = $this->createAuthContext(['create goods']);

        $csvContent = "Name\n"
            ."Cotton Bales\n"
            ."   \n" // empty row 3
            ."Industrial Machinery\n"
            ."\n" // empty row 5
            ."Steel Rods\n";

        $file = UploadedFile::fake()->createWithContent('goods.csv', $csvContent);

        $response = $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 3,
                'successful' => 3,
                'failed' => 0,
                'skipped' => 2,
            ],
            'errors' => [],
        ]);

        $this->assertDatabaseHas('goods', ['name' => 'Cotton Bales']);
        $this->assertDatabaseHas('goods', ['name' => 'Industrial Machinery']);
        $this->assertDatabaseHas('goods', ['name' => 'Steel Rods']);
    }

    public function test_goods_bulk_upload_validation_and_duplicate_handling(): void
    {
        $context = $this->createAuthContext(['create goods']);

        // Pre-create an existing good
        Good::create([
            'owner_id' => $context['owner']->id,
            'name' => 'Existing Rods',
        ]);

        $csvContent = "Name\n"
            ."Existing Rods\n" // Row 2: already exists in DB
            ."\n" // Row 3: empty row (skipped)
            ."\n" // Row 4: empty row (skipped)
            ."New Product\n" // Row 5: valid
            ."\n" // Row 6: empty row (skipped)
            ."New Product\n"; // Row 7: duplicate within file batch

        $file = UploadedFile::fake()->createWithContent('goods.csv', $csvContent);

        $response = $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'total' => 3,
                'successful' => 1,
                'failed' => 2,
                'skipped' => 3,
            ],
        ]);

        $json = $response->json();
        $this->assertCount(2, $json['errors']);
        $this->assertEquals(2, $json['errors'][0]['row']);
        $this->assertStringContainsString('already exists', $json['errors'][0]['message']);
        $this->assertEquals(7, $json['errors'][1]['row']);
        $this->assertStringContainsString('Duplicate', $json['errors'][1]['message']);

        // 'New Product' was successfully saved once
        $this->assertDatabaseHas('goods', ['name' => 'New Product']);
    }

    public function test_package_types_bulk_upload(): void
    {
        $context = $this->createAuthContext(['create package_types']);

        $csvContent = "Name\n"
            ."Wooden Box\n"
            ."Heavy Carton\n"
            ."Steel Drum\n";

        $file = UploadedFile::fake()->createWithContent('packages.csv', $csvContent);

        $response = $this->actingAs($context['user'])
            ->post(route('package-types.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 3,
                'successful' => 3,
                'failed' => 0,
                'skipped' => 0,
            ],
        ]);

        $this->assertDatabaseHas('package_types', ['name' => 'Wooden Box']);
        $this->assertDatabaseHas('package_types', ['name' => 'Heavy Carton']);
        $this->assertDatabaseHas('package_types', ['name' => 'Steel Drum']);
    }

    public function test_truck_types_bulk_upload(): void
    {
        $context = $this->createAuthContext(['create truck_types']);

        $csvContent = "Name\n"
            ."14 Ft Open Body\n"
            ."17 Ft Container\n"
            ."32 Ft Multi-Axle\n";

        $file = UploadedFile::fake()->createWithContent('truck_types.csv', $csvContent);

        $response = $this->actingAs($context['user'])
            ->post(route('truck-types.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 3,
                'successful' => 3,
                'failed' => 0,
                'skipped' => 0,
            ],
        ]);

        $this->assertDatabaseHas('truck_types', ['name' => '14 Ft Open Body']);
        $this->assertDatabaseHas('truck_types', ['name' => '17 Ft Container']);
        $this->assertDatabaseHas('truck_types', ['name' => '32 Ft Multi-Axle']);
    }

    public function test_bulk_upload_requires_create_permission(): void
    {
        $context = $this->createAuthContext([]); // No permissions

        $file = UploadedFile::fake()->createWithContent('test.csv', "Name\nItem 1");

        $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file])
            ->assertForbidden();

        $this->actingAs($context['user'])
            ->post(route('package-types.bulk-upload'), ['file' => $file])
            ->assertForbidden();

        $this->actingAs($context['user'])
            ->post(route('truck-types.bulk-upload'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_bulk_upload_rejects_txt_files(): void
    {
        $context = $this->createAuthContext(['create goods']);

        $file = UploadedFile::fake()->createWithContent('goods.txt', "Name\nItem 1");

        $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_bulk_upload_supports_excel_xlsx_file(): void
    {
        $context = $this->createAuthContext(['create goods']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('A2', 'Excel Good 1');
        $sheet->setCellValue('A3', 'Excel Good 2');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $file = new UploadedFile(
            $tempFile,
            'goods.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 2,
                'successful' => 2,
                'failed' => 0,
            ],
        ]);

        $this->assertDatabaseHas('goods', ['name' => 'Excel Good 1']);
        $this->assertDatabaseHas('goods', ['name' => 'Excel Good 2']);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function test_bulk_upload_supports_real_csv_file_with_text_plain_mime(): void
    {
        $context = $this->createAuthContext(['create truck_types']);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_').'.csv';
        file_put_contents($tempFile, "Name\n20 Ft High Cube\n");

        $file = new UploadedFile(
            $tempFile,
            'truck_types.csv',
            null,
            null,
            true
        );

        $response = $this->actingAs($context['user'])
            ->post(route('truck-types.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 1,
                'successful' => 1,
                'failed' => 0,
            ],
        ]);

        $this->assertDatabaseHas('truck_types', ['name' => '20 Ft High Cube']);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
