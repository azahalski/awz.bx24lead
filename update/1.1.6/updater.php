<?
$moduleId = "awz.cookiessett";
if(IsModuleInstalled($moduleId)) {
	$eventManager = \Bitrix\Main\EventManager\EventManager::getInstance();
    $eventManager->registerEventHandlerCompatible('form', 'onAfterResultAdd', $moduleId,
		"\Awz\Bx24Lead\Handlers", "onAfterResultAdd"
	);
}