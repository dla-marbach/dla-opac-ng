<?php
namespace Subugoe\Find\Controller;

class StartController extends \Subugoe\Find\Controller\SearchController
{
    public static $my_static = 0;


    /**
     * Start Action.
     */
    public function startAction()
    {
        ++self::$my_static;

        if (self::$my_static > 1) {
            self::$my_static == 0;
            return FALSE;
        }
    }
}