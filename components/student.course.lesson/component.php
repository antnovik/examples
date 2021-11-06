<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader,
    Kdelo\StudentGroup;


$USER_ID = $userID = (int)$USER->getId();
$isAuth = $USER->IsAuthorized();
$arNavigation = false;
$pagerParameters = array();

if($arParams["USE_CACHE"] !=="Y" || $this->startResultCache(false))
{
	if(!Loader::includeModule("kdelo.adminlearn")){
		$this->abortResultCache();
		ShowError("Модуль kdelo.adminlearn не установлен");
		return;
    }
    
    if($isAuth){

    if(isset($arParams['GROUP_ID'])){
        $arResult['GROUP_ID'] = $arParams['GROUP_ID'];
        $GROUP = new StudentGroup($arParams['GROUP_ID']);      
        $GROUP->init()->setLessons();

        $this->setBaseCourseOptions($GROUP);
        $this->setTimeOptions($GROUP);
        $this->setFormOptions($GROUP, $userID);
        
        if(isset($arParams['LESSON_ID'])){
            $lessonList = $GROUP->course->lessons;
            foreach($lessonList as $index => $obLesson){
                if($obLesson->getId() == $arParams['LESSON_ID']){
                    $obLesson->init();

                    $arResult['LESSON'] = array(
                        'NAME' => $obLesson->name,
                        'ALT_NAME' => $obLesson->addName,
                        'TEXT' => $obLesson->text,
                        'VIDEO' => $obLesson->video,
                        'FILES' => $obLesson->files,
                        'FILE_ROWS' => array_chunk($obLesson->files, 2),
                        'ORDER' => $obLesson->order,
                    );

                    if(isset($lessonList[$index - 1]))
                        $arResult['PREV_LESSON']['ID'] = (string) $lessonList[$index - 1]->getId();

                    if(isset($lessonList[$index + 1]))
                        $arResult['NEXT_LESSON']['ID'] = (string) $lessonList[$index + 1]->getId();

                    break;
                }
            }
        }
    }

    }else{
        $this->arResult['AUTHORIZED'] = false;
    }
 
	$this->includeComponentTemplate();
}
?>