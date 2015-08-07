<?php

use Aeris\Fn;
use Aeris\FnTest\Fixture\SomeClass;

class FnTest extends \PHPUnit_Framework_TestCase {

	/** @test */
	public function method_any() {
		$this->assertTrue(Fn\any([false, true, false]));
		$this->assertFalse(Fn\any([false, false, false]));

		$isShazaam = function($arg) {
			return $arg === 'shazaam';
		};
		$this->assertTrue(Fn\any(['baz', 'shazaam', 'baz'], $isShazaam));
		$this->assertFalse(Fn\any(['baz', 'baz', 'baz'], $isShazaam));
		$this->assertFalse(Fn\any([]), $isShazaam);
	}

	/** @test */
	public function method_factory() {
		$someClassFactory = Fn\factory('Aeris\FnTest\Fixture\SomeClass');

		$someClassInstance = $someClassFactory('a', 'b', 'c');

		$this->assertInstanceOf('Aeris\FnTest\Fixture\SomeClass', $someClassInstance);
		$this->assertEquals(['a', 'b', 'c'], $someClassInstance->ctorArgs);
	}

	/** @test */
	public function method_factoryMap() {
		$someClassMapper = Fn\factoryMap('Aeris\FnTest\Fixture\SomeClass');

		$someClassInstances = $someClassMapper(['a', 'b', 'c']);

		$this->assertEquals(count($someClassInstances), 3);
		$this->assertInstanceOf('Aeris\FnTest\Fixture\SomeClass', $someClassInstances[0]);
		$this->assertInstanceOf('Aeris\FnTest\Fixture\SomeClass', $someClassInstances[1]);
		$this->assertInstanceOf('Aeris\FnTest\Fixture\SomeClass', $someClassInstances[2]);
		$this->assertEquals(['a'], $someClassInstances[0]->ctorArgs);
		$this->assertEquals(['b'], $someClassInstances[1]->ctorArgs);
		$this->assertEquals(['c'], $someClassInstances[2]->ctorArgs);
	}


	/** @test */
	public function method_caller() {
		// Basic example, though result is useless
		$passThruCaller = Fn\caller('passThru');
		$this->assertEquals('foo', $passThruCaller(new SomeClass(), 'foo'));

		// A more useful example, using mapping.
		$objects = [
			new SomeClass(),
			new SomeClass(),
			new SomeClass()
		];
		$valList = array_map(Fn\caller('getSomeProp'), $objects);
		$this->assertEquals(['someVal', 'someVal', 'someVal'], $valList);
	}

	/** @test */
	public function method_countWhere() {
		$this->assertEquals(3, Fn\countWhere(['foo', 'bar', 'foo'], true), 'Should return array count, when predicate is true');
		$this->assertEquals(0, Fn\countWhere(['foo', 'bar', 'foo'], false), 'Should return 0, when predicate is false');

		$this->assertEquals(2, Fn\countWhere(['foo', 'bar', 'foo'], function($item) {
			return $item == 'foo';
		}), 'Should return the count of items passing a predicate fn.');
	}


	/** @test */
	public function method_accessor() {
		$arr = ['foo' => 'bar'];
		$fooAccessor = Fn\accessor('foo');

		$this->assertEquals('bar', $fooAccessor($arr));
	}

	/** @test */
	public function method_all() {
		$arr = [3, 4, 5, 6];

		$this->assertTrue(Fn\all($arr, Fn\moreThanOrEqualTo(3)));
		$this->assertFalse(Fn\all($arr, Fn\even()));
		$this->assertFalse(Fn\all($arr, Fn\odd()));
	}

	/** @test */
	public function method_even() {
		$isEven = Fn\even();
		$this->assertTrue($isEven(4));
		$this->assertFalse($isEven(15));
	}

	/** @test */
	public function method_odd() {
		$isOdd = Fn\odd();
		$this->assertFalse($isOdd(4));
		$this->assertTrue($isOdd(15));
	}

	/** @test */
	public function method_always() {
		$always = Fn\always();
		$this->assertTrue($always());
		$this->assertTrue($always(true));
		$this->assertTrue($always(false));
	}

	/** @test */
	public function method_never() {
		$never = Fn\never();
		$this->assertFalse($never());
		$this->assertFalse($never(true));
		$this->assertFalse($never(false));
	}

	/** @test */
	public function method_resolver() {
		$resolve = Fn\resolver('a', 'b', 'c');

		$this->assertTrue($resolve(true));
		$this->assertFalse($resolve(false));

		$this->assertTrue($resolve(function($a, $b, $c) {
			return $a == 'a' && $b == 'b' && $c == 'c';
		}));
		$this->assertFalse($resolve(function($a, $b, $c) {
			return false;
		}));
	}

	/** @test */
	public function method_joinAnd() {
		$predicates = [
			Fn\even(),
			Fn\moreThanOrEqualTo(10),
		];
		$checkAll = Fn\joinAnd($predicates);

		$this->assertTrue($checkAll(12));
		$this->assertTrue($checkAll(14));
		$this->assertFalse($checkAll(8));
		$this->assertFalse($checkAll(13));
	}

	/** @test */
	public function method_joinOr() {
		$predicates = [
			Fn\even(),
			Fn\moreThanOrEqualTo(10),
		];
		$checkAny = Fn\joinOr($predicates);

		$this->assertTrue($checkAny(2));
		$this->assertTrue($checkAny(11));
		$this->assertFalse($checkAny(7));
	}

	/** @test */
	public function method_reduceAssoc() {
		$arr = [
			'foo' => 'bar',
			'faz' => 'baz'
		];

		$reduced = Fn\reduceAssoc($arr, function($reduced, $val, $key) {
			return $reduced . $key . $val;
		}, '');

		$this->assertEquals('foobarfazbaz', $reduced);
	}

	/** @test */
	public function method_conditional() {
		$cbCallCount = 0;
		$isFooBar = function($argA, $argB) {
			return $argA == 'foo' && $argB == 'bar';
		};
		$assertFooBar = function($argA, $argB) use (&$cbCallCount) {
			$cbCallCount++;
			$this->assertEquals('foo', $argA);
			$this->assertEquals('bar', $argB);
		};

		$assertWhenFooBar = Fn\conditional($isFooBar, $assertFooBar);

		$assertWhenFooBar('faz', 'baz');
		$assertWhenFooBar('foo', 'shnoz');
		$this->assertEquals(0, $cbCallCount);

		$assertWhenFooBar('foo', 'bar');
		$this->assertEquals(1, $cbCallCount);
	}

	/** @test */
	public function method_concat() {
		$this->assertEquals(
			['a', 'b', 'c'],
			Fn\concat(['a', 'b'], 'c'),
			'Should add a value to an array'
		);
		$this->assertEquals(
			['a', 'b', 'c', 'd'],
			Fn\concat(['a', 'b'], ['c', 'd']),
			'Should merge an array'
		);
	}

	/** @test */
	public function method_pluck() {
		$this->assertEquals(
			['moe', 'larry', 'curly'],
			Fn\pluck([
				['name' => 'moe', 'age' => 45],
				['name' => 'larry', 'age' => 55],
				['name' => 'curly', 'age' => 65]
			], 'name'),
			'Should pluck item properties'
		);

		$this->assertEquals(
			[45, 55],
			Fn\pluck([
				['name' => 'moe', 'age' => 45],
				['name' => 'larry', 'age' => 55],
				['name' => 'curly']
			], 'age'),
			'Should handle missing properties'
		);

		$this->assertEquals(
			[],
			Fn\pluck([], 'name'),
			'Should handle empty collections'
		);
	}
}
