<?php


namespace App\Generators;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelGenerator extends Generator
{
    public $title;
    public $tables = [];
    public $cells = [];
    private $_rowIndex = 1;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    public function init()
    {
        $this->spreadsheet = new Spreadsheet();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    function generate($output)
    {
        if (substr($output, -4) != 'xlsx') {
            $output .= '.xlsx';
        }
        $this->renderTitle();
        $this->renderTables();
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($output);
        return true;
    }

    protected function renderTitle()
    {
        if ($this->title) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A' . $this->_rowIndex, $this->title)
                ->setTitle($this->title)
                ->mergeCells('A' . $this->_rowIndex . ':F' . $this->_rowIndex);
            $this->spreadsheet->getActiveSheet()->getCell('A' . $this->_rowIndex)
                ->getStyle()->applyFromArray([
                    'font' => [
                        'size' => 18,
                        'bold' => true,
                        'color' => [
                            'argb' => 'FFFFFFFF'
                        ],
                    ],
                ]);
            $this->setBorders($this->_rowIndex);

            $this->setBackground('A' . $this->_rowIndex, 'FF222222');
            $this->_rowIndex++;
        }
    }

    protected function setBorders($rownIndex)
    {
        $this->setCellBorders('A' . $rownIndex);
        $this->setCellBorders('B' . $rownIndex);
        $this->setCellBorders('C' . $rownIndex);
        $this->setCellBorders('D' . $rownIndex);
        $this->setCellBorders('E' . $rownIndex);
        $this->setCellBorders('F' . $rownIndex);
    }

    protected function setCellBorders($pCoordinate)
    {
        $this->spreadsheet->getActiveSheet()
            ->getCell($pCoordinate)
            ->getStyle()
            ->applyFromArray([
                'borders' => [
                    'diagonalDirection' => Borders::DIAGONAL_BOTH,
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
    }

    protected function setBackground($pCoordinate, $rgba)
    {
        $this->spreadsheet->getActiveSheet()
            ->getCell($pCoordinate)
            ->getStyle()
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => $rgba,
                    ],
                ],
            ]);
    }

    protected function setAsHeader($pCoordinate)
    {
        $this->setBackground($pCoordinate, 'FF666666');
        $this->spreadsheet->getActiveSheet()
            ->getCell($pCoordinate)
            ->getStyle()
            ->applyFromArray([
                'font' => [
                    'size' => 12,
                    'bold' => true,
                    'color' => [
                        'argb' => 'FFFFFFFF'
                    ],
                ],
            ]);
    }

    /**
     * @param string|array $pCoordinate
     */
    protected function setAsSubHeader($pCoordinate)
    {
        if (!is_array($pCoordinate)) {
            $pCoordinate = [$pCoordinate];
        }
        foreach ($pCoordinate as $item) {
            $this->setBackground($item, 'FF888888');
            $this->spreadsheet->getActiveSheet()
                ->getCell($item)
                ->getStyle()
                ->applyFromArray([
                    'font' => [
                        'color' => [
                            'argb' => 'FFFFFFFF'
                        ],
                    ],
                ]);
        }

    }


    protected function renderTables()
    {
        foreach ($this->tables as $table) {
            $this->renderTable($table);
            if (isset($table['indexes']) && count($table['indexes']) > 0) {
                $this->renderTableIndexes($table);
            }

        }
    }

    protected function renderTable($table)
    {
        $this->renderTableHeader($table);
        foreach ($table['columns'] as $column) {
            $this->renderTableColumn($column);
        }
        $this->_rowIndex++;
    }

    protected function renderTableHeader($table)
    {
        // 表名
        if ($table['type'] == 'view') {
            $this->spreadsheet->getActiveSheet()
                ->setCellValue('A' . $this->_rowIndex, "视图 `{$table['name']}`")
                ->mergeCells('A' . $this->_rowIndex . ':F' . $this->_rowIndex);
            $this->setBorders($this->_rowIndex);
            $this->setAsHeader('A' . $this->_rowIndex);

            $this->_rowIndex++;
        } elseif ($table['type'] == 'base table') {
            $this->spreadsheet->getActiveSheet()
                ->setCellValue('A' . $this->_rowIndex, "表 `{$table['name']}`")
                ->mergeCells('A' . $this->_rowIndex . ':F' . $this->_rowIndex);
            $this->setBorders($this->_rowIndex);
            $this->setAsHeader('A' . $this->_rowIndex);
            $this->_rowIndex++;
        }

        // 表说明
        $this->spreadsheet->getActiveSheet()
            ->setCellValue('A' . $this->_rowIndex, $table['comment'])
            ->mergeCells('A' . $this->_rowIndex . ':F' . $this->_rowIndex);
        $this->setBorders($this->_rowIndex);
        $this->_rowIndex++;

        // 字段名 | 数据类型 | 默认值 | 允许非空 | 索引/自增 | 备注
        $this->spreadsheet->getActiveSheet()
            ->setCellValue('A' . $this->_rowIndex, '字段名')
            ->setCellValue('B' . $this->_rowIndex, '数据类型')
            ->setCellValue('C' . $this->_rowIndex, '默认值')
            ->setCellValue('D' . $this->_rowIndex, '允许非空')
            ->setCellValue('E' . $this->_rowIndex, '索引/自增')
            ->setCellValue('F' . $this->_rowIndex, '备注');
        $this->setBorders($this->_rowIndex);
        $this->setAsSubHeader([
            'A' . $this->_rowIndex,
            'B' . $this->_rowIndex,
            'C' . $this->_rowIndex,
            'D' . $this->_rowIndex,
            'E' . $this->_rowIndex,
            'F' . $this->_rowIndex,
        ]);
        $this->_rowIndex++;
    }

    protected function renderTableColumn($column)
    {
        $this->spreadsheet->getActiveSheet()
            ->setCellValue('A' . $this->_rowIndex, $column['name'])
            ->setCellValue('B' . $this->_rowIndex, $column['type'])
            ->setCellValue('C' . $this->_rowIndex, $column['defaultValue'])
            ->setCellValue('D' . $this->_rowIndex, $column['isNullable'] ? '是' : '否')
            ->setCellValue('E' . $this->_rowIndex, $column['key'])
            ->setCellValue('F' . $this->_rowIndex, $column['comment']);
        $this->setBorders($this->_rowIndex);
        $this->_rowIndex++;
    }

    protected function renderTableIndexes($table)
    {
        $this->renderTableIndexHeader($table);
        foreach ($table['indexes'] as $index) {
            $this->renderTableIndexColumn($index);
        }
        $this->_rowIndex++;
    }

    protected function renderTableIndexHeader($table)
    {
        // 索引名 | 索引顺序 | 备注
        $this->spreadsheet->getActiveSheet()
            ->setCellValue('A' . $this->_rowIndex, '索引名')
            ->setCellValue('B' . $this->_rowIndex, '索引顺序')
            ->setCellValue('C' . $this->_rowIndex, '备注');

        $this->setCellBorders('A' . $this->_rowIndex);
        $this->setCellBorders('B' . $this->_rowIndex);
        $this->setCellBorders('C' . $this->_rowIndex);
        $this->setAsSubHeader([
            'A' . $this->_rowIndex,
            'B' . $this->_rowIndex,
            'C' . $this->_rowIndex,
        ]);
        $this->_rowIndex++;
    }

    protected function renderTableIndexColumn($index)
    {
        $this->spreadsheet->getActiveSheet()
            ->setCellValue('A' . $this->_rowIndex, $index['name'])
            ->setCellValue('B' . $this->_rowIndex, $index['seq'] . ': ' . $index['columnName'] . ($index['unique'] ? '(unique)' : ''))
            ->setCellValue('C' . $this->_rowIndex, $index['comment']);
        $this->setCellBorders('A' . $this->_rowIndex);
        $this->setCellBorders('B' . $this->_rowIndex);
        $this->setCellBorders('C' . $this->_rowIndex);
        $this->_rowIndex++;
    }
}
