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
use Kdelo\Settings,
    Kdelo\Helper,
    Kdelo\CommentsTree,
    Kdelo\GroupNewsItem,
    CIBlockElement,
    CFIle;

class GroupNews
{
    private const SELECTED_FIELDS =  array('ID','CODE','NAME','PREVIEW_TEXT','PREVIEW_PICTURE', 'DATE_CREATE', 'ACTIVE_FROM','CREATED_BY','PROPERTY_PHOTO','PROPERTY_FILES', 'PROPERTY_PREPOD');
    
    public $test;
    
    private $groupId;
    private $newsList = array();

    private $newsOnPage = 5;
    private $curNewsPage;
    private $newsPagesCount;

    public $errors = array();

    static public function getNewsItemById($newsId, bool $addPhotoData = false) : array
    {
        $arNews = array();
        
        if(is_numeric($newsId)){
            $select =  self::SELECTED_FIELDS;
            $select[] =  'DETAIL_TEXT';

            $obNews = CiblockElement::getList(
                array(),
                array(
                    'IBLOCK_ID'=>Settings::getGroupNewsIblockID(),
                    'ID' => $newsId,
                    'ACTIVE'=>'Y'
                ),
                false,
                false,
                $select
            );

            $arNews = $obNews->fetch();
        }

        return self::prepareNewsItemArray($arNews, $addPhotoData);
    }

    static private function prepareNewsItemArray(array $rawArNews, bool $addPhotoData = false) : array
    {       
        if($rawArNews['PROPERTY_PHOTO_VALUE']){
            foreach($rawArNews['PROPERTY_PHOTO_VALUE'] as $fileId){
                $photos[$fileId] = CFile::GetPath($fileId);
                if($addPhotoData)
                    $photoData[$fileId] = CFile::GetFileArray($fileId);
            }
        }

        if($rawArNews['PROPERTY_FILES_VALUE']){
            foreach($rawArNews['PROPERTY_FILES_VALUE'] as $fileId){
                $arFile = CFile::GetFileArray($fileId);
                $files[$fileId] = array(
                    'SRC' =>  $arFile['SRC'],
                    'NAME' =>  $arFile['ORIGINAL_NAME'],
                    'ORIGINAL_NAME' =>  $arFile['ORIGINAL_NAME'],
                    'SIZE_TEXT' =>  Helper::formatFileSize($arFile['FILE_SIZE'])
                );
            }
        }

        $result = array(
            'ID' =>  $rawArNews['ID'],
            'NAME' =>  $rawArNews['NAME'],
            'DETAIL_TEXT' =>  $rawArNews['DETAIL_TEXT'],
            'PREVIEW_TEXT' =>  $rawArNews['PREVIEW_TEXT'],
            'PROPERTY_PHOTO_VALUE' =>  $rawArNews['PROPERTY_PHOTO_VALUE'],
            'PROPERTY_FILES_VALUE' =>  $rawArNews['PROPERTY_FILES_VALUE'],
            'PHOTOS' => $photos,
            'FILES' => $files,
        );

        if($addPhotoData)
            $result['PHOTO_DATA'] =  $photoData;

        return $result;
    }

    static private function checkNewsItemForAdd(GroupNewsItem $obNewsItem) : bool
    {
        return ($obNewsItem->title && $obNewsItem->announce  && $obNewsItem->text && $obNewsItem->groupId && $obNewsItem->authorId);
    }

    static public function editNewsItem(GroupNewsItem $obNewsItem) : ?bool
    {       
        if($obNewsItem->id && self::checkNewsItemForAdd($obNewsItem)){
            global $USER;
            $el = new CIBlockElement;
            $arProperties = array(
                'GROUP'=>$obNewsItem->groupId, 
                'PREPOD' => $obNewsItem->authorId
            );

            $arElemFields = Array(
                "MODIFIED_BY"    => $obNewsItem->authorId,
                "ACTIVE"         => $obNewsItem->isActive?'Y':'N', 
                "NAME"           => $obNewsItem->title,
                "PREVIEW_TEXT"   => $obNewsItem->announce,
                "DETAIL_TEXT"    => $obNewsItem->text,
                'PROPERTY_VALUES' => $arProperties 
            );

            self::editPhotoAndFiles($obNewsItem);
            return $el->Update($obNewsItem->id, $arElemFields);
        }else{
            return null;
        }
    }

    static private function editPhotoAndFiles(GroupNewsItem $obNewsItem)  : void
    {
        $newsItemId = $obNewsItem->id;
        
        $result = array();
        CIBlockElement::GetPropertyValuesArray(
            $result,
            Settings::getGroupNewsIblockID(),
            array('ID' =>$newsItemId),
            array('CODE' => array('FILES', 'PHOTO')),
            array()
        );

        if($result[$newsItemId]){
            self::editFileProp($newsItemId, 'PHOTO', $result[$newsItemId]['PHOTO'], $obNewsItem->photoList?$obNewsItem->photoList:array());
            self::editFileProp($newsItemId, 'FILES', $result[$newsItemId]['FILES'], $obNewsItem->files?$obNewsItem->files:array());
        }
    }

    static private function editFileProp(int $newsItemId, string $propCode, array $arProp, array $arNewFileList) : void
    {
        $arOldFileList = $arProp['VALUE'];

        $propValues = array();
        if($arProp['VALUE'] && $arProp['PROPERTY_VALUE_ID']){
            $filesForDelete = array();
            foreach($arOldFileList as $index => $fileId){
                if(!in_array($fileId, $arNewFileList))
                    $filesForDelete[$arProp['PROPERTY_VALUE_ID'][$index]] = array('id' => $fileId, 'index' => $index);
            }
            
            foreach($filesForDelete as $propValueId => $fileData){
                $propValues[$propValueId] = array("VALUE"=>array('del' => 'Y'));
                unset($arOldFileList[$fileData['index']]);
            }
          
            //Удаляем из свойства записи об удаленных файлах
            if($propValues)
                CIBlockElement::SetPropertyValueCode($newsItemId, $propCode, $propValues);
        }

        $propValues = array();
        foreach($arNewFileList as $fileId){
            if(!in_array($fileId, $arOldFileList))
                $propValues[] = array("VALUE"=>$fileId);
        }

        //Добавляем в свойство записи о добавленных файлах
        if($propValues)
            CIBlockElement::SetPropertyValueCode($newsItemId, $propCode, $propValues);
    }


    static public function addNewsItem(GroupNewsItem $obNewsItem) : ?int
    {
        if(self::checkNewsItemForAdd($obNewsItem)){
            global $USER;
            $el = new CIBlockElement;
            $arProperties = array(
                'GROUP'=>$obNewsItem->groupId, 
                'PREPOD' => $obNewsItem->authorId
            );

            if($obNewsItem->photoList)
                $arProperties['PHOTO'] = $obNewsItem->photoList;

            if($obNewsItem->files)
                $arProperties['FILES'] = $obNewsItem->files;


            $arElemFields = Array(
                "CREATED_BY"    => $obNewsItem->authorId, 
                "IBLOCK_ID"      => Settings::getGroupNewsIblockID(),
                "ACTIVE"         => $obNewsItem->isActive?'Y':'N',
                "NAME"           => $obNewsItem->title,
                "PREVIEW_TEXT"   => $obNewsItem->announce,
                "DETAIL_TEXT"    => $obNewsItem->text,
                'PROPERTY_VALUES' => $arProperties 
            );

            $newsItemId = $el->Add($arElemFields);
            return  (int)  $newsItemId;
        }else{
            return null;
        }
    }


    static public function delNewsItemById($newsId) : bool
    {
        if(is_numeric($newsId))
            return CiblockElement::Delete($newsId);
        else
            return false;
    }

    public function __construct($groupId)
    {
        if(is_numeric($groupId))
            $this->groupId = $groupId;
        else
            $this->errors[time()] = array('__construct' => 'groupId is not numeric');
    }

    private function emptyNewsList() : GroupNews
    {
        $this->newsList = array();
        return $this;
    }

    public function getNewsList() : array
    {
        if(!$this->newsList)
            $this->setNewsListFromIBlock();

        return $this->newsList;
    }

    /**
    * Сеттер и геттер количества новостей на странице при постраничной навигации
    **/
    public function setNewsOnPageNum($newsOnPage) : GroupNews
    {
        if(is_numeric($newsOnPage))
            $this->newsOnPage = (int) $newsOnPage;
        return $this;
    }

    public function getNewsOnPageNum() : int
    {
        return $this->newsOnPage;
    }

    /**
    * Геттер номера текущей страницы новостей при постраничной навигации
    **/
    public function getCurNewsPageNum() : int
    {
        return $this->curNewsPage;
    }

    /**
    * Геттер общего количества страниц новостей при постраничной навигации
    **/
    public function getNewsPagesCount() : int
    {
        return $this->newsPagesCount;
    }

    /**
    * Запись в объект массива новостей страницы при постраничной навигации (без подсчета комментариев)
    **/
    public function setNewsListPage($pageNum = 1, bool $prepareResultArray = true) : GroupNews
    {
        if(is_numeric($pageNum))
            $this->emptyNewsList()->setNewsListFromIBlock($prepareResultArray, false, array('nPageSize'=>$this->newsOnPage, 'iNumPage' => $pageNum));
        return $this;
    }

    /**
    * Запись массива всех новостей группы в объект без подсчета комментариев
    **/
    public function writeNewsToObject(bool $prepareResultArray = true) : GroupNews
    {
        $this->setNewsListFromIBlock($prepareResultArray, false);
        return $this;
    }

    /**
    * Запись в объект oбщего числа комментариев к новости
    **/
    public function writeCommentsCountToNewsList() : GroupNews
    {
        if($this->newsList){
            foreach($this->newsList as & $arNews)
                $arNews['COMMENTS_COUNT'] = $this->countNewsItemComments($arNews['ID']);
        }
        return $this;
    }

    /**
    * Запись в объект списка новостей из инфоблока Записи на стене курсов (с подсчетом комментариев)
    **/
    private function setNewsListFromIBlock(bool $prepareResultArray = true, bool $countComments = true, array $arNavParams = array()) : void
    {
        if(is_numeric($this->groupId)){

            if(empty($arNavParams))
                $navParams = false;
            else
                $navParams = $arNavParams;

            $obNews = CiblockElement::getList(
                array('DATE_CREATE'=>'DESC'),
                array(
                    'IBLOCK_ID'=>Settings::getGroupNewsIblockID(), 
                    'PROPERTY_GROUP'=>$this->groupId, 
                    'ACTIVE'=>'Y'
                ),
                false,
                $navParams,
                self::SELECTED_FIELDS
            );
            if($navParams)
                $this->setPageNavOptions($obNews);

            $this->test = $obNews;

            while($arNews = $obNews->getNext()){

                if($prepareResultArray){
                    $arNews = $this->prepareResultArray($arNews);
                }

                if($countComments){
                    $arNews['COMMENTS_COUNT'] = $this->countNewsItemComments($arNews['ID']);
                    //$arNews['NEW_COMMENTS_COUNT'] = $this->countNewCommentsForNewsItem($arNews['ID']);
                }

                $this->newsList[$arNews['ID']] = $arNews;

            } 
        }
        else {
            $this->errors[time()] = array('setNewsListFromIBlock' => 'groupId is not set or not numeric');
        }
    }

    /**
    * Подготовка массива данных новости
    **/
    private function prepareResultArray(array $rawArNews, bool $getFilePathes = false) : array
    {
        $files = $photos = array();
        if($rawArNews['PROPERTY_PHOTO_VALUE']){
            foreach($rawArNews['PROPERTY_PHOTO_VALUE'] as $fileId)
                $photos[$fileId] = CFile::GetPath($fileId);
        }

        if($getFilePathes && $rawArNews['PROPERTY_FILES_VALUE']){
            foreach($rawArNews['PROPERTY_FILES_VALUE'] as $fileId)
                $files[$fileId] = CFile::GetPath($fileId);
        }

        return array(
            'ID' =>  $rawArNews['ID'],
            'NAME' =>  $rawArNews['NAME'],
            'ACTIVE_FROM' =>  $rawArNews['ACTIVE_FROM'],
            'DATE_CREATE' => $rawArNews['DATE_CREATE'],
            'PREVIEW_TEXT' =>  $rawArNews['PREVIEW_TEXT'],
            'CREATED_BY' =>  $rawArNews['CREATED_BY'],
            'AUTHOR' => Helper::getUserFullName($rawArNews['PROPERTY_PREPOD_VALUE']?$rawArNews['PROPERTY_PREPOD_VALUE']:$rawArNews['CREATED_BY']),
            'PROPERTY_PHOTO_VALUE' =>  $rawArNews['PROPERTY_PHOTO_VALUE'],
            'PROPERTY_FILES_VALUE' =>  $rawArNews['PROPERTY_FILES_VALUE'],
            'PHOTOS' => $photos,
            'FILES' => $files
        );
    }

    /**
    * Запись параметров постраничной навигации (текущая страница, общее число страниц)
    **/
    private function setPageNavOptions(\CIBlockResult $obNews) : void
    {
        $this->curNewsPage = (int) $obNews->NavPageNomer;
        $this->newsPagesCount = (int) $obNews->NavPageCount;
    }

 

    private function countNewsItemComments($newsId) : int
    {
        return (int) CommentsTree::getCountForGroupNewsFromDB($this->groupId, $newsId);
    }
}