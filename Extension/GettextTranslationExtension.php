<?php
namespace Lsw\GettextTranslationBundle\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Gettext translation extention
 *
 */
class GettextTranslationExtension extends AbstractExtension
{
  /**
   * Sets aliases for functions
   *
   * @see Twig_Extension::getFunctions()
   * @return array
   */
  public function getFunctions()
  {
    return array(
      new TwigFunction('_', 'gettext'),
      new TwigFunction('_n', 'ngettext'),
      new TwigFunction('__' , array($this, 'gettext')),
      new TwigFunction('__n', array($this, 'ngettext')),
    );
  }

  /**
   * Returns text from the gettext library by message ID
   *
   * @param int $msgid Message ID
   *
   * @return mixed
   */
  public static function gettext($msgid)
  {
    $args = func_get_args();
    $args[0] = gettext($msgid);

    return call_user_func_array('sprintf', $args);
  }

  /**
   * Returns plural text (i.e. 1 button, 2 buttons)
   *
   * @param string  $msgid1 Message in one amount
   * @param string  $msgid2 Message in two amounts
   * @param integer $n      Plural count
   *
   * @return string
   */
  public static function ngettext($msgid1, $msgid2, $n)
  {
    $args = func_get_args();
    array_splice($args, 0, 3, array(ngettext($msgid1, $msgid2, $n)));

    return call_user_func_array('sprintf', $args);
  }

  /**
   * Returns the name of the extension.
   *
   * @return string The extension name
   *
   * @see Twig_ExtensionInterface::getName()
   */
  public function getName()
  {
    return 'lsw_gettext_translation_extension';
  }

}

