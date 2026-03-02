<?php

namespace App\Http\Controllers;

use App\Models\LevelOfCare;
use App\Models\ServiceCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceCodeController extends Controller
{
    public function index()
    {
        $serviceCodes = ServiceCode::with('levelsOfCare')
            ->orderBy('service_code')
            ->get();

        return view('service_codes.index', compact('serviceCodes'));
    }

    public function create()
    {
        $levels = LevelOfCare::orderBy('display_name')->get();

        return view('service_codes.create', compact('levels'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateServiceCode($request);

        DB::transaction(function () use ($validated, $request) {
            $serviceCode = ServiceCode::create([
                'service_code' => $validated['service_code'],
                'friendly_name' => $validated['friendly_name'],
                'length_data' => $validated['length_data'],
                'service_type' => $validated['service_type'],
            ]);

            $serviceCode->levelsOfCare()->sync($validated['level_of_care_ids']);
            $serviceCode->prices()->createMany($this->normalizePriceRows($request));
        });

        return redirect()->route('service-codes.index')
            ->with('success', 'Service code created successfully.');
    }

    public function show(ServiceCode $serviceCode)
    {
        $serviceCode->load(['levelsOfCare', 'prices' => fn ($query) => $query->orderBy('starting_date')]);

        return view('service_codes.show', compact('serviceCode'));
    }

    public function edit(ServiceCode $serviceCode)
    {
        $serviceCode->load([
            'levelsOfCare',
            'prices' => fn ($query) => $query->orderBy('starting_date'),
        ]);
        $levels = LevelOfCare::orderBy('display_name')->get();

        return view('service_codes.edit', compact('serviceCode', 'levels'));
    }

    public function update(Request $request, ServiceCode $serviceCode)
    {
        $validated = $this->validateServiceCode($request, $serviceCode->id);

        DB::transaction(function () use ($validated, $request, $serviceCode) {
            $serviceCode->update([
                'service_code' => $validated['service_code'],
                'friendly_name' => $validated['friendly_name'],
                'length_data' => $validated['length_data'],
                'service_type' => $validated['service_type'],
            ]);

            $serviceCode->levelsOfCare()->sync($validated['level_of_care_ids']);
            $serviceCode->prices()->delete();
            $serviceCode->prices()->createMany($this->normalizePriceRows($request));
        });

        return redirect()->route('service-codes.index')
            ->with('success', 'Service code updated successfully.');
    }

    public function destroy(ServiceCode $serviceCode)
    {
        $serviceCode->delete();

        return redirect()->route('service-codes.index')
            ->with('success', 'Service code deleted successfully.');
    }

    private function validateServiceCode(Request $request, ?int $serviceCodeId = null): array
    {
        $validator = Validator::make($request->all(), [
            'level_of_care_ids' => ['required', 'array', 'min:1'],
            'level_of_care_ids.*' => ['required', 'exists:level_of_cares,id'],
            'service_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_codes', 'service_code')->ignore($serviceCodeId),
            ],
            'friendly_name' => ['nullable', 'string', 'max:255'],
            'length_data' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'price_starting_date' => ['required', 'array', 'min:1'],
            'price_starting_date.*' => ['nullable', 'date'],
            'price_ending_date' => ['required', 'array', 'min:1'],
            'price_ending_date.*' => ['nullable', 'date'],
            'price_amount' => ['required', 'array', 'min:1'],
            'price_amount.*' => ['required', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $rows = $this->collectPriceRows($request);

            if (empty($rows)) {
                $validator->errors()->add('price_amount', 'At least one price range is required.');
                return;
            }

            foreach ($rows as $index => $row) {
                if ($row['starting_date'] && $row['ending_date']) {
                    $start = Carbon::parse($row['starting_date']);
                    $end = Carbon::parse($row['ending_date']);

                    if ($end->lt($start)) {
                        $validator->errors()->add("price_ending_date.$index", 'Ending date must be on or after the starting date.');
                    }
                }
            }

            $sortedRows = $rows;
            usort($sortedRows, function ($a, $b) {
                if ($a['starting_date'] === null) {
                    return -1;
                }
                if ($b['starting_date'] === null) {
                    return 1;
                }
                return strcmp($a['starting_date'], $b['starting_date']);
            });

            $rowCount = count($sortedRows);

            foreach ($sortedRows as $index => $row) {
                if ($row['starting_date'] === null && $index !== 0) {
                    $validator->errors()->add('price_starting_date', 'Only the first starting date can be empty.');
                }

                if ($row['ending_date'] === null && $index !== $rowCount - 1) {
                    $validator->errors()->add('price_ending_date', 'Only the last ending date can be empty.');
                }
            }

            $previousEnd = null;
            foreach ($sortedRows as $row) {
                $currentStart = $row['starting_date'] ? Carbon::parse($row['starting_date']) : null;

                if ($previousEnd !== null) {
                    if ($currentStart === null || $previousEnd->gte($currentStart)) {
                        $validator->errors()->add('price_starting_date', 'Price date ranges cannot overlap.');
                        break;
                    }
                }

                $previousEnd = $row['ending_date'] ? Carbon::parse($row['ending_date']) : null;
            }
        });

        return $validator->validate();
    }

    private function collectPriceRows(Request $request): array
    {
        $starts = $request->input('price_starting_date', []);
        $ends = $request->input('price_ending_date', []);
        $prices = $request->input('price_amount', []);

        $rowCount = max(count($starts), count($ends), count($prices));
        $rows = [];

        for ($index = 0; $index < $rowCount; $index++) {
            if (! array_key_exists($index, $prices)) {
                continue;
            }

            $rows[] = [
                'starting_date' => ($starts[$index] ?? null) ?: null,
                'ending_date' => ($ends[$index] ?? null) ?: null,
                'price' => $prices[$index],
            ];
        }

        return $rows;
    }

    private function normalizePriceRows(Request $request): array
    {
        $rows = $this->collectPriceRows($request);

        return array_map(function ($row) {
            return [
                'starting_date' => $row['starting_date'] ?: null,
                'ending_date' => $row['ending_date'] ?: null,
                'price' => $row['price'],
            ];
        }, $rows);
    }
}
