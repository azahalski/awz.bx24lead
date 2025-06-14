<?php
namespace Awz\Bx24Lead\Api\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Awz\Bx24Lead\Api\Filters\Sign;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

use Bitrix\Main\Request;

Loc::loadMessages(__FILE__);

class Trace extends Controller
{

    private string $moduleId = '';

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function configureActions()
    {
        return array(
            'save' => array(
                'prefilters' => array(
                    new Scope(Scope::AJAX),
                    new Sign(array('time'))
                )
            )
        );
    }

    public function saveAction(int $time=0){

        if($time<time()){
            $this->addError(
                new Error(
                    "timeout expired",
                    100
                )
            );
            return null;
        }

        \Awz\Bx24Lead\bx24Trace::OnProlog();

        return null;

    }

}