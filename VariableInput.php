<?php

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

    /**
     * The input fields that are going to get displayed on every row.
     * @example $attributes = [
     *              [
     *                  'name'=>'employee_name',
     *                  'type'=>'text'
     *              ],
     *              [
     *                  'name'=>'company',
     *                  'type'=>'textarea'
     *              ]
     *          ];    
     * 
     * @var array
     */
    public $attributes = [];
    public $addItemTooltipText = 'Add a new Item';
    public $removeItemTooltipText = 'Remove this Item';

    /**
     * Values (if any) to initialize the widget. Fields initialized here must match those found in the $attributes attribute.
     * 
     * 
     * @example the following example matches the example used for the $attributes attribute. It could be used to initialize the widget with two pre-populated rows.
     *  $values = [
     *      [
     *          'employee_name'=>'John',
     *          'company'=>'IBM'
     *      ],
     *      [
     *          'employee_name'=>'Joana',
     *          'company'=>'Msoft'
     *      ];                      
     *          
     * 
     * @var array 
     */
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

        Yii::app()->getClientScript()->registerScriptFile($this->getAssetsUrl() . '/lib.js');
        Yii::app()->getClientScript()->registerCssFile($this->getAssetsUrl() . '/main.css');

        $js = <<<EOF
$(document).on('click','.variable_input_widget_button.remove_button',function(){
    //if remove button is pressed, remove the current row from the div and re-number all the subsequent ones
   
    formDiv=$(this).parent().parent().parent();

    var buttonId = $(this).attr('id');

    var rowId = buttonId.match(/\d+$/);

    removeRow(formDiv,rowId[0]);
});
   
$(document).on('click','#{$this->id}',function(){   
   
    var formdiv = $("#{$this->divId}");
    
    matches = formdiv.html().match(/(\[\d+\])/ig);
    
    var num;
    
    if(matches===null){
        num = 0;
    }else{
    
        nums = matches.map(function(x){ return x.replace(/\[|\]/g,""); }).map(function(x){ return parseInt(x); } );
        num = Math.max.apply(null, nums) +1;
    }
    formdiv.append('<div class=\'variable_input_widget_row_div\' id=\'viw_row_div_'+num+'\'>
EOF;

        if ($this->allAttributesAreLinear())
            $separator = '&nbsp;';
        else
            $separator = '<br />';

        
        foreach ($this->attributes as $element) {

            if (!is_array($element))
                throw new VariableInputException('Attribute $attributes of VariableInputWidget should be an array of associative arrays. Each of the inner arrays is composed of name-value pairs that each set the options for what a row should look like.');

            if ($element['type'] !== 'textarea' && $element['type'] !== 'text' && $element['type'] !== 'dropdownlist')
                throw new VariableInputException('Invalid input type. Valid input types are "text","textarea" and "dropdownlist" got: "' . $element['type'] . '".');

            $input_name = $element['name'];
            $input_type = $element['type'];

            if ($input_type === 'textarea') {
                $js .= <<<EOF
<textarea name="{$this->name}['+ num +'][{$input_name}]" style="height:90px;resize:vertical;width:90%;"></textarea>{$separator}
EOF;
            } elseif ($input_type === 'text') {
                $js .= <<<EOF
<input type=\'text\' name="{$this->name}['+ num +'][{$input_name}]" placeholder="{$input_name}" />                      
EOF;
            } elseif ($input_type === 'dropdownlist') {
                $options = preg_split('/,/', $this->getOptions($input_name));

                $code = <<<EOF
<select name="{$this->name}['+ num +'][{$input_name}]"><option value=\'Please select\'>Please select</option>";
EOF;

                foreach ($options as $option) {
                    $code .= "<option value=\'{$option}\'>{$option}</option>";
                }

                $code .= "</select>{$separator}";

                $js .= $code;
            }
            else
                throw new VariableInputException('Input type not supported: "' . $input_type . '"');
        }
        $js = preg_replace('/<br \/>$/', '', $js);

        $js .=<<<EOF
&nbsp;<button type=\'button\' tabIndex=\'-1\' class=\'variable_input_widget_button remove_button\' style=\'position:relative;top:-1px;\' id=\'viw_remove_button_'+num+'\' ><img src=\'{$this->removeItemIconPath}\' {$this->removeItemTooltipText} /></button><br /></div>');
$('[rel="tooltip"]').tooltip({show:200,hide:1});
});
EOF;

        Yii::app()->getClientScript()->registerScript('variable-form' . $this->divId, $js);

        $attribute = $this->attribute;

        //we need to initialize the widget the same way normal inputs are initialized when we set a value to the form model attribute.
        if (isset($this->values) && !empty($this->values))
            $this->setInitialValuesBasedOnValuesArray();
        elseif (isset($this->model->$attribute))
            $this->setInitialValuesBasedOnModel($attribute);
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
        }
        else
            return $this->assetsUrl;
    }

    /**
     * PRIVATE PARTS 
     */
    private function allAttributesAreLinear() {
        $attributes = $this->attributes;

        foreach ($attributes as $row_config) {
            if ($row_config['type'] !== 'text' && $row_config['type'] !== 'dropdownlist')
                return false;
        }
        return true;
    }

    private function setInitialValuesBasedOnValuesArray() {
        $initialization_array = $this->values;

        $js = <<<EOF
var formdiv = $("#{$this->divId}");
    
var num = 0;

EOF;

        if ($this->allAttributesAreLinear())
            $separator = '&nbsp;';
        else
            $separator = '<br />';

        foreach ($initialization_array as $row_values) {

            $js .=" formdiv.append('<div class=\'variable_input_widget_row_div\' id=\'viw_row_div_'+ num +'\'>";
            //associative array , 'name'=>'value'
            foreach ($row_values as $name => $value) {

                $type = $this->getInputType($name);

                if ($type === 'textarea') {
                    //javascript chokes on newlines
                    $value = preg_replace('/(\n|\r\n|\r)/', '\\n', $value);
                    $value = str_replace("'", "\\'", $value);

                    $js .= <<<EOF
<textarea name="{$this->name}['+ num +'][{$name}]" style="height:90px;resize:vertical;width:90%;">{$value}</textarea>{$separator}
EOF;
                } elseif ($type === 'text') {
                    $js .= <<<EOF
<input type=\'text\' name="{$this->name}['+ num +'][{$name}]" placeholder="{$name}" value="{$value}" />{$separator}
EOF;
                } elseif ($type === 'dropdownlist') {

                    $options = preg_split('/,/', $this->getOptions($name));

                    $code = <<<EOF
<select name="{$this->name}['+ num +'][{$name}]"><option value=\'Please select\'>Please select</option>";
EOF;

                    foreach ($options as $option) {

                        if ($value === $option)
                            $code .= "<option value=\'{$option}\' selected=\'selected\' >{$option}</option>";
                        else
                            $code .= "<option value=\'{$option}\'>{$option}</option>";
                    }

                    $code .= "</select>{$separator}";

                    $js .= preg_replace('/(\n|\r\n|\r)/', '\\n', $code);
                }
                else
                    throw new VariableInputException('Input type not supported: "' . $type . '"');
            }

            $js = preg_replace('/<br \/>$/', '', $js);

            $js .=<<<EOF
&nbsp;<button type=\'button\' tabIndex=\'-1\' class=\'variable_input_widget_button remove_button\' style=\'position:relative;top:-1px;\' id=\'viw_remove_button_'+ num++ +'\'><img src=\'{$this->removeItemIconPath}\' {$this->removeItemTooltipText} /></button><br /></div>');
EOF;
        }

        $js.=<<<EOF
$('[rel="tooltip"]').tooltip({show:200,hide:1});
EOF;
        Yii::app()->getClientScript()->registerScript('initializing-values' . $this->divId, $js);
    }
    
    private function getInputType($input_name) {
        foreach ($this->attributes as $attribute_config_array) {

            if ($attribute_config_array['name'] === $input_name)
                return $attribute_config_array['type'];
        }

        throw new VariableInputException('Failed to find input type for input whose name is :"' . $input_name . '"');
    }

    /**
     * Get options to be displayed in a dropdownlist (will throw an Exception if $input_name does not represent a dropdownlist.)
     * 
     * @param type $input_name
     * @return string
     * @throws VariableInputException
     */
    private function getOptions($input_name) {
        foreach ($this->attributes as $row_config) {
            if ($row_config['name'] === $input_name)
                if ($row_config['type'] === 'dropdownlist') {
                    if (isset($row_config['options']))
                        return $row_config['options'];
                    else
                        throw new VariableInputException('Options not found for attribute "' . $input_name . '"');
                }
                else
                    throw new VariableInputException('Failed to fetch "options" for attribute "' . $input_name . '": its type is not dropdownlist.');
        }
        throw new VariableInputException('Failed to fetch "options" node attribute for attribute "' . $input_name . '": attribute not found.');
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

        if (is_null($this->attribute) && is_null($this->model))
            throw new VariableInputException('Either "attribute" or "model" attributes for VariableInputWidget must be set.');

        if (empty($this->attributes))
            throw new VariableInputException('The "attributes" attribute must be a non-empty array of associative arrays, each defining an input field to be displayed.');
    }

    private function setDefaultValuesForOptions() {
        if (is_null($this->id)) {
            if (is_null($this->model))
                $this->id = $this->p->purify($this->name);
            else
                $this->id = $this->p->purify(get_class($this->model) . "_" . $this->attribute);
        }

        if (is_null($this->model))
            $this->name = $this->p->purify($this->attribute);
        else
            $this->name = $this->p->purify(get_class($this->model) . "[" . $this->attribute . "]");

        $this->id = $this->p->purify($this->id);

        $this->addItemIconPath = $this->p->purify(Yii::app()->baseUrl . '/images/icons/' . $this->addItemIcon . ".png");
        $this->addItemTooltipText = $this->p->purify(is_null($this->addItemTooltipText) ? "" : "title='{$this->addItemTooltipText}' rel='tooltip'");

        $this->removeItemIconPath = $this->p->purify(Yii::app()->baseUrl . '/images/icons/' . $this->removeItemIcon . ".png");
        $this->removeItemTooltipText = $this->p->purify(is_null($this->removeItemTooltipText) ? "" : "title=\'{$this->removeItemTooltipText}\' rel=\'tooltip\'");

        $this->divId = 'div' . uniqid();
    }

}

?>
