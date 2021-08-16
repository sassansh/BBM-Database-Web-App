<?php


use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Result;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


/**
 * Kudos to https://stackoverflow.com/questions/39384644/extension-gd-is-missing-from-your-system-laravel-composer-update#comment66109575_39385266
 * for help with installing the excel package!
 * Class ExcelExport
 */
class ExcelExport
{
    private Result $result;
    private Spreadsheet $spreadsheet;

    public function __construct(Result $result)
    {
        $this->result = $result;
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * @throws FileMakerException
     */
    public function addDataToSheet() {
        // Make sure the results has less than 2000 records
        if ($this->result->fetchCount > 2000)
            return;

        $sheet = $this->spreadsheet->getActiveSheet();

        $records = $this->result->getRecords();

        $sheet->fromArray(
            $this->result->getFields(),
        );

        $rowIndex = 2;

        foreach ($records as $record) {
            $values = [];

            foreach ($record->getFields() as $field)
                array_push($values, $record->getField($field));

            $sheet->fromArray(
                $values,
                startCell: "A${rowIndex}"
            );

            $rowIndex++;
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getFile() {
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, "Xlsx");
        $writer->save("BBM_Download_data");
    }

}