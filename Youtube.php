<?php

namespace lesha724\youtubewidget;
use conquer\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * This is just an example.
 */
class Youtube extends \yii\base\Widget
{
    /**
     *  @link  https://developers.google.com/youtube/iframe_api_reference?hl=ru
     *  шаблон скпирта
     */
    const JS_SCRIPT = <<<JS
        var __player_id__;
        function onYouTubeIframeAPIReady() {
            __player_id__ = new YT.Player('__div_id__', {
                height: '%s',
                width: '%s',
                videoId: '%s',
                playerVars:{
                    %s
                },
                events: {
                    /*'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange*/
                    %s
                }
            });
        }
JS;


    public static $autoIdPrefix = 'wyoutube';
    /**
     * @var string $video youtube video url
     */
    public $video;

    protected $_defaultSettings = [
        'controls' => 1, //Этот параметр определяет, будут ли отображаться элементы управления проигрывателем. 0- не отображать, 1 или 2 - отображать
        'autoplay' => 0, // Определяет, начинается ли воспроизведение исходного видео сразу после загрузки проигрывателя.
        'showinfo' => 0, //Значения: 0 или 1. Значение по умолчанию: 1. При значении 0 проигрыватель перед началом воспроизведения не выводит информацию о видео, такую как название и автор видео.
        'start '   => 0, //Значение: положительное целое число. Если этот параметр определен, то проигрыватель начинает воспроизведение видео с указанной секунды.
        'loop '    => 0, //Значения: 0 или 1. Значение по умолчанию: 0. Если значение равно 1, то одиночный проигрыватель будет воспроизводить видео по кругу, в бесконечном цикле.
        'modestbranding'=>1 //Этот параметр позволяет использовать проигрыватель YouTube, в котором не отображается логотип YouTube.
    ];
    /**
     * @var int Этот параметр определяет, будут ли отображаться элементы управления проигрывателем. 0- не отображать, 1 или 2 - отображать
     */
    //public $controls = 1;
    /**
     * @var int Определяет, начинается ли воспроизведение исходного видео сразу после загрузки проигрывателя.
     */
    //public $autoplay = 0;
    /**
     * @var int Значения: 0 или 1. Значение по умолчанию: 1. При значении 0 проигрыватель перед началом воспроизведения не выводит информацию о видео, такую как название и автор видео.
     */
    //public $showinfo = 0;
    /**
     * @var int Значение: положительное целое число. Если этот параметр определен, то проигрыватель начинает воспроизведение видео с указанной секунды.
     */
    //public $start = 0;
    /**
     * @var int Значения: 0 или 1. Значение по умолчанию: 0. Если значение равно 1, то одиночный проигрыватель будет воспроизводить видео по кругу, в бесконечном цикле.
     */
    //public $loop = 0;
    /**
     * @var int //Этот параметр позволяет использовать проигрыватель YouTube, в котором не отображается логотип YouTube.
     */
    //public $modestbranding = 1;
    /*
     * @var set height video player
     */
    public $height = 390;
    /*
     * @var set width video player
     */
    public $width = 640;

    /**
     * @link https://developers.google.com/youtube/player_parameters?playerVersion=HTML5&hl=ru#playerapiid
     * @var array настройки плеера
     */
    public $playerVars = [];
    /*
     * @var array события плеера
     */
    public $events = [];

    /**
     * @var bool|string $_videoId youtube video id
     */
    protected $_videoId = false;

    public function init()
    {
        parent::init();

        $this->_videoId = $this->_getVideoIdByUrl($this->video);
    }

    public function run()
    {
        if($this->_videoId===false){
            $html = Html::tag('div', \Yii::t('yii','Error'));
            return $html;
        }
        $view = $this->getView();
        YoutubeAsset::register($view);
        //parent::run();

        return $this->_runWidget();
    }

    /*
    * подгрузка настроек
    */
    protected function _mergeSettings($settings) {
        if(!isset($this->playerVars['hl'])) {
            $this->playerVars['hl'] = substr(\Yii::$app->language, 0, 2 );
        }
        if(isset($this->playerVars['loop']) && $this->playerVars['loop']){
            $this->playerVars['playlist'] = $this->_videoId;
        }
        return array_merge($settings, $this->playerVars);
    }

    /**
     * Регистрация скрипта для вывода
     */
    protected function _registerJs() {
        /*$settings =[
            'controls' => $this->controls,
            'autoplay' => $this->autoplay,
            'showinfo' => $this->showinfo,
            'start '   => $this->start,
            'loop '    => $this->loop,
            'modestbranding'=>$this->modestbranding
        ];*/

        $settings = $this->_mergeSettings($this->_defaultSettings);

        $_settingsStr = Json::encode($this->settings);
        $_eventsStr = Json::encode($this->events);

        $_playerId = 'player_'.$this->id;

        $_script = sprintf(
            self::JS_SCRIPT,
            $this->height,
            $this->width,
            $this->_videoId,
            $_settingsStr,
            $_eventsStr
        );

        $_script = str_replace('__player_id__', $_playerId, $_script);
        $_script = str_replace('__div_id__', 'div_'.$this->id, $_script);

        $view = $this->getView();
        $view->registerJs($_script, View::POS_READY);
    }
    /**
     * Вывод виджета
     * @return string html widget
     */
    protected function _runWidget() {

        $html =
            Html::tag('div',
                Html::tag('div', '', ArrayHelper::merge(
                    [
                        'id' => 'div_'.$this->id
                    ],
                    $this->options
                )),
                [
                    'id' => $this->id
                ]
            );

        return $html;
    }

    /**
     * @param $url string Video url
     * @return bool|string
     */
    protected function _getVideoIdByUrl($url){
        $videoId = false;
        $url = parse_url($url);
        if (strcasecmp($url['host'], 'youtu.be') === 0)
        {
            #### (dontcare)://youtu.be/<video id>
            $videoId = substr($url['path'], 1);
        }
        elseif (strcasecmp($url['host'], 'www.youtube.com') === 0)
        {
            if (isset($url['query']))
            {
                parse_str($url['query'], $url['query']);
                if (isset($url['query']['v']))
                {
                    #### (dontcare)://www.youtube.com/(dontcare)?v=<video id>
                    $video_id = $url['query']['v'];
                }
            }
            if ($videoId == false)
            {
                $url['path'] = explode('/', substr($url['path'], 1));
                if (in_array($url['path'][0], array('e', 'embed', 'v')))
                {
                    #### (dontcare)://www.youtube.com/(whitelist)/<video id>
                    $videoId = $url['path'][1];
                }
            }
        }
        return $videoId;
    }
}
