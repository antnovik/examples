<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if($arResult['AUTHORIZED']):?>
<?$APPLICATION->SetTitle($arResult['COURSE']['NAME']);?>
<?$APPLICATION->AddChainItem($arResult['COURSE']['NAME']);?>



<?$APPLICATION->IncludeComponent(
	"kdelo:course.menu",
	"student",
	array(
		'COURSE_FINISH_DATE' => $arResult['COURSE']['FINISH_DATE'],
		'COURSE_START_DATE' => $arResult['COURSE']['START_DATE'],
		'DAYS_LEFT' => $arResult['DAYS_LEFT'],
		'TIME_LEFT' => $arResult['TIME_LEFT'],
		'IS_WAITING' => $arResult['IS_WAITING'],
		'IS_FORM_ACTIVE' => $arResult['IS_FORM_ACTIVE'],
		'ACTIVE_TAB' => 'news'
	),
	false
);?>
<?endif;?>