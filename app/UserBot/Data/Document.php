<?php

namespace App\UserBot\Data;

use App\Singleton;
use App\UserBot\SessionsHandler;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Document
{
    use Singleton;

    private string $file;

    public array $wanted_columns = [
        'email' => [
            'columnIndex' => '',
        ],
        'phone' => [
            'columnIndex' => '',
    ]];

    public array $result_cells = [
        'OSINT_email' => [
            'columnIndex' => '',
        ],
        'OSINT_phone' => [
            'columnIndex' => '',
        ],
        'OSINT_socials' => [
            'columnIndex' => '',
        ],
    ];

    private array $meta_cells = [
        '_current_cell' => [
            'columnIndex' => '',
        ]
    ];

    private int $chunk_size = 14;

    private Worksheet $activeSheet;
    private IReader $reader;
    private IReadFilter $chunkFilter;
    private Spreadsheet $document;

    public function __construct() {}

    public function init($file)
    {
        $this->file = $file;

        $this->document = IOFactory::load( $file );
        $this->activeSheet = $this->document->getActiveSheet();

        $this->result_cells = $this->initColumns($this->result_cells);
        $this->meta_cells = $this->initColumns($this->meta_cells);
        $this->wanted_columns = $this->initColumns($this->wanted_columns, false);

        $this->setupChunkReader();

        $this->saveDocument();
    }

    /**
     * Save changes into file
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function saveDocument()
    {
        $writer = IOFactory::createWriter($this->document, 'Xlsx');
        $writer->save($this->file);
    }

    /**
     * Create`s non-existent meta columns in document
     *
     * @param array $source_arr
     * @param bool $createColumns
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function initColumns(array $source_arr, bool $createColumns = true) :array
    {
        $highestColumn = $this->activeSheet->getHighestColumn(0);
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $maxColumnsNeeded = $highestColumnIndex + count($source_arr);

        $column_names = [];
        for ($i = 1; $i <= $maxColumnsNeeded; $i++) {
            $column =  Coordinate::stringFromColumnIndex($i);
            $cell = $this->activeSheet->getCell($column.'1');
            $key = $cell->getValue();

            if (array_key_exists($key, $source_arr)) {
                $source_arr[$key]['columnIndex'] = $column;
            }
            elseif ($key === null) {
                if ( $key = array_key_first(array_diff_key($source_arr, $column_names)) ) {
                    $source_arr[$key]['columnIndex'] = $column;
                }
                if ($createColumns) {
                    $cell->setValue( $key ?: null );
                }
            }

            $column_names[$key] = null;
        }

        return $source_arr;
    }

    /**
     * Setup standard PhpOffice chunk-reader
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function setupChunkReader() :void
    {
        $this->reader = IOFactory::createReader('Xlsx');
        $this->chunkFilter = new ChunkReadFilter();

        $this->reader->setReadFilter($this->chunkFilter);
        $this->reader->setReadEmptyCells(false);
    }

    private function getLastModifiedCell()
    {
        $column = $this->meta_cells['_current_cell']['columnIndex'];
        return $this->activeSheet->getCell($column.'2')->getValue();
    }

    /**
     * Row by row iterator of presented sheet
     *
     * @throws Exception
     */
    public function iterator(): \Generator
    {
        $highestRow = $this->activeSheet->getHighestRow();

        $firstColumn = $this->wanted_columns[array_key_first($this->wanted_columns)]['columnIndex'];
        $lastColumn = $this->wanted_columns[array_key_last($this->wanted_columns)]['columnIndex'];

        $lastModifiedRow = null;
        if (($modifiedCell = $this->getLastModifiedCell()) !== null) {
            preg_match("/([a-zA-Z]+)(\d+)/", $modifiedCell, $matches);
            if (!$matches) {
                throw new Exception("Неверно задано значение '_current_cell'!");
            }
            list(, , $lastModifiedRow) = $matches;
        }
        $startingRow = $lastModifiedRow ?: 2;

        for ($startRow = $startingRow; $startRow < $highestRow; $startRow += $this->chunk_size ){

            $this->chunkFilter->setRows($startRow, $this->chunk_size);
            $spreadSheet = $this->reader->load($this->file);

            $range = $firstColumn.$startRow.':'.$lastColumn.($startRow + $this->chunk_size - 1);


            yield $spreadSheet->getActiveSheet()->rangeToArray( $range, null,false, true, true);
        }

        SessionsHandler::getInstance()->dieScript(null, null);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveToSheet(string $data, int $rowId, string $column)
    {
        $columnIndex = '';

        if ($column === 'email') {
            $columnIndex = $this->result_cells['OSINT_phone']['columnIndex'];
        }
        else if ($column === 'phone') {
            $columnIndex = $this->result_cells['OSINT_email']['columnIndex'];
        }

        $dataCell = $this->activeSheet->getCell($columnIndex . $rowId);

        $lastValue = $dataCell->getValue();
        $dataCell->setValue($lastValue ."\n". $data);

        $this->saveDocument();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveSocialsToSheet(string $data, int $rowId)
    {
        $socialsColumnIndex = $this->result_cells['OSINT_socials']['columnIndex'];

        $socialsCell = $this->activeSheet->getCell($socialsColumnIndex . $rowId);

        $socialsLastValue = $socialsCell->getValue();
        $socialsCell->setValue($socialsLastValue . "\n" . $data);

        $this->saveDocument();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveLastCell($currentColumn, $currentRowId)
    {
        if (!$currentColumn || !$currentRowId) {
            return;
        }
        $index = $this->wanted_columns[$currentColumn]['columnIndex'];

        $currentCell = $this->activeSheet->getCell($this->meta_cells['_current_cell']['columnIndex'] . '2') ;
        $currentCell->setValue($index . $currentRowId);

        $this->saveDocument();
    }

}