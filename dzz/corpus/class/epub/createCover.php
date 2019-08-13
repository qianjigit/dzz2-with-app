<?php
class createCover{
    public $title = '';//封面标题
    public $bgColor = '';//封面背景
    public $color = '#000';//字体颜色
    public $img = false;//图片标识
    public $file = 'fangzheng.ttf';//字体文件
    public $maxTitleWidth = 680;//标题最大宽度
    public $fontSize = 80;//字体大小
    public $top = 550;//文字距离图片顶部距离
    public $leftRight = 200;//文字距离左右两边距离
    private $width = 1080;//图片宽度
    private $height=1920;//图片高度

    public function __construct($title,$bgColor){
        $this->bgColor = $bgColor;
        $this->title = $title;
    }
    private function writeWords($title,$width){//处理标题
        mb_internal_encoding("UTF-8"); // 设置编码
        $content = '';
        $textstr = '';
        $strpre = '/[a-zA-Z]+/';
        if(preg_match($strpre,$title)){
            $words = explode(' ',$title);
        }else{
            for($i = 0; $i < mb_strlen($title);$i++){
                $words[] = mb_substr($title,$i,1);
            }
        }

        foreach($words as $v){
            $textstr = $content.' '.$v;
            $textbox = imagettfbbox($this->fontSize,0,'fangzheng.ttf',$textstr);
            if($textbox[2] > $width && $content !=''){
                $content .="\n";
            }
            if(preg_match($strpre,$v)) $key = ' ';
            else $key = '';
            $content .= $key.$v;
        }
        return $content;
    }
    private function hexToRgb($color){
        $color = str_replace('#', '', $color);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $color;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }
    public function makCover()
    {
        $bgcolor = $this->hexToRgb($this->bgColor);
        $fontColor = $this->hexToRgb($this->color);
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $name = $this->writeWords($this->title,$this->maxTitleWidth);

        $nameArr = explode("\n",$name);
        $color = imagecolorallocate($this->img, $bgcolor['r'], $bgcolor['g'], $bgcolor['b']);
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
        $fontColor = imagecolorallocate($this->img, $fontColor['r'], $fontColor['g'], $fontColor['b']);
        foreach($nameArr as $k=>$val){
            $fontArr = imagettfbbox($this->fontSize,0,'fangzheng.ttf',$val);
            imagettftext($this->img, $this->fontSize, 0, floor(($this->maxTitleWidth-$fontArr[2])/2)+$this->leftRight, $this->top+ 130*$k, $fontColor, $this->file, $val);
        }
        ob_start();
        imagepng($this->img);
        $image_data = ob_get_contents();
        ob_end_clean();
        return $image_data;
    }
}