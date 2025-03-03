<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function array_map;
use function array_merge;
use function count;
use function strcasecmp;
use function usort;
use const PHP_INT_MIN;

class UnionTypeHelper
{

	/**
	 * @param Type[] $types
	 * @return string[]
	 */
	public static function getReferencedClasses(array $types): array
	{
		return array_merge(
			...array_map(
				static fn (Type $type) => $type->getReferencedClasses(),
				$types,
			),
		);
	}

	/**
	 * @param Type[] $types
	 * @return list<ArrayType>
	 */
	public static function getArrays(array $types): array
	{
		return array_merge(
			...array_map(
				static fn (Type $type) => $type->getArrays(),
				$types,
			),
		);
	}

	/**
	 * @param Type[] $types
	 * @return list<ConstantArrayType>
	 */
	public static function getConstantArrays(array $types): array
	{
		return array_merge(
			...array_map(
				static fn (Type $type) => $type->getConstantArrays(),
				$types,
			),
		);
	}

	/**
	 * @param Type[] $types
	 * @return Type[]
	 */
	public static function sortTypes(array $types): array
	{
		if (count($types) > 1024) {
			return $types;
		}

		usort($types, static function (Type $a, Type $b): int {
			if ($a instanceof NullType) {
				return 1;
			} elseif ($b instanceof NullType) {
				return -1;
			}

			if ($a instanceof AccessoryType) {
				if ($b instanceof AccessoryType) {
					return self::compareStrings($a->describe(VerbosityLevel::value()), $b->describe(VerbosityLevel::value()));
				}

				return 1;
			}
			if ($b instanceof AccessoryType) {
				return -1;
			}

			$aIsBool = $a instanceof ConstantBooleanType;
			$bIsBool = $b instanceof ConstantBooleanType;
			if ($aIsBool && !$bIsBool) {
				return 1;
			} elseif ($bIsBool && !$aIsBool) {
				return -1;
			}
			if ($a instanceof ConstantScalarType && !$b instanceof ConstantScalarType) {
				return -1;
			} elseif (!$a instanceof ConstantScalarType && $b instanceof ConstantScalarType) {
				return 1;
			}

			if (
				(
					$a instanceof ConstantIntegerType
					|| $a instanceof ConstantFloatType
				)
				&& (
					$b instanceof ConstantIntegerType
					|| $b instanceof ConstantFloatType
				)
			) {
				$cmp = $a->getValue() <=> $b->getValue();
				if ($cmp !== 0) {
					return $cmp;
				}
				if ($a instanceof ConstantIntegerType && $b instanceof ConstantFloatType) {
					return -1;
				}
				if ($b instanceof ConstantIntegerType && $a instanceof ConstantFloatType) {
					return 1;
				}
				return 0;
			}

			if ($a instanceof IntegerRangeType && $b instanceof IntegerRangeType) {
				return ($a->getMin() ?? PHP_INT_MIN) <=> ($b->getMin() ?? PHP_INT_MIN);
			}

			if ($a instanceof IntegerRangeType && $b instanceof IntegerType) {
				return 1;
			}

			if ($b instanceof IntegerRangeType && $a instanceof IntegerType) {
				return -1;
			}

			if ($a instanceof ConstantStringType && $b instanceof ConstantStringType) {
				return self::compareStrings($a->getValue(), $b->getValue());
			}

			if ($a instanceof ConstantArrayType && $b instanceof ConstantArrayType) {
				if ($a->isEmpty()) {
					if ($b->isEmpty()) {
						return 0;
					}

					return -1;
				} elseif ($b->isEmpty()) {
					return 1;
				}

				return self::compareStrings($a->describe(VerbosityLevel::value()), $b->describe(VerbosityLevel::value()));
			}

			return self::compareStrings($a->describe(VerbosityLevel::typeOnly()), $b->describe(VerbosityLevel::typeOnly()));
		});
		return $types;
	}

	private static function compareStrings(string $a, string $b): int
	{
		$cmp = strcasecmp($a, $b);
		if ($cmp !== 0) {
			return $cmp;
		}

		return $a <=> $b;
	}

}
