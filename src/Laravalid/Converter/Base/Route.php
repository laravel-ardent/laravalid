<?php namespace LaravelArdent\Laravalid\Converter\Base;
/**
 * Some description...
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Collective\Html\FormBuilder
 */

use LaravelArdent\Laravalid\Helper;

abstract class Route extends Container {

	public function convert($name, $parameters)
	{
		$methodName = strtolower($name);

		if(isset($this->customMethods[$methodName]))
		{
			return call_user_func_array($this->customMethods[$methodName], $parameters);
		}

		if(method_exists($this, $methodName))
		{
			return call_user_func_array([$this, $methodName], $parameters);
		}

		return $this->defaultRoute($name, $parameters);
	}

	public function defaultRoute($name, $parameters)
	{
		$params = Helper::decrypt($parameters['params']);
		unset($parameters['params']);

		$rules = array();
		foreach ($parameters as $k => $v)
		{
			$rules[$k] = $name . ':' . $params;
		}

		$validator = \Validator::make(
		    $parameters,
		    $rules
		);

		if (!$validator->fails())
			return \Response::json(true);

		return \Response::json($validator->messages()->first());
	}
}