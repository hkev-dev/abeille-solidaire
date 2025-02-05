<?php

namespace App\Service;

use ReflectionClass;
use ReflectionException;

class ObjectService
{

    /**
     * @return mixed|null
     * @throws ReflectionException
     */
    public static function getCallerClass($interface): mixed
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerClass = null;

        foreach ($trace as $frame) {
            if (isset($frame['class'])) {
                $class = $frame['class'];

                // Vérifier si la classe implémente PaymentServiceInterface et n'est pas abstraite
                if (in_array($interface, class_implements($class)) &&
                    !(new ReflectionClass($class))->isAbstract()) {
                    $callerClass = $class;
                    break;
                }
            }
        }
        return $callerClass;
    }
}