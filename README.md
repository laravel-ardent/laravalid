#Laravalid + Ardent
#### Laravel Validation For Client Side, using self-validating smart models from Ardent

This package makes validation rules defined in Laravel work in the client by converting validation rules to HTML + JS plugins (such as jQuery Validation). It also allows you to use Laravel validation messages so you can show the same messages on both sides.


### Table of contents
 - [Feature Overview](#feature-overview)
 - [Installation](#installation)
 - [Configuration](#configuration)
     - [Validation messages](#validation-messages)
 - [Usage](#usage)
     - [With Ardent](#usage-with-ardent)
 - [Extending](#extending)
 - [Plugins and Supported Rules](#plugins-and-supported-rules)
 - [Known Issues](#known-issues)
 - [To Do](#todo)
 - [Changelog](#changelog)
 - [Licence](#licence)


### Feature Overview
- Multi-Plugin Support  *(For now, there is just one :)*
    - `jQuery Validation`
- Extensible
- Laravel form builder based
- Validation rules can be set from controller
- Distinguishing between numeric input and string input
- User-friendly input names
- Remote rules such as unique and exists

### Installation

Require `bllim/laravel-validation-for-client-side` in composer.json and run `composer update`.

    {
        "require": {
            "laravel/framework": "5.1.*",
            ...
            "laravel-ardent/laravalid": "2.*"
        }
        ...
    }

> **Note:** For **Laravel 4** use `laravel4` branch: `"laravel-ardent/laravalid": "dev-laravel4"`

Composer will download the package. After that, open `config/app.php` and add the service provider and aliases:
```php
    'providers' => [
        ...
        'LaravelArdent\Laravalid\LaravalidServiceProvider',
    ],
    
    'aliases' => [
        ...
        'HTML' => 'Illuminate\Support\Facades\HTML',
        'Form' => 'LaravelArdent\Laravalid\Facade',
    ],
```

Also you need to publish configuration file and assets by running the following Artisan commands.
```php
$ ./artisan vendor:publish
```

### Configuration
After publishing configuration file, you can find it in config/laravalid folder. Configuration parameters are as below:

| Parameter | Description | Values |
|-----------|-------------|--------|
| plugin | Choose plugin you want to use | See [Plugins and Supported Rules](#plugins-and-supported-rules) |
| useLaravelMessages | If it is true, laravel validation messages are used in client side otherwise messages of chosen plugin are used  | `boolean`. See [Validation Messages](#validation-messages) | 
| route | Route name for remote validation | Any route name (default: laravalid). The route will receive an argument named `rule` |
| action | A custom action to run the remote validation procedure | An action string, such as `SiteController@getValidation`. You must create that action if you plan to run remote validations. This is needed if you want to cache routes (`./artisan route:cache`)

#### Validation Messages
If you set `useLaravelMessages` to `true`, you're able to use (Laravel's Localization package)[l10n] to generate validation messages. To do so, follow the [docs][l10n] to get the package configured (by setting your default/fallback/current locales). Then, create a folder for each locale (as the docs says) and create a `validation.php` file for each one. Inside those files you'll set a message for each rule name, as follows:
```php
<?php return [
    'required' => 'This is a required field',
    'min'      => [
        'string' => 'This is too short',
        'number' => 'This is too low',
    ]
    //...
];
```

### Usage

The package uses Laravel Form Builder to make validation rules work for both sides. While opening a form by using `Form::open` you can pass the $rules as the second parameter:
```php
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::open(['url' => 'foo/bar', 'method' => 'put'], $rules);
    Form::text('name');
    Form::text('email');
    Form::text('birthdate');
    Form::close(); // don't forget to close form, it reset validation rules
```

Also if you don't want to struggle with $rules at view files, you can set it in Controller or route with or without form name by using Form::setValidation($rules, $formName). If you don't give form name, this sets rules for first Form::open
```php    
    // in controller or route
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::setValidation($rules, 'firstForm'); // you can also use without giving form name Form::setValidation($rules) because there is just one.
    
    // in view
    Form::open(array('url' => 'foo/bar', 'method' => 'put', 'name' => 'firstForm'), $rules);
    // some form inputs
    Form::close();
```
For rules which is related to input type in laravel (such as max, min), the package looks for other given rules to understand which type is input. If you give integer or numeric as rule with max, min rules, the package assume input is numeric and convert to data-rule-max instead of data-rule-maxlength.
```php
    $rules = ['age' => 'numeric|max'];
```
The converter assume input is string by default. File type is not supported yet.

#### Usage with Ardent

The magic from this package extension, though, comes from the integration with Ardent models. Here are two ways to use it with Ardent (the second being the preferred one):
```php
    // you can bring in the rules from the model...
    Form::open(['url' => 'foo/bar', 'method' => 'put'], App\Models\User::$rules);
    // ...or use the model form to make things even cleaner: the rules will be imported from it!
    Form::model($user, ['url' => 'foo/bar', 'method' => 'put']);
```

**Validation Messages**

Converter uses validation messages of laravel (app/lang/en/validation.php) by default for client-side too. If you want to use jquery validation messages, you can set useLaravelMessages, false in config file of package which you copied to your config dir. 

#### Plugins
**Jquery Validation**
While using Jquery Validation as html/js validation plugin, you should include jquery.validate.laravalid.js in your views, too. After assets published, it will be copied to your public folder. The last thing you should do at client side is initializing jquery validation plugin as below:
```html
<script type="text/javascript">
$('form').validate({onkeyup: false}); //while using remote validation, remember to set onkeyup false
</script>
```


#### Example
Controller/Route side
```php
class UserController extends Controller {
    
    public $createValidation = ['name' => 'required|max:255', 'username' => 'required|regex:/^[a-z\-]*$/|max:20', 'email' => 'required|email', 'age' => 'numeric'];
    public $createColumns = ['name', 'username', 'email', 'age'];

    public function getCreate()
    {
        Form::setValidation($this->createValidation);
        return View::make('user.create');
    }

    public function postCreate()
    {
        $inputs = Input::only($this->createColumns);
        $rules = $this->createValidation;

        $validator = Validator::make($inputs, $rules);

        if($validator->fails())
        {
            // actually withErrors is not really neccessary because we already show errors at client side for normal users
            return Redirect::back()->withErrors($validator);
        }

        // try to create user

        return Redirect::back()->with('success', 'User is created successfully');
    }
}
```
View side
```html
<!DOCTYPE html>
<html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Laravalid</title>
    </head>
    <body>
    
        {{ Form::open('url'=>'create', 'method'=>'post') }}
        {{ Form::text('name') }}
        {{ Form::text('username') }}
        {{ Form::email('email') }}
        {{ Form::number('age') }}
        {{ Form::close() }}

        <script src="{{ asset('js/jquery-1.10.2.min.js') }}"></script>
        <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('js/jquery.validate.laravalid.js') }}"></script>
        <script type="text/javascript">
        $('form').validate({onkeyup: false});
        </script>
    </body>
</html>
```
### Extending
There are two ways to extend package with your own rules. 
First, you can extend current converter plugin dynamically like below:
```php
Form::converter()->rule()->extend('someotherrule', function($parsedRule, $attribute, $type){
    // some code
    return ['data-rule-someotherrule' => 'blablabla'];
});
Form::converter()->message()->extend('someotherrule', function($parsedRule, $attribute, $type){
    // some code
    return ['data-message-someotherrule' => 'Some other message'];
});
Form::converter()->route()->extend('someotherrule', function($name, $parameters){
    // some code
    return ['valid' => false, 'messages' => 'Seriously dude, what kind of input is this?'];
});

```
Second, you can create your own converter (which extends baseconverter or any current plugin converter) in `Bllim\Laravalid\Converter\` namespace and change plugin configuration in config file with your own plugin name.

> **Note:** If you are creating a converter for some existed html/js plugin please create it in `converters` folder and send a pull-request.

### Plugins and Supported Rules
**Jquery Validation**
To use Jquery Validation, change plugin to `JqueryValidation` in config file and import jquery, jquery-validation and **jquery.validation.laravel.js** in views.


| Rules          | Jquery Validation |
| ---------------|:----------------:|
| Accepted  | - |
| Active URL  | - |
| After (Date)  | - |
| Alpha  | `+` |
| Alpha Dash  | - |
| Alpha Numeric  | - |
| Array  | - |
| Before (Date)  | - |
| Between  | `+` |
| Boolean  | - |
| Confirmed  | - |
| Date  | `+` |
| Date Format  | - |
| Different  | - |
| Digits  | - |
| Digits Between  | - |
| E-Mail  | `+` |
| Exists (Database)  | `+` |
| Image (File)  | - |
| In  | - |
| Integer  | - |
| IP Address  | `+` |
| Max  | `+` |
| MIME Types  | - |
| Min  | `+` |
| Not In  | - |
| Numeric  | `+` |
| Regular Expression  | `+` |
| Required  | `+` |
| Required If  | - |
| Required With  | - |
| Required With All  | - |
| Required Without  | - |
| Required Without All  | - |
| Same  | `+` |
| Size  | - |
| String  | - |
| Timezone  | - |
| Unique (Database)  | `+` |
| URL  | `+` |

> **Note:** It is easy to add some rules. Please check `Rule` class of related converter.

### Contribution
You can fork and contribute to development of the package. All pull requests is welcome.

**Convertion Logic**
Package converts rules by using converters (in src/converters). It uses Converter class of chosen plugin which extends BaseConverter/Converter class. 
You can look at existed methods and plugins to understand how it works. Explanation will be ready, soon.

### Known issues
- Some rules are not supported for now

### TODO
- Test script
- Support unsupported rules
- Improve doc
- Comment code

### Changelog
See the [project's releases](https://github.com/laravel-ardent/laravalid/releases)!

### License
Licensed under the MIT License

[l10n]:http://laravel.com/docs/5.1/localizationl
