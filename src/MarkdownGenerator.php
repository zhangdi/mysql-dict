<?php


namespace App;


class MarkdownGenerator extends Generator
{
    private $_tables;

    public function __construct(array $tables)
    {
        $this->_tables = $tables;
    }

    public function getTableContent(array $table): string
    {
        $sections = [];
        if ($table['type'] == 'view') {
            $sections[] = "## 视图 `{$table['name']}`";
        } elseif ($table['type'] == 'base table') {
            $sections[] = "## 表 `{$table['name']}`";
        }
        $sections[] = "\n{$table['comment']}\n";

        $sections[] = "### 字段\n";
        $sections[] = "| 字段名 | 数据类型 | 默认值 | 允许非空 | 索引/自增 | 备注 |";
        $sections[] = "| --- | --- | --- | --- | --- | --- |";
        $sections[] = $this->getColumnsContent($table['columns']);

        if(count($table['indexes'])> 0){
            $sections[] = "\n";
            $sections[] = "### 索引\n";
            $sections[] = "| 索引名 | 索引顺序 | 备注 |";
            $sections[] = "| --- | --- | --- |";
            $sections[] = $this->getIndexesContent($table['indexes']);
        }

        $sections[] = "\n";
        return implode("\n", $sections);
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


    public function generate($output)
    {
        $sections = [];
        foreach ($this->_tables as $table) {
            $sections[] = $this->getTableContent($table);
        }

        $content = implode("\n", $sections);
        return file_put_contents($output, $content);
    }
}
