<?php

declare(strict_types=1);

namespace Chella\AwsService\Tests\Service;

use Chella\AwsService\Exception\AssertionException;
use Chella\AwsService\Service\Assert;
use Chella\AwsService\Tests\TestCase;

class AssertTest extends TestCase
{
    // Test methods for isNumber
    public function testIsNumberValid()
    {
        Assert::isNumber('12345', 'Should not throw');
        $this->assertTrue(true); // Assertion to ensure test runs
    }

    public function testIsNumberInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isNumber('123a5', 'Should throw for non-numeric string');
    }

    // Test methods for isArray
    public function testIsArrayValid()
    {
        Assert::isArray([], 'Should not throw for empty array');
        Assert::isArray([1, 2], 'Should not throw for indexed array');
        Assert::isArray(['a' => 1], 'Should not throw for associative array');
        $this->assertTrue(true);
    }

    public function testIsArrayInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isArray('not an array', 'Should throw for string');
    }

    // Test methods for isAssociateArray
    public function testIsAssociateArrayValid()
    {
        Assert::isAssociateArray(['a' => 1, 'b' => 2], 'Should not throw');
        $this->assertTrue(true);
    }

    public function testIsAssociateArrayInvalidIndexed()
    {
        $this->expectException(AssertionException::class);
        Assert::isAssociateArray([1, 2, 3], 'Should throw for indexed array');
    }

    public function testIsAssociateArrayInvalidNonArray()
    {
        $this->expectException(AssertionException::class);
        Assert::isAssociateArray('not an array', 'Should throw for non-array');
    }

    public function testIsAssociateArrayEmptyArray()
    {
        $this->expectException(AssertionException::class);
        Assert::isAssociateArray([], 'Should throw for empty array');
    }

    // Test methods for isString
    public function testIsStringValid()
    {
        Assert::isString('hello', 'Should not throw for string');
        Assert::isString('', 'Should not throw for empty string');
        $this->assertTrue(true);
    }

    public function testIsStringInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isString(123, 'Should throw for integer');
    }

    // Test methods for arrayNotEmpty
    public function testArrayNotEmptyValid()
    {
        Assert::arrayNotEmpty([1], 'Should not throw');
        $this->assertTrue(true);
    }

    public function testArrayNotEmptyInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::arrayNotEmpty([], 'Should throw for empty array');
    }

    // Test methods for arrayIsEmpty
    public function testArrayIsEmptyValid()
    {
        Assert::arrayIsEmpty([], 'Should not throw');
        $this->assertTrue(true);
    }

    public function testArrayIsEmptyInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::arrayIsEmpty([1], 'Should throw for non-empty array');
    }

    // Test methods for notNull
    public function testNotNullValid()
    {
        Assert::notNull('data', 'Should not throw');
        Assert::notNull(0, 'Should not throw for 0');
        Assert::notNull([], 'Should not throw for empty array');
        $this->assertTrue(true);
    }

    public function testNotNullInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::notNull(null, 'Should throw for null');
    }

    // Test methods for notEmpty (string specific)
    public function testNotEmptyStringValid()
    {
        Assert::notEmpty('abc', 'Should not throw for non-empty string');
        $this->assertTrue(true);
    }

    public function testNotEmptyStringInvalidEmpty()
    {
        $this->expectException(AssertionException::class);
        Assert::notEmpty('', 'Should throw for empty string');
    }

    public function testNotEmptyStringInvalidNull()
    {
        // notEmpty first calls notNull, so this will throw due to the null check
        $this->expectException(AssertionException::class);
        Assert::notEmpty(null, 'Should throw for null');
    }

    public function testNotEmptyStringInvalidNotString()
    {
        // notEmpty then calls isString, so this will throw due to the type check
        $this->expectException(AssertionException::class);
        Assert::notEmpty(['a'], 'Should throw for array');
    }

    // Test methods for isEmpty (general PHP empty)
    public function testIsEmptyInvalidBecauseItIsEmpty()
    {
        $this->expectException(AssertionException::class);
        Assert::isEmpty('', 'Should throw for empty string');
    }

    public function testIsEmptyInvalidBecauseItIsNull()
    {
        $this->expectException(AssertionException::class);
        Assert::isEmpty(null, 'Should throw for null');
    }

    public function testIsEmptyInvalidBecauseItIsZero()
    {
        $this->expectException(AssertionException::class);
        Assert::isEmpty(0, 'Should throw for 0');
    }

    public function testIsEmptyValidBecauseItIsNotEmpty()
    {
        Assert::isEmpty('not empty', 'Should not throw for non-empty string');
        Assert::isEmpty([1], 'Should not throw for non-empty array');
        Assert::isEmpty(1, 'Should not throw for 1');
        $this->assertTrue(true);
    }

    // Test methods for maxLength
    public function testMaxLengthValid()
    {
        Assert::maxLength('abc', 3, 'Should not throw');
        Assert::maxLength('ab', 3, 'Should not throw');
        Assert::maxLength('', 0, 'Should not throw for empty string and zero max length');
        $this->assertTrue(true);
    }

    public function testMaxLengthInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::maxLength('abcd', 3, 'Should throw');
    }

    // Test methods for isZero
    public function testIsZeroInvalidBecauseItIsZero()
    {
        $this->expectException(AssertionException::class);
        Assert::isZero(0, 'Should throw for 0');
    }

    public function testIsZeroInvalidBecauseItIsZeroFloat()
    {
        $this->expectException(AssertionException::class);
        Assert::isZero(0.0, 'Should throw for 0.0');
    }

    public function testIsZeroValidBecauseItIsNotZero()
    {
        Assert::isZero(1, 'Should not throw for 1');
        Assert::isZero(-1, 'Should not throw for -1');
        Assert::isZero('a', 'Should not throw for string "a" as it is not === 0');
        $this->assertTrue(true);
    }

    // Test methods for isPositiveInteger
    public function testIsPositiveIntegerValid()
    {
        Assert::isPositiveInteger(1, 'Should not throw for 1');
        Assert::isPositiveInteger(100, 'Should not throw for 100');
        $this->assertTrue(true);
    }

    public function testIsPositiveIntegerInvalidZero()
    {
        $this->expectException(AssertionException::class);
        Assert::isPositiveInteger(0, 'Should throw for 0');
    }

    public function testIsPositiveIntegerInvalidNegative()
    {
        $this->expectException(AssertionException::class);
        Assert::isPositiveInteger(-1, 'Should throw for -1');
    }

    public function testIsPositiveIntegerInvalidFloat()
    {
        $this->expectException(AssertionException::class);
        Assert::isPositiveInteger(1.5, 'Should throw for float');
    }

    public function testIsPositiveIntegerInvalidString()
    {
        $this->expectException(AssertionException::class);
        Assert::isPositiveInteger('1', 'Should throw for string "1"');
    }

    // Test methods for isNotZero
    public function testIsNotZeroInvalidBecauseItIsNotZero()
    {
        $this->expectException(AssertionException::class);
        Assert::isNotZero(1, 'Should throw for 1');
    }

    public function testIsNotZeroValidBecauseItIsZero()
    {
        Assert::isNotZero(0, 'Should not throw for 0');
        Assert::isNotZero(0.0, 'Should not throw for 0.0 because 0.0 === 0 is false, but 0.0 !== 0 is also false');
        // The original code uses !==, so 0.0 passes.
        $this->assertTrue(true);
    }

    // Test methods for notEquals
    public function testNotEqualsInvalidBecauseTheyAreNotEqual()
    {
        $this->expectException(AssertionException::class);
        Assert::notEquals(1, 2, 'Should throw for 1 !== 2');
    }

    public function testNotEqualsInvalidBecauseTypesAreDifferent()
    {
        $this->expectException(AssertionException::class);
        Assert::notEquals(1, '1', 'Should throw for 1 !== "1"');
    }

    public function testNotEqualsValidBecauseTheyAreEqual()
    {
        Assert::notEquals(1, 1, 'Should not throw for 1 === 1');
        Assert::notEquals('a', 'a', 'Should not throw for "a" === "a"');
        $this->assertTrue(true);
    }

    // Test methods for keysExist
    public function testKeysExistValid()
    {
        Assert::keysExist(['a' => 1, 'b' => 2], ['a', 'b'], 'Should not throw');
        Assert::keysExist(['a' => 1, 'b' => 2], ['a'], 'Should not throw');
        Assert::keysExist(['a' => 1], [], 'Should not throw if no keys are required');
        $this->assertTrue(true);
    }

    public function testKeysExistInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::keysExist(['a' => 1], ['a', 'b'], 'Should throw if key "b" is missing');
    }

    public function testKeysExistInvalidEmptyArrayTarget()
    {
        $this->expectException(AssertionException::class);
        Assert::keysExist([], ['a'], 'Should throw if key "a" is missing in empty array');
    }

    // Test methods for isValidXml
    public function testIsValidXmlValid()
    {
        Assert::isValidXml('<root><item>Test</item></root>', 'Should not throw for valid XML');
        $this->assertTrue(true);
    }

    public function testIsValidXmlInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isValidXml('<root><item>Test</item></roo>', 'Should throw for invalid XML');
    }

    // Test methods for isAllowedFileType
    public function testIsAllowedFileTypeValid()
    {
        Assert::isAllowedFileType(['txt', 'jpg'], 'txt', 'Should not throw');
        $this->assertTrue(true);
    }

    public function testIsAllowedFileTypeInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isAllowedFileType(['jpg', 'png'], 'gif', 'Should throw');
    }

    // Test methods for isAllowedFileSize
    public function testIsAllowedFileSizeValid()
    {
        Assert::isAllowedFileSize(100, 50, 'Should not throw');
        Assert::isAllowedFileSize(100, 100, 'Should not throw'); // equal is allowed
        $this->assertTrue(true);
    }

    public function testIsAllowedFileSizeInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isAllowedFileSize(100, 101, 'Should throw');
    }

    // Test methods for arrayValueExists (checks if all values in an array are not "empty()")
    public function testArrayValueExistsValid()
    {
        Assert::arrayValueExists(['a', 1, [2]], 'Should not throw for non-empty values');
        Assert::arrayValueExists([], 'Should not throw for empty array as loop wont run');
        $this->assertTrue(true);
    }

    public function testArrayValueExistsInvalidDueToNull()
    {
        $this->expectException(AssertionException::class);
        Assert::arrayValueExists(['a', null], 'Should throw if a value is null');
    }

    public function testArrayValueExistsInvalidDueToEmptyString()
    {
        $this->expectException(AssertionException::class);
        Assert::arrayValueExists(['a', ''], 'Should throw if a value is an empty string');
    }

    public function testArrayValueExistsInvalidDueToZero()
    {
        $this->expectException(AssertionException::class);
        Assert::arrayValueExists(['a', 0], 'Should throw if a value is zero');
    }

    // Test methods for multipleArrayValueExists
    public function testMultipleArrayValueExistsValid()
    {
        Assert::multipleArrayValueExists([['a', 'b'], [1, 2]], 'Should not throw');
        Assert::multipleArrayValueExists([['valid']], 'Should not throw for single sub-array');
        Assert::multipleArrayValueExists([], 'Should not throw for empty outer array');
        Assert::multipleArrayValueExists([[], []], 'Should not throw for sub-arrays being empty themselves as arrayValueExists handles empty arrays');
        $this->assertTrue(true);
    }

    public function testMultipleArrayValueExistsInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::multipleArrayValueExists([['a', ''], [1, 2]], 'Should throw if one sub-array has an empty value');
    }

    public function testMultipleArrayValueExistsNonArrayInputForOuterLoop()
    {
        // This test is tricky. The first isArray check in multipleArrayValueExists is on $array itself.
        // If $array is not an array, it throws.
        // If $array = ['not an array'], it passes the first check.
        // Then, in the loop, $value becomes 'not an array'.
        // Then Assert::arrayValueExists('not an array', $message) is called.
        // Inside arrayValueExists, self::isArray('not an array', $message) is called, which throws.
        $this->expectException(AssertionException::class);
        Assert::multipleArrayValueExists(['not an array child'], 'Should throw as child is not an array for arrayValueExists');
    }


    // Test methods for isNull
    public function testIsNullValid()
    {
        Assert::isNull(null, 'Should not throw for null');
        $this->assertTrue(true);
    }

    public function testIsNullInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isNull('not null', 'Should throw for non-null string');
    }

    public function testIsNullInvalidZero()
    {
        $this->expectException(AssertionException::class);
        Assert::isNull(0, 'Should throw for zero');
    }


    // Test methods for isNumeric
    public function testIsNumericValid()
    {
        Assert::isNumeric('123', 'Should not throw for numeric string');
        Assert::isNumeric(123, 'Should not throw for integer');
        Assert::isNumeric(12.3, 'Should not throw for float');
        $this->assertTrue(true);
    }

    public function testIsNumericInvalid()
    {
        $this->expectException(AssertionException::class);
        Assert::isNumeric('abc', 'Should throw for non-numeric string');
    }

    // Test methods for validateEmail
    public function testValidateEmailValid()
    {
        Assert::validateEmail('test@example.com', 'Should not throw for single valid email');
        Assert::validateEmail('test1@example.com,test2@example.com', 'Should not throw for multiple valid emails');
        Assert::validateEmail(' test@example.com ', 'Should not throw for valid email with spaces');
        $this->assertTrue(true);
    }

    public function testValidateEmailInvalidSingle()
    {
        $this->expectException(AssertionException::class);
        Assert::validateEmail('invalid-email', 'Should throw for invalid email');
    }

    public function testValidateEmailInvalidMultipleOneBad()
    {
        $this->expectException(AssertionException::class);
        Assert::validateEmail('test1@example.com,invalid,test2@example.com', 'Should throw if one email is invalid');
    }
}
