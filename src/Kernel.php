<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private ?string $projectDir = null;

    // test
    public function getProjectDir(): string
    {
        return $this->projectDir ??= dirname(__DIR__);
    }
}
