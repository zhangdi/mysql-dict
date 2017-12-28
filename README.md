# MySQL 字典工具

基于 Yii 2 开发的一个导出 MySQL 字典表的工具。支持导出: Markdown, Word 格式。

[![Build Status](https://travis-ci.org/zhangdi/mysql-dict.svg?branch=master)](https://travis-ci.org/zhangdi/mysql-dict)

## 安装/使用

下载 [mysql-dict.phar](https://github.com/zhangdi/mysql-dict/releases/download/0.2.0/mysql-dict.phar)

### 导出 Markdown 格式

执行命令:

```bash
php mysql-dict.phar generate output.md --format=markdown \
  --host="你的 MySQL 主机" --db="你的数据库名" \
  --username="你的 MySQL 用户名" --password="你的 MySQL 密码"
```

命令执行完成后会生成一个名为 output.md 的字典文件。

### 导出 Excel 格式

> 只支持导出 `.xlsx` 格式的 Excel

执行命令:

```bash
php mysql-dict.phar generate output.xlsx --format=excel \
  --host="你的 MySQL 主机" --db="你的数据库名" \
  --username="你的 MySQL 用户名" --password="你的 MySQL 密码"
```

命令执行完成后会生成一个名为 output.xlsx 的字典文件。

## 作者

- Di Zhang | [Github](https://github.com/zhangdi/) | <zhangdi_me@163.com>

## License

mysql-dict is licensed under the BSD-3-Clause License - see the [LICENSE](LICENSE.md) file for details
