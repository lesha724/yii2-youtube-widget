<?php
/**
 * Created by PhpStorm.
 * User: Neff
 * Date: 06.02.2017
 * Time: 16:01
 */

namespace lesha724\youtubewidget;


use yii\web\AssetBundle;

class YoutubeAsset extends AssetBundle
{
    public $js = [
        'https://www.youtube.com/iframe_api'
    ];

    public $jsOptions = [
        'position'=>\yii\web\View::POS_HEAD
    ];
}