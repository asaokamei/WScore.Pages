<?php
namespace tests\pages\mocks;

use WScore\Pages\ControllerAbstract;

class TestController extends ControllerAbstract
{
    /**
     * checking if dispatcher executed this method
     * @var bool
     */
    public $executed = false;
    
    function onExecute()
    {
        $this->executed = true;
        return ['execute' => 'executed'];
    }
    
    function onArgument( $arg )
    {
        if( !$arg ) {
            $this->critical( 'please specify the argument' );
        }
        return ['argument' => $arg ];
    }
}