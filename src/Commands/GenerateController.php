<?php


namespace App\Commands;


use App\Generators\Generator;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Exception;
use yii\helpers\Console;

class GenerateController extends Controller
{
    const FORMAT_MARKDOWN = 'markdown';
    const FORMAT_EXCEL = 'excel';

    public static $generators = [
        self::FORMAT_MARKDOWN => 'App\Generators\MarkdownGenerator',
        self::FORMAT_EXCEL=>'App\Generators\ExcelGenerator',
    ];

    /**
     * @var string 标题
     */
    public $title;
    /**
     * @var string MySQL 主机
     */
    public $host;
    /**
     * @var string MySQL 数据库名
     */
    public $db;
    /**
     * @var string MySQL 用户名
     */
    public $username;
    /**
     * @var string MySQL 密码
     */
    public $password;
    /**
     * @var string MySQL 字符集
     */
    public $charset = 'utf8';
    /**
     * @var string 导出格式
     */
    public $format = self::FORMAT_MARKDOWN;


    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'host';
        $options[] = 'db';
        $options[] = 'username';
        $options[] = 'password';
        $options[] = 'format';
        $options[] = 'title';
        return $options;
    }

    /**
     * @return Connection
     */
    protected function getDb()
    {
        try {
            /** @var Connection $db */
            $db = \Yii::createObject([
                'class' => 'yii\db\Connection',
                'dsn' => "mysql:host={$this->host};dbname={$this->db}",
                'username' => $this->username,
                'password' => $this->username,
                'charset' => $this->charset,
            ]);
            $db->open();
            return $db;
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
            \Yii::$app->end();
        }
    }

    private $_tables = [];

    protected function load()
    {
        $db = $this->getDb();
        $tmpTables = $db->createCommand('show tables')->queryColumn();
        $tables = [];
        foreach ($tmpTables as $tableName) {
            $table = [
                'name' => $tableName,
                'comment' => null,
            ];
            $sql = "select * from information_schema.tables where table_schema = :db and table_name = :tableName";
            $tableInfo = $db->createCommand($sql, [
                ':db' => $this->db,
                ':tableName' => $tableName,
            ])->queryOne();
            if ($tableInfo) {
                $table['comment'] = $tableInfo['TABLE_COMMENT'];
                $table['type'] = $tableInfo['TABLE_TYPE'] == 'VIEW' ? 'view' : 'base table';
            }
            $sql = "select * from information_schema.columns where table_schema =:db and table_name = :tableName";
            $columnsInfo = $db->createCommand($sql, [
                ':db' => $this->db,
                ':tableName' => $tableName,
            ])->queryAll();
            $columns = [];
            foreach ($columnsInfo as $columnInfo) {
                $column = [
                    'name' => $columnInfo['COLUMN_NAME'],
                    'type' => $columnInfo['COLUMN_TYPE'],
                    'defaultValue' => $columnInfo['COLUMN_DEFAULT'],
                    'isNullable' => $columnInfo['IS_NULLABLE'] == 'YES',
                    'key' => $columnInfo['COLUMN_KEY'] . ' ' . $columnInfo['EXTRA'],
                    'comment' => $columnInfo['COLUMN_COMMENT'],
                ];
                $columns[] = $column;
            }
            $table['columns'] = $columns;

            // 索引
            $indexesInfo = $db->createCommand("show index from `$tableName`")->queryAll();
            $indexes = [];
            foreach ($indexesInfo as $indexInfo) {
                $index = [
                    'name' => $indexInfo['Key_name'],
                    'seq' => $indexInfo['Seq_in_index'],
                    'unique' => $indexInfo['Non_unique'] == 1,
                    'columnName' => $indexInfo['Column_name'],
                    'comment' => $indexInfo['Comment'],
                ];
                $indexes[] = $index;
            }
            $table['indexes'] = $indexes;

            $tables[] = $table;
        }
        $this->_tables = $tables;
        return $this->_tables;
    }

    /**
     * 生成数据库字典
     *
     * @param string $output 保存的文件
     * @return
     */
    public function actionIndex($output)
    {
        $this->load();
        $file = \Yii::getAlias($output);
        if (file_exists($file) && !Console::confirm("文件已存在，是否覆盖")) {
            $this->stdout("已取消\n", Console::BOLD, Console::FG_RED);
            return ExitCode::OK;
        }

        if (!isset(self::$generators[$this->format])) {
            $this->stdout("不支持的导出格式，只支持: " . implode(array_keys(self::$generators), ', '), Console::BOLD, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->load();
        $generatorClass = self::$generators[$this->format];
        /** @var Generator $generator */
        $generator = new $generatorClass([
            'title' => $this->title,
            'tables' => $this->_tables,
        ]);

        $rs = $generator->generate($output);
        if ($rs) {
            $this->stdout("成功\n", Console::BOLD, Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stderr("失败\n", Console::BOLD, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

    }

}
