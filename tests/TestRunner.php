<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

class TestRunner
{
    protected int $passed = 0;
    protected int $failed = 0;

    public function run(): void
    {
        echo "\033[32mRunning NexusPHP Native Test Suite...\033[0m\n\n";

        $files = glob(__DIR__ . '/*/*Test.php');
        if (empty($files)) {
            echo "No test files found.\n";
            exit(0);
        }

        foreach ($files as $file) {
            require_once $file;
            $className = basename($file, '.php');
            $class = "Nexus\\Tests\\Feature\\{$className}";
            if (!class_exists($class)) {
                $class = $className;
                if (!class_exists($class)) {
                    continue;
                }
            }

            $test = new $class($className);

            foreach (get_class_methods($test) as $method) {
                if (str_starts_with($method, 'test')) {
                    try {
                        if (method_exists($test, 'setUp')) {
                            (function () { $this->setUp(); })->call($test);
                        }
                        $test->$method();

                        // After running, detect if the test registered an expected exception
                        $expected = null;
                        try {
                            $ref = new \ReflectionObject($test);
                            if ($ref->hasProperty('expectedException')) {
                                $prop = $ref->getProperty('expectedException');
                                $prop->setAccessible(true);
                                $expected = $prop->getValue($test);
                            }
                        } catch (\ReflectionException $e) {
                            $expected = null;
                        }

                        if ($expected !== null) {
                            echo "  \033[31m✖\033[0m {$class}::{$method} failed: Expected exception {$expected} was not thrown\n";
                            $this->failed++;
                        } else {
                            if (method_exists($test, 'tearDown')) {
                                (function () { $this->tearDown(); })->call($test);
                            }
                            echo "  \033[32m✔\033[0m {$class}::{$method}\n";
                            $this->passed++;
                        }
                    } catch (\Throwable $e) {
                        // If an exception was thrown, consider PHPUnit's expectedException
                        $expected = null;
                        $expectedMessage = null;
                        try {
                            $ref = new \ReflectionObject($test);
                            if ($ref->hasProperty('expectedException')) {
                                $prop = $ref->getProperty('expectedException');
                                $prop->setAccessible(true);
                                $expected = $prop->getValue($test);
                            }
                            if ($ref->hasProperty('expectedExceptionMessage')) {
                                $prop2 = $ref->getProperty('expectedExceptionMessage');
                                $prop2->setAccessible(true);
                                $expectedMessage = $prop2->getValue($test);
                            }
                        } catch (\ReflectionException $re) {
                            // ignore
                        }

                        if ($expected !== null) {
                            $matched = false;
                            try {
                                if (is_string($expected) && ($e instanceof $expected || is_subclass_of(get_class($e), $expected) || get_class($e) === $expected)) {
                                    if ($expectedMessage === null || str_contains($e->getMessage(), $expectedMessage)) {
                                        $matched = true;
                                    }
                                }
                            } catch (\Throwable $_) {
                                $matched = false;
                            }

                            if ($matched) {
                                echo "  \033[32m✔\033[0m {$class}::{$method} (expected exception {$expected})\n";
                                $this->passed++;
                                continue;
                            }
                        }

                        echo "  \033[31m✖\033[0m {$class}::{$method} failed: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n";
                        $this->failed++;
                    }
                }
            }
        }

        echo "\n\033[36mTest Summary:\033[0m {$this->passed} passed, {$this->failed} failed.\n";
        exit($this->failed > 0 ? 1 : 0);
    }
}

(new TestRunner())->run();
