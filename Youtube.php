<?php

namespace lesha724\youtubewidget;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\web\View;

/**
 * This is just an example.
 */
class Youtube extends \yii\base\Widget
{
    const ID_JS = 'YoutubeAPIReady';
    const POS_JS = View::POS_HEAD;
    const START_JS = "function onYouTubePlayerAPIReady() {";
    /**
     *  @link  https://developers.google.com/youtube/iframe_api_reference?hl=ru
     *  шаблон скпирта
     */
    const JS_SCRIPT = <<<JS
        __player_id__ = new YT.Player('__div_id__', {
                height: '%s',
                width: '%s',
                videoId: '%s',
                playerVars:%s,
                /*events*/%s
            });
JS;

    /**
     * @link https://developers.google.com/youtube/player_parameters?playerVersion=HTML5&hl=ru#playerapiid
     * @var array настройки по умолчанию
     */
    protected $_defaultSettings = [
        /*	Значения: 0, 1 или 2. Значение по умолчанию: 1. Этот параметр определяет, будут ли отображаться элементы управления проигрывателем. При встраивании IFrame с загрузкой проигрывателя Flash он также определяет, когда элементы управления отображаются в проигрывателе и когда загружается проигрыватель:*/
        'controls' => 1,
        /*Значения: 0 или 1. Значение по умолчанию: 0. Определяет, начинается ли воспроизведение исходного видео сразу после загрузки проигрывателя.*/
        'autoplay' => 0,
        /*Значения: 0 или 1. Значение по умолчанию: 1. При значении 0 проигрыватель перед началом воспроизведения не выводит информацию о видео, такую как название и автор видео.*/
        'showinfo' => 0,
        /*Значение: положительное целое число. Если этот параметр определен, то проигрыватель начинает воспроизведение видео с указанной секунды. Обратите внимание, что, как и для функции seekTo, проигрыватель начинает воспроизведение с ключевого кадра, ближайшего к указанному значению. Это означает, что в некоторых случаях воспроизведение начнется в момент, предшествующий заданному времени (обычно не более чем на 2 секунды).*/
        'start'   => 0,
        /*Значение: положительное целое число. Этот параметр определяет время, измеряемое в секундах от начала видео, когда проигрыватель должен остановить воспроизведение видео. Обратите внимание на то, что время измеряется с начала видео, а не со значения параметра start или startSeconds, который используется в YouTube Player API для загрузки видео или его добавления в очередь воспроизведения.*/
        'end' => 0,
        /*Значения: 0 или 1. Значение по умолчанию: 0. Если значение равно 1, то одиночный проигрыватель будет воспроизводить видео по кругу, в бесконечном цикле. Проигрыватель плейлистов (или пользовательский проигрыватель) воспроизводит по кругу содержимое плейлиста.*/
        'loop ' => 0,
        /*тот параметр позволяет использовать проигрыватель YouTube, в котором не отображается логотип YouTube. Установите значение 1, чтобы логотип YouTube не отображался на панели управления. Небольшая текстовая метка YouTube будет отображаться в правом верхнем углу при наведении курсора на проигрыватель во время паузы*/
        'modestbranding'=>  1,
        /*Значения: 0 или 1. Значение по умолчанию 1 отображает кнопку полноэкранного режима. Значение 0 скрывает кнопку полноэкранного режима.*/
        'fs'=>1,
        /*Значения: 0 или 1. Значение по умолчанию: 1. Этот параметр определяет, будут ли воспроизводиться похожие видео после завершения показа исходного видео.*/
        'rel'=>1,
        /*Значения: 0 или 1. Значение по умолчанию: 0. Значение 1 отключает клавиши управления проигрывателем. Предусмотрены следующие клавиши управления.
            Пробел: воспроизведение/пауза
            Стрелка влево: вернуться на 10% в текущем видео
            Стрелка вправо: перейти на 10% вперед в текущем видео
            Стрелка вверх: увеличить громкость
            Стрелка вниз: уменьшить громкость
        */
        'disablekb'=>0
    ];

    public static $autoIdPrefix = 'wyoutube';
    /**
     * @var string $video youtube video url
     */
    public $video;
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
    /*
     * @var array опцыи для контейнера div
     */
    public $divOptions = [];
    /*
     * @var array опцыи для контейнера iframe
     */
    public $iframeOptions = [];

    /**
     * @var bool|string $_videoId youtube video id
     */
    protected $_videoId = false;
    /**
     * @var string 
     */
    private $_playerId;

    /**
     * получить id video (проверка являеться ли ссылкой или сразу уже айди)
     * @return string
     */
    protected function _getVideoId(){
        if(filter_var($this->video, FILTER_VALIDATE_URL)!== false){
            return $this->_getVideoIdByUrl($this->video);
        }else{
            return $this->video;
        }
    }

    public function init()
    {
        parent::init();
        $this->_videoId = $this->_getVideoId();
        $this->_playerId = 'player_'.$this->id;
    }

    public function run()
    {
        if($this->_videoId===false){
            $html = Html::tag('div', \Yii::t('yii','Error'));
            return $html;
        }
        $view = $this->getView();
        YoutubeAsset::register($view);
        return $this->_runWidget();
    }

    /*
     * Merge script for many players
     */
    protected function addJs($js) {
        $script = $this->view->js[self::POS_JS][self::ID_JS];
        $new_script = str_replace(self::START_JS, self::START_JS . ' ' . $js, $script);
        return $new_script;
    }
    /*
     * check setting
     */
    protected function checkSettings() {
        if(!$this->video){
            throw new InvalidParamException('Empty video indentificator.');
        }
    }

    /*
    * подгрузка настроек
    */
    protected function _mergeSettings($settings) {
        if(!isset($this->playerVars['hl'])) {
            $this->playerVars['hl'] = substr(\Yii::$app->language, 0, 2 );
        }
        return array_merge($settings, $this->playerVars);
    }

    /**
     * Регистрация скрипта для вывода
     */
    protected function _registerJs() {

        $settings = $this->_mergeSettings($this->_defaultSettings);

        $_settingsStr = Json::encode($settings);

        //$_eventsStr = !(empty($this->events))?'events: '.Json::encode($this->events):'';
        $_eventsStr = '';
        if(!empty($this->events)) {
            $_eventsStr = 'events: {';
            foreach ($this->events as $name => $event) {
                $_function = new JsExpression($event);

                $_eventsStr .= "$name : $_function,";
            }

            $_eventsStr .= '}';
        }

        $_playerId = $this->_playerId;

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

        $script = '';
        if(!isset($this->view->js[self::POS_JS][self::ID_JS])) {
            $script .= self::START_JS . $_script;
            $script .= "}";
        } else {
            $script = $this->addJs($_script);
        }
        $view->registerJs($script, View::POS_HEAD, self::ID_JS);
    }
    /**
     * Вывод виджета
     * @return string html widget
     */
    protected function _runWidget() {

        $js = "var " . $this->_playerId .";";
        $this->getView()->registerJs($js, View::POS_HEAD);

        $this->_registerJs();
        
        $html =
            Html::tag('div',
                Html::tag('div', '', ArrayHelper::merge(
                    [
                        'id' => 'div_'.$this->id
                    ],
                    $this->iframeOptions
                )),
                ArrayHelper::merge(
                    [
                        'id' => $this->id
                    ],
                    $this->divOptions
                )
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
                    $videoId = $url['query']['v'];
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
