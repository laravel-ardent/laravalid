<?php namespace LaravelArdent\Laravalid;

/**
 * This class is extending \Collective\Html\FormBuilder to make
 * validation easy for both client and server side. Package convert
 * laravel validation rules to javascript validation plugins while
 * using laravel FormBuilder.
 *
 * USAGE: Just pass $rules to Form::open($options, $rules) and use.
 * You can also pass by using Form::setValidation from controller or router
 * for coming first form::open.
 * When Form::close() is used, $rules are reset.
 *
 * NOTE: If you use min, max, size, between and type of input is different from string
 * don't forget to specify the type (by using numeric, integer).
 *
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @author     Igor Santos <igorsantos07+ardent@gmail.com>
 * @license    MIT
 * @see        Collective\Html\FormBuilder
 */
use LaravelArdent\Laravalid\Converter\Base\Converter;
use Collective\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;

class FormBuilder extends \Collective\Html\FormBuilder
{

    protected $converter;

    public function __construct(HtmlBuilder $html, UrlGenerator $url, $csrfToken, Converter $converter)
    {
        parent::__construct($html, $url, $csrfToken);
        $plugin          = \Config::get('laravalid.plugin');
        $this->converter = $converter;
    }

    /**
     * Set rules for validation
     *
     * @param array $rules Laravel validation rules
     *
     */
    public function setValidation($rules, $formName = null)
    {
        $this->converter()->set($rules, $formName);
    }

    /**
     * Get bound converter class
     */
    public function converter()
    {
        return $this->converter;
    }

    /**
     * Reset validation rules
     *
     */
    public function resetValidation()
    {
        $this->converter()->reset();
    }

    /**
     * Executes the remote validation and returns true or the error message
     * @param string $rule
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function remoteValidation($rule)
    {
        return $this->converter->route()->convert($rule, \Input::all());
    }

    /**
     * Opens form with a set of validation rules
     * @param array $rules Laravel validation rules
     * @see Collective\Html\FormBuilder
     * @return string
     */
    public function open(array $options = [], $rules = null)
    {
        $this->setValidation($rules);

        if (isset($options['name'])) {
            $this->converter->setFormName($options['name']);
        } else {
            $this->converter->setFormName(null);
        }

        return parent::open($options);
    }

    /**
     * Create a new model based form builder.
     * @param \LaravelArdent\Ardent\Ardent $model An Ardent model instance. Validation rules will be taken from it
     * @param array                        $options
     * @return string
     * @see Collective\Html\FormBuilder
     */
    public function model($model, array $options = [])
    {
        $this->setValidation($model::$rules);
        return parent::model($model, $options);
    }

    /**
     * @see Collective\Html\FormBuilder
     */
    public function input($type, $name, $value = null, $options = [])
    {
        $options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
        return parent::input($type, $name, $value, $options);
    }

    /**
     * @see Collective\Html\FormBuilder
     */
    public function textarea($name, $value = null, $options = [])
    {
        $options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
        return parent::textarea($name, $value, $options);
    }

    /**
     * @see Collective\Html\FormBuilder
     */
    public function select($name, $list = [], $selected = null, $options = [])
    {
        $options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
        return parent::select($name, $list, $selected, $options);
    }

    protected function checkable($type, $name, $value, $checked, $options)
    {
        $options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
        return parent::checkable($type, $name, $value, $checked, $options);
    }

    /**
     * Closes form and reset $this->rules
     *
     * @see Collective\Html\FormBuilder
     */
    public function close()
    {
        $this->resetValidation();
        return parent::close();
    }
}
