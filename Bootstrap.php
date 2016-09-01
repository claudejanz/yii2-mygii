<?php


namespace claudejanz\mygii;

use yii\base\Application;
use yii\base\BootstrapInterface;


/**
 * Class Bootstrap
 * @package claudejanz\mygii
 * @author Claude Janz  
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if ($app->hasModule('gii')) {
            if (!isset($app->getModule('gii')->generators['doubleModel'])) {
                $app->getModule('gii')->generators['doubleModel'] = 'claudejanz\mygii\generators\model\Generator';
            }
        }
    }
}