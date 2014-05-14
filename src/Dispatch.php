<?php
namespace WScore\Pages;

/**
 * A simple page-based controller.
 *
 * Class PageController
 * @package Demo\Legacy
 */
class Dispatch
{
    /**
     * @var ControllerAbstract
     */
    protected $controller;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var PageView
     */
    protected $view;

    // +----------------------------------------------------------------------+
    //  construction of dispatch and setting controller object.
    // +----------------------------------------------------------------------+
    /**
     * @param Request $request
     * @param PageView    $view
     */
    public function __construct( $request, $view )
    {
        $this->request = $request;
        $this->view    = $view;
    }

    /**
     * @param ControllerAbstract $controller
     */
    public function setController( $controller )
    {
        $controller->inject( 'view',    $this->view );
        $controller->inject( 'request', $this->request );
        $controller->inject( 'session', Session::getInstance() );
        $this->controller = $controller;
    }

    /**
     * @param $controller
     * @return $this
     */
    public static function getInstance( $controller )
    {
        /** @var self $me */
        $me = new static(
            new Request(),
            new PageView()
        );
        $me->setController( $controller );
        return $me;
    }

    // +----------------------------------------------------------------------+
    //  execution of controllers. 
    // +----------------------------------------------------------------------+
    /**
     * @param $execMethod
     * @return array
     */
    protected function execMethod( $execMethod )
    {
        $controller = $this->controller;
        $refMethod  = new \ReflectionMethod( $controller, $execMethod );
        $refArgs    = $refMethod->getParameters();
        $parameters = array();
        foreach( $refArgs as $arg ) {
            $key  = $arg->getPosition();
            $name = $arg->getName();
            $opt  = $arg->isOptional() ? $arg->getDefaultValue() : null;
            $val  = $this->request->get( $name, $opt );
            $val  = $this->safe( $val );
            $parameters[$key] = $val;
            $this->view->set( $name, $val );
        }
        $refMethod->setAccessible(true);
        return $refMethod->invokeArgs( $controller, $parameters );
    }

    protected function safe( $value )
    {
        if( preg_match('/^[-_a-zA-Z0-9]*$/', $value ) ) {
            return $value;
        }
        return null;
    }

    /**
     * @param null|string $method
     * @return PageView
     */
    public function execute( $method='_method' )
    {
        $method = $this->request->getMethod( $method );
        $execMethod = 'on' . ucwords( $method );

        try {

            if( !method_exists( $this->controller, $execMethod ) ) {
                throw new \RuntimeException( 'no method: ' . $method );
            }
            if( $contents = $this->execMethod( $execMethod ) ) {
                $this->view->assign( $contents );
            }

        } catch( \Exception $e ) {
            $this->view->critical( $e->getMessage() );
        }
        return $this->view;
    }
    // +----------------------------------------------------------------------+
}