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
    Kdelo\Helper,
    Kdelo\StudentGroup,
    Kdelo\GroupNews,
    Kdelo\NewComments;


$USER_ID = (int)$USER->getId();
$isAuth = $USER->IsAuthorized();

if($arParams["USE_CACHE"] !=="Y" || $this->startResultCache(false))
{
	if(!Loader::includeModule("kdelo.adminlearn")){
		$this->abortResultCache();
		ShowError("Модуль kdelo.adminlearn не установлен");
		return;
    }
    
    if($isAuth){
        $this->arResult['AUTHORIZED'] = true;
        if($USER->IsAdmin()){
            $userID = isset($_GET['CHECK_USER'])?$_GET['CHECK_USER']:7012;;
        }else{
            $userID = $USER_ID;
        }

    if(isset($_REQUEST['GROUP_ID'])){

        $GROUP = new StudentGroup ($_REQUEST['GROUP_ID']);    
        $GROUP_NEWS = new GroupNews($_REQUEST['GROUP_ID']);  
        
        $GROUP->init();

        $arResult['GROUP_ID'] = $_REQUEST['GROUP_ID'];

        $this->setBaseCourseOptions($GROUP);
        $this->setTimeOptions($GROUP);
        $this->setFormOptions($GROUP, $userID);


        $arResult['GRADES_TABLE'] = array(
            'SRC' => $GROUP->getGradesTableLink(),
            'DATE' =>  'от '.$GROUP->getGradesTableDate()
        );


        if($arParams["USE_AJAX"] ==="Y"){
            if($arParams["NEWS_ON_PAGE"]){
                $GROUP_NEWS->setNewsOnPageNum($arParams["NEWS_ON_PAGE"]);
                $arResult['ITEMS_ON_PAGE'] = $arParams["NEWS_ON_PAGE"];
            }
                
            $arResult['NEWS'] = $GROUP_NEWS->setNewsListPage()->getNewsList();

            $arResult['CUR_PAGE'] = $GROUP_NEWS->getCurNewsPageNum();
            $arResult['PAGES_COUNT'] = $GROUP_NEWS->getNewsPagesCount();
            $arResult['NEWS_ON_PAGE'] = $GROUP_NEWS->getNewsOnPageNum();

            $arResult['LOAD_BY_AJAX'] = true;
            $arResult['GROUP_ID'] = $_REQUEST['GROUP_ID'];
            
        } else {
            $arResult['NEWS'] = $GROUP_NEWS->getNewsList();
            $arResult['LOAD_BY_AJAX'] = false;
        }


        foreach($arResult['NEWS'] as & $arNews){
            $NEW_COMMENTS = new NewComments();                          
            $arNews['NEW_COMMENTS_COUNT'] = $NEW_COMMENTS->setSubscriber($userID)->setFromFile()->getCountForObject($arNews['ID']);
        }

        function timestampToDate($timestamp){
            return Helper::timestampToDate($timestamp);
        }
    }

    }else{
        $this->arResult['AUTHORIZED'] = false;
    }
 
	$this->includeComponentTemplate();
}
?>