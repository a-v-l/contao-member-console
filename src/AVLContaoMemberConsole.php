<?php

declare(strict_types=1);

namespace AVL\MemberConsole;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AVLContaoMemberConsole extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
