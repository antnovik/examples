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

use Kdelo\TestSettings,
    Kdelo\Test,
    Kdelo\Task,
    Kdelo\Helper;

class TaskManager
{
    const SUBDIR_TASKS_FILES = 'tasks-files';
    
    public $test;

    public $testId;
    public $taskList;

    public $errors = array();

    private $DB;
    private $tableType;
    private $tableName;
    private $tableSructure;
    private $tableFields;
    private $testsDir;

    private $lastSortNum = 0;
    private $sortNumStep = 10;

    public function __construct($testId)
    {
        if(is_numeric($testId)){
            global $DB;
            $this->testId = $testId;
            $this->DB = $DB;
            $this->tableType = TestSettings::getTasksTableType();
            $this->tableSructure = TestSettings::getTasksTableStructure();
            $this->tableFields = TestSettings::getTasksTableFields();
            $this->testsDir = TestSettings::getTestsDir();
        } else{
            $this->errors[time()] = array('__construct' => 'not numeric test ID');
        }
    }

    public function initTable() : bool
    {
        $noErrors = true;
        if(is_numeric($this->testId)){
            $table =  $this->tableType . $this->testId;
            if(!Helper::checkTableExist($table)){
                $query = "CREATE TABLE $table ".$this->tableSructure;
                $res = $this->DB->Query($query);
                $noErrors = (bool) $res->result;
            }
        } else{
            $this->errors[time()] = array('initTable' => 'not numeric test ID');
            $noErrors = false;
        }

        if($noErrors)
            $this->tableName = $this->tableType . $this->testId;
        else
            $this->tableName = null;

        return $noErrors;
    }

    public function setStartSortNum() : TaskManager
    {
        if($this->initTable()){
            $table =  $this->tableName;

            $sortFieldName = $this->tableFields['task-sort'];

            $query = "SELECT $sortFieldName FROM $table WHERE task_sort=(SELECT max(task_sort) FROM $table)";
            $res = $this->DB->Query($query);
            if($res->result->num_rows){
                $arResult = $res->fetch(); 
                $this->lastSortNum =  $arResult[$sortFieldName];
            }         
        }

        return $this;
    }

    public function changeSortNumStep($step) : TaskManager
    {
        if(is_numeric($step))
            $this->sortNumStep = (int) $step;
        return $this;
    }

    public function getTaskListFromDB() : array
    {
        $this->taskList = array();
        
        if($this->tableName || $this->initTable()){

            $query = "SELECT * FROM ".$this->tableName." ORDER BY ".$this->tableFields['task-sort']." ASC";
            $res = $this->DB->Query($query);
            while($arTask = $res->fetch()){
                $this->taskList[] = $this->makeTaskfromArray($arTask);
            } 
        }
        return $this->taskList;
    }

    public function getTaskArraysFromDB() : array
    {
        $arTaskLIst = array();
        
        if($this->tableName || $this->initTable()){
            $arFields = $this->tableFields;
            $query = "SELECT * FROM ".$this->tableName." ORDER BY ".$this->tableFields['task-sort']." ASC";
            $res = $this->DB->Query($query);
            
            while($arTaskFromBD = $res->fetch()){
                $arTask = array();
                $arTask['ID'] = $arTaskFromBD[$arFields['id']];
                $arTask['TEST_ID'] = $this->testId;
                $arTask['TEXT'] = $arTaskFromBD[$arFields['task-text']];
                $arTask['SORT'] = $arTaskFromBD[$arFields['task-sort']];
                $arTask['ANSWER_TYPE'] = $arTaskFromBD[$arFields['answer-type']];
    
                if($arTaskFromBD[$arFields['answer-list']])
                    $arTask['ANSWERS_LIST'] = unserialize($arTaskFromBD[$arFields['answer-list']]);
                
                $arTask['FILES'] = array();
                if($arTaskFromBD[$arFields['task-file']]){

                    $files = unserialize($arTaskFromBD[$arFields['task-file']]);
                    foreach($files as $filePath){  
                        $arTask['FILES'][] = array(
                            'FILE' => $filePath,
                            'FILE_SIZE' => Helper::formatFileSize(filesize($_SERVER['DOCUMENT_ROOT'].$filePath)),
                            'FILE_NAME' => str_replace($this->testsDir.self::SUBDIR_TASKS_FILES.'/', '', $filePath)
                        );
                    }
                }

                if($arTaskFromBD[$arFields['task-image']])
                    $arTask['IMAGES'] = unserialize($arTaskFromBD[$arFields['task-image']]);

                $arTaskLIst[] =  $arTask;
            } 
        }
        return $arTaskLIst;
    }

    public function getTaskByID($taskID) : Task
    {
        $task = new Task();
        $arTaskFromBD = $this->getTaskDataFromDB($taskID);
        if($arTaskFromBD){
            $arFields = $this->tableFields;
            $task->setId($arTaskFromBD[$arFields['id']])
                 ->setTestId($this->testId)
                 ->setText($arTaskFromBD[$arFields['task-text']])
                 ->setSort($arTaskFromBD[$arFields['task-sort']])                  
                 ->setAnswerType($arTaskFromBD[$arFields['answer-type']]);
                    
            if($arTaskFromBD[$arFields['answer-list']])
                $task->setAnswerList(unserialize($arTaskFromBD[$arFields['answer-list']]));

            if($arTaskFromBD[$arFields['task-file']])
                $task->setFileList(unserialize($arTaskFromBD[$arFields['task-file']]));

            if($arTaskFromBD[$arFields['task-image']])
                $task->setImageList(unserialize($arTaskFromBD[$arFields['task-image']]));
        }
        return $task;
    }

    public function getTaskArrayByID($taskID) : array
    {
        $arTask = array();
        $arTaskFromBD = $this->getTaskDataFromDB($taskID);

 
        if($arTaskFromBD){
            $arFields = $this->tableFields;
            $arTask['ID'] = $arTaskFromBD[$arFields['id']];
            $arTask['TEST_ID'] = $this->testId;
            $arTask['TEXT'] = $arTaskFromBD[$arFields['task-text']];
            $arTask['SORT'] = $arTaskFromBD[$arFields['task-sort']];
            $arTask['ANSWER_TYPE'] = $arTaskFromBD[$arFields['answer-type']];

            if($arTaskFromBD[$arFields['answer-list']])
                $arTask['ANSWERS_LIST'] = unserialize($arTaskFromBD[$arFields['answer-list']]);

            if($arTaskFromBD[$arFields['task-file']]){
                $fileList = unserialize($arTaskFromBD[$arFields['task-file']]);
                $arTask['FILES'] = array();
                foreach($fileList as $filePath){
                    $arTask['FILES'][] = array(
                        'FILE_PATH' => $filePath,
                        'FILE_SIZE' => Helper::formatFileSize(filesize($_SERVER['DOCUMENT_ROOT'].$filePath)),
                        'FILE_NAME' => str_replace($this->testsDir.self::SUBDIR_TASKS_FILES.'/', '',$filePath)
                    ); 
                }
            }
                
            if($arTaskFromBD[$arFields['task-image']]){
                $fileList = unserialize($arTaskFromBD[$arFields['task-image']]);
                $arTask['IMAGES'] = array();
                foreach($fileList as $filePath)
                    $arTask['IMAGES'][] = $filePath;
            }
        }
        return $arTask;
    }

    private function getTaskDataFromDB($taskID) : array
    {
        $arTask = array();
        if(is_numeric($taskID)){
            if($this->tableName || $this->initTable()){
                $query = "SELECT * FROM ".$this->tableName." WHERE ID = ".$taskID;
                $res = $this->DB->Query($query);

                if($res->result->num_rows)
                    $arTask =  $res->fetch();
            }
        }else{
            $this->errors[time()] = array('getTaskFromDB' => 'not numeric task ID');
        }

        return $arTask;
    }

    //ПЕРЕНЕСТИ В Task ??
    private function makeTaskfromArray(array $arTask) : Task
    {
        $task = new Task();
        $arFields = $this->tableFields;
        
        $task->setId($arTask[$arFields['id']])
            ->setTestId($this->testId)
            ->setText($arTask[$arFields['task-text']])->setSort($arTask[$arFields['task-sort']])
            ->setAnswerType($arTask[$arFields['answer-type']]);

        if($arTask[$arFields['task-image']])    
            $task->setImageList(unserialize($arTask[$arFields['task-image']]));

        //if($arTask[$arFields['task-file']])    
        //    $task->setFile($arTask[$arFields['task-file']]);

        if($arTask[$arFields['task-file']])
            $task->setFileList(unserialize($arTask[$arFields['task-file']]));

        if($arTask[$arFields['answer-list']])
            $task->answerList = unserialize($arTask[$arFields['answer-list']]);

        return $task;
    }

    private function checkTask(Task $task) : bool
    {
        $noErrors = true;

        if(!$task->text)
            $noErrors = false;

        return $noErrors;
    }

    private function saveTaskFiles(Task $task) : TaskManager
    {
        $dir = $this->testsDir.self::SUBDIR_TASKS_FILES.'/';

        if($task->rowImageData){
            $fileName = $dir.$task->rowImageData['name'];
            if(move_uploaded_file($task->rowImageData['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].$fileName))
                $task->image =  $fileName;
            else
                $this->errors[time()] = array('saveTaskFiles' => 'move_uploaded_file error for image');
        }
    
        if($task->rowFileData){
            $fileName = $dir.$task->rowFileData['name'];
            if(move_uploaded_file($task->rowFileData['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].$fileName))
                $task->file =  $fileName;
            else
                $this->errors[time() + 1] = array('saveTaskFiles' => 'move_uploaded_file error for file');
        }

        return $this;
    }

    private function processTaskFileLists(Task $task) : TaskManager
    {
        $dir = $this->testsDir.self::SUBDIR_TASKS_FILES.'/';

        if($task->imageRawDataList && is_array($task->imageRawDataList)){
            foreach($task->imageRawDataList as $rawImageData){
                $fileName = $dir.$rawImageData['name'];
                if(move_uploaded_file($rawImageData['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].$fileName))
                    $task->imageList[] =  $fileName;
                else
                    $this->errors[time()] = array('processTaskFileLists' => 'move_uploaded_file error for image');
            }
        }

        if($task->imageList)
            $task->serializedImageList = serialize($task->imageList);

        if($task->fileRawDataList && is_array($task->fileRawDataList)){
            foreach($task->fileRawDataList as $rawFileData){
                $fileName = $dir.$rawFileData['name'];
                if(move_uploaded_file($rawFileData['tmp_name'],  $_SERVER['DOCUMENT_ROOT'].$fileName))
                    $task->fileList[] =  $fileName;
                else
                    $this->errors[time() + 1] = array('processTaskFileLists' => 'move_uploaded_file error for file');
            }
        }

        if($task->fileList)
            $task->serializedFileList = serialize($task->fileList);

        return $this;
    }


    private function saveTask(Task $task) : bool
    {  
        $arData =  $this->prepareTaskDataForDB($task);
        
        $taskID = $this->DB->Insert(
            $this->tableName,
            $arData
        );

        if($taskID){
            $task->setId((string)$taskID);
            $this->lastSortNum =  $sortNum;
        }

        return (bool) $taskID;
    }

    public function prepareTaskDataForDB(Task $task) : array
    {
        $arFields = $this->tableFields;

        $arData = array(
            $arFields['task-text'] =>  "'".$task->text."'",
            $arFields['answer-type'] =>  "'".$task->answerType."'"
        );

        if($task->serializedAnswerList)
            $arData[$arFields['answer-list']] = "'".$task->serializedAnswerList."'";

        if($task->serializedImageList)
            $arData[$arFields['task-image']] = "'".$task->serializedImageList."'";

        if($task->serializedFileList)
            $arData[$arFields['task-file']] = "'".$task->serializedFileList."'";

        if($task->sort){
            $arData[$arFields['task-sort']] = $task->sort;
        }else {
            $sortNum = $this->lastSortNum + $this->sortNumStep;
            $arData[$arFields['task-sort']] = "'".(string) $sortNum."'";
        }

        return $arData;
    }

    public function addTaskToDB(Task $task) : bool
    {
        if(!$this->tableName){
            if(!$this->initTable()){
                $this->errors[time()] = array('addTaskToDB' => 'initTable() error');
                return false;
            }
        }
            
        $this->setStartSortNum();
     
        if($this->checkTask($task)){
            $isSaveSuccess = $this->processTaskFileLists($task)->saveTask($task);
            if($isSaveSuccess){
                $test = new Test();
                $test->id = $this->testId;
                if(!$test->checkHasTask())
                    $test->setIsEmpty(false)->save();
            }
            return $isSaveSuccess;
        }else{
            $this->errors[time()] = array('addTaskToDB' => 'checkTask() error');
            return false;
        }
    }
  
    public function updateTaskInDB(Task $task, bool $updateImage = false, bool $updateFile = false, $delOldAnswers = true) : bool
    {
        if(is_numeric($task->id) && $this->checkTask($task)){
            if($this->tableName || $this->initTable()){
                $filesToDelete = array();
                $arData =  $this->prepareTaskDataForDB($task);
                            
                $arFields = $this->tableFields;



                if($updateImage || $updateFile){
                    $this->processTaskFileLists($task);
                    $oldTask = $this->getTaskByID($task->id);


                    if($updateImage){
                        $arData[$arFields['task-image']] = "'".$task->serializedImageList."'";

                        $indexList = array();
                        foreach($oldTask->imageList as $index => $filePath){
                            if(in_array($filePath, $task->imageList))
                                $indexList[] = $index;
                        }

                        $oldImageList = $oldTask->imageList;
                        foreach($indexList as $index)
                            unset($oldImageList[$index]);

                        $filesToDelete = array_merge($oldImageList,  $filesToDelete);
                    }

                    if($updateFile){

                        $arData[$arFields['task-file']] = "'".$task->serializedFileList."'";

                        $indexList = array();
                        foreach($oldTask->fileList as $index => $filePath){
                            if(in_array($filePath, $task->fileList))
                                $indexList[] = $index;
                        }

                        $oldFileList = $oldTask->fileList;
                        foreach($indexList as $index)
                            unset($oldFileList[$index]);

                        $filesToDelete = array_merge($oldFileList,  $filesToDelete);
                    }
                }
                
                if($delOldAnswers && $task->answerType && !$task->isNeedChooseAnswer($task->answerType))
                    $arData[$arFields['answer-list']] = "''";
                

                /*   */

                $res = $this->DB->Update(
                    $this->tableName,
                    $arData,
                    "WHERE ID='".$task->id."'"
                );

                if($res && !empty($filesToDelete)){
                    foreach($filesToDelete as $fileName)
                        unlink($_SERVER['DOCUMENT_ROOT'].$fileName);
                }
                
                return (bool) $res; 
            }
        }else{
            $this->errors[time()] = array('updateTaskInDB' => 'no task id or checkTask() error');
            return false;
        }
    }
    
    public function delTask($taskId) : bool
    {
        if(is_numeric($taskId)){
            if($this->tableName || $this->initTable()){
                $query = "DELETE FROM ".$this->tableName." WHERE ID = ".$taskId;
                $isDelSuccess = (bool) $this->DB->Query($query);
                if($isDelSuccess && $this->countTasks() === 0){
                    $test = new Test();
                    $test->id = $this->testId;
                    if($test->checkHasTask())
                        $test->setIsEmpty(true)->save();
                }
                return $isDelSuccess;
            }else{
                $this->errors[time()] = array('delTask' => 'initTable() error');
                return false;
            }
        }else {
            $this->errors[time()] = array('delTask' => 'not numeric task id');
            return false;
        }
    }

    public function countTasks() : int
    {
        if($this->tableName || $this->initTable()){
            $query = "SELECT id FROM ".$this->tableName;
            return (int) $this->DB->Query($query)->result->num_rows;
        }
    }
}