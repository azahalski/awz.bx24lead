<?
$moduleId = "awz.cookiessett";
if(IsModuleInstalled($moduleId)) {
	$eventManager = \Bitrix\Main\EventManager\EventManager::getInstance();
    $eventManager->registerEventHandlerCompatible('form', 'onAfterResultAdd', $this->MODULE_ID,
		"\Awz\Bx24Lead\Handlers", "onAfterResultAdd"
	);
}