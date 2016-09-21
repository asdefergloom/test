<?
set_time_limit(0);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
$_SERVER["DOCUMENT_ROOT"]="/home/bitrix/ext_www/darnis.ru";
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Mail\Event;

if (!Bitrix\Main\Loader::IncludeModule('sale')) die();

$dateDelay = new \Bitrix\Main\Type\DateTime;
$dbBasketDelayItems = CSaleBasket::GetList(
	Array(),
	Array(
		"LID"			=> SITE_ID,
		"DELAY"			=> "Y",
		">DATE_UPDATE"	=> $dateDelay->add("-30 day")
	),
	false,
	false
	Array("PRODUCT_ID", "NAME")
);
$arUserDelayBasketItems = Array();
while ($arBasketDelayItems = $dbBasketDelayItems->Fetch())
    $arUserDelayBasketItems[$arBasketDelayItems["FUSER_ID"]][] = $arBasketDelayItems;

foreach ($arUserDelayBasketItems as $nFuserID => $arBasketDelayItems)
{
	if($nUserID = \Bitrix\Sale\Fuser::getUserIdById($nFuserID))
	{
		$dateOrder = new \Bitrix\Main\Type\DateTime;
		$dbOrderItems = CSaleBasket::GetList(
			Array(),
			Array(
				"LID"			=> SITE_ID,
				"!ORDER_ID"		=> false,
				"FUSER_ID"		=> $nFuserID,
				">DATE_UPDATE"	=> $dateOrder->add("-1 month")
			),
			false,
			false,
			Array("PRODUCT_ID")
		);
		$arOrderItems = Array();
		while($arOrderItem = $dbOrderItems->Fetch())
			$arOrderItems[] = $arOrderItem["PRODUCT_ID"];
		
		$arItems = Array();
		foreach($arBasketDelayItems as $arBasketDelayItem){
			if(!in_array($arBasketDelayItem["PRODUCT_ID"], $arOrderItems))
			{
				$arItems[] = $arBasketDelayItem["NAME"];
			}
		}
		
		if(0 < count($arItems))
		{
			Event::send(array(
				"EVENT_NAME"	=> "DELAY_30",
				"LID"			=> SITE_ID,
				"C_FIELDS"		=> Array(
					"EMAIL" => CUser::GetByID($nUserID)->Fetch()["EMAIL"],
					"ITEMS" => implode(", ", $arItems)
				),
			)); 
		}
	}
}
?>