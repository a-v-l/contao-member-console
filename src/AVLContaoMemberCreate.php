<?php

declare(strict_types=1);

namespace AVL\MemberCreate;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AVLContaoMemberCreate extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
