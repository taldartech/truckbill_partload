<?php

namespace App\Http\Controllers;

use App\Models\TruckType;
use App\Services\BulkUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TruckTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view truck_types', only: ['index', 'show']),
            new Middleware('permission:create truck_types', only: ['create', 'store', 'quickStore', 'bulkUpload', 'bulkUploadTemplate']),
            new Middleware('permission:edit truck_types', only: ['edit', 'update']),
            new Middleware('permission:delete truck_types', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $truckTypes = TruckType::query()
            ->when($search !== '', fn($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TruckTypes/Index', [
            'truckTypes' => $truckTypes,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        TruckType::create($validated);

        return redirect()->back()->with('success', 'Truck type created successfully.');
    }

    public function show(TruckType $truckType)
    {
        return Inertia::render('TruckTypes/Show', [
            'truckType' => $truckType,
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $truckType = TruckType::create($validated);

        return response()->json([
            'truck_type' => [
                'id' => $truckType->id,
                'name' => $truckType->name,
            ],
        ], 201);
    }

    public function update(Request $request, TruckType $truckType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $truckType->update($validated);

        return redirect()->back()->with('success', 'Truck type updated successfully.');
    }

    public function destroy(TruckType $truckType)
    {
        $truckType->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $truckType->delete();

        return redirect()->route('truck-types.index')->with('success', 'Truck type deleted successfully.');
    }

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: ['Name'],
            fileName: 'truck_types_bulk_upload_template.csv'
        );
    }

    public function bulkUpload(Request $request, BulkUploadService $bulkUploadService): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'extensions:csv,xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
            'file.extensions' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
        ]);

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: TruckType::class,
            columnSpecs: [
                'name' => [
                    'label' => 'Name',
                    'required' => true,
                    'aliases' => ['name', 'truck_type', 'truck_type_name', 'type'],
                    'rules' => ['required', 'string', 'max:255'],
                    'unique' => true,
                ],
            ]
        );

        return response()->json($result);
    }
}
