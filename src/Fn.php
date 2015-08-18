<?php
namespace Aeris\Fn;

function partial($func /*, $var_args*/) {
	$applied_args = array_slice($args = func_get_args(), 1);

	return function () use ($func, $applied_args) {
		$args = array_merge($applied_args, func_get_args());
		return call_user_func_array($func, $args);
	};
}

// adapted from https://gist.github.com/adaburrows/941874
function compose($fnA, $fnB) {
	return function () use ($fnA, $fnB) {
		$args = func_get_args();
		return $fnA(call_user_func_array($fnB, $args));
	};
}

function identity() {
	return function ($val) {
		return $val;
	};
}

function constant($val) {
	return function () use ($val) {
		return $val;
	};
}


function always() {
	return function () {
		return true;
	};
}

function never() {
	return function () {
		return false;
	};
}


function cat($item, array $array) {
	return array_merge(array($item), $array);
}

function moreThanOrEqualTo($min) {
	return function ($val) use ($min) {
		return $val >= $min;
	};
}

function lessThan($max) {
	return function ($val) use ($max) {
		return $val < $max;
	};
}

function equalTo($target) {
	return function ($val) use ($target) {
		return $val === $target;
	};
}

function notEqualTo($target) {
	$equalToTarget = partial(cb('equalTo'), $target);

	return negate($equalToTarget);
}

function even() {
	return function ($num) {
		return $num % 2 === 0;
	};
}

function odd() {
	return negate(even());
}

function both($predA, $predB) {
	return function ($val) use ($predA, $predB) {
		return resolve($predA, $val) && resolve($predB, $val);
	};
}

function invoker( /*$var_fns*/) {
	$fns = func_get_args();

	return function () use ($fns) {
		$args = func_get_args();

		foreach ($fns as $fn) {
			call_user_func_array($fn, $args);
		}
	};
}

function invokeAll( /*$var_fns*/) {
	$fns = func_get_args();
	// Returns last result
	return array_reduce($fns, function ($carry, $fn) {
		return invoke($fn);
	});
}

function invoke($fn) {
	$appliedArgs = array_slice($args = func_get_args(), 1);

	return call_user_func_array($fn, $appliedArgs);
}

function negate($fnOrVal) {
	if (is_callable($fnOrVal)) {
		return function () use ($fnOrVal) {
			return !call_user_func_array($fnOrVal, func_get_args());
		};
	}
	return !$fnOrVal;
}

function conditional($predicate, $cb) {
	return function () use ($predicate, $cb) {
		$appliedArgs = func_get_args();
		$resolveArgs = cat($predicate, $appliedArgs);
		$predicateVal = call_user_func_array(cb('resolve'), $resolveArgs);

		if ($predicateVal) {
			call_user_func_array($cb, $appliedArgs);
		}
	};
}

function doWhen($predicate, $fn) {
	if (resolve($predicate)) {
		return $fn();
	}
	return null;
}


function errorThrower($ErrorType, $opt_msg = null) {
	return partial(cb('throwError'), $ErrorType, $opt_msg);
}

function conditionalThrower($predicate, $ErrorType, $opt_msg = null) {
	return conditional($predicate, errorThrower($ErrorType, $opt_msg));
}

function throwWhen($predicate, $ErrorType, $opt_msg = null) {
	doWhen($predicate, errorThrower($ErrorType, $opt_msg));
}

/**
 * @param string $ErrorType
 * @param null   $opt_msg
 */
function throwError($ErrorType, $opt_msg = null) {
	$error = is_callable($ErrorType) ? $ErrorType() : new $ErrorType($opt_msg);

	throw $error;
}

function resolve($fnOrVal/* $var_args*/) {
	$applied_args = array_slice($args = func_get_args(), 1);

	return is_callable($fnOrVal) ? call_user_func_array($fnOrVal, $applied_args) : $fnOrVal;
}

function resolver(/*$var_args*/) {
	$args = func_get_args();
	return function ($predicate) use ($args) {
		$argsForResolve = cat($predicate, $args);

		return call_user_func_array(cb('resolve'), $argsForResolve);
	};
}

/**
 * Creates a function which calls the specified method
 * with the array item, only if the array has the item.
 *
 * eg.
 *
 *  $config = array('foo' => 'bar');
 *
 *  $callObjectWithConfig = F::whenSetInvoker($obj, $config);
 *
 *  $callObjectWithConfig('setFoo', 'foo');
 *  // calls $obj->setFoo('foo')
 *
 *  $callObjectWithConfig('setFaz', 'faz');
 *  // does nothing
 *
 * @param array  $arr
 * @param object $opt_obj Object to bind method names against.
 *                        If not set, returned fn will expect callables as arguments.
 * @return callable
 */
function whenSetInvoker(array $arr, $opt_obj = null) {
	return function ($method, $key) use ($opt_obj, $arr) {
		if (isKeySet($arr, $key)) {
			$cb = $opt_obj ? array($opt_obj, $method) : $method;
			call_user_func($cb, $arr[$key]);
		}
	};
}

function keySetChecker(array $arr) {
	return partial(cb('isKeySet'), $arr);
}

function isKeySet(array $arr, $key) {
	return isset($arr[$key]);
}

function accessor($key) {
	return function (array $arr) use ($key) {
		return $arr[$key];
	};
}

function cb($methodName) {
	return __NAMESPACE__ . '\\' . $methodName;
}


function zip($arr1, $arr2) {
	return array_map(null, $arr1, $arr2);
}


function mapAssoc(array $arr, $cb) {
	$mappedArr = array();

	foreach ($arr as $key => $val) {
		$mappedArr = $cb($val, $key);
	}

	return $mappedArr;
}


function pick(array $arr /*,$var_keys*/) {
	$pickedArr = array();
	$whitelistedKeys = array_slice($args = func_get_args(), 1);

	foreach ($whitelistedKeys as $key) {
		$pickedArr[$key] = $arr[$key];
	}

	return $pickedArr;
}

/**
 * Plucks a property from a collection of associative arrays.
 *
 * eg:
 * 	Fn\pluck([
 * 			['name' => 'moe', 'age' => 45],
 * 			['name' => 'larry', 'age' => 55],
 * 			['name' => 'curly', 'age' => 65]
 * 		], 'name')
 * // ['moe', 'larry', 'curly']
 *
 * @param array[] $collection
 * @param string $propName
 * @return mixed
 */
function pluck(array $collection, $propName) {
	return array_reduce($collection, function($vals, $item) use ($propName) {
		return isset($item[$propName]) ? concat($vals, $item[$propName]) : $vals;
	}, []);
}

/**
 * Add a value to an array, or merge a set of values.
 *
 * Fn\concat(['a', 'b'], 'c');  				// ['a', 'b', 'c']
 * Fn\concat(['a', 'b'], ['c', 'd']);  	// ['a', 'b', 'c', 'd']
 *
 * @param array $arr
 * @param array|mixed $valOrArray
 * @return array
 */
function concat(array $arr, $valOrArray) {
	$arrToMerge = is_array($valOrArray) ? $valOrArray : [$valOrArray];
	return array_merge([], $arr, $arrToMerge);
}

/**
 * Returns true if any item in the array
 * pass the $test callable.
 *
 * @param array    $arr
 * @param callable $test
 * @return boolean
 */
function any(array $arr, $test = null) {
	return array_reduce($arr, function ($anyIsTrue, $item) use ($test) {
		$itemIsTrue = $test ? call_user_func($test, $item) : $item;

		return $anyIsTrue || $itemIsTrue;
	}, false);
}

function all(array $arr, $test = null) {
	return array_reduce($arr, function ($anyIsTrue, $item) use ($test) {
		$itemIsTrue = $test ? call_user_func($test, $item) : $item;

		return $anyIsTrue && $itemIsTrue;
	}, true);
}

function joinOr(array $predicates) {
	return joinUsing($predicates, cb('any'));
}

function joinAnd(array $predicates) {
	return joinUsing($predicates, cb('all'));
}

function joinUsing(array $predicates, $mapTest) {
	return function (/** $var_args */) use ($predicates, $mapTest) {
		$args = func_get_args();

		// Call each filter with the applied args,
		// and check the results with
		// either 'any' or 'all'
		return call_user_func($mapTest, $predicates, function ($pred) use ($args) {
			return call_user_func_array($pred, $args);
		});
	};
}

/**
 * Return a factory for creating an object.
 *
 * eg.
 * $entityFactory = F::factory('MyApp\Model\Entity');
 * $entity = $entityFactory($ctorArg);  // => ~ new MapApp\Model\Entity($ctorArg)
 *
 * @param $className
 * @return callable
 */
function factoryMap($className) {
	return function ($data) use ($className) {
		return array_map(factory($className), $data);
	};
}

/**
 * Return a factory for creating an object.
 *
 * eg.
 * $entityFactory = F::factory('MyApp\Model\Entity');
 * $entity = $entityFactory($ctorArg);  // => ~ new MapApp\Model\Entity($ctorArg)
 *
 * @param string $className
 * @return callable
 */
function factory($className) {
	return function ($data) use ($className) {
		$ctorArgs = func_get_args();
		$reflector = new \ReflectionClass($className);
		return $reflector->newInstanceArgs($ctorArgs);
	};
}

function find(array $collection, callable $predicate) {
	foreach ($collection as $key => $val) {
		if ($predicate($val, $key)) {
			return $val;
		}
	}
	return null;
}

/**
 * eg.
 *  $obj->getFoo = function() { return 'foo' }
 *
 *  $getFooCaller = F::caller('getFoo');
 *  $getFooCaller($obj);  // => 'foo'
 *
 * Looks useless, but check this out:
 *
 *  array_map(F::caller('getFoo'), [$objA, $objB, $objC])  // => ['foo', 'foo', 'foo']
 *
 * @param string $methodName
 * @return callable
 */
function caller($methodName) {
	return function ($obj) use ($methodName) {
		$args = array_slice($args = func_get_args(), 1);
		$methodCallable = array($obj, $methodName);

		return call_user_func_array($methodCallable, $args);
	};
}

/**
 * @param array         $array
 * @param bool|callable $wherePredicate
 * @return number The count of items in the array which match the predicate.
 */
function countWhere(array $array, $wherePredicate = true) {
	return array_reduce($array, function ($sum, $item) use ($wherePredicate) {
		return $sum + (resolve($wherePredicate, $item) ? 1 : 0);
	}, 0);
}

/**
 * @param array $array
 * @param       $mapper
 */
function reduceAssoc(array $array, $mapper, $init) {
	$res = $init;

	foreach ($array as $key => $val) {
		$res = call_user_func($mapper, $res, $val, $key);
	};

	return $res;
}

function times($times, callable $cb) {
	if ($times < 0) {
		throw new \InvalidArgumentException('Fn\times arg must be at least 0');
	}

	$runCount = 0;

	while ($runCount < $times) {
		$cb($runCount);
		$runCount++;
	}
}