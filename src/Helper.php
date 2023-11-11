<?php 

namespace dmitryrogolev\Can;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Helper 
{
    /**
     * Преобразует строку в slug
     *
     * @param string $value
     * @return string
     */
    public static function slug(string $value): string 
    {
        return str($value)->snake(config('can.separator'))->slug(config('can.separator'))->toString();
    }

    /**
     * Привести аргумент к массиву
     *
     * @param mixed $argument
     * @return array
     */
    public static function arrayFrom(mixed $argument): array
    {
        if (is_array($argument) || $argument instanceof Collection) {
            $result = Arr::flatten($argument);

            for ($i = 0; $i < count($result); $i++) {
                if (is_string($result[$i])) {
                    $result[$i] = str($result[$i])->split('/[,|]/')->toArray();
                }
            }

            return Arr::flatten($result);
        }

        if (is_string($argument)) {
            return str($argument)->split('/[,|]/')->toArray();
        }

        return Arr::wrap($argument);
    }
}
