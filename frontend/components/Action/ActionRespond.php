<?php

namespace frontend\components\Action;

class ActionRespond
{
    public static function getInternalName(): string
    {
        return Action::ACTION_RESPOND;
    }

    public static function checkRight(): bool
    {
        return true;
    }
}