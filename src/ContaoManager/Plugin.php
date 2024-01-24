<?php

declare(strict_types=1);

namespace AVL\MemberConsole\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use AVL\MemberConsole\AVLContaoMemberConsole;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(AVLContaoMemberConsole::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load('@AVLContaoMemberConsole/src/Resources/config/commands.yml');
    }
}
