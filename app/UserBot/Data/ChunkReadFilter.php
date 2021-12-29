<?php

namespace App\UserBot\Data;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{

    private int $startRow = 0;
    private int $endRow = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }

   public function readCell($columnAddress, $row, $worksheetName = ''): bool
   {
       //  Only read the heading row, and the configured rows
       if ($row >= $this->startRow && $row < $this->endRow) {
           return true;
       }
       return false;
   }

}