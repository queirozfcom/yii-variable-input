<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of VariableInputWidget
 *
 * @author falmeida
 */
class VariableInput extends CWidget {

    public $model = null;
    public $id = null;
    public $addItemIcon = 'plus';
    public $removeItemIcon = 'minus';
    public $attribute = null;
    public $attributes = [];
    public $addItemTooltipText = 'Add a new Item';
    public $removeItemTooltipText = 'Remove this Item';
    public $values = null;
    private $addItemIconPath;
    private $assetsUrl;
    private $divId;
    private $name;
    private $p;
    private $removeItemIconPath;

    public function init() {
        parent::init();

        $this->p = new CHtmlPurifier;

        $this->validateOptions();
        $this->setDefaultValuesForOptions();

        $js = <<<EOF
   
Array.max = function( array ){
    return Math.max.apply( Math, array );
};
Array.min = function( array ){
    return Math.min.apply( Math, array );
};   
   
$(document).on('click','.variable_input_widget_button.remove_button',function(){
   //if remove button is pressed, remove the current row from the div and re-number all the subsequent ones
   
    formDiv=$(this).parent().parent().parent();

    var buttonId = $(this).attr('id');

    var rowId = buttonId.match(/\d+$/);

    removeRow(formDiv,rowId[0]);
});
   
$(document).on('click','#{$this->id}',function(){   
   
    var formdiv = $("#{$this->divId}");
    
    matches = formdiv.html().match(/(\[\d\])/ig);

    var num;
    
    if(matches===null){
        num = 0;
    }else{
        nums = matches.map(function(x){ return x.replace(/\[|\]/g,""); });
        num = Math.max.apply(null, nums) +1;
    }
    formdiv.append('<div class=\'variable_input_widget_row_div\' id=\'viw_row_div_'+num+'\'>
EOF;

        foreach ($this->attributes as $key => $value) {

            //this IF deals separately with cases where just a name was supplied as opposed
            //to cases where something like "name"=>[options] was supplied for each attribute
            //this needs to be changed if other input types are to be supported
            if (is_int($key)) {

                $js .= <<<EOF
<input type=\'text\' name="{$this->name}['+ num +'][{$value}]" placeholder="{$value}"  /> 
EOF;
            }
        }
        $js .=<<<EOF
<button type=\'button\' tabIndex=\'-1\' class=\'variable_input_widget_button remove_button\' style=\'position:relative;top:-1px;\' id=\'viw_remove_button_'+num+'\' ><img src=\'{$this->removeItemIconPath}\' {$this->removeItemTooltipText} /></button>
EOF;

        $js.=<<<EOF
<br /></div>');
$('[rel="tooltip"]').tooltip({show:200,hide:1});
});

function removeRow(divElement,rowIdToRemove){
    $('.tooltip').remove();
    $('#viw_row_div_'+rowIdToRemove,divElement).remove();
}

EOF;

        Yii::app()->getClientScript()->registerCssFile($this->getAssetsUrl() . "/main.css");

        Yii::app()->getClientScript()->registerScript('variable-form' . $this->divId, $js);

        $attribute = $this->attribute;

        //we need to initialize the widget the same way normal inputs are initialized when we set a value to the form model attribute.
        if (isset($this->values) && !empty($this->values)) {
            $this->setInitialValuesBasedOnValuesArray();
        } elseif (isset($this->model->$attribute)) {
            $this->setInitialValuesBasedOnModel($attribute);
        }
    }

    public function run() {
        $html = <<<EOF
<div id='{$this->divId}'></div>
<button type='button' tabIndex='-1' id='{$this->id}' name='{$this->attribute}' class='variable_input_widget_button add_button' {$this->addItemTooltipText} ><img src='{$this->addItemIconPath}'/>
</button>
EOF;
        echo $html;
    }

    public function getAssetsUrl() {
        if (!isset($this->assetsUrl)) {
            $url = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('ext.widgets.VariableInput.assets'));

            $this->assetsUrl = $url;

            return $this->assetsUrl;
        } else {
            return $this->assetsUrl;
        }
    }

    private function setInitialValuesBasedOnValuesArray() {
        $initialization_array = $this->values;

        $js = <<<EOF
var formdiv = $("#{$this->divId}");
    
var num = 0;

EOF;
        foreach ($initialization_array as $line) {

            $js .=" formdiv.append('<div class=\'variable_input_widget_row_div\' id=\'viw_row_div_'+ num +'\'>";

            foreach ($line as $name => $value) {
                $js .= <<<EOF
<input type=\'text\' name="{$this->name}['+ num +'][{$name}]" placeholder="{$name}" value="{$value}" /> 
EOF;
            }
            $js .=<<<EOF
<button type=\'button\' tabIndex=\'-1\' class=\'variable_input_widget_button remove_button\' style=\'position:relative;top:-1px;\' id=\'viw_remove_button_'+ num++ +'\'><img src=\'{$this->removeItemIconPath}\' {$this->removeItemTooltipText} /></button><br /></div>');
EOF;
        }

        $js.=<<<EOF

$('[rel="tooltip"]').tooltip({show:200,hide:1});
EOF;
        Yii::app()->getClientScript()->registerScript('initializing-values' . $this->divId, $js);
    }

    private function setInitialValuesBasedOnModel($attribute) {
        $initialization_array = $this->model->$attribute;

        $js = <<<EOF
var formdiv = $("#{$this->divId}");
    
var num = 0;

EOF;

        foreach ($initialization_array as $line) {

            $js .="formdiv.append('<div class=\'variable_input_widget_row_div\' id=\'viw_row_div_'+ num +'\'>";

            foreach ($line as $name => $value) {
                $js .= <<<EOF
<input type=\'text\' name="{$this->name}['+ num +'][{$name}]" placeholder="{$name}" value="{$value}" /> 
EOF;
            }
            $js .=<<<EOF
<button type=\'button\' tabIndex=\'-1\' class=\'variable_input_widget_button remove_button\' style=\'position:relative;top:-1px;\' id=\'viw_remove_button_'+ num++ +'\' ><img src=\'{$this->removeItemIconPath}\' {$this->removeItemTooltipText} /></button>
EOF;
        }

        $js.=<<<EOF
<br /></div>');
$('[rel="tooltip"]').tooltip({show:200,hide:1});
EOF;
        Yii::app()->getClientScript()->registerScript('initializing-values' . $this->divId, $js);
    }

    private function validateOptions() {
        Yii::import('ext.widgets.VariableInput.exceptions.VariableInputException');
        if (is_null($this->attribute) and is_null($this->model)) {
            throw new VariableInputException('Either "attribute" or "model" attributes for VariableInputWidget');
        }
        if (empty($this->attributes)) {
            throw new VariableInputException('The "attributes" attribute must be an array with, at least, one attribute name.');
        }
    }

    private function setDefaultValuesForOptions() {
        if (is_null($this->id)) {
            if (is_null($this->model)) {
                $this->id = $this->p->purify($this->name);
            } else {
                $this->id = $this->p->purify(get_class($this->model) . "_" . $this->attribute);
            }
        }

        if (is_null($this->model)) {
            $this->name = $this->p->purify($this->attribute);
        } else {
            $this->name = $this->p->purify(get_class($this->model) . "[" . $this->attribute . "]");
        }


        $this->id = $this->p->purify($this->id);

        $this->addItemIconPath = $this->p->purify(Yii::app()->baseUrl . '/images/icons/' . $this->addItemIcon . ".png");
        $this->addItemTooltipText = $this->p->purify(is_null($this->addItemTooltipText) ? "" : "title='{$this->addItemTooltipText}' rel='tooltip'");

        $this->removeItemIconPath = $this->p->purify(Yii::app()->baseUrl . '/images/icons/' . $this->removeItemIcon . ".png");
        $this->removeItemTooltipText = $this->p->purify(is_null($this->removeItemTooltipText) ? "" : "title=\'{$this->removeItemTooltipText}\' rel=\'tooltip\'");

        $this->divId = 'div' . uniqid();
    }

}

?>
