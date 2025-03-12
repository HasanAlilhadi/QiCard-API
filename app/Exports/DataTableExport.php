<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataTableExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomChunkSize, WithTitle
{
    use Exportable;

    protected Builder $query;
    protected array $headings;
    protected array $mappings;
    protected Model $model;
    protected bool $useTranslations;
    protected ?string $translationPrefix;
    protected array $relations = [];

    public function __construct(
        Builder $query,
        Model $model,
        array $headings = [],
        array $mappings = [],
        bool $useTranslations = true,
        ?string $translationPrefix = null
    ) {
        $this->query = $query;
        $this->model = $model;
        $this->useTranslations = $useTranslations;
        $this->translationPrefix = $translationPrefix;

        // Initialize headings and mappings
        $this->initializeExportFields($headings, $mappings);

        // Extract relations from mappings
        $this->extractRelations();
    }

    protected function initializeExportFields(array $headings, array $mappings): void
    {
        // If custom headings/mappings are provided, use them
        if (!empty($headings) && !empty($mappings)) {
            $this->headings = $headings;
            $this->mappings = $mappings;
            return;
        }

        // Get model's custom export settings if available
        if (isset($this->model->exportHeadings) && isset($this->model->exportMappings)) {
            $this->headings = $this->model->exportHeadings;
            $this->mappings = $this->model->exportMappings;
            return;
        }

        // Fallback to fillable fields
        $fillable = $this->model->getFillable();
        $this->headings = array_map(function ($field) {
            return $this->formatHeading($field);
        }, $fillable);
        $this->mappings = array_combine($fillable, $fillable);
    }

    protected function extractRelations(): void
    {
        $this->relations = collect($this->mappings)
            ->filter(fn($mapping) => is_string($mapping) && str_contains($mapping, '.'))
            ->map(function ($mapping) {
                $parts = explode('.', $mapping);
                array_pop($parts); // Remove the attribute part
                return implode('.', $parts);
            })
            ->unique()
            ->values()
            ->toArray();
    }

    public function headings(): array
    {
        // Get locale
        $locale = request()->header('Language', config('app.locale'));
        $isRTL = strtolower($locale) === 'ar';

        // Get translated headings
        $headings = array_map(function ($heading) {
//            ddh(__('messages.channel.channel_name'));
            $translation = __('fields.' . $heading);
            if ($translation !== $heading) {
                return $translation;
            }

            return ucwords(str_replace(['_', '.'], ' ', $heading));
        }, $this->headings);

        // Reverse the order for Arabic
        if ($isRTL) {
            $headings = array_reverse($headings);
        }

        return $headings;
    }

    public function query()
    {
        // Clone the query to avoid modifying the original
        $query = clone $this->query;

        // Add eager loading for relations if needed
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }

        return $query;
    }

    protected function formatValue($value, ?string $field = null): string | null
    {
        // Return empty string for null values
        if ($value === null) {
            return '-';
        }

        // Handle Collection instances
        if ($value instanceof \Illuminate\Support\Collection) {
            return $this->handleCollectionValue($value, $field);
        }

        // Handle boolean values
        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        // Handle DateTime/Carbon instances
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        // Handle Model instances
        if ($value instanceof Model) {
            return $this->handleModelValue($value, $field);
        }

        // Handle generic objects
        if (is_object($value)) {
            return $this->handleObjectValue($value);
        }

        // Handle arrays
        if (is_array($value)) {
            return $this->handleArrayValue($value, $field);
        }

        // Return string value for everything else
        return (string)$value;
    }

    protected function handleCollectionValue(\Illuminate\Support\Collection $collection, ?string $field = null): string | null
    {
        // Return empty string for empty collections
        if ($collection->isEmpty()) {
            return '-';
        }

        // If the field is specified (e.g., channel_name)
        if ($field && $collection->first() instanceof Model) {

            // Directly pluck the specified field
            return $collection->pluck($field)
                ->filter()
                ->values() // Reset array keys
                ->join(', ');
        }

        // Handle collection of models without specified field
        if ($collection->first() instanceof Model) {
            return $collection->map(function ($item) {
                return $this->handleModelValue($item);
            })->filter()->join(', ');
        }

        // For simple collections
        return $collection->filter()->join(', ');
    }

    protected function handleModelValue(Model $model, ?string $field = null): string | null
    {
        if ($field && method_exists($model, $field)) {
            return (string)$model->$field ?? '-';
        }

        // Try common fields in order of preference
        $commonFields = ['name', 'title', 'label', 'display_name', 'value'];
        foreach ($commonFields as $commonField) {
            if (isset($model->$commonField)) {
                return (string)$model->$commonField;
            }
        }

        // Fallback to ID or empty string
        return $model->id ? (string)$model->id : '-';
    }

    protected function handleObjectValue(object $object): string | null
    {
        if (method_exists($object, '__toString')) {
            return (string)$object;
        }

        if (method_exists($object, 'toArray')) {
            $array = $object->toArray();
            return $this->handleArrayValue($array);
        }

        return '-';
    }

    protected function handleArrayValue(array $value, ?string $field = null): string | null
    {
        // If it's an array of models
        if (!empty($value) && isset($value[0]) && $value[0] instanceof Model) {
            return collect($value)->map(function ($item) use ($field) {
                return $this->handleModelValue($item, $field);
            })->filter()->join(', ');
        }

        // For regular arrays
        return collect($value)
            ->map(fn($item) => $this->formatValue($item, $field))
            ->filter()
            ->join(', ');
    }

    public function map($row): array
    {
        $data = [];
        foreach ($this->mappings as $mapping) {
            try {
                $value = $this->resolveMapping($row, $mapping);
                $data[] = $value;
            } catch (\Exception $e) {
                \Log::error("Export mapping error for {$mapping}: " . $e->getMessage());
                $data[] = '-';
            }
        }

        // Get locale and reverse data if Arabic
        $locale = request()->header('Language', config('app.locale'));
        if (strtolower($locale) === 'ar') {
            $data = array_reverse($data);
        }

        return $data;
    }

    protected function resolveMapping($row, $mapping): string | null
    {
        // Handle callable mappings
        if (is_callable($mapping)) {
            return $this->formatValue($mapping($row));
        }

        // Handle string mappings
        if (is_string($mapping)) {
            // Handle relation.field format
            if (str_contains($mapping, '.')) {
                return $this->resolveRelationMapping($row, $mapping);
            }

            // Handle direct field access
            return $this->formatValue($row->{$mapping});
        }

        return '-';
    }

    protected function resolveRelationMapping($row, string $mapping): string | null
    {
        $parts = explode('.', $mapping);
        $field = array_pop($parts);
        $relation = implode('.', $parts);

        try {
            // Get the relation value using the relationship method
            $value = data_get($row, $relation);

            // Handle different types of relations
            if ($value instanceof \Illuminate\Support\Collection) {
                // For many-to-many or has-many relations
                return $this->formatValue($value, $field);
            }

            if ($value instanceof Model) {
                // For belongs-to or has-one relations
                return $this->formatValue(data_get($value, $field) ?? '-');
            }

            if (is_array($value)) {
                // For array values
                return $this->handleArrayValue($value, $field);
            }

            // Get the exact field value if the above cases don't match
            $fullValue = data_get($row, $mapping);
            return $this->formatValue($fullValue ?? '-');
        } catch (\Exception $e) {
            \Log::error("Relation mapping error for {$mapping}: " . $e->getMessage());
            return '-';
        }
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Get locale and determine if RTL
        $locale = request()->header('Language', config('app.locale'));
        $isRTL = strtolower($locale) === 'ar';

        if ($isRTL) {
            // Get all the data including headers
            $data = $sheet->toArray();

            // Clear the sheet
            $sheet->fromArray([], NULL, 'A1');

            // Rewrite the data with reversed columns
            foreach ($data as $rowIndex => $row) {
                // Reverse the columns in each row
                $reversedRow = array_reverse($row);
                $sheet->fromArray([$reversedRow], NULL, 'A' . ($rowIndex + 1));
            }

            // Set RTL for the sheet after data is reversed
            $sheet->setRightToLeft(true);
        }

        // Style header row
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E4E4E4'],
            ],
            'alignment' => [
                'horizontal' => $isRTL ?
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT :
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrap' => true,
            ],
        ]);

        // Style all cells
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => $isRTL ?
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT :
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrap' => true,
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set row height
        $sheet->getDefaultRowDimension()->setRowHeight(15);
        $sheet->getRowDimension(1)->setRowHeight(20);
    }

    public function chunkSize(): int
    {
        return 1000; // Adjust based on your needs
    }

    public function title(): string
    {
        return class_basename($this->model);
    }
}
