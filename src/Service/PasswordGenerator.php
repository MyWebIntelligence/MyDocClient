<?php

namespace App\Service;

use Exception;

class PasswordGenerator
{

    const MIN_LENGTH = 6;

    /**
     * @throws Exception
     */
    public static function generate($length = 8): string
    {
        $password = '';
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = mb_strlen($keyspace, '8bit') - 1;

        if ($length < self::MIN_LENGTH) {
            throw new \RuntimeException(sprintf(
                "La longueur du mot de passe doit être égale ou supérieure à %s caractères",
                self::MIN_LENGTH
            ));
        }

        for ($i = 0; $i < $length; $i++) {
            $password .= $keyspace[random_int(0, $max)];
        }

        return $password;
    }

}