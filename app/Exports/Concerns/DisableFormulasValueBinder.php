<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Str;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;

class DisableFormulasValueBinder extends DefaultValueBinder
{
    /**
     * @throws Exception
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && Str::startsWith($value, '=')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
