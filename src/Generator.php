<?php


namespace App;


use yii\base\BaseObject;

abstract class Generator extends BaseObject
{
    abstract function generate($output);
}
