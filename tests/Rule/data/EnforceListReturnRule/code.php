<?php

namespace EnforceListReturnRule;

interface I {

    /**
     * @return array<string>
     */
    public function getList(): array;
}

class A implements I {

    /**
     * @return array<string>
     */
    public function getList(): array // error: Method getList always return list, but is marked as array<string>
    {
        return ['list'];
    }

    /**
     * @return string[]
     */
    public function getAlwaysList(bool $decide): array // error: Method getAlwaysList always return list, but is marked as array<string>
    {
        if ($decide) {
            return ['list1'];
        } else {
            return ['list2'];
        }
    }

    /**
     * @return string[]
     */
    public function getListOrEmpty(bool $decide): array // error: Method getListOrEmpty always return list, but is marked as array<string>
    {
        if ($decide) {
            return ['list1'];
        } else {
            return [];
        }
    }

    /**
     * @return array<string>
     */
    public function getSometimesList(bool $condition): array
    {
        if ($condition) {
            return ['not' => 'list'];
        }
        return ['list'];
    }

    /**
     * @return array<string>
     */
    public function getEmptyArray(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public function getHashMap(): array
    {
        return ['hash' => 'map'];
    }

}

