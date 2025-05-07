# Changelog

## 1.11.0 (2024-06-04)

*   Feature: Improve PHP 8.4+ support by avoiding implicitly nullable type declarations.
    (#70 by @clue)

*   Feature: Full PHP 8.3 compatibility.
    (#68 by @clue)

## 1.10.0 (2023-07-20)

*   Feature: Use Promise v3 template types.
    (#67 by @clue and #63 and #64 by @WyriHaximus)

*   Minor documentation improvements.
    (#59 by @nhedger)

*   Improve test suite, avoid unhandled promise rejections and report failed assertions.
    (#66 and #62 by @clue and #61 by @WyriHaximus)

## 1.9.0 (2022-06-13)

*   Feature: Improve forward compatibility with upcoming Promise v3 API.
    (#54 and #55 by @clue)

*   Minor documentation improvements for upcoming Promise v3.
    (#58 by @clue and #56 by @SimonFrings)

*   Improve test suite, fix legacy HHVM build by downgrading Composer.
    (#57 by @SimonFrings)

## 1.8.0 (2021-12-06)

*   Feature: Add new `sleep()` function and deprecate `resolve()` and `reject()` functions.
    (#51 by @clue)

    ```php
    // deprecated
    React\Promise\Timer\resolve($time);
    React\Promise\Timer\reject($time);

    // new
    React\Promise\Timer\sleep($time);
    ```

*   Feature: Support PHP 8.1 release.
    (#50 by @Thomas-Gelf, #52 by @clue and #48 by @SimonFrings)

*   Improve API documentation and add parameter types and return types.
    (#49 by @clue and #47 by @SimonFrings)

## 1.7.0 (2021-07-11)

A major new feature release, see [**release announcement**](https://clue.engineering/2021/announcing-reactphp-default-loop).

*   Feature: Simplify usage by supporting new [default loop](https://reactphp.org/event-loop/#loop).
    (#46 by @clue)

    ```php
    // old (still supported)
    $promise = timeout($promise, $time, $loop);
    $promise = resolve($time, $loop);
    $promise = reject($time, $loop);

    // new (using default loop)
    $promise = timeout($promise, $time);
    $promise = resolve($time);
    $promise = reject($time);
    ```

*   Improve test suite, use GitHub actions for continuous integration (CI),
    update PHPUnit config, run tests on PHP 8 and add full core team to the license.
    (#43 by @WyriHaximus, #44 and #45 by @SimonFrings)

## 1.6.0 (2020-07-10)

*   Feature: Forward compatibility with react/promise v3.
    (#37 by @WyriHaximus)

*   Improve test suite and add `.gitattributes` to exclude dev files from exports.
    Run tests on PHPUnit 9 and PHP 7.4 and clean up test suite.
    (#38 by @WyriHaximus, #39 by @reedy, #41 by @clue and #42 by @SimonFrings)

## 1.5.1 (2019-03-27)

*   Fix: Typo in readme
    (#35 by @aak74)

*   Improvement: Only include functions file when functions aren't defined
    (#36 by @Niko9911)

## 1.5.0 (2018-06-13)

*   Feature: Improve memory consumption by cleaning up garbage references to pending promise without canceller.
    (#34 by @clue)

## 1.4.0 (2018-06-11)

*   Feature: Improve memory consumption by cleaning up garbage references.
    (#33 by @clue)

## 1.3.0 (2018-04-24)

*   Feature: Improve memory consumption by cleaning up unneeded references.
    (#32 by @clue)

## 1.2.1 (2017-12-22)

*   README improvements
    (#28 by @jsor)

*   Improve test suite by adding forward compatiblity with PHPUnit 6 and
    fix test suite forward compatibility with upcoming EventLoop releases
    (#30 and #31 by @clue)

## 1.2.0 (2017-08-08)

* Feature: Only start timers if input Promise is still pending and
  return a settled output promise if the input is already settled.
  (#25 by @clue)

* Feature: Cap minimum timer interval at 1µs across all versions
  (#23 by @clue)

* Feature: Forward compatibility with EventLoop v1.0 and v0.5
  (#27 by @clue)

* Improve test suite by adding PHPUnit to require-dev and
  lock Travis distro so new defaults will not break the build
  (#24 and #26 by @clue)

## 1.1.1 (2016-12-27)

* Improve test suite to use PSR-4 autoloader and proper namespaces.
  (#21 by @clue)

## 1.1.0 (2016-02-29)

* Feature: Support promise cancellation for all timer primitives
  (#18 by @clue)

## 1.0.0 (2015-09-29)

* First tagged release
