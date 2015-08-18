# Fn

Functional utility library for PHP.


## API

### partial


### compose


### identity


### constant


### always


### never


### cat


### moreThanOrEqualTo


### lessthan


### equalTo


### notEqualTo


### even


### odd


### both


### invoker


### invokeAll


### invoke


### negate


### conditional


### doWhen


### errorThrower


### conditionalThrower


### throwWhen


### throwError


### resolve


### resolver


### whenSetInvoker


### keySetChecker


### isKeySet


### accessor


### cb


### zip


### mapAssoc


### pick


### pluck

Plucks a property from a collection of associative arrays.

eg:

```php
	Fn\pluck([
			['name' => 'moe', 'age' => 45],
			['name' => 'larry', 'age' => 55],
			['name' => 'curly', 'age' => 65]
		], 'name');
  // --> ['moe', 'larry', 'curly']
```

### concat

Add a value to an array, or merge a set of values.

```php
    Fn\concat(['a', 'b'], 'c');  				  // ['a', 'b', 'c']
    Fn\concat(['a', 'b'], ['c', 'd']);  	// ['a', 'b', 'c', 'd']
```

### any

Returns true if any item in the array passes a predicate

#### Example:

```php
Fn\any([1, 3, 4, 5, 9], Fn\even()) // true
Fn\any([1, 3, 5, 9], Fn\even()) // false
```

### all

Returns true if every item in the array passes a predicate

#### Example:

```php
Fn\all([1, 3, 5, 9], Fn\odd()) // true
Fn\all([1, 3, 4, 5, 9], Fn\even()) // false
```

### joinOr


### joinAnd


### factoryMap


### factory


### caller


### countWhere


### reduceAssoc


### times

Invoke a callable some number of times.

```php
Fn\times(3, function($i) {
	echo "call #$i";
})

// call 1
// call 2
// call 3
```