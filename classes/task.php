<?php
/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global string $by
 * @global string $order
 */

namespace Kdelo;

class Task
{
    const CHOICE_ANSWER_TASK_TYPES = array('only', 'several');
        
    public $id;
    public $testId;
    public $text;
    public $image;
    public $file;
    public $rowImageData = array();
    public $rowFileData = array();

    public $imageList = array();
    public $fileList = array();
    public $imageRawDataList = array();
    public $fileRawDataList = array();
    public $serializedImageList;
    public $serializedFileList;

    public $sort;
    public $answerType;
    public $answerList;
    public $serializedAnswerList;
    public $errors = array();


    public function setId(string $id) : Task
    {
        if(is_numeric($id))
            $this->id = $id;
        else
            $this->errors[time()] = array('setId' => 'id is not numeric');
        return $this;
    }

    public function setTestId(string $testId) : Task
    {
        if(is_numeric($testId))
            $this->testId = $testId;
        else
            $this->errors[time()] = array('setTestId' => 'testId is not numeric');
        return $this;
    }


    public function setText(string $text) : Task
    {
        if($text)
            $this->text = $text;
        else
            $this->errors[time()] = array('setText' => 'empty name');
        return $this;
    }
    
    //Убрать метод
    public function setRowImageData(array $arFile) : Task
    {
        if($arFile)
            $this->rowImageData = $arFile;
        else
            $this->errors[time()] = array('setRowImageData' => 'empty data');
        return $this;
    }

    public function addRawImageData(array $arFile) : Task
    {
        if($arFile)
            $this->imageRawDataList[] = $arFile;
        else
            $this->errors[time()] = array('setRowImageData' => 'empty data');
        return $this;
    }

    public function setRawImageList(array $arFileList) : Task
    {
        if($arFileList && is_array($arFileList))
            $this->imageRawDataList = $arFileList;
        else
            $this->errors[time()] = array('setRawImageList' => 'empty or not array data');
        return $this;
    }

    public function setImage(string $src) : Task
    {
        if($src)
            $this->image = $src;
        else
            $this->errors[time()] = array('setImage' => 'empty src');
        return $this;
    }

    public function setFile(string $src) : Task
    {
        if($src)
            $this->file = $src;
        else
            $this->errors[time()] = array('setFile' => 'empty src');
        return $this;
    }

    public function setImageList(array $fileList) : Task
    {
        if($fileList && is_array($fileList))
            $this->imageList = $fileList;
        else
            $this->errors[time()] = array('setImageList' => 'empty or not array list');
        return $this;
    }

    public function setFileList(array $fileList) : Task
    {
        if($fileList && is_array($fileList))
            $this->fileList = $fileList;
        else
            $this->errors[time()] = array('setFileList' => 'empty or not array list');
        return $this;
    }

    public function addImage(string $src) : Task
    {
        if($src){
            $this->imageList[] = $src;
            $this->serializedImageList = serialize($this->imageList);
        }
        else{
            $this->errors[time()] = array('addImage' => 'empty src');
        }
        return $this;
    }

    public function addFile(string $src) : Task
    {
        if($src){
            $this->fileList[] = $src;
            $this->serializedFileList = serialize($this->fileList);
        }
        else{
            $this->errors[time()] = array('addFile' => 'empty src');
        }
        return $this;
    }

    public function delImageByName(string $src) : Task
    {
        if($src && in_array($src, $this->imageList)){
            $index = array_search($src, $this->imageList);
            unset($this->imageList[$index]);
            $this->imageList = array_values($this->imageList);
            $this->serializedImageList = serialize($this->imageList);
        }
        else{
            $this->errors[time()] = array('delImageByName' => 'empty src or no name in list');
        }
        return $this;
    }

    public function delFileByName(string $src) : Task
    {
        if($src && in_array($src, $this->fileList)){
            $index = array_search($src, $this->fileList);
            unset($this->fileList[$index]);
            $this->fileList = array_values($this->fileList);
            $this->serializedFileList = serialize($this->fileList);
        }
        else{
            $this->errors[time()] = array('delFileByName' => 'empty src or no name in list');
        }
        return $this;
    }

    public function delImageByIndex($index) : Task
    {
        if(is_numeric($index) && isset($this->imageList[$index])){
            unset($this->imageList[$index]);
            $this->imageList = array_values($this->imageList);
            $this->serializedImageList = serialize($this->imageList);
        }
        else{
            $this->errors[time()] = array('delImageByIndex' => 'not numeric index  or no image in list');
        }
        return $this;
    }

    public function delFileByIndex($index) : Task
    {
        if(is_numeric($index) && isset($this->fileList[$index])){
            unset($this->fileList[$index]);
            $this->fileList = array_values($this->fileList);
            $this->serializedFileList = serialize($this->fileList);
        }
        else{
            $this->errors[time()] = array('delFileByIndex' => 'not numeric index  or no image in list');
        }
        return $this;
    }
  
    //Убрать метод
    public function setRowFileData(array $arFile) : Task
    {
        if($arFile)
            $this->rowFileData = $arFile;
        else
            $this->errors[time()] = array('setRowFileData' => 'empty data');
        return $this;
    }

    public function addRawFileData(array $arFile) : Task
    {
        if($arFile)
            $this->fileRawDataList[] = $arFile;
        else
            $this->errors[time()] = array('setRowFileData' => 'empty data');
        return $this;
    }

    public function setRawFileList(array $arFileList) : Task
    {
        if($arFileList && is_array($arFileList))
            $this->fileRawDataList = $arFileList;
        else
            $this->errors[time()] = array('setRawFileList' => 'empty or not array data');
        return $this;
    }

    public function setAnswerType(string $type) : Task
    {
        if($type)
            $this->answerType = $type;
        else
            $this->errors[time()] = array('setAnswerType' => 'empty type');
        return $this;
    }

    public function setAnswerList(array $answerList) : Task
    {
        if($answerList){
            $this->answerList = $answerList;
            $this->serializedAnswerList = serialize($answerList);
        }else {
            $this->errors[time()] = array('setAnswerList' => 'empty answerList');

        }
        return $this;
    }

    public function setSort($sort) : Task
    {
        if(is_numeric($sort))
            $this->sort = $sort;
        else
            $this->errors[time()] = array('setSort' => 'sort is not numeric');
        return $this;
    }

    public function isNeedChooseAnswer($answerType) : bool
    {
        return in_array($this->answerType, self::CHOICE_ANSWER_TASK_TYPES);
    }
}