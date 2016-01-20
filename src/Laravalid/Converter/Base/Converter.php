<?php namespace LaravelArdent\Laravalid\Converter\Base;

use LaravelArdent\Laravalid\Helper;

/**
 * Base converter class for converter plugins
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Collective\Html\FormBuilder
 */
abstract class Converter
{

    /**
     * Rule converter class instance
     * @var array
     */
    protected static $rule;

    /**
     * Message converter class instance
     * @var array
     */
    protected static $message;

    /**
     * Route redirecter class instance
     * @var array
     */
    protected static $route;

    /**
     * Rules which specify input type is numeric
     * @var array
     */
    protected $validationRules = [];

    /**
     * Current form name
     * @var string
     */
    protected $currentFormName = null;

    /**
     * Rules which specify input type is numeric
     * @var array
     */
    protected $numericRules = ['integer', 'numeric'];

    public function __construct()
    {
        self::$rule    = new Rule();
        self::$message = new Message();
        self::$route   = new Route();
    }

    public function rule()
    {
        return static::$rule;
    }

    public function message()
    {
        return static::$message;
    }

    public function route()
    {
        return static::$route;
    }

    /**
     * Set rules for validation
     * @param array $rules Laravel validation rules
     */
    public function set($rules, $formName = null)
    {
        if ($rules === null) {
            return;
        }

        $this->validationRules[$formName] = $rules;
    }

    /**
     * Reset validation rules
     */
    public function reset()
    {
        if (isset($this->validationRules[$this->currentFormName])) {
            unset($this->validationRules[$this->currentFormName]);
        } else {
            if (isset($this->validationRules[null])) {
                unset($this->validationRules[null]);
            }
        }
    }

    /**
     * Set form name in order to get related validation rules
     * @param array $formName Form name
     */
    public function setFormName($formName)
    {
        $this->currentFormName = $formName;
    }

    /**
     * Get all given validation rules
     * @param array $rules Laravel validation rules
     */
    public function getValidationRules()
    {
        if (isset($this->validationRules[$this->currentFormName])) {
            return $this->validationRules[$this->currentFormName];
        } else {
            if (isset($this->validationRules[null])) {
                return $this->validationRules[null];
            }
        }

        return null;
    }

    /**
     * Returns validation rules for given input name
     * @return array
     */
    public function getValidationRule($inputName)
    {
        $rules = $this->getValidationRules();
        if (isset($rules[$inputName])) {
            return is_array($rules[$inputName])? $rules[$inputName] : explode('|', $rules[$inputName]);
        } else {
            return [];
        }
    }

    /**
     * Checks if there is a rules for given input name
     * @return string
     */
    protected function checkValidationRule($inputName)
    {
        return isset($this->getValidationRules()[$inputName]);
    }

    public function convert($inputName)
    {
        $outputAttributes  = [];
        $messageAttributes = [];

        if ($this->checkValidationRule($inputName) === false) {
            return [];
        }

        $rules = $this->getValidationRule($inputName);
        $type  = $this->getTypeOfInput($rules);

        foreach ($rules as $rule) {
            $parsedRule     = $this->parseValidationRule($rule);
            $ruleAttributes = $this->rule()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);
            $outputAttributes += $ruleAttributes;

            if (in_array($parsedRule['name'], ['max', 'between'])) {
                $outputAttributes['maxlength'] = $ruleAttributes['data-rule-maxlength'];
            }

            if (\Config::get('laravalid.useLaravelMessages', true)) {
                $messageAttributes = $this->message()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);

                if (empty($messageAttributes)) {
                    $messageAttributes = $this->getDefaultErrorMessage($parsedRule['name'], $inputName);
                }
            }

            $outputAttributes = $outputAttributes + $messageAttributes;
        }

        return $outputAttributes;
    }

    /**
     * Gets all rules and returns type of input if rule specifies type. Now, just for numeric.
     * @return string
     */
    protected function getTypeOfInput($rulesOfInput)
    {
        foreach ($rulesOfInput as $key => $rule) {
            $parsedRule = $this->parseValidationRule($rule);
            if (in_array($parsedRule['name'], $this->numericRules)) {
                return 'numeric';
            } elseif ($parsedRule['name'] === 'array') {
                return 'array';
            }
        }

        return 'string';
    }

    /**
     * Parses validation rule of laravel
     * @return array
     */
    protected function parseValidationRule($rule)
    {
        $ruleArray = ['name' => '', 'parameters' => []];

        $explodedRule            = explode(':', $rule);
        $ruleArray['name']       = array_shift($explodedRule);
        $ruleArray['parameters'] = explode(',', array_shift($explodedRule));

        return $ruleArray;
    }

    /**
     * Gets default error message
     * @return string
     */
    protected function getDefaultErrorMessage($laravelRule, $attribute)
    {
        // getting user friendly validation message
        $message = Helper::getValidationMessage($attribute, $laravelRule);

        return ['data-msg-'.$laravelRule => $message];
    }
}
