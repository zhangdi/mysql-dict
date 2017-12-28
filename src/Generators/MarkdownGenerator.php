<?php


namespace App\Generators;


class MarkdownGenerator extends Generator
{
    /**
     * @var string 标题
     */
    public $title;
    /**
     * @var array 表详情
     */
    public $tables;
    /**
     * @var array
     */
    private $_sections = [];

    public function renderTable(array $table)
    {
        if ($table['type'] == 'view') {
            $this->_sections[] = "## 视图 `{$table['name']}`";
        } elseif ($table['type'] == 'base table') {
            $this->_sections[] = "## 表 `{$table['name']}`";
        }
        $this->_sections[] = "\n{$table['comment']}\n";

        $this->_sections[] = "### 字段\n";
        $this->_sections[] = "| 字段名 | 数据类型 | 默认值 | 允许非空 | 索引/自增 | 备注 |";
        $this->_sections[] = "| --- | --- | --- | --- | --- | --- |";
        $this->_sections[] = $this->getColumnsContent($table['columns']);

        if (count($table['indexes']) > 0) {
            $this->_sections[] = "\n";
            $this->_sections[] = "### 索引\n";
            $this->_sections[] = "| 索引名 | 索引顺序 | 备注 |";
            $this->_sections[] = "| --- | --- | --- |";
            $this->_sections[] = $this->getIndexesContent($table['indexes']);
        }

        $this->_sections[] = "\n";
    }

    public function getColumnsContent(array $columns): string
    {
        $sections = [];
        foreach ($columns as $column) {
            $sections[] = strtr('| {name} | {type} | {defaultValue} | {isNullable} | {key} | {comment} |', [
                '{name}' => $column['name'],
                '{type}' => $column['type'],
                '{defaultValue}' => $column['defaultValue'],
                '{isNullable}' => $column['isNullable'] ? '是' : '否',
                '{key}' => $column['key'],
                '{comment}' => $column['comment'],
            ]);
        }
        return implode("\n", $sections);
    }

    protected function getIndexesContent(array $indexes): string
    {
        $sections = [];
        foreach ($indexes as $index) {
            $sections[] = strtr('| {name} | {content} | {comment} |', [
                '{name}' => $index['name'],
                '{content}' => $index['seq'] . ': ' . $index['columnName'] . ($index['unique'] ? '(unique)' : ''),
                '{comment}' => $index['comment'],
            ]);
        }
        return implode("\n", $sections);
    }

    /**
     * @inheritdoc
     */
    public function generate($output)
    {
        $this->renderTitle();
        foreach ($this->tables as $table) {
            $this->renderTable($table);
        }

        $content = implode("\n", $this->_sections);
        return file_put_contents($output, $content);
    }

    /**
     */
    public function renderTitle()
    {
        if ($this->title) {
            $this->_sections[] = "# $this->title\n";
        }
    }

}
