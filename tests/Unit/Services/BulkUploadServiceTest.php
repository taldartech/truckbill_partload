<?php

namespace Tests\Unit\Services;

use App\Services\BulkUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class BulkUploadServiceTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('bulk_upload_items', function ($table) {
            $table->id();
            $table->string('name')->nullable()->unique();
            $table->string('qty')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function specs(bool $uniqueName = true): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'required' => true,
                'aliases' => ['Item Name'],
                'rules' => ['required', 'max:20'],
                'unique' => $uniqueName,
                'transform' => fn (string $v): string => strtoupper($v),
            ],
            'qty' => ['label' => 'Qty'],
        ];
    }

    private function tempPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bulk').'.'.$extension;
        $this->tempFiles[] = $path;

        return $path;
    }

    private function csvUpload(string $contents, string $extension = 'csv'): UploadedFile
    {
        $path = $this->tempPath($extension);
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'upload.'.$extension, null, null, true);
    }

    private function model(): string
    {
        return BulkUploadItem::class;
    }

    public function test_unreadable_file_is_rejected(): void
    {
        $upload = $this->csvUpload("Name,Qty\nA,1\n");
        unlink($upload->getPathname());

        $result = (new BulkUploadService)->process($upload, $this->model(), $this->specs());

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to read uploaded file.', $result['message']);
        $this->assertSame('File', $result['errors'][0]['field']);
    }

    public function test_unsupported_extension_is_rejected(): void
    {
        $upload = $this->csvUpload('hello', 'txt');

        $result = (new BulkUploadService)->process($upload, $this->model(), $this->specs());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported file format', $result['message']);
    }

    public function test_empty_and_blank_header_files_are_rejected(): void
    {
        foreach (['', "\n", ",,\n1,2\n"] as $contents) {
            $result = (new BulkUploadService)->process($this->csvUpload($contents), $this->model(), $this->specs());

            $this->assertFalse($result['success']);
            $this->assertSame('Header', $result['errors'][0]['field']);
            $this->assertSame('The uploaded file does not contain valid headers.', $result['message']);
        }
    }

    public function test_missing_required_column_is_rejected(): void
    {
        $result = (new BulkUploadService)->process(
            $this->csvUpload("Qty\n1\n"),
            $this->model(),
            $this->specs()
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required column(s): Name', $result['message']);
    }

    public function test_successful_import_with_semicolon_delimiter_bom_aliases_and_transform(): void
    {
        $contents = "\xEF\xBB\xBFItem Name;Qty;\nalpha;1;\nbeta;2;\n;;\n";

        $result = (new BulkUploadService)->process(
            $this->csvUpload($contents),
            $this->model(),
            $this->specs()
        );

        $this->assertTrue($result['success']);
        $this->assertSame(['total' => 2, 'successful' => 2, 'failed' => 0, 'skipped' => 1], $result['summary']);
        $this->assertSame(['ALPHA', 'BETA'], BulkUploadItem::query()->orderBy('id')->pluck('name')->all());
        $this->assertStringContainsString('1 empty row skipped', $result['message']);
    }

    public function test_tab_delimiter_and_single_column_default_comma(): void
    {
        $tab = (new BulkUploadService)->process(
            $this->csvUpload("Name\tQty\nA\t1\n"),
            $this->model(),
            $this->specs()
        );
        $this->assertSame(1, $tab['summary']['successful']);

        $single = (new BulkUploadService)->process(
            $this->csvUpload("Name\nB\n"),
            $this->model(),
            $this->specs()
        );
        $this->assertSame(1, $single['summary']['successful']);
    }

    public function test_validation_file_duplicate_and_database_duplicate_errors(): void
    {
        BulkUploadItem::create(['name' => 'EXISTING']);

        $contents = "Name,Qty\n,1\nDUP,1\ndup,2\nexisting,3\nok,4\n".str_repeat('x', 30).",5\n";

        $result = (new BulkUploadService)->process(
            $this->csvUpload($contents),
            $this->model(),
            $this->specs()
        );

        $this->assertFalse($result['success']);
        $this->assertSame(2, $result['summary']['successful']);
        $messages = implode('|', array_column($result['errors'], 'message'));
        $this->assertStringContainsString('required', $messages);
        $this->assertStringContainsString('Duplicate Name "DUP" in uploaded file (first seen on row 3)', $messages);
        $this->assertStringContainsString('already exists in records', $messages);
        $this->assertStringContainsString('must not be greater than 20', $messages);

        $rows = array_column($result['errors'], 'row');
        $sorted = $rows;
        sort($sorted);
        $this->assertSame($sorted, $rows);
    }

    public function test_row_validator_before_save_and_save_handler_are_used(): void
    {
        $saved = [];

        $result = (new BulkUploadService)->process(
            $this->csvUpload("Name,Qty\na,1\nb,2\nc,3\n"),
            $this->model(),
            $this->specs(),
            fn (array $data): array => array_merge($data, ['qty' => 'before']),
            function (array $data, int $row) {
                if ($data['name'] === 'B') {
                    return ['Row B is not allowed'];
                }
                if ($data['name'] === 'C') {
                    return [['row' => $row, 'field' => 'Custom', 'value' => 'c', 'message' => 'Custom failure']];
                }

                return [];
            },
            function (array $data, int $row) use (&$saved): void {
                $saved[] = [$data, $row];
            }
        );

        $this->assertSame(1, $result['summary']['successful']);
        $this->assertSame(2, $result['summary']['failed']);
        $this->assertCount(1, $saved);
        $this->assertSame('A', $saved[0][0]['name']);
        $this->assertSame('before', $saved[0][0]['qty']);
        $this->assertSame(0, BulkUploadItem::query()->count());
        $messages = array_column($result['errors'], 'message');
        $this->assertContains('Row B is not allowed', $messages);
        $this->assertContains('Custom failure', $messages);
    }

    public function test_row_level_save_exception_is_reported_without_aborting_chunk(): void
    {
        $result = (new BulkUploadService)->process(
            $this->csvUpload("Name,Qty\na,1\nb,2\n"),
            $this->model(),
            $this->specs(),
            null,
            null,
            function (array $data): void {
                if ($data['name'] === 'A') {
                    throw new RuntimeException('boom');
                }
            }
        );

        $this->assertSame(1, $result['summary']['successful']);
        $this->assertSame(1, $result['summary']['failed']);
        $this->assertSame('Database', $result['errors'][0]['field']);
        $this->assertStringContainsString('boom', $result['errors'][0]['message']);
    }

    public function test_transaction_level_failure_rolls_back_chunk_counts(): void
    {
        DB::shouldReceive('transaction')->once()->andThrow(new RuntimeException('commit failed'));

        $result = (new BulkUploadService)->process(
            $this->csvUpload("Name,Qty\na,1\nb,2\n"),
            $this->model(),
            $this->specs()
        );

        $this->assertSame(0, $result['summary']['successful']);
        $this->assertSame('Transaction', $result['errors'][0]['field']);
        $this->assertStringContainsString('commit failed', $result['errors'][0]['message']);
    }

    public function test_large_uploads_are_chunked_and_error_list_is_capped(): void
    {
        $rows = ['Name,Qty'];
        for ($i = 1; $i <= 260; $i++) {
            $rows[] = "item{$i},1";
        }

        $result = (new BulkUploadService)->process(
            $this->csvUpload(implode("\n", $rows)."\n"),
            $this->model(),
            $this->specs()
        );

        $this->assertSame(260, $result['summary']['successful']);
        $this->assertSame(260, BulkUploadItem::query()->count());

        $bad = ['Name,Qty'];
        for ($i = 1; $i <= 1005; $i++) {
            $bad[] = ',1';
        }

        $capped = (new BulkUploadService)->process(
            $this->csvUpload(implode("\n", $bad)."\n"),
            $this->model(),
            $this->specs()
        );

        $this->assertCount(1000, $capped['errors']);
        $this->assertSame(1005, $capped['summary']['failed']);
        $this->assertStringContainsString('Only the first 1000 errors are listed (5 additional errors omitted)', $capped['message']);
    }

    public function test_additional_error_omitted_message_is_singular(): void
    {
        $bad = ['Name,Qty'];
        for ($i = 1; $i <= 1001; $i++) {
            $bad[] = ',1';
        }

        $result = (new BulkUploadService)->process(
            $this->csvUpload(implode("\n", $bad)."\n"),
            $this->model(),
            $this->specs()
        );

        $this->assertStringContainsString('(1 additional error omitted)', $result['message']);
    }

    public function test_unique_column_with_only_blank_values_skips_database_lookup(): void
    {
        $specs = $this->specs();
        $specs['name']['required'] = false;
        $specs['name']['rules'] = [];

        $result = (new BulkUploadService)->process(
            $this->csvUpload("Name,Qty\n,1\n"),
            $this->model(),
            $specs
        );

        $this->assertSame(1, $result['summary']['successful']);
    }

    public function test_process_chunk_edge_cases_via_reflection(): void
    {
        $service = new BulkUploadService;
        $method = (new ReflectionClass($service))->getMethod('processChunk');

        $successful = 0;
        $failed = 0;
        $errors = [];
        $errorCount = 0;

        $method->invokeArgs($service, [[], $this->model(), $this->specs(), null, null, &$successful, &$failed, &$errors, &$errorCount]);
        $this->assertSame(0, $successful);

        $chunk = [
            ['row' => 2, 'data' => ['name' => '', 'qty' => '1'], 'rawValues' => ['name' => '', 'qty' => '1']],
            ['row' => 3, 'data' => ['name' => 'Z', 'qty' => '1'], 'rawValues' => ['name' => 'Z', 'qty' => '1']],
        ];
        $method->invokeArgs($service, [$chunk, $this->model(), $this->specs(), null, null, &$successful, &$failed, &$errors, &$errorCount]);
        $this->assertSame(2, $successful);

        BulkUploadItem::create(['name' => 'DBDUP']);
        $chunk = [['row' => 2, 'data' => ['name' => 'DBDUP', 'qty' => '1'], 'rawValues' => ['name' => 'dbdup', 'qty' => '1']]];
        $method->invokeArgs($service, [$chunk, $this->model(), $this->specs(), null, null, &$successful, &$failed, &$errors, &$errorCount]);
        $this->assertSame(1, $failed);
    }

    public function test_xlsx_import_succeeds(): void
    {
        $path = $this->tempPath('xlsx');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Name', 'Qty', ''],
            ['one', 1, null],
            [null, null, null],
            ['two', 2, null],
        ]);
        (new XlsxWriter($spreadsheet))->save($path);

        $result = (new BulkUploadService)->process(
            new UploadedFile($path, 'sheet.xlsx', null, null, true),
            $this->model(),
            $this->specs()
        );

        $this->assertSame(['total' => 2, 'successful' => 2, 'failed' => 0, 'skipped' => 1], $result['summary']);
    }

    public function test_xls_import_succeeds(): void
    {
        $path = $this->tempPath('xls');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Name', 'Qty'], ['xls-one', 1]]);
        (new XlsWriter($spreadsheet))->save($path);

        $result = (new BulkUploadService)->process(
            new UploadedFile($path, 'sheet.xls', null, null, true),
            $this->model(),
            $this->specs()
        );

        $this->assertSame(1, $result['summary']['successful']);
    }

    public function test_empty_or_corrupt_spreadsheets_are_rejected(): void
    {
        $path = $this->tempPath('xlsx');
        (new XlsxWriter(new Spreadsheet))->save($path);

        $empty = (new BulkUploadService)->process(
            new UploadedFile($path, 'empty.xlsx', null, null, true),
            $this->model(),
            $this->specs()
        );
        $this->assertSame('Header', $empty['errors'][0]['field']);

        $corrupt = (new BulkUploadService)->process(
            $this->csvUpload('not a spreadsheet', 'xlsx'),
            $this->model(),
            $this->specs()
        );
        $this->assertSame('Header', $corrupt['errors'][0]['field']);
    }

    public function test_unopenable_paths_are_handled_defensively(): void
    {
        $service = new BulkUploadService;
        $reflection = new ReflectionClass($service);
        $missing = sys_get_temp_dir().'/definitely-missing-'.uniqid().'.csv';

        set_error_handler(fn (): bool => true);
        try {
            $this->assertSame(',', $reflection->getMethod('detectDelimiter')->invoke($service, $missing));
            $this->assertNull($reflection->getMethod('readSheet')->invoke($service, $missing, 'csv'));
        } finally {
            restore_error_handler();
        }
    }

    public function test_download_template_streams_csv_with_bom(): void
    {
        $response = (new BulkUploadService)->downloadTemplate(['Name', 'Qty'], 'template.csv');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith("\xEF\xBB\xBF", $output);
        $this->assertStringContainsString('Name,Qty', $output);
        $this->assertStringContainsString('template.csv', $response->headers->get('Content-Disposition'));
    }
}

class BulkUploadItem extends Model
{
    protected $table = 'bulk_upload_items';

    protected $guarded = [];
}
