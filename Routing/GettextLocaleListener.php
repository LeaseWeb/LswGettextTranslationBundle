<?php
namespace Lsw\GettextTranslationBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Gettext locale listener
 *
 */
class GettextLocaleListener
{
    private $localeShortcuts;
    /**
    * @var Symfony\Component\Routing\RouterInterface
    */
    private $router;

    /** @var string  */
    private $rootDir;

    /** @var string */
    private $resourcesSubfolder;

    /**
     * Constructor
     *
     * @param string $localeShortcuts Locale shortcuts
     * @param RouterInterface $router Router interface
     * @param string $rootDir Root directory
     * @param string $resourcesSubfolder
     */
    public function __construct($localeShortcuts, RouterInterface $router, $rootDir, $resourcesSubfolder)
    {
        $this->localeShortcuts = $localeShortcuts;
        $this->router  = $router;
        $this->rootDir = rtrim($rootDir, '/');
        $this->resourcesSubfolder = $resourcesSubfolder;
    }

    /**
    * Binds the default domain to the combined translations on Kernel Request
    *
    * @param GetResponseEvent $event event
    *
    * @throws InvalidParameterException
    */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentLocale = $this->getCurrentLocale();
        $requestedLocale = $this->getRequestedLocale();

        if ($requestedLocale && $currentLocale != $requestedLocale) {
            $request = $event->getRequest();
            // ugly backwards compatibility fix for symfony 2.0 (start)
            if (!method_exists($request, 'setLocale')) {
                $request = $request->getSession();
            }
            // ugly backwards compatibility fix for symfony 2.0 (end)
            if (!setlocale(LC_MESSAGES, $requestedLocale.'.UTF-8', $requestedLocale.'.utf8', $requestedLocale.'.utf-8', $requestedLocale.'UTF8')) {
                $request->setLocale(null);
                throw new InvalidParameterException("Requested locale '$requestedLocale' could not be set. Is this locale installed? Hint: Execute 'locale -a' on the Linux command line to list installed locales.");
            }
            $request->setLocale($requestedLocale);
        }

        $this->setupGetText();
    }

    private function getCurrentLocale()
    {
        $current = setlocale(LC_MESSAGES, 0);
        if ($current && strpos($current, '.')) {
            list($current,$charset) = explode('.', $current);
        }

        return $current;
    }

    private function getRequestedLocale()
    {
        $context = $this->router->getContext();
        $requested = $context->getParameter('_locale');
        if ($requested && strlen($requested)<5 && isset($this->localeShortcuts[$requested])) {
            $requested=$this->localeShortcuts[$requested];
        }

        return $requested;
    }

    private function setupGetText()
    {
        $versionFile = $this->rootDir. $this->resourcesSubfolder . 'version';
        $version = file_exists($versionFile) ? file_get_contents($versionFile) : '';
        $domain = 'messages' . $version;
        bindtextdomain($domain, $this->rootDir . $this->resourcesSubfolder . 'combined/');
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');
    }
}
